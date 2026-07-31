<?php

namespace App\Application\DTOs\Movement;

use App\Application\DTOs\Dto;
use App\Domain\Enums\MovementType;

/**
 * DTO de movimentação (Kardex). Usado por entradas/saídas/ajustes/transferências.
 */
class MovementDto extends Dto
{
    public function __construct(
        public readonly int $productId,
        public readonly MovementType $type,
        public readonly float $quantity,
        public readonly ?int $warehouseId,
        public readonly ?int $warehouseDestinationId,
        public readonly float $unitCost,
        public readonly ?string $reason,
        public readonly ?string $sourceType,
        public readonly ?int $employeeId,
        public readonly ?int $costCenterId,
        public readonly ?int $supplierId,
        public readonly ?string $documentNumber,
        public readonly ?string $observation,
        public readonly ?\DateTimeInterface $occurredAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            productId: $data['product_id'],
            type: MovementType::from($data['type']),
            quantity: (float) $data['quantity'],
            warehouseId: $data['warehouse_id'] ?? null,
            warehouseDestinationId: $data['warehouse_destination_id'] ?? null,
            unitCost: (float) ($data['unit_cost'] ?? 0),
            reason: $data['reason'] ?? null,
            sourceType: $data['source_type'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            costCenterId: $data['cost_center_id'] ?? null,
            supplierId: $data['supplier_id'] ?? null,
            documentNumber: $data['document_number'] ?? null,
            observation: $data['observation'] ?? null,
            occurredAt: isset($data['occurred_at']) ? new \DateTimeImmutable($data['occurred_at']) : null,
        );
    }

    public function toArray(): array
    {
        return $this->compact([
            'product_id' => $this->productId,
            'type' => $this->type->value,
            'quantity' => $this->quantity,
            'warehouse_id' => $this->warehouseId,
            'warehouse_destination_id' => $this->warehouseDestinationId,
            'unit_cost' => $this->unitCost,
            'reason' => $this->reason,
            'source_type' => $this->sourceType,
            'employee_id' => $this->employeeId,
            'cost_center_id' => $this->costCenterId,
            'supplier_id' => $this->supplierId,
            'document_number' => $this->documentNumber,
            'observation' => $this->observation,
            'occurred_at' => $this->occurredAt?->format('Y-m-d H:i:s'),
        ]);
    }
}
