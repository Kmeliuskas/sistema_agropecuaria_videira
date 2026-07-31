<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\View\View;

class MovementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Movement::class);

        $query = Movement::query()
            ->with(['product' => fn ($q) => $q->with('unit'), 'warehouse', 'user'])
            ->latest('occurred_at');

        if ($search = request('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('warehouse_id')) {
            $query->where('warehouse_id', request('warehouse_id'));
        }

        $perPage = request('per_page', 5);
        $movements = $query->paginate($perPage)->withQueryString();

        $warehouses = \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('movements.index', [
            'movements' => $movements,
            'warehouses' => $warehouses,
        ]);
    }
}
