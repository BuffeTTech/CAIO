<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use App\Models\MenuEvent\MenuEvent;
use App\Models\MenuEvent\MenuEventHasItem;
use App\Models\MenuEvent\MenuEventItemHasIngredient;
use App\Models\MenuEvent\MenuEventItemHasMatherial;

class CreateMenuEventService
{
    public function handle(Event $event, Menu $menu)
    {
        // Criar o evento do menu e obter a instância
        $menuEvent = MenuEvent::create([
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

        foreach ($items as $item) {
            // Adiciona a relação MenuEventHasItem
            $menuItems[] = [
                "menu_id" => $menuEvent->id,
                "item_id" => $item->item_id, // Corrigindo para o ID correto do item
                "checked_at" => null,
                "created_at" => now(),
                "updated_at" => now()
            ];

            foreach ($item->item->matherials as $matherial) {
                $matherials[] = [
                    "item_id" => $item->item_id,
                    "matherial_id" => $matherial->matherial_id,
                    "checked_at" => null,
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            }

            foreach ($item->item->ingredients as $ingredient) {
                $ingredients[] = [
                    "item_id" => $item->item_id,
                    "ingredient_id" => $ingredient->ingredient_id,
                    "checked_at" => null,
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            }
        }

        // Inserção em lote para otimizar performance
        if (!empty($menuItems)) {
            MenuEventHasItem::insert($menuItems);
        }

        if (!empty($matherials)) {
            MenuEventItemHasMatherial::insert($matherials);
        }

        if (!empty($ingredients)) {
            MenuEventItemHasIngredient::insert($ingredients);
        }

        return true;
    }
}
