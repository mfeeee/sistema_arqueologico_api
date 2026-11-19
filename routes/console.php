<?php

use App\Actions\RegistrarColetaAction;
use App\Models\Auditoria;
use App\Models\BemMaterial;
use App\Models\BemNomePopular;
use App\Models\Coleta;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('acervo:corrigir-dados {--dry-run : Só conta, não grava}', function (RegistrarColetaAction $acao): void {
    $simular = (bool) $this->option('dry-run');

    // 0) Aviso: antes do P15, cada reenvio do app criava uma coleta nova (o id do app era descartado).
    $gruposDuplicados = DB::table('coletas')
        ->whereNull('deleted_at')
        ->select('usuario_id', 'nome_bem', 'data_coleta')
        ->groupBy('usuario_id', 'nome_bem', 'data_coleta')
        ->havingRaw('count(*) > 1')
        ->get()
        ->count();
    $this->warn("Grupos de coletas provavelmente duplicadas (mesmo usuário, nome e data): {$gruposDuplicados}. Revise antes de curar.");

    // 1) Fotos já enviadas pelo app que nunca viraram Midia (P15). A coleta não entra na fila:
    //    quem envia para curadoria é o coletor, pelo app.
    $semFila = Coleta::query()->whereNotExists(function ($consulta): void {
        $consulta->selectRaw('1')->from('curadorias')
            ->where('curadorias.entidade_tipo', 'coleta')
            ->whereColumn('curadorias.entidade_id', 'coletas.id');
    });
    $this->info('Coletas ainda não enviadas para curadoria (fotos a ligar): '.(clone $semFila)->count());

    if (! $simular) {
        $semFila->chunkById(200, function ($coletas) use ($acao): void {
            foreach ($coletas as $coleta) {
                $acao->registrarFotos($coleta);
            }
        });
    }

    // 2) Sítios sem curador responsável (P11): usa quem aprovou, pela auditoria da curadoria.
    $semCurador = BemMaterial::query()->whereNull('curador_responsavel_id');
    $this->info('Sítios sem curador responsável: '.(clone $semCurador)->count());

    if (! $simular) {
        $semCurador->chunkById(200, function ($bens): void {
            foreach ($bens as $bem) {
                $aprovador = Auditoria::where('entidade_tipo', BemMaterial::class)
                    ->where('entidade_id', $bem->id)
                    ->where('operacao', 'Inserção')
                    ->where('meio', 'Curadoria')
                    ->orderBy('data_hora')
                    ->value('usuario_id');

                if ($aprovador !== null) {
                    BemMaterial::withoutEvents(fn () => $bem->update(['curador_responsavel_id' => $aprovador]));
                }
            }
        });
    }

    // 3) Nomes populares da coluna antiga para bem_nomes_populares (P21).
    $comNomes = BemMaterial::query()->whereNotNull('nomes_populares')->where('nomes_populares', '!=', '');
    $this->info('Sítios com nomes populares na coluna antiga: '.(clone $comNomes)->count());

    if (! $simular) {
        $comNomes->chunkById(200, function ($bens): void {
            foreach ($bens as $bem) {
                foreach (preg_split('/[;,]/', (string) $bem->getAttribute('nomes_populares')) as $nome) {
                    if (trim($nome) !== '') {
                        BemNomePopular::firstOrCreate(['bem_material_id' => $bem->id, 'nome' => trim($nome)]);
                    }
                }
            }
        });
    }

    $this->info($simular ? 'Simulação: nada foi gravado.' : 'Pronto.');
})->purpose('Corrige dados gravados antes das correções P11, P15 e P21 (pode rodar mais de uma vez)');
