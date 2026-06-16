<?php

namespace App\Models;

use App\Enums\TipoMidia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read string|null $url
 */
class Midia extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'storage_disk',
        'storage_path',
        'mime_type',
        'tipo',
        'descricao',
    ];

    protected $casts = [
        'tipo' => TipoMidia::class,
    ];

    public function getUrlAttribute(): ?string
    {
        if (! $this->storage_path) {
            return null;
        }

        return $this->storage_disk === 'external'
            ? $this->storage_path
            : Storage::disk($this->storage_disk ?? 'public')
                ->url($this->storage_path);
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
