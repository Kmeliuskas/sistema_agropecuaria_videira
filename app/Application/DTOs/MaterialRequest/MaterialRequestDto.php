<?php

namespace App\Application\DTOs\MaterialRequest;

use App\Application\DTOs\Dto;

/**
 * DTO de Solicitação de Materiais (cabeçalho + itens).
 * Isola o caso de uso da requisição HTTP e da persistência.
 */
class MaterialRequestDto extends Dto
{
    /**
     * @param  MaterialRequestItemDto[]|null  $items
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly int $requesterId,
        public readonly ?int $employeeId,
        public readonly ?int $sectorId,
        public readonly ?int $costCenterId,
        public readonly string $status,
        public readonly ?string $justification,
        public readonly ?string $observation,
        public readonly ?array $items,
    ) {}

    public static function fromArray(array $data): static
    {
        $items = null;
        if (isset($data['items']) && is_array($data['items'])) {
            $items = array_map(
                fn (array $i) => MaterialRequestItemDto::fromArray($i),
                $data['items']
            );
        }

        return new self(
            id: $data['id'] ?? null,
            code: $data['code'] ?? null,
            requesterId: $data['requester_id'],
            employeeId: $data['employee_id'] ?? null,
            sectorId: $data['sector_id'] ?? null,
            costCenterId: $data['cost_center_id'] ?? null,
            status: $data['status'] ?? 'solicitado',
            justification: $data['justification'] ?? null,
            observation: $data['observation'] ?? null,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'code' => $this->code,
            'requester_id' => $this->requesterId,
            'employee_id' => $this->employeeId,
            'sector_id' => $this->sectorId,
            'cost_center_id' => $this->costCenterId,
            'status' => $this->status,
            'justification' => $this->justification,
            'observation' => $this->observation,
        ]);
    }
}
