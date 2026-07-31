<?php

namespace App\Http\Controllers\Web;

use App\Domain\Enums\MaterialRequestStatus;
use App\Domain\Enums\MovementType;
use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Movement;
use App\Models\Product;
use App\Models\Sector;
use App\Models\Warehouse;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MaterialRequestController extends Controller
{
    /**
     * Lista as solicitações de material.
     */
    public function index(): View
    {
        $this->authorize('viewAny', MaterialRequest::class);

        $query = MaterialRequest::query()
            ->with(['requester', 'items'])
            ->latest();

        if ($search = request('search')) {
            $query->where('code', 'like', "%{$search}%")
                ->orWhere('justification', 'like', "%{$search}%");
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $perPage = request('per_page', 5);
        $requests = $query->paginate($perPage)->withQueryString();

        return view('material_requests.index', [
            'requests' => $requests,
            'statuses' => MaterialRequestStatus::cases(),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    /**
     * Formulário de nova solicitação.
     */
    public function create(): View
    {
        $this->authorize('create', MaterialRequest::class);

        $products = Product::query()
            ->where('active', true)
            ->with(['unit', 'warehouse'])
            ->orderBy('name')
            ->get(['id', 'name', 'internal_code', 'unit_id', 'warehouse_id', 'current_stock']);

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $sectors = Sector::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('material_requests.create', [
            'products' => $products,
            'warehouses' => $warehouses,
            'sectors' => $sectors,
        ]);
    }

    /**
     * Armazena uma nova solicitação (status: solicitado).
     */
    public function store(Request $request): RedirectResponse
    {
        // Validação base dos campos e itens (já retorna o array validado).
        $validated = $request->validate([
            'justification' => ['nullable', 'string', 'max:1000'],
            'sector_id' => ['nullable', 'exists:sectors,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.observation' => ['nullable', 'string', 'max:500'],
        ], [
            'items.*.quantity.gt' => 'A quantidade deve ser maior que zero.',
        ]);

        // Trava por item: a quantidade solicitada não pode exceder o estoque
        // atual do produto. Montamos regras "max:" dinâmicas por linha.
        $quantityRules = [];
        $messages = [];
        foreach ((array) $request->input('items', []) as $index => $item) {
            $product = isset($item['product_id']) ? Product::find($item['product_id']) : null;
            $max = $product ? (float) $product->current_stock : 0;
            $quantityRules["items.{$index}.quantity"] = ['required', 'numeric', 'gt:0', 'max:' . $max];
            $messages["items.{$index}.quantity.max"] =
                "A quantidade de {$product->name} excede o estoque atual disponível ({$max}).";
        }

        $validator = Validator::make($request->all(), $quantityRules, $messages);
        $validator->validate();

        $mr = MaterialRequest::create([
            'code' => 'SM-' . str_pad(0, 4, '0', STR_PAD_LEFT),
            'requester_id' => Auth::id(),
            'sector_id' => $validated['sector_id'] ?: null,
            'status' => MaterialRequestStatus::SOLICITADO->value,
            'justification' => $validated['justification'],
        ]);

        // O código acompanha o id do registro (id 15 => SM-0015).
        $mr->update(['code' => $this->codeFromId($mr->id)]);

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);

            // O produto deve pertencer ao almoxarifado selecionado na solicitação.
            abort_if(
                $product->warehouse_id != $validated['warehouse_id'],
                422,
                "O produto {$product->name} não pertence ao almoxarifado selecionado nesta solicitação."
            );

            $mr->items()->create([
                'product_id' => $item['product_id'],
                'quantity_requested' => $item['quantity'],
                'warehouse_id' => $validated['warehouse_id'],
                'observation' => $item['observation'] ?? null,
            ]);
        }

        return redirect()
            ->route('material-requests.show', $mr)
            ->with('success', "Solicitação {$mr->code} criada com sucesso.");
    }

    /**
     * Detalhe da solicitação.
     */
    public function show(MaterialRequest $materialRequest): View
    {
        $this->authorize('view', $materialRequest);

        $materialRequest->load([
            'requester',
            'approver',
            'sector',
            'items.product.unit',
            'items.warehouse',
        ]);

        $status = $materialRequest->statusEnum();

        return view('material_requests.show', [
            'mr' => $materialRequest,
            'isPending' => $status === MaterialRequestStatus::SOLICITADO,
            'canDeliver' => $status === MaterialRequestStatus::APROVADO,
        ]);
    }

    /**
     * Atende (aprova) a solicitação: marca como aprovado e libera as
     * quantidades solicitadas para separação.
     */
    public function approve(MaterialRequest $materialRequest): RedirectResponse
    {
        $this->authorize('approve', $materialRequest);

        abort_if($materialRequest->statusEnum() !== MaterialRequestStatus::SOLICITADO, 403);

        $materialRequest->update([
            'status' => MaterialRequestStatus::APROVADO->value,
            'approver_id' => Auth::id(),
            'approved_at' => now(),
        ]);

        foreach ($materialRequest->items as $item) {
            $item->update(['quantity_approved' => $item->quantity_requested]);
        }

        return redirect()
            ->route('material-requests.show', $materialRequest)
            ->with('success', "Solicitação {$materialRequest->code} aprovada.");
    }

    /**
     * Recusa a solicitação.
     */
    public function reject(MaterialRequest $materialRequest): RedirectResponse
    {
        $this->authorize('approve', $materialRequest);

        abort_if($materialRequest->statusEnum() !== MaterialRequestStatus::SOLICITADO, 403);

        $materialRequest->update([
            'status' => MaterialRequestStatus::CANCELADO->value,
            'approver_id' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('material-requests.show', $materialRequest)
            ->with('success', "Solicitação {$materialRequest->code} recusada.");
    }

    /**
     * Entrega o material: desconta a quantidade aprovada do estoque atual de
     * cada item e gera uma movimentação de SAÍDA (Kardex). Aprovação é
     * pré-requisito. O desconto é idempotente por item (quantity_delivered).
     *
     * No fluxo web, entregar já FINALIZA a solicitação (pula o estado
     * intermediário "entregue"): material entregue = solicitação concluída.
     * As datas de entrega e finalização são preservadas para o histórico.
     */
    public function deliver(MaterialRequest $materialRequest): RedirectResponse
    {
        $this->authorize('deliver', $materialRequest);

        abort_if($materialRequest->statusEnum() !== MaterialRequestStatus::APROVADO, 403);

        DB::transaction(function () use ($materialRequest) {
            foreach ($materialRequest->items as $item) {
                $toDeliver = (float) $item->quantity_approved - (float) $item->quantity_delivered;
                if ($toDeliver <= 0) {
                    continue;
                }

                $product = $item->product;
                $balanceBefore = (float) $product->current_stock;
                $newStock = max(0, $balanceBefore - $toDeliver);
                $product->current_stock = $newStock;
                $product->save();
                $product->recalcAvailable();
                $product->broadcastStockUpdate();

                // Atualiza o saldo por almoxarifado na tabela stock_balances com o mesmo valor do produto
                $sb = \App\Models\StockBalance::firstOrNew([
                    'product_id' => $product->id,
                    'warehouse_id' => $item->warehouse_id,
                ]);
                $sb->current = $newStock;
                $sb->save();
                $sb->recalcAvailable();

                Movement::create([
                    'product_id' => $product->id,
                    'type' => MovementType::EXIT->value,
                    'reason' => "Atendimento de solicitação {$materialRequest->code}",
                    'source_type' => MaterialRequest::class,
                    'warehouse_id' => $item->warehouse_id,
                    'quantity' => $toDeliver,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $product->current_stock,
                    'user_id' => Auth::id(),
                    'document_number' => $materialRequest->code,
                    'occurred_at' => now(),
                ]);

                $item->increment('quantity_delivered', $toDeliver);
            }

            $materialRequest->update([
                'status' => MaterialRequestStatus::FINALIZADO->value,
                'delivered_at' => now(),
                'finished_at' => now(),
                'approver_id' => $materialRequest->approver_id ?? Auth::id(),
            ]);

            // Tempo real: avisa o painel para re-renderizar os totais.
            TurboStream::broadcastRefresh(new \Illuminate\Broadcasting\PrivateChannel('dashboard'));
        });

        return redirect()
            ->route('material-requests.show', $materialRequest)
            ->with('success', "Material de {$materialRequest->code} entregue e solicitação finalizada.");
    }

    /**
     * Finaliza a solicitação (após a entrega). Não altera o estoque.
     */
    public function finish(MaterialRequest $materialRequest): RedirectResponse
    {
        $this->authorize('deliver', $materialRequest);

        abort_if($materialRequest->statusEnum() !== MaterialRequestStatus::ENTREGUE, 403);

        $materialRequest->update([
            'status' => MaterialRequestStatus::FINALIZADO->value,
            'finished_at' => now(),
        ]);

        return redirect()
            ->route('material-requests.show', $materialRequest)
            ->with('success', "Solicitação {$materialRequest->code} finalizada.");
    }

    /**
     * Gera o código da solicitação a partir do seu id (id 15 => SM-0015).
     */
    protected function codeFromId(int $id): string
    {
        return 'SM-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    }
}
