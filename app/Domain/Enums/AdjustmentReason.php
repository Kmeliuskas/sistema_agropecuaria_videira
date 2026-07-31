<?php

namespace App\Domain\Enums;

/**
 * Motivos de ajuste de estoque (6 motivos do WMS).
 * Cada motivo é registrado no Kardex via MovementType::ADJUST.
 */
enum AdjustmentReason: string
{
    case ERRO = 'erro';
    case QUEBRA = 'quebra';
    case PERDA = 'perda';
    case ROUBO = 'roubo';
    case VENCIMENTO = 'vencimento';
    case CORRECAO = 'correcao';

    public function label(): string
    {
        return match ($this) {
            self::ERRO => 'Erro de contagem',
            self::QUEBRA => 'Quebra',
            self::PERDA => 'Perda',
            self::ROUBO => 'Roubo',
            self::VENCIMENTO => 'Vencimento',
            self::CORRECAO => 'Correção de saldo',
        };
    }
}
