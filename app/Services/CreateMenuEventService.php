<?php

namespace App\Services;

use App\Enums\FoodProductionType;
use App\Enums\MenuInformationType;
use App\Models\Event;
use App\Models\EventInformation;
use App\Models\EventPricing;
use App\Models\EventRoleInformation;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use App\Models\MenuEvent\MenuEvent;
use App\Models\MenuEvent\MenuEventHasItem;
use App\Models\MenuEvent\MenuEventItemHasIngredient;
use App\Models\MenuEvent\MenuEventItemHasMatherial;
use App\Models\MenuHasRoleQuantity;
use App\Models\MenuInformation;
use Illuminate\Support\Facades\DB;

class CreateMenuEventService
{
    public function handle(Event $event, Menu $menu)
    {
        return DB::transaction(function () use ($event, $menu) {
            $menuProfit = 2000;


            // Criar o evento do menu e associá-lo ao evento correto
            $menuEvent = MenuEvent::create([
                "event_id" => $event->id, // Adicionando a referência ao evento
                "menu_id" => $menu->id
            ]);

            // Carregar os itens com seus ingredientes e materiais (evita N+1 queries)
            $items = MenuHasItem::where("menu_id", $menu->id)
                ->with(['item.ingredients', 'item.matherials'])
                ->get();

            // Arrays para inserção em lote
            $menuItems = [];
            $ingredients = [];
            $matherials = [];

            $data_cost = 0;
            // Criar os itens primeiro para obter os IDs gerados
            foreach ($items as $item) {
                $costPerGuest = $item->cost * $item->consumed_per_client; // Custo por convidado
                $data_cost += $costPerGuest * $event->guests_amount; // Custo total para todos os convidados

                $menuItem = MenuEventHasItem::create([
                    "menu_event_id" => $menuEvent->id,
                    "item_id" => $item->item_id,
                    "checked_at" => null,
                    "consumed_per_client" => $item->item->consumed_per_client,
                    "unit" => $item->item->unit,
                    'cost' => $item->item->cost
                ]);
                // Associar os ingredientes ao item recém-criado
                foreach ($item->item->ingredients as $ingredient) {
                    $ingredients[] = [
                        "menu_event_has_items_id" => $menuItem->id,
                        "ingredient_id" => $ingredient->ingredient_id,
                        "checked_at" => null,
                        "created_at" => now(),
                        "updated_at" => now(),
                        "proportion_per_item" => $ingredient->proportion_per_item,
                        "unit" => $ingredient->unit,
                    ];
                }


                // Associar os materiais ao item recém-criado
                foreach ($item->item->matherials as $matherial) {
                    $matherials[] = [
                        "menu_event_has_items_id" => $menuItem->id,
                        "matherial_id" => $matherial->matherial_id,
                        "checked_at" => null,
                        "created_at" => now(),
                        "updated_at" => now()
                    ];
                }
            }

            // Inserção em lote para otimizar performance
            if (!empty($ingredients)) {
                MenuEventItemHasIngredient::insert($ingredients);
            }

            if (!empty($matherials)) {
                MenuEventItemHasMatherial::insert($matherials);
            }

            $fixed_cost = 0;
            $fixed_costs = $this->mount_costs($menu, $event->guests_amount);
            foreach ($fixed_costs as $cost) {
                if ($cost['type'] !== MenuInformationType::SERVICES->name) {
                    $fixed_cost += $cost['unit_price'] * $cost['quantity'];
                } else {
                    $fixed_cost += $cost['quantity'] * ($data_cost + $menuProfit);
                }
                if ($cost['type'] == MenuInformationType::EMPLOYEES->name) {
                    EventRoleInformation::create([
                        "event_id" => $event->id,
                        "unit_price" => $cost['unit_price'],
                        "quantity" => $cost['quantity'],
                        "menu_has_role_quantities_id" => $cost['id']
                    ]);
                } else {
                    EventInformation::create([
                        "event_id" => $event->id,
                        "unit_price" => $cost['unit_price'],
                        "quantity" => $cost['quantity'],
                        "menu_information_id" => $cost['id']
                    ]);
                }
            }

            $total = $fixed_cost + $data_cost + $menuProfit;

            $estimate_pricing = EventPricing::create([
                "event_id" => $event->id,
                "profit" => $menuProfit,
                "agency" => 0,
                "data_cost" => $data_cost,
                "fixed_cost" => $fixed_cost,
                "total" => $total
            ]);

            return true;
        });
    }

    private function mount_costs(Menu $menu, int $quantity)
    {
        $role_quantity = MenuHasRoleQuantity::where('menu_id', $menu->id)
            ->whereHas('quantity', function ($query) use ($quantity) {
                $query->where('guests_init', '<=', $quantity)
                    ->where('guests_end', '>=', $quantity);
            })
            ->get();
        if (count($role_quantity) == 0) {
            throw new \Exception('Invalid number of guests');
            // return response()->json([
            //     'message' => 'Invalid number of guests'
            // ], 404);
        }
        $informations = MenuInformation::get();


        $role_information = collect($role_quantity->map(function ($information) {
            return [
                'id' => $information->quantity->id, // id do relacionamento
                'name' => $information->quantity->role->name,
                'unit_price' => $information->quantity->role->price,
                'quantity' => $information->quantity->quantity,
                'created_at' => $information->quantity->created_at,
                'updated_at' => $information->quantity->updated_at,
                'type' => MenuInformationType::EMPLOYEES->name
            ];
        }));

        $merged = collect($role_information)->merge($informations);

        return $merged;
    }
}
