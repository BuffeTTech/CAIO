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
            ["ALCOOL EM GEL", 10, 0, MenuInformationType::EVENT],
            ["FRETE", 200, 1, MenuInformationType::EVENT],
            ["LOCAÇÕES GERAL", 250, 1, MenuInformationType::EVENT],
            ["VAN PARA FUNCIONARIOS", 500, 0, MenuInformationType::EVENT],
            ["PRODUTOS DE LIMPEZA", 30, 1, MenuInformationType::EVENT],
            ["CARVÃO 20KG", 67, 0, MenuInformationType::EVENT],
            ["BANHA", 13, 5, MenuInformationType::EVENT],
            ["Combustível Dia a Dia", 100, 1.5, MenuInformationType::EVENT],
            ["ALUGUEL", 0, 0.02, MenuInformationType::SERVICES],
            ["AGUA", 0, 0.007, MenuInformationType::SERVICES],
            ["LUZ", 0, 0.008, MenuInformationType::SERVICES],
            ["GÁS", 0, 0.004, MenuInformationType::SERVICES],
            ["CAIXA RESERVA", 0, 0.01, MenuInformationType::SERVICES]
        ];

    //     { name: "Álcool em Gel", quantity: 0.0, unit_price: 10.0, type: "event" },
    //     { name: "Frete", quantity: 1.0, unit_price: 200.0, type: "event" },
    //     { name: "Locações Geral", quantity: 1.0, unit_price: 250.0, type: "event" },
    //     { name: "Van para Funcionários", quantity: 0.0, unit_price: 500.0, type: "event" },
    //     { name: "Produtos de Limpeza", quantity: 1.0, unit_price: 30.0, type: "event" },
    //     { name: "Carvão 20KG", quantity: 0.0, unit_price: 67.0, type: "event" },
    //     { name: "Banha", quantity: 5.0, unit_price: 13.0, type: "event" },
    //     { name: "Combustível Dia a Dia", quantity: 1.5, unit_price: 100.0, type: "event" },
    //     { name: "Aluguel", quantity: 0.02, unit_price: 0.0, type: "services" },
    //     { name: "Água", quantity: 0.007, unit_price: 0.0, type: "services" },
    //     { name: "Luz", quantity: 0.008, unit_price: 0.0, type: "services" },
    //     { name: "Gás", quantity: 0.004, unit_price: 0.0, type: "services" },
    //     { name: "Caixa Reserva", quantity: 0.01, unit_price: 0.0, type: "services" },
        
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
