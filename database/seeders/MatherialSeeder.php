<?php

namespace Database\Seeders;

use App\Enums\MatherialType;
use App\Models\ItemHasMatherial;
use App\Models\Matherial;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MatherialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kitchenEquipment = [
            "Fogão",
                "Geladeira",
                "Liquidificador",
                "Micro-ondas",
                "Forno elétrico",
                "Cafeteira",
                "Torradeira",
                "Máquina de lavar louça",
                "Processador de alimentos",
                "Batedeira",
                "Air fryer",
                "Grill elétrico",
                "Chaleira elétrica",
                "Máquina de café expresso",
                "Sanduicheira",
                "Panela de pressão",
                "Coifa (exaustor de cozinha)",
                "Forno combinado",
                "Máquina de fazer pão",
                "Máquina de sorvete",
                "Placa de indução",
                "Espremedor elétrico de frutas",
                "Mini processador",
                "Máquina de waffles",
                "Fogão a lenha",
                "Desidratador de alimentos",
                "Sous-vide",
                "Máquina de crepe",
                "Centrífuga de salada"
        ];

        $kitchenTool = [
            "Colher de pau",
                "Faca",
                "Espátula",
                "Concha",
                "Ralador",
                "Abridor de garrafas",
                "Batedor de ovos",
                "Peneira",
                "Tábua de corte",
                // 20 itens adicionais
                "Descascador de legumes",
                "Batedor de claras",
                "Colher de medida",
                "Concha medidora",
                "Pegador de salada",
                "Fouet (batedor de arame)",
                "Colher de silicone",
                "Espátula de silicone",
                "Pinça de cozinha",
                "Rolo de massa",
                "Cortador de pizza",
                "Faca de desossar",
                "Faca de pão",
                "Faca de legumes",
                "Descascador de frutas",
                "Garfo de cozinha",
                "Colher de servir",
                "Copo medidor",
                "Pegador de massa",
                "Suporte para facas"
        ];
    
        $tamanhos = ["P", "M", "G"];

        $items = [];

        foreach($kitchenEquipment as $equipment) {
            $item = Matherial::create([
                "name"=>$equipment,
                "category"=>MatherialType::EQUIPMENT->name,
                // "unit"=>"",
                "quantity"=>random_int(1, 15),
                "observation"=>"",
            ]);
            array_push($items, $item);
        }

        foreach($kitchenTool as $tool) {
            $item = Matherial::create([
                "name"=>$tool,
                "category"=>MatherialType::TOOL->name,
                // "unit"=>"",
                "quantity"=>random_int(1, 15),
                "observation"=>"Tamanho ".$tamanhos[array_rand($tamanhos, 1)],
            ]);
            array_push($items, $item);
        }

        $count = Matherial::count();

        foreach($items as $item) {
            $usedMaterials = []; // Mantém os materiais já associados ao item atual
        
            $numMaterials = random_int(0, 7); // Define quantos materiais serão adicionados
        
            for ($i = 0; $i < $numMaterials; $i++) {
                do {
                    $randomMaterialId = random_int(1, $count);
                } while (in_array($randomMaterialId, $usedMaterials)); // Garante que o material não se repita
        
                $usedMaterials[] = $randomMaterialId; // Adiciona à lista de IDs usados
        
                ItemHasMatherial::create([
                    "item_id" => $item->id,
                    "matherial_id" => $randomMaterialId,
                    "observation" => "",
                    "quantity" => random_int(1, 3),
                ]);
            }
        }

    }
}