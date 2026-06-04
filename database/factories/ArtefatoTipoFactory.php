<?php

namespace Database\Factories;

use App\Models\ArtefatoTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArtefatoTipo>
 */
class ArtefatoTipoFactory extends Factory
{
    protected $model = ArtefatoTipo::class;

    /** Tipos de artefato arqueológico comuns em sítios brasileiros. */
    private static array $tipos = [
        ['nome' => 'Lítico Lascado', 'descricao' => 'Artefatos de pedra obtidos por lascamento, como pontas de projétil e raspadores.'],
        ['nome' => 'Lítico Polido', 'descricao' => 'Artefatos de pedra polida, como machados e mãos de pilão.'],
        ['nome' => 'Cerâmica', 'descricao' => 'Fragmentos ou peças inteiras de vasilhames, urnas e outros objetos de argila cozida.'],
        ['nome' => 'Ossos Humanos', 'descricao' => 'Remanescentes ósseos de indivíduos humanos associados a contextos funerários.'],
        ['nome' => 'Ossos de Fauna', 'descricao' => 'Ossos de animais resultantes de atividades de subsistência ou rituais.'],
        ['nome' => 'Material Orgânico', 'descricao' => 'Fibras, sementes, carvão e outros vestígios orgânicos preservados.'],
        ['nome' => 'Arte Rupestre', 'descricao' => 'Pinturas e gravuras em suportes rochosos.'],
        ['nome' => 'Metal', 'descricao' => 'Artefatos metálicos de cobre, bronze, ferro ou outros metais.'],
        ['nome' => 'Concha', 'descricao' => 'Conchas e materiais malacológicos associados a sambaquis ou contextos rituais.'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(self::$tipos);

        return [
            'nome' => $tipo['nome'],
            'descricao' => $tipo['descricao'],
        ];
    }
}
