<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtigoAutor extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'artigo_autores';

    public $timestamps = false;

    protected $fillable = ['artigo_id', 'nome_autor', 'ordem'];

    public function artigo(): BelongsTo
    {
        return $this->belongsTo(ArtigoCientifico::class, 'artigo_id');
    }
}
