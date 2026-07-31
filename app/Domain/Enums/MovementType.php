<?php

namespace App\Domain\Enums;

/**
 * Tipos de movimentação de estoque (Kardex).
 * 'in' incrementa o saldo; 'out' decrementa; 'transfer' é in+out entre almoxarifados;
 * 'adjust' corrige para um valor absoluto (pode ser +/-); 'reserve'/'release' afetam saldo reservado.
 */
enum MovementType: string
{
    case ENTRY = 'entry';          // Entrada (compra, NF, produção, devolução, ajuste+, importação)
    case EXIT = 'exit';            // Saída (consumo, venda, quebra, perda, doação, baixa, ajuste-)
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case ADJUST = 'adjust';        // ajuste de saldo (erro, roubo, vencimento, correção)
    case RESERVE = 'reserve';      // reserva de estoque
    case RELEASE = 'release';      // liberação de reserva

    public function direction(): string
    {
        return match ($this) {
            self::ENTRY, self::TRANSFER_IN, self::RELEASE => 'in',
            self::EXIT, self::TRANSFER_OUT, self::RESERVE => 'out',
            self::ADJUST => 'neutral',
        };
    }

    public function isEntrance(): bool
    {
        return in_array($this->direction(), ['in', 'neutral'], true)
            && ! in_array($this, [self::TRANSFER_OUT, self::RESERVE], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entrada',
            self::EXIT => 'Saída',
            self::TRANSFER_IN => 'Transferência (entrada)',
            self::TRANSFER_OUT => 'Transferência (saída)',
            self::ADJUST => 'Ajuste',
            self::RESERVE => 'Reserva',
            self::RELEASE => 'Liberação',
        };
    }
}
