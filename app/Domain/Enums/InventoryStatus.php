<?php

namespace App\Domain\Enums;

/**
 * Estado do inventário. draft -> in_progress -> finalized (ou cancelled).
 */
enum InventoryStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case FINALIZED = 'finalized';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::IN_PROGRESS => 'Em contagem',
            self::FINALIZED => 'Finalizado',
            self::CANCELLED => 'Cancelado',
        };
    }
}
