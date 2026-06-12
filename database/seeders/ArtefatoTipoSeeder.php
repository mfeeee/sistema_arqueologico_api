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
        $mapping = [
            'Faiança' => '019eaf4f-7316-7245-935e-da1dc0a2fedf',
            'Cerâmica' => '019eaf4f-7760-737c-8c00-12636aec7f23',
            'Lítico' => '019eaf4f-7bb5-721e-8e15-acac3bad7f44',
            'Madeira' => '019eaf4f-7ff7-70c5-ad01-e467b00f018a',
            'Malacológico' => '019eaf4f-843c-73e5-9a6e-538199fdf817',
            'Semente' => '019eaf4f-887e-7341-b35b-a4af2fe916ae',
            'Ossos faunísticos' => '019eaf4f-8cc5-71f4-883f-a7b8812ef47b',
            'Plástico' => '019eaf4f-9106-7236-ae25-2d577dc7d066',
            'Grés' => '019eaf4f-954b-71fa-a64f-fc10ab6f752e',
            'Carvão' => '019eaf4f-998e-70c4-9b35-7a5a3468c2f2',
            'Faiança fina' => '019eaf4f-9dd2-72c0-afb4-38305a350459',
            'Porcelana' => '019eaf4f-a215-73b3-b9fe-f7b2dc0c610a',
            'Têxtil' => '019eaf4f-a65f-71f9-bd72-39ddb3ceb700',
            'Fibra Vegetal' => '019eaf4f-aaa5-732e-9ada-d52c939aad53',
            'Vítreo' => '019eaf4f-aeeb-7386-891f-f9265f366480',
            'Borracha' => '019eaf4f-b32e-702d-9b19-dcf87e300ec0',
            'Sedimento' => '019eaf4f-b773-7237-a45e-ea6eb34295fc',
            'Cerâmica vidrada' => '019eaf4f-bbb5-7188-a0ee-1ee49dca40de',
            'Metálico' => '019eaf4f-bffa-72d5-8961-04907ee60a1c',
            'Ossos humanos' => '019eaf4f-c456-7169-97ee-ddb908afebbb',
            'Outros' => '019eaf4f-c8ad-7239-a26d-d874d333b9c8',
        ];

        foreach (ArtefatoBem::cases() as $case) {
            $nome = $case->label();

            ArtefatoTipo::firstOrCreate(
                ['id' => $mapping[$nome] ?? null],
                [
                    'nome' => $nome,
                    'descricao' => self::$descricoes[$nome] ?? null
                ],
            );
        }

        $this->command->info('ArtefatoTipoSeeder: '.ArtefatoTipo::count().' tipos carregados.');
    }
}
