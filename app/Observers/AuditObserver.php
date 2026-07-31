<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use ReflectionClass;

/**
 * Observer genérico de auditoria. Acoplado a qualquer model que use o trait
 * Auditable. Registra created/updated/deleted com before/after, IP, user-agent
 * e usuário autenticado. É append-only: nunca atualiza nem remove o log.
 */
class AuditObserver
{
    /**
     * @param  Model&Auditable  $model
     */
    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $model->getAuditSnapshot());
    }

    /**
     * @param  Model&Auditable  $model
     */
    public function updated(Model $model): void
    {
        $before = $model->diffAgainst($model->getOriginal());
        $after = $model->diffAgainst($model->getAttributes());

        if (empty($after)) {
            return; // nenhuma coluna relevante mudou
        }

        $this->write($model, 'updated', $before, $after);
    }

    /**
     * @param  Model&Auditable  $model
     */
    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $model->getAuditSnapshot(), null);
    }

    /**
     * @param  Model&Auditable  $model
     */
    public function restored(Model $model): void
    {
        $this->write($model, 'restored', null, $model->getAuditSnapshot());
    }

    /**
     * @param  Model&Auditable  $model
     */
    public function forceDeleted(Model $model): void
    {
        $this->write($model, 'force_deleted', $model->getAuditSnapshot(), null);
    }

    protected function write(Model $model, string $action, ?array $before, ?array $after): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'event' => (new ReflectionClass($model))->getShortName().'.'.$action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'before' => $before,
            'after' => $after,
            'metadata' => [
                'route' => Request::route()?->getName(),
                'request_id' => Request::header('X-Request-Id'),
            ],
        ]);
    }
}
