<?php

namespace App\Models;

use App\Enums\TipoMidia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
