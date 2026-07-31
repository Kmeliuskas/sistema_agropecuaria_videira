<?php

namespace App\Domain\Enums;

/**
 * Estado da transferência entre almoxarifados.
 * pending -> in_transit (embarque consome origem + crédita destino)
 *         -> received (recebimento confere quantidade)
 * cancelled é terminal (apenas a partir de pending).
 */
enum TransferStatus: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::IN_TRANSIT => 'Em trânsito',
            self::RECEIVED => 'Recebida',
            self::CANCELLED => 'Cancelada',
        };
    }

    /**
     * Próximo estado válido por transição. null = sem saída (terminal).
     *
     * @return self[]|null
     */
    public function next(): ?array
    {
        return match ($this) {
            self::PENDING => [self::IN_TRANSIT, self::CANCELLED],
            self::IN_TRANSIT => [self::RECEIVED],
            self::RECEIVED, self::CANCELLED => null,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::RECEIVED || $this === self::CANCELLED;
    }
}
