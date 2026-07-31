<?php

namespace App\Models;

use App\Domain\Enums\MaterialRequestStatus;
use App\Traits\Auditable;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

#[Fillable([
    'code', 'requester_id', 'employee_id', 'sector_id', 'cost_center_id',
    'status', 'justification', 'approver_id', 'approved_at',
    'delivered_at', 'finished_at', 'observation',
])]
class MaterialRequest extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected $auditableFields = [
        'code', 'requester_id', 'employee_id', 'sector_id', 'cost_center_id',
        'status', 'justification', 'approver_id', 'observation',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'delivered_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function statusEnum(): MaterialRequestStatus
    {
        return MaterialRequestStatus::from($this->status);
    }

    public function isCancelled(): bool
    {
        return $this->status === MaterialRequestStatus::CANCELADO->value;
    }

    protected static function booted(): void
    {
        static::updated(function (MaterialRequest $mr) {
            $mr->loadMissing(['requester', 'items']);
            $mr->broadcastRow();
        });

        static::created(function (MaterialRequest $mr) {
            $mr->loadMissing(['requester', 'items']);
            $content = view('material_requests._row', ['mr' => $mr])->render();
            TurboStream::broadcastPrepend(
                target: 'material_requests_table_body',
                content: new HtmlString($content),
                channel: new PrivateChannel('material-requests'),
            );
        });

        static::deleted(function (MaterialRequest $mr) {
            TurboStream::broadcastRemove(
                target: dom_id($mr),
                channel: new PrivateChannel('material-requests'),
            );
            TurboStream::broadcastRemove(
                target: dom_id($mr),
                channel: new PrivateChannel('material-request.' . $mr->id),
            );
        });
    }

    protected function broadcastRow(): void
    {
        $content = view('material_requests._row', ['mr' => $this])->render();
        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: dom_id($this),
            channel: new PrivateChannel('material-request.' . $this->id),
        );
        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: dom_id($this),
            channel: new PrivateChannel('material-requests'),
        );
    }
}
