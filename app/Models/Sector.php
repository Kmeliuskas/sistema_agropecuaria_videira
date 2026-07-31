<?php

namespace App\Models;

use App\Traits\Auditable;
use HotwiredLaravel\TurboLaravel\Facades\TurboStream;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;

#[Fillable(['code', 'name', 'description', 'cost_center_id', 'is_active'])]
class Sector extends Model
{
    use Auditable;
    use HasFactory;
    use SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::updated(function (Sector $sector) {
            $sector->broadcastRow();
        });

        static::created(function (Sector $sector) {
            $content = view('sectors._row_broadcast', ['sector' => $sector])->render();
            TurboStream::broadcastPrepend(
                target: 'sectors_table_body',
                content: new HtmlString($content),
                channel: new PrivateChannel('sectors'),
            );
        });

        static::deleted(function (Sector $sector) {
            TurboStream::broadcastRemove(
                target: dom_id($sector),
                channel: new PrivateChannel('sectors'),
            );
            TurboStream::broadcastRemove(
                target: dom_id($sector),
                channel: new PrivateChannel('sector.' . $sector->id),
            );
        });
    }

    protected function broadcastRow(): void
    {
        $content = view('sectors._row_broadcast', ['sector' => $this])->render();
        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: dom_id($this),
            channel: new PrivateChannel('sector.' . $this->id),
        );
        TurboStream::broadcastAction(
            action: 'replace',
            content: new HtmlString($content),
            target: dom_id($this),
            channel: new PrivateChannel('sectors'),
        );
    }
}
