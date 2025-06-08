<?php

namespace Database\Seeders;

use App\Enums\IngredientCategory;
use App\Enums\IngredientSourceType;
use App\Enums\UnitEnum;
use App\Models\Menu\Ingredient;
use App\Models\Menu\ItemHasIngredient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            IngredientCategory::SEASONINGS->name => [
                "kg"=>[
                    "Sal",
                    "Açúcar",
                    "Canela",
                    "Noz-moscada",
                    "Pimenta-do-reino",
                    "Orégano",
                    "Manjericão",
                    "Salsinha",
                    "Cebolinha",
                    "Coentro",
                    "Gengibre"
                ],
                "unid"=>[

                ]
            ],
            IngredientCategory::MEATS->name => [
                "kg"=>[
                    "Carne bovina",
                    "Frango",
                    "Peixe",
                    "Camarão"
                ],
                "unid"=>[
                    "Picanha Premium"
                ]
            ],
            IngredientCategory::GRAINS->name => [
                "kg"=>[
                    "Farinha de trigo",
                    "Feijão",
                    "Arroz",
                    "Macarrão",
                    "Pão"
                ],
                "unid"=>[]
            ],
            IngredientCategory::VEGETABLES->name => [
                "kg"=>[
                    "Tomate",
                    "Cebola",
                    "Alho",
                    "Pimentão",
                    "Batata",
                    "Cenoura",
                    "Abobrinha",
                    "Berinjela",
                    "Milho",
                    "Ervilha"
                ],
                "unid"=>[],
            ],
            IngredientCategory::FRUITS->name => [
                "kg"=>[
                    "Laranja",
                    "Limão",
                    "Banana",
                    "Maçã",
                    "Pera",
                    "Uva",
                    "Abacaxi",
                    "Manga",
                    "Morango"
                ]
            ],
            IngredientCategory::OILS->name => [
                "kg"=>[

                ],
                "unid"=>[],
                "liter"=>[
                    "Óleo de soja",
                    "Manteiga"
                ]
            ]
        ];

        $allIngredientIds = Ingredient::pluck('id')->toArray(); // Obtém todos os IDs existentes
        $proportionsArray = [
            0.010,
            0.020,
            0.060,
            0.100,
            0.150,
            0.225,
            0.250
        ];
        $sourceTypeArray = [
            IngredientSourceType::MARKET->name,
            IngredientSourceType::SUPPLIER->name,

        ];

        $fornecedores = [
            "Atacadão Central",
            "Distribuidora Brasil",
            "Fornecedora Global",
            "Comercial Silva & Cia",
            "Mega Atacado",
            "Distribuidora União",
            "Atacado dos Campos",
            "Central de Suprimentos",
            "Grupo ForneceMais",
            "Distribuidora Alfa",
            "Super Atacado SP",
            "Comercial Nova Era",
            "Rede de Fornecimento RJ"
        ];


        $batchInsertItems = [];
        $batchInsertRelations = [];

        foreach ($ingredients as $category => $unit) {
            foreach ($unit as $key => $data) {
                foreach ($data as $ingredient) {
                    $batchInsertItems[] = [
                        "name" => $ingredient,
                        "category" => $category,
                        "source_type"=> $sourceTypeArray[random_int(0,count($sourceTypeArray) - 1)],
                        "ingredient_source"=> $fornecedores[random_int(0,count($fornecedores) - 1)],
                        "unit" => $key,
                        "quantity" => in_array($unit, ["kg", "liters"]) ? random_int(1, 10) : random_int(1, 15),
                        "observation" => "",
                    ];
                }
            }
        }

        // Insere todos os ingredientes de uma vez
        if (!empty($batchInsertItems)) {
            Ingredient::insert($batchInsertItems);

            // Obtém os novos IDs inseridos
            $newIngredients = Ingredient::whereIn('name', array_column($batchInsertItems, 'name'))->pluck('id')->toArray();
            $allIngredientIds = array_merge($allIngredientIds, $newIngredients); // Atualiza a lista com os novos ingredientes
        }

        // Garante que há ingredientes antes de tentar sortear IDs
        if (!empty($allIngredientIds)) {
            foreach ($allIngredientIds as $ingredientId) {
                $usedIngredients = [];
                $numIngredients = random_int(0, 7);

                for ($i = 0; $i < $numIngredients; $i++) {
                    do {
                        $randomIngredientId = $allIngredientIds[array_rand($allIngredientIds)];
                    } while (in_array($randomIngredientId, $usedIngredients));

                    $usedIngredients[] = $randomIngredientId;
                    

                    // Adiciona relação para inserção em lote
                    $batchInsertRelations[] = [
                        "item_id" => $ingredientId,
                        "ingredient_id" => $randomIngredientId,
                        "observation" => "",
                        "proportion_per_item" => $proportionsArray[random_int(0, count($proportionsArray) - 1)],
                        "unit"=>UnitEnum::KG->name
                    ];
                }
            }

            // Insere todas as relações de uma vez
            if (!empty($batchInsertRelations)) {
                ItemHasIngredient::insert($batchInsertRelations);
            }
        } else {
            echo "⚠️ Nenhum ingrediente encontrado. Verifique se a lista de ingredientes está correta.";
        }
    }
}
