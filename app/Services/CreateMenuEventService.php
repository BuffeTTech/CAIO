<?php

namespace App\Services;

use App\Enums\FoodProductionType;
use App\Models\Event;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use App\Models\MenuEvent\MenuEvent;
use App\Models\MenuEvent\MenuEventHasItem;
use App\Models\MenuEvent\MenuEventItemHasIngredient;
use App\Models\MenuEvent\MenuEventItemHasMatherial;
use Illuminate\Support\Facades\DB;

class CreateMenuEventService
{
    public function handle(Event $event, Menu $menu)
    {
        return DB::transaction(function () use ($event, $menu) {
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


            // Criar os itens primeiro para obter os IDs gerados
            foreach ($items as $item) {
                $menuItem = MenuEventHasItem::create([
                    "menu_event_id" => $menuEvent->id,
                    "item_id" => $item->item_id,
                    "checked_at" => null,
                    "consumed_per_client"=>$item->item->consumed_per_client,
                    "unit"=>$item->item->unit,
                    'cost'=>$item->item->cost
                ]);
                // Associar os ingredientes ao item recém-criado
                foreach ($item->item->ingredients as $ingredient) {
                    $ingredients[] = [
                        "menu_event_has_items_id" => $menuItem->id,
                        "ingredient_id" => $ingredient->ingredient_id,
                        "checked_at" => null,
                        "created_at" => now(),
                        "updated_at" => now(),
                        "proportion_per_item"=>$ingredient->proportion_per_item,
                        "unit"=>$ingredient->unit,
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

            return true;
        });
    }
}
