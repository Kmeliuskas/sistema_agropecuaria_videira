<?php

namespace App\Application\DTOs;

/**
 * DTO base (Data Transfer Object).
 * Toda entrada/saída de caso de uso passa por DTO para isolar a camada de
 * aplicação da camada de transporte (HTTP/Request). Implementações devem
 * expor fromArray() + toArray().
 */
abstract class Dto
{
    /**
     * Constrói o DTO a partir de um array (tipicamente $request->validated()).
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Serializa para persistência/transporte.
     */
    abstract public function toArray(): array;

    /**
     * Remove chaves nulas (útil em updates parciais).
     */
    protected function compact(array $data): array
    {
        return array_filter($data, fn ($v) => ! is_null($v));
    }
}
