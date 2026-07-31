<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log de auditoria imutável. Registra absolutamente tudo: login, logout,
 * cadastro, alteração, exclusão, movimentação, inventário, transferências,
 * ajustes. Não possui SoftDeletes — é append-only por design (conformidade).
 */
#[Fillable([
    'user_id', 'action', 'event', 'auditable_type', 'auditable_id',
    'ip_address', 'user_agent', 'before', 'after', 'metadata',
])]
class AuditLog extends Model
{
    public $timestamps = true;

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeInPeriod(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
