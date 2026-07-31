<?php

namespace App\Domain\Enums;

/**
 * Modalidades de inventário suportadas pelo sistema.
 * - general: todo o estoque
 * - partial: lista restrita de itens
 * - rotating: ABC rotativo (ameses)
 * - by_category: uma categoria
 * - by_location: um endereço/almoxarifado
 * - by_lot: por lote (validade)
 */
enum InventoryType: string
{
    case GENERAL = 'general';
    case PARTIAL = 'partial';
    case ROTATING = 'rotating';
    case BY_CATEGORY = 'by_category';
    case BY_LOCATION = 'by_location';
    case BY_LOT = 'by_lot';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'Geral',
            self::PARTIAL => 'Parcial',
            self::ROTATING => 'Rotativo',
            self::BY_CATEGORY => 'Por categoria',
            self::BY_LOCATION => 'Por localização',
            self::BY_LOT => 'Por lote',
        };
    }
}
