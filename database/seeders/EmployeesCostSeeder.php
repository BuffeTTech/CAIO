<?php

namespace Database\Seeders;

use App\Models\MenuHasRoleQuantity;
use App\Models\RoleInformations;
use App\Models\RoleQuantities;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeesCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $role_info = RoleInformations::create([
        //     'name' => 'Bebidas',
        //     'price' => 200,
        // ]);
        // $quantity = RoleQuantities::create([
        //     "menu_role_id"=>$role_info->id,
        //     "guests_init"=>25,
        //     "guests_end"=>50,
        //     "quantity"=>1,
        // ]);
        // $menu_quantity = MenuHasRoleQuantities::create([
        //     'menu_id' => 1,
        //     'role_id' => $quantity->id,
        // ]);
        // $info = MenuInformation::create([
        //     "name"=>"Aluguel",
        //     "price"=>123,
        //     "quantity"=>1,
        //     "type"=>MenuInformationType::SERVICES->name,
        // ]);
        // // // MenuEventInformation

        $menus = [
            1, 2, 3, 4, 11,
            5, 12, 7,
            6, 8, 10, 9
            // 'Fatiados Padrão', 'Fatiados Premium', 'Espetinhos', 'Kids', 'Arraiá',
            // 'Comida de Boteco', 'Coquetel', 'Lanchinhos',
            // 'Feijoada', 'Natal', 'Comida Mineira', 'Casamento'
        ];
        
        $roles = [
            'Bebida', 'Churrasqueira', 'Cozinha / Fritura', 'Auxiliar / Louças', 'Garçom', 'Chapa / Lanches', 'Cozinha'
        ];
        
        $quantities = [
            [25, 50, [1, 1, 1, 0, 1]],
            [51, 80, [1, 1, 1, 1, 2]],
            [81, 100, [1, 1, 1, 1, 2]],
            [101, 120, [1, 1, 1, 1, 3]],
            [121, 150, [1, 1, 1, 2, 3]],
            [151, 180, [2, 1, 2, 2, 4]],
            [181, 200, [2, 2, 2, 2, 5]],
        ];
        
        foreach ($menus as $menu) {
            foreach ($roles as $index => $role) {
                $role_info = RoleInformations::create([
                    'name' => $role,
                    'price' => 200,
                ]);
                
                foreach ($quantities as [$init, $end, $values]) {
                    $quantity = RoleQuantities::create([
                        "role_information_id" => $role_info->id,
                        "guests_init" => $init,
                        "guests_end" => $end,
                        "quantity" => $values[$index] ?? 0,
                    ]);
                    
                    MenuHasRoleQuantity::create([
                        'menu_id' => $menu, // Substituir pelo ID correto manualmente
                        'role_quantity_id' => $quantity->id,
                    ]);
                }
            }
        }
        
    }
}
