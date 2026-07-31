<?php

namespace App\Domain\Enums;

/**
 * Ciclo de vida da Solicitação de Materiais (6 etapas):
 * solicitado -> aprovado -> separado -> conferido -> entregue -> finalizado.
 * 'cancelado' é terminal alternativo.
 */
enum MaterialRequestStatus: string
{
    case SOLICITADO = 'solicitado';
    case APROVADO = 'aprovado';
    case SEPARADO = 'separado';
    case CONFERIDO = 'conferido';
    case ENTREGUE = 'entregue';
    case FINALIZADO = 'finalizado';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::SOLICITADO => 'Solicitado',
            self::APROVADO => 'Aprovado',
            self::SEPARADO => 'Separado',
            self::CONFERIDO => 'Conferido',
            self::ENTREGUE => 'Entregue',
            self::FINALIZADO => 'Finalizado',
            self::CANCELADO => 'Cancelado',
        };
    }

    /**
     * Ordem canônica para validar transições (índice crescente).
     */
    public function order(): int
    {
        return match ($this) {
            self::SOLICITADO => 1,
            self::APROVADO => 2,
            self::SEPARADO => 3,
            self::CONFERIDO => 4,
            self::ENTREGUE => 5,
            self::FINALIZADO => 6,
            self::CANCELADO => 0,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FINALIZADO, self::CANCELADO], true);
    }

    /**
     * Classe de badge (Tailwind) conforme o estágio do ciclo de vida.
     * solicitado=azul, aprovado=amarelo, separado/conferido=ciano,
     * entregue/finalizado=verde, cancelado=vermelho.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::SOLICITADO => 'badge-primary',
            self::APROVADO => 'badge-warning',
            self::SEPARADO, self::CONFERIDO => 'badge-info',
            self::ENTREGUE, self::FINALIZADO => 'badge-success',
            self::CANCELADO => 'badge-danger',
        };
    }
}
