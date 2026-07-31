<?php

namespace App\Models;

use App\Domain\Enums\TransferStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'origin_warehouse_id', 'destination_warehouse_id', 'status',
    'requester_id', 'sender_id', 'receiver_id', 'observation',
    'shipped_at', 'received_at',
])]
class Transfer extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'code', 'origin_warehouse_id', 'destination_warehouse_id', 'status',
        'requester_id', 'sender_id', 'receiver_id', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function statusEnum(): TransferStatus
    {
        return TransferStatus::from($this->status);
    }

    public function isCancelled(): bool
    {
        return $this->statusEnum() === TransferStatus::CANCELLED;
    }
}
