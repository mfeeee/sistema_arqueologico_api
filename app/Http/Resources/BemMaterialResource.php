<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BemMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coleta_id' => $this->coleta_id,
            'localizacao_id' => $this->localizacao_id,
            'curador_responsavel_id' => $this->curador_responsavel_id,
            'codigo_iphan' => $this->codigo_iphan,
            'nome_bem' => $this->nome_bem,
            'nomes_populares' => $this->nomesPopulares
                ->pluck('nome')
                ->values()
                ->all(),
            'natureza' => $this->natureza?->value,
            'tipo' => $this->tipo?->value,
            'meios_acesso' => $this->meios_acesso,
            'uf' => $this->uf,
            'municipio' => $this->municipio,
            'publicado' => (bool) $this->publicado,
            'artefato_tipos' => ArtefatoTipoResource::collection(
                $this->whenLoaded('artefatoTipos')
            ),

            'responsaveis' => BemResponsavelResource::collection(
                $this->whenLoaded('responsaveis')
            ),
            'midias' => MidiaResource::collection(
                $this->whenLoaded('midias')
            ),
            'localizacao' => $this->resolveLocalizacao(),
            'geojson' => $this->geojson,
            'ano_registro' => $this->ano_registro,
            'descricao_atualizacao' => $this->descricao_atualizacao,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }

    private function resolveLocalizacao(): ?array
    {
        $temDados = $this->uf || $this->municipio
                 || $this->latitude || $this->longitude
                 || $this->cep || $this->endereco;

        if (! $temDados) {
            return null;
        }

        return [
            'id' => $this->id.'_loc',
            'uf' => $this->uf,
            'municipio' => $this->municipio,
            'cep' => $this->cep,
            'logradouro' => $this->endereco,
            'lat' => $this->latitude ? (float) $this->latitude : null,
            'lng' => $this->longitude ? (float) $this->longitude : null,
        ];
    }
}
