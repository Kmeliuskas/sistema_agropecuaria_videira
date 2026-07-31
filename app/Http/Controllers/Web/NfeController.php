<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Nfe;
use App\Models\NfeItem;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Movement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NfeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Nfe::class);

        $query = Nfe::query()
            ->with(['supplier', 'user'])
            ->latest('emission_date');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('series', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('supplier_id')) {
            $query->where('supplier_id', request('supplier_id'));
        }

        $perPage = request('per_page', 5);
        $nfes = $query->paginate($perPage)->withQueryString();

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name']);

        return view('nfe.index', [
            'nfes' => $nfes,
            'suppliers' => $suppliers,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Nfe::class);

        return view('nfe.form', [
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'internal_code', 'unit_id', 'warehouse_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Nfe::class);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'number' => ['required', 'string', 'max:20'],
            'series' => ['required', 'string', 'max:10'],
            'emission_date' => ['required', 'date'],
            'receipt_date' => ['nullable', 'date'],
            'observation' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.warehouse_id' => ['required', 'exists:warehouses,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_value' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        DB::transaction(function () use ($validated, $request) {
            $totalValue = 0;

            $nfe = Nfe::create([
                'supplier_id' => $validated['supplier_id'],
                'number' => $validated['number'],
                'series' => $validated['series'],
                'emission_date' => $validated['emission_date'],
                'receipt_date' => $validated['receipt_date'],
                'total_value' => 0,
                'status' => $validated['status'],
                'observation' => $validated['observation'],
                'user_id' => $validated['user_id'],
            ]);

            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_value'];
                $totalValue += $itemTotal;

                NfeItem::create([
                    'nfe_id' => $nfe->id,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'],
                    'total_value' => $itemTotal,
                ]);
            }

            $nfe->update(['total_value' => $totalValue]);
        });

        return redirect()
            ->route('nfe.index')
            ->with('success', 'Nota fiscal cadastrada com sucesso!');
    }

    public function show(Nfe $nfe): View
    {
        $this->authorize('view', $nfe);

        $nfe->load(['supplier', 'user', 'items.product', 'items.warehouse']);

        return view('nfe.show', [
            'nfe' => $nfe,
        ]);
    }

    public function edit(Nfe $nfe): View
    {
        $this->authorize('update', $nfe);

        if ($nfe->status === 'received') {
            return redirect()
                ->route('nfe.index')
                ->with('error', 'Não é possível editar uma nota fiscal já recebida.');
        }

        $nfe->load('items');

        return view('nfe.form', [
            'nfe' => $nfe,
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::active()->orderBy('name')->get(['id', 'name', 'internal_code', 'unit_id', 'warehouse_id']),
        ]);
    }

    public function update(Request $request, Nfe $nfe): RedirectResponse
    {
        $this->authorize('update', $nfe);

        if ($nfe->status === 'received') {
            return redirect()
                ->route('nfe.index')
                ->with('error', 'Não é possível editar uma nota fiscal já recebida.');
        }

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'number' => ['required', 'string', 'max:20'],
            'series' => ['required', 'string', 'max:10'],
            'emission_date' => ['required', 'date'],
            'receipt_date' => ['nullable', 'date'],
            'observation' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.warehouse_id' => ['required', 'exists:warehouses,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_value' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($nfe, $validated) {
            $nfe->update([
                'supplier_id' => $validated['supplier_id'],
                'number' => $validated['number'],
                'series' => $validated['series'],
                'emission_date' => $validated['emission_date'],
                'receipt_date' => $validated['receipt_date'],
                'observation' => $validated['observation'],
            ]);

            $nfe->items()->delete();

            $totalValue = 0;
            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_value'];
                $totalValue += $itemTotal;

                NfeItem::create([
                    'nfe_id' => $nfe->id,
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'quantity' => $item['quantity'],
                    'unit_value' => $item['unit_value'],
                    'total_value' => $itemTotal,
                ]);
            }

            $nfe->update(['total_value' => $totalValue]);
        });

        return redirect()
            ->route('nfe.index')
            ->with('success', 'Nota fiscal atualizada com sucesso!');
    }

    public function cancel(Nfe $nfe): RedirectResponse
    {
        $this->authorize('update', $nfe);

        if ($nfe->status !== 'pending') {
            return redirect()
                ->route('nfe.index')
                ->with('error', 'Apenas notas fiscais pendentes podem ser canceladas.');
        }

        $nfe->update(['status' => 'canceled']);

        return redirect()
            ->route('nfe.index')
            ->with('success', 'Nota fiscal cancelada com sucesso!');
    }

    public function receive(Nfe $nfe): RedirectResponse
    {
        $this->authorize('update', $nfe);

        if ($nfe->status === 'received') {
            return redirect()
                ->route('nfe.index')
                ->with('error', 'Nota fiscal já recebida.');
        }

        DB::transaction(function () use ($nfe) {
            $nfe->load('items');

            foreach ($nfe->items as $item) {
                $product = Product::findOrFail($item->product_id);
                $warehouseId = $item->warehouse_id;
                $quantity = $item->quantity;

                // Atualiza o estoque do produto
                $product->increment('current_stock', $quantity);

                // Cria ou atualiza a posição de estoque
                $stockBalance = StockBalance::withTrashed()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                if ($stockBalance) {
                    if ($stockBalance->trashed()) {
                        $stockBalance->restore();
                    }
                    $stockBalance->increment('current', $quantity);
                    $stockBalance->recalcAvailable();
                } else {
                    $stockBalance = StockBalance::create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $warehouseId,
                        'current' => $quantity,
                        'available' => $quantity,
                    ]);
                }

                // Cria a movimentação de entrada
                $balanceBefore = $product->getRawOriginal('current_stock') - $quantity;
                $balanceAfter = $product->getRawOriginal('current_stock');

                // Usa a data de recebimento informada na NFE, mas com o horário atual.
                // Se o receipt_date for apenas uma data (sem horário), o horário será 00:00.
                // Para preservar a data informada mas com horário real, concatenamos.
                $occurredAt = $nfe->receipt_date
                    ? \Carbon\Carbon::parse($nfe->receipt_date)->setTimeFrom(now())
                    : now();

                Movement::create([
                    'product_id' => $item->product_id,
                    'type' => 'entry',
                    'reason' => 'Recebimento NF-E ' . $nfe->number . '/' . $nfe->series,
                    'source_type' => 'nfe',
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'unit_cost' => $item->unit_value,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'user_id' => auth()->id(),
                    'supplier_id' => $nfe->supplier_id,
                    'document_number' => $nfe->number,
                    'occurred_at' => $occurredAt,
                ]);
            }

            $nfe->update([
                'status' => 'received',
                'receipt_date' => $nfe->receipt_date ?? now(),
            ]);
        });

        return redirect()
            ->route('nfe.index')
            ->with('success', 'Nota fiscal recebida e estoque atualizado com sucesso!');
    }

    public function destroy(Nfe $nfe): RedirectResponse
    {
        $this->authorize('delete', $nfe);

        if ($nfe->status === 'received') {
            return redirect()
                ->route('nfe.index')
                ->with('error', 'Não é possível excluir uma nota fiscal já recebida.');
        }

        $nfe->delete();

        return redirect()
            ->route('nfe.index')
            ->with('success', 'Nota fiscal excluída com sucesso!');
    }
}
