<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PapelResponsavelBem;
use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\BemResponsavel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BemResponsavelController extends Controller
{
    public function store(Request $request, BemMaterial $bemMaterial): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        $papelValues = implode(',', array_column(PapelResponsavelBem::cases(), 'value'));

        $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->whereNull('deleted_at')->where('ativo', true)],
            'papel' => ['required', 'string', "in:{$papelValues}"],
        ]);

        $responsavel = BemResponsavel::firstOrCreate(
            [
                'bem_material_id' => $bemMaterial->id,
                'user_id' => $request->user_id,
            ],
            ['papel' => $request->papel]
        );

        if ($responsavel->wasRecentlyCreated) {
            $this->auditar($request, $responsavel, 'Inserção', null, $this->dadosDoResponsavel($responsavel));
        } elseif ($responsavel->papel->value !== $request->papel) {
            $anterior = $this->dadosDoResponsavel($responsavel);
            $responsavel->update(['papel' => $request->papel]);
            $this->auditar($request, $responsavel, 'Alteração', $anterior, $this->dadosDoResponsavel($responsavel));
        }

        return response()->json($bemMaterial->fresh(['responsaveis.usuario', 'curadorResponsavel']));
    }

    public function destroy(Request $request, BemMaterial $bemMaterial, BemResponsavel $bemResponsavel): JsonResponse
    {
        $this->authorize('update', $bemMaterial);

        if ($bemResponsavel->bem_material_id !== $bemMaterial->id) {
            abort(404);
        }

        $anterior = $this->dadosDoResponsavel($bemResponsavel);
        $bemResponsavel->delete();
        $this->auditar($request, $bemResponsavel, 'Remoção', $anterior, null);

        return response()->json($bemMaterial->fresh(['responsaveis.usuario', 'curadorResponsavel']));
    }

    /**
     * @return array{bem_material_id: string, user_id: string, nome: string|null, papel: string|null}
     */
    private function dadosDoResponsavel(BemResponsavel $responsavel): array
    {
        return [
            'bem_material_id' => $responsavel->bem_material_id,
            'user_id' => $responsavel->user_id,
            'nome' => $responsavel->usuario?->name,
            'papel' => $responsavel->papel?->value,
        ];
    }

    /**
     * Registra a mudança no histórico (RF04). A entidade é o vínculo, como em
     * ArtigoBemMaterialController: assim a restauração (só para sítios) não o confunde com o sítio.
     *
     * @param  array<string, mixed>|null  $anterior
     * @param  array<string, mixed>|null  $novo
     */
    private function auditar(Request $request, BemResponsavel $responsavel, string $operacao, ?array $anterior, ?array $novo): void
    {
        Auditoria::create([
            'usuario_id' => $request->user()->id,
            'entidade_tipo' => BemResponsavel::class,
            'entidade_id' => $responsavel->id,
            'curadoria_id' => null,
            'operacao' => $operacao,
            'meio' => 'Manual',
            'data_hora' => now(),
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
        ]);
    }
}
