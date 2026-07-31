<?php

namespace App\Application\Services;

use App\Models\Lot;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * Caso de uso de Lotes. Foco em validade: alertas na janela de 7 a 90 dias
 * (regra de negócio) e lotes já vencidos. Leituras + criação via Repository.
 */
class LotService
{
    public function list(array $filters = [], int $perPage = 15): Paginator
    {
        $query = Lot::query()->with(['product', 'warehouse', 'supplier']);

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }
        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('expires_at')->paginate($perPage);
    }

    /**
     * Lotes na janela de alerta (7 a 90 dias) — ainda não vencidos.
     */
    public function expiringSoon(int $perPage = 15): Paginator
    {
        return Lot::query()
            ->with(['product', 'warehouse'])
            ->expiringBetween(7, 90)
            ->orderBy('expires_at')
            ->paginate($perPage);
    }

    /**
     * Lotes já vencidos.
     */
    public function expired(int $perPage = 15): Paginator
    {
        return Lot::query()
            ->with(['product', 'warehouse'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->toDateString())
            ->orderBy('expires_at')
            ->paginate($perPage);
    }
}
