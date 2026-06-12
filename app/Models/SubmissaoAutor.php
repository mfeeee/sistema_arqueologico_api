<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissaoAutor extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'submissao_autores';

    public $timestamps = true;

    protected $fillable = ['submissao_id', 'nome_autor', 'ordem'];

    public function submissao(): BelongsTo
    {
        return $this->belongsTo(SubmissaoArtigo::class, 'submissao_id');
    }
}
