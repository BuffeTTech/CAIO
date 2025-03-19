<?php

namespace Database\Seeders;

use App\Enums\MenuInformationType;
use App\Models\MenuHasRoleQuantities;
use App\Models\MenuHasRoleQuantity;
use App\Models\MenuInformation;
use App\Models\RoleInformation;
use App\Models\RoleInformations;
use App\Models\RoleQuantities;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ["ALCOOL EM GEL", 10, 1, MenuInformationType::EVENT],
            ["FRETE", 200, 1, MenuInformationType::EVENT],
            ["LOCAÇÕES GERAL", 250, 1, MenuInformationType::EVENT],
            ["VAN PARA FUNCIONARIOS", 500, 1, MenuInformationType::EVENT],
            ["PRODUTOS DE LIMPEZA", 30, 1, MenuInformationType::EVENT],
            ["CARVÃO 20KG", 67, 1, MenuInformationType::EVENT],
            ["BANHA", 13, 5, MenuInformationType::EVENT],
            ["Combustível Dia a Dia", 100, 1.5, MenuInformationType::EVENT],
            ["ALUGUEL", 0.02, 0, MenuInformationType::SERVICES],
            ["AGUA", 0.007, 0, MenuInformationType::SERVICES],
            ["LUZ", 0.008, 0, MenuInformationType::SERVICES],
            ["GÁS", 0.004, 0, MenuInformationType::SERVICES],
            ["CAIXA RESERVA", 0.01, 0, MenuInformationType::SERVICES]
        ];
        
        foreach ($items as [$name, $price, $quantity, $type]) {
            $info = MenuInformation::create([
                "name" => $name,
                "unit_price" => $price,
                "quantity" => $quantity,
                "type" => $type->name,
            ]);
        }
        
    }
}
