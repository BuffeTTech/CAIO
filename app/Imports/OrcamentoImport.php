<?php

namespace App\Imports;

use App\Enums\FoodCategory;
use App\Models\Menu\{Item, Menu, MenuHasItem};
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class OrcamentoImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'CALC. ORÇAMENTO' => new ImportSheet(), // Nome exato da aba
        ];
    }
}

// class ImportSheet implements ToCollection, WithChunkReading
// {
//     public function chunkSize(): int
//     {
//         return 1000; // Ajuste conforme necessário
//     }

//     public function collection(Collection $collection)
//     {
//         /**
//          * Row[0] = Nome
//          * Row[1] = Categoria
//          * Row[2] = Cardapio
//          * Row[3] = Consumo p/ convidado
//          * Row[4] = Unidade de medida
//          * Row[5] = Custo
//          */

//          $filtered = $collection->filter(function ($row) {
//             return !empty(array_filter($row->toArray()));
//         });
    
//         $filteredArray = $filtered->values()->toArray();
    
//         if (count($filteredArray) > 1) {
//             array_shift($filteredArray);
//         }
        
//         foreach($filteredArray as $row) {
//             $row_name = $row[0];
//             $row_category = explode("- ", $row[1])[1];
//             $row_menu = explode("- ", $row[2])[1];
//             $row_consumed_per_client = $row[3];
//             $row_unit = $row[4];
//             $row_cost = $row[5];

//             $menu = Menu::where('name', $row_menu)->first();
//             if(!$menu) {
//                 $menu = Menu::create([
//                     "name"=>$row_menu
//                 ]);
//             }

//             $item = Item::where('name', $row_name)->first();
//             if(!$item) {
//                 $item = Item::create([
//                     "name"=>$row_name,
//                     "cost"=>$row_cost,
//                     "category"=>FoodCategory::getEnumByValue($row_category)->name,
//                     "consumed_per_client"=>$row_consumed_per_client,
//                     "unit"=>$row_unit
//                 ]);
//             }
//         }

//     }
// }

class ImportSheet implements ToCollection, WithChunkReading
{
    public function chunkSize(): int
    {
        return 500; // Ajuste para o tamanho adequado
    }

    public function collection(Collection $collection)
    {
        $filtered = $collection->filter(fn($row) => !empty(array_filter($row->toArray())));
        $filteredArray = $filtered->values()->toArray();

        if (count($filteredArray) > 1) {
            array_shift($filteredArray);
        }

        foreach ($filteredArray as $row) {
            $this->processRow($row);
        }
    }

    private function processRow($row)
    {
        $itemEnum = FoodCategory::getEnumByName("ITEM_INSUMO");
        try {
            $row_name = $row[0] ?? null;
            $row_category = isset($row[1]) ? explode("- ", $row[1])[1] : null;
            $row_menu = isset($row[2]) ? explode("- ", $row[2])[1] : null;
            $row_consumed_per_client = $row[3] ?? null;
            $row_unit = $row[4] ?? null;
            $row_cost = $row[5] ?? null;

            if (!$row_name || !$row_category || !$row_menu) {
                return;
            }

            $menu = Menu::firstOrCreate(['name' => $row_menu]);
            $item = Item::firstOrCreate([
                "name" => $row_name,
            ], [
                "cost" => $row_cost,
                "type" => $itemEnum, 
                "category" => FoodCategory::getEnumByValue($row_category)->name,
                "consumed_per_client" => $row_consumed_per_client,
                "unit" => $row_unit
            ]);

            $menuItem = MenuHasItem::firstOrCreate([
                "item_id" => $item->id,
                "menu_id" => $menu->id,
            ], [
                "item_id" => $item->id,
                "menu_id" => $menu->id,
            ]);

        } catch (\Exception $e) {
            dd("Erro ao importar linha: " . json_encode($row) . " - Erro: " . $e->getMessage());
            // \Log::error("Erro ao importar linha: " . json_encode($row) . " - Erro: " . $e->getMessage());
        }
    }
}
