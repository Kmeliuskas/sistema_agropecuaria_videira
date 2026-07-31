<?php

namespace App\Application\DTOs\Transfer;

use App\Application\DTOs\Dto;
use App\Domain\Enums\TransferStatus;

/**
 * DTO de Transferência entre almoxarifados (cabeçalho + itens).
 */
class TransferDto extends Dto
{
    public function __construct(
        public readonly ?string $code,
        public readonly int $originWarehouseId,
        public readonly int $destinationWarehouseId,
        public readonly string $status,
        public readonly ?int $requesterId,
        public readonly ?string $observation,
        /** @var TransferItemDto[] */
        public readonly array $items = [],
    ) {}

    public static function fromArray(array $data): static
    {
        $items = array_map(
            fn (array $i) => TransferItemDto::fromArray($i),
            $data['items'] ?? []
        );

        return new self(
            code: $data['code'] ?? null,
            originWarehouseId: (int) $data['origin_warehouse_id'],
            destinationWarehouseId: (int) $data['destination_warehouse_id'],
            status: $data['status'] ?? TransferStatus::PENDING->value,
            requesterId: isset($data['requester_id']) ? (int) $data['requester_id'] : null,
            observation: $data['observation'] ?? null,
            items: $items,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'code' => $this->code,
            'origin_warehouse_id' => $this->originWarehouseId,
            'destination_warehouse_id' => $this->destinationWarehouseId,
            'status' => $this->status,
            'requester_id' => $this->requesterId,
            'observation' => $this->observation,
        ]);
    }
}
