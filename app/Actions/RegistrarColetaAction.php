<?php

namespace App\Actions;

use App\Enums\ArtefatoBem;
use App\Enums\StatusColeta;
use App\Enums\StatusCuradoria;
use App\Enums\TipoMidia;
use App\Exceptions\SincronizacaoException;
use App\Models\ArtefatoTipo;
use App\Models\Coleta;
use App\Models\Curadoria;
use App\Models\Localizacao;
use App\Models\Midia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrarColetaAction
{
    /**
     * Cria ou atualiza uma coleta (sync do app ou rota REST) e a coloca na fila de curadoria.
     *
     * @param  array<string, mixed>  $dados  Uma coleta já validada.
     *
     * @throws SincronizacaoException quando a coleta é de outro usuário, foi excluída ou tem versão antiga.
     */
    public function execute(User $usuario, array $dados): Coleta
    {
        return DB::transaction(function () use ($usuario, $dados): Coleta {
            $existente = Coleta::withTrashed()->lockForUpdate()->find($dados['id']);

            if ($existente !== null && $usuario->cannot('update', $existente)) {
                throw new SincronizacaoException("Coleta {$dados['id']} pertence a outro usuário.");
            }

            if ($existente?->trashed()) {
                throw new SincronizacaoException("Coleta {$dados['id']} foi excluída.");
            }

            $versao = (int) ($dados['versao'] ?? 1);

            if ($existente !== null && $existente->versao > $versao) {
                throw new SincronizacaoException(
                    "Conflito de versão na coleta {$dados['id']}: servidor={$existente->versao}, cliente={$versao}"
                );
            }

            $valores = [
                'usuario_id' => $existente?->usuario_id ?? $usuario->id, // o dono nunca muda
                'nome_bem' => $dados['nome_bem'],
                'data_coleta' => $dados['data_coleta'],
                'latitude' => $dados['latitude'],
                'longitude' => $dados['longitude'],
                'versao' => $versao,
                'dados_coletados' => $dados['dados_coletados'] ?? $existente?->dados_coletados ?? [],
                'status_sincronizacao' => StatusColeta::SINCRONIZADO,
                'localizacao_id' => $this->salvarLocalizacao($existente, $dados),
            ];

            // Opcionais: só mudam quando vêm no payload, para uma edição parcial não apagar dados.
            foreach (['natureza' => 'natureza_bem', 'tipo' => 'tipo_bem', 'uf' => 'uf'] as $chave => $coluna) {
                if (array_key_exists($chave, $dados)) {
                    $valores[$coluna] = $dados[$chave];
                }
            }

            // O id vem do app e não está no $fillable: updateOrCreate o descartaria e geraria outro UUID,
            // e cada reenvio criaria uma coleta nova. Por isso o id é atribuído explicitamente.
            $coleta = $existente ?? (new Coleta)->forceFill(['id' => $dados['id']]);
            $coleta->fill($valores)->save();

            $this->sincronizarArtefatos($coleta, $dados);
            $this->registrarFotos($coleta);
            $this->abrirCuradoriaSePreciso($coleta);

            return $coleta;
        });
    }

    /**
     * Liga à coleta as fotos que o app já enviou ao S3 (dados_coletados.foto_urls).
     * Só aceita URLs do nosso bucket; ignora as demais.
     */
    public function registrarFotos(Coleta $coleta): void
    {
        $urls = $coleta->dados_coletados['foto_urls'] ?? [];

        if (! is_array($urls) || $urls === []) {
            return;
        }

        // url('') falha no driver S3 (chave vazia); a base sai de um caminho qualquer.
        $base = Str::beforeLast(Storage::disk('s3')->url('_'), '_');

        foreach ($urls as $url) {
            if (! is_string($url) || ! str_starts_with($url, $base)) {
                continue;
            }

            $caminho = substr($url, strlen($base));

            Midia::firstOrCreate(
                ['mediable_type' => Coleta::class, 'mediable_id' => $coleta->id, 'storage_path' => $caminho],
                ['storage_disk' => 's3', 'tipo' => TipoMidia::IMAGEM, 'mime_type' => $this->mimePorExtensao($caminho)],
            );
        }
    }

    /**
     * Coloca a coleta na fila de curadoria (RF03) quando ela ainda não tem nenhuma curadoria.
     * Reabrir a revisão quando a coleta muda depois de avaliada é a D4 (adiada).
     */
    public function abrirCuradoriaSePreciso(Coleta $coleta): void
    {
        $jaTemCuradoria = Curadoria::where('entidade_tipo', 'coleta')
            ->where('entidade_id', $coleta->id)
            ->exists();

        if (! $jaTemCuradoria) {
            // Mesmo formato do ColetaECuradoriaSeeder.
            Curadoria::create([
                'entidade_tipo' => 'coleta',
                'entidade_id' => $coleta->id,
                'bem_material_id' => null,
                'usuario_id' => $coleta->usuario_id,
                'status' => StatusCuradoria::PENDENTE,
            ]);
        }
    }

    /**
     * Grava a localização da coleta e o ponto PostGIS. O id da localização é do servidor, nunca do cliente.
     *
     * @param  array<string, mixed>  $dados
     */
    private function salvarLocalizacao(?Coleta $existente, array $dados): string
    {
        $origem = $dados['localizacao'] ?? [];
        $localizacao = $existente?->localizacao ?? new Localizacao;

        $localizacao->fill([
            'uf' => $dados['uf'] ?? $origem['uf'] ?? $localizacao->uf,
            'municipio' => $origem['municipio'] ?? $dados['municipio'] ?? $localizacao->municipio,
            'cep' => $origem['cep'] ?? $dados['cep'] ?? $localizacao->cep,
            'logradouro' => $origem['logradouro'] ?? $dados['logradouro'] ?? $localizacao->logradouro,
        ])->save();

        DB::statement(
            'UPDATE localizacoes SET geom = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$dados['longitude'], $dados['latitude'], $localizacao->id],
        );

        return $localizacao->id;
    }

    /**
     * Substitui os tipos de artefato da coleta pelos enviados.
     * Aceita o formato do app (artefato_tipos) e o antigo (artefatos = valores do enum).
     * Tipos novos viram "Outros", com o texto digitado em descricao_nova.
     *
     * @param  array<string, mixed>  $dados
     */
    private function sincronizarArtefatos(Coleta $coleta, array $dados): void
    {
        if (! array_key_exists('artefato_tipos', $dados) && ! array_key_exists('artefatos', $dados)) {
            return; // payload sem lista: mantém o que já existe
        }

        /** @var array<string, list<string|null>> $porTipo */
        $porTipo = [];
        $idOutros = ArtefatoTipo::where('nome', ArtefatoBem::OUTROS->label())->value('id');

        foreach ($dados['artefato_tipos'] ?? [] as $item) {
            if (! empty($item['novo_tipo'])) {
                if ($idOutros !== null) {
                    $porTipo[$idOutros][] = $item['descricao_nova'] ?? $item['nome'] ?? null;
                }

                continue;
            }

            $porTipo[$item['id']] ??= [];
        }

        foreach ($dados['artefatos'] ?? [] as $valor) {
            $tipoId = ArtefatoTipo::where('nome', ArtefatoBem::from($valor)->label())->value('id');

            if ($tipoId !== null) {
                $porTipo[$tipoId] ??= [];
            }
        }

        $idsValidos = ArtefatoTipo::whereIn('id', array_keys($porTipo))->pluck('id');

        $coleta->artefatoTipos()->delete();

        foreach ($idsValidos as $tipoId) {
            $descricoes = array_values(array_filter($porTipo[$tipoId]));

            $coleta->artefatoTipos()->create([
                'artefato_tipo_id' => $tipoId,
                'novo_tipo' => $descricoes !== [],
                'descricao_nova' => $descricoes !== [] ? implode('; ', $descricoes) : null,
            ]);
        }
    }

    private function mimePorExtensao(string $caminho): string
    {
        return match (strtolower(pathinfo($caminho, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            default => 'image/jpeg',
        };
    }
}
