<?php

namespace Database\Seeders;

use App\Enums\ArtefatoBem;
use App\Models\ArtefatoTipo;
use Illuminate\Database\Seeder;

class ArtefatoTipoSeeder extends Seeder
{
    /** @var array<string, string> Descrições para os tipos derivados do enum ArtefatoBem. */
    private static array $descricoes = [
        'Faiança' => 'Cerâmica de revestimento branco opaco à base de estanho, de origem europeia.',
        'Cerâmica' => 'Artefatos de argila cozida, incluindo vasilhames, urnas e fragmentos.',
        'Lítico' => 'Artefatos confeccionados em pedra, por lascamento ou polimento.',
        'Madeira' => 'Objetos ou estruturas de madeira preservados em contexto arqueológico.',
        'Malacológico' => 'Conchas e materiais de moluscos, comuns em sambaquis e sítios costeiros.',
        'Semente' => 'Sementes e materiais botânicos preservados por carbonização ou dessecação.',
        'Ossos faunísticos' => 'Remanescentes ósseos de animais resultantes de atividades de subsistência ou rituais.',
        'Plástico' => 'Artefatos de plástico associados a sítios históricos contemporâneos.',
        'Grés' => 'Cerâmica de alta temperatura com pasta vitrificada, de origem europeia ou asiática.',
        'Carvão' => 'Material carbonizado utilizado para datação por radiocarbono e reconstituição ambiental.',
        'Faiança fina' => 'Louça branca fina de pasta calcária, produzida industrialmente a partir do século XVIII.',
        'Porcelana' => 'Cerâmica translúcida de alta temperatura, de origem asiática ou europeia.',
        'Têxtil' => 'Fibras, tecidos e trançados preservados em contextos arqueológicos especiais.',
        'Fibra Vegetal' => 'Cestaria, cordoaria e outros artefatos de fibras vegetais.',
        'Vítreo' => 'Artefatos de vidro, incluindo contas, garrafas e fragmentos de janelas.',
        'Borracha' => 'Artefatos de látex ou borracha associados a contextos históricos amazônicos.',
        'Sedimento' => 'Amostras de sedimento coletadas para análise paleoambiental ou química.',
        'Cerâmica vidrada' => 'Cerâmica com revestimento vítreo aplicado por fusão, de origem europeia ou colonial.',
        'Metálico' => 'Artefatos de metal — cobre, bronze, ferro, prata ou ouro.',
        'Ossos humanos' => 'Remanescentes ósseos humanos associados a contextos funerários ou deposicionais.',
        'Outros' => 'Tipos de artefato não classificados nas categorias anteriores.',
    ];

    public function run(): void
    {
        foreach (ArtefatoBem::cases() as $case) {
            $nome = $case->label();

            ArtefatoTipo::firstOrCreate(
                ['nome' => $nome],
                ['descricao' => self::$descricoes[$nome] ?? null],
            );
        }

        $this->command->info('ArtefatoTipoSeeder: '.ArtefatoTipo::count().' tipos carregados.');
    }
}
