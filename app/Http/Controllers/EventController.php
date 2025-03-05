<?php

namespace App\Http\Controllers;

use App\Enums\FoodCategory;
use App\Enums\FoodType;
use App\Enums\MatherialType;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
use App\Models\Menu\Ingredient;
use App\Models\Menu\Item;
use App\Models\Menu\Matherial;
use App\Models\Menu\Menu;
use App\Models\MenuEvent\MenuEvent;
use App\Models\MenuEvent\MenuEventHasItem;
use App\Models\MenuEvent\MenuEventItemHasIngredient;
use App\Models\MenuEvent\MenuEventItemHasMatherial;
use App\Services\CreateMenuEventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(
        protected Menu $menu,
        protected Item $item,
        protected Event $event,
        protected Matherial $matherial,
        protected Ingredient $ingredient,
        protected MenuEvent $menu_event,
        protected MenuEventItemHasIngredient $menu_event_item_has_ingredient,
        protected MenuEventItemHasMatherial $menu_event_item_has_matherial,
        protected MenuEventHasItem $menu_event_has_item,
    ){}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = $this->event
            ->with('menu')
            ->with('client')
            ->with('address')
            ->get();
        return response()->json($events);
        // return view('event.index',['events'=>$events]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('event.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        
        $client = Client::inRandomOrder()->first();
        $menu = Menu::inRandomOrder()->first();
        $event = Event::create([
            "client_id"=>$client->id,
            "menu_id"=>$menu->id,
            "date"=>fake()->dateTimeBetween('now', '+4 months'),
            "address_id" => random_int(0, 1) == 0 ? $client->address_id : Address::factory()->create()->id
        ]);

        $ingredientService = new CreateMenuEventService();
        $ingredientService->handle($event, $menu);

        return response()->json($event, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $event = $this->event
            ->with('menu')
            ->with('client')
            ->with('address')
            ->where('id', $request->event_id)
            ->get()
            ->first();

        if(!$event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }
        
        return response()->json($event);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        //
    }

    public function add_item_to_event(Request $request){
        $id = $request->event_id;

        $event = $this->event->find($id);
        if(!$event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        $items = null;
    
        if ($request->has('query')) {
            $query = $request->query('query');

            $itemIdsAlreadyInMenu = $event->menu_event->items->pluck('item_id')->toArray();
    
            $items = $this->item::where('name', 'like', '%' . $query . '%')
                ->whereNotIn('id', $itemIdsAlreadyInMenu) 
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString();
        }

        return response()->json(['event'=>$event, 'items'=>$items]);
    }
    public function store_item_to_event(Request $request){
        
        $event = $this->event->find($request->event_id);
        if(!$event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if(!$event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        $item = $this->item->find($request->item_id);
        if(!$event) {
            return response()->json(["data"=>"Invalid item id"], 404);
        }
        $item_exists = $this->menu_event_has_item->where('item_id', $item->id)
                                    ->where('menu_event_id', $menu_event->id)
                                    ->get()
                                    ->first();
        
        if(!$event) {
            return response()->json(["data"=>"Item was already on event"], 404);
        }

        $item = $this->menu_event_has_item->create([
            "menu_event_id"=>$menu_event->id,
            "item_id"=>$request->item_id,
            "checked_at"=>null
        ]);

        return response()->json("",201);
    }
    public function checklist(Request $request) {
        $id = $request->event_id;

        $event = $this->event
                    ->with('menu')
                    ->with('client')
                    ->with('address')
                    ->with('menu_event.items.ingredients.ingredient')
                    ->with('menu_event.items.matherials.matherial')
                    ->with('menu_event.items.item')
                    // ->with('menu_event')
                    ->where('id', $id)
                    ->get()
                    ->first();
                    // ->paginate($request->get('per_page', 5), ['*'], 'common', $request->get('page', 1));
                    // ->find($id);
        
        if(!$event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        return response()->json($event);
    }
    public function shopping_list(Request $request) {
        $id = $request->event_id;

        $event = $this->event->find($id);
        if(!$event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if(!$menu_event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }
        $type = $request->type;
        if(!$type) {
            $type = "by_category";
        } else if($type && !in_array($type, ["by_item","by_category"])) {
            return response()->json(["data"=>"Invalid type"], 404);
        } 

        if($type == "by_category") {
            $eventIngredientsByCategory = [];

            foreach ($event->menu_event->items as $menuItem) {
                foreach ($menuItem->ingredients as $eventIngredient) {
                    $category = $eventIngredient->ingredient->category; // Pegando a categoria do ingrediente
                    
                    if ($category) {

                        // Se a categoria ainda não existir no array, cria um novo espaço para ela
                        if (!isset($eventIngredientsByCategory[$category])) {
                            $eventIngredientsByCategory[$category] = (object) [
                                'ingredients' => []
                            ];
                        }
                        $itemExists = false;
                        foreach ($eventIngredientsByCategory[$category]->ingredients as $itemIngredient) {
                            if($eventIngredient->ingredient == $itemIngredient)
                            $itemExists = true;
                        }
                        // Adiciona o ingrediente ao grupo da categoria correspondente
                        if (!$itemExists)
                        $eventIngredientsByCategory[$category]->ingredients[] = $eventIngredient->ingredient;
                    }
                }
            }
            return response()->json(['event'=>$event,"data"=>$eventIngredientsByCategory, "type"=>$type]);

        } else {
            $eventItems = $this->menu_event_has_item
                ->where('menu_event_id', $menu_event->id)
                ->whereHas('item', function($query) {
                    $query->where('type', FoodType::ITEM_INSUMO->name);
                })
                ->with([
                    'item',
                    'ingredients.ingredient',
                    // 'item.matherials.matherial'
                ])
                ->get();
            return response()->json(['event'=>$event,"data"=>$eventItems, "type"=>$type]);
        }
        
        // dd($eventIngredientsByCategory);

        // $eventIngredients = $this->ingredient->where('menu_event_id', $id)->get();


        // return view('event.shopping_list', ['event'=>$event,"eventItems"=>$eventItems,"eventIngredients"=>$eventIngredientsByCategory]);

        

        // $items = $this->menu_event_has_item
        //     ->where('menu_event_id', $menu_event->id)
        //     ->with([
        //         'item.ingredients.ingredient', // Carrega os ingredientes e o próprio item
        //     ])
        //     ->get()
        //     ->groupBy(function($menuItem) {
        //         // Verifica se o item tem ingredientes e se o primeiro ingrediente tem um 'ingredient' válido
        //         $ingredient = $menuItem->item->ingredients->first();
        //         return $ingredient && $ingredient->ingredient ? $ingredient->ingredient->category : 'Sem Categoria'; // Valor padrão caso não tenha categoria
        //     });
    
    

        
        return response()->json(['event'=>$event,"eventIngredients"=>$eventIngredientsByCategory]);
    }

    public function equipment_list(Request $request) {
        $id = $request->event_id;

        $event = $this->event->find($id);
        if(!$event) {
            return response()->json(["data"=>"Invalid type"], 404);
        }
        $eventItems = $this->menu_event_has_item
            ->where('menu_event_id', $id)
            ->whereHas('item', function($query) {
                $query->where('type', FoodType::ITEM_INSUMO->name);
            })
            ->with([
                'matherials.matherial'
            ])
            ->get();

        $groupedItems = $eventItems->flatMap(function($menuItem) {
            return $menuItem->matherials->map(function($matherial) use ($menuItem) {
                return [
                    'category' => $matherial->matherial->category,
                    'menuItem' => $menuItem,
                    // 'matherial' => $matherial->matherial
                ];
            });
        })->groupBy('category');

        $eventFixedItems = $this->menu_event_has_item
        ->where('menu_event_id', $id)
        ->whereHas('item', function($query) {
            $query->where('type',FoodType::ITEM_FIXO->name);
        })
        ->with([
            // 'item.ingredients.ingredient',
            'matherials.matherial'
        ])
        ->get();


        $eventFixedItemsbyCategory = [];
        foreach ($eventFixedItems as $fixedItem) {
                $category = $fixedItem->item->category; // Pegando a categoria do ingrediente
                if ($category) {
                    // Se a categoria ainda não existir no array, cria um novo espaço para ela
                    if (!isset($eventFixedItemsbyCategory[$category])) {
                        $eventFixedItemsbyCategory[$category] = (object) [
                            'fixedItems' => []
                        ];
                    }
                    $eventFixedItemsbyCategory[$category]->fixedItems[] = $fixedItem->item;
                }
        }
        return response()->json(['event'=>$event,"eventItems"=>$groupedItems,"eventFixedItems"=>$eventFixedItemsbyCategory]);
        // return view('event.equipment_list', ['event'=>$event,"eventItems"=>$eventItems,"eventFixedItems"=>$eventFixedItemsbyCategory]);
    }

    // public function change_catalog(Request $request) {
    //     $id = $request->event_id;
    //     $request->changeViewMode = !$request->changeViewMode;

    //     return back()->with('changeViewMode');
    // }

    public function check_ingredient(Request $request) {
        $check = $request->check ?? false;

        $event = $this->event->find($request->event_id);
        $ingredient = $this->ingredient->find($request->ingredient_id);
        $item = $this->item->find($request->item_id);

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if(!$menu_event) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        $menu_event_has_item = $this->menu_event_has_item->where('menu_event_id', $menu_event->id)
                                                         ->where('item_id', $item->id)->get()->first();
        if(!$menu_event_has_item) {
            return response()->json(["data"=>"Evento não encontrado"], 404);
        }
        

        $menu_event_item_has_ingredient = $this->menu_event_item_has_ingredient->where('menu_event_has_items_id', $menu_event_has_item->id)
                                                                               ->where('ingredient_id', $ingredient->id)->get()->first();
        if(!$menu_event_item_has_ingredient) {
            return response()->json(["data"=>"Ingredientes não encontrados"], 404);
        }

        $menu_event_item_has_ingredient->update([
            "checked_at"=>$check == true ? now() : null
        ]);

        $this->is_checked_all_items($menu_event_has_item);
        return response()->json("", 201);
    }
    public function check_matherial(Request $request) {
        $check = $request->check ?? false;

        $event = $this->event->find($request->event_id);
        $matherial = $this->matherial->find($request->matherial_id);
        $item = $this->item->find($request->item_id);

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if(!$menu_event) 
        return response()->json("Evento nao encontrado");

        $menu_event_has_item = $this->menu_event_has_item->where('menu_event_id', $menu_event->id)
                                                         ->where('item_id', $item->id)->get()->first();
        if(!$menu_event_has_item) 
        return response()->json("Item nao encontrado");
        

        $menu_event_item_has_matherial = $this->menu_event_item_has_matherial->where('menu_event_has_items_id', $menu_event_has_item->id)->where('matherial_id', $matherial->id)->get()->first();
        if(!$menu_event_item_has_matherial) 
        return response()->json("Material nao encontrado");

        $menu_event_item_has_matherial->update([
            "checked_at"=>$check == true ? now() : null
        ]);

        $this->is_checked_all_items($menu_event_has_item);
        return response()->json("", 201);
    }

    public function check_item(Request $request) {
        $check = $request->check ?? false;
    
        // Busca o evento e o item ou falha se não encontrar
        $event = $this->event->findOrFail($request->event_id);
        $item = $this->item->findOrFail($request->item_id);
    
        // Busca o menu_event associado ao evento
        $menu_event = $this->menu_event->where('event_id', $event->id)->firstOrFail();
    
        // Busca o item dentro do evento do menu
        $menu_event_has_item = $this->menu_event_has_item
            ->where('menu_event_id', $menu_event->id)
            ->where('item_id', $item->id)
            ->firstOrFail();
    
        // Atualiza os ingredientes e materiais, independentemente do estado atual
        $this->menu_event_item_has_matherial
            ->where('menu_event_has_items_id', $menu_event_has_item->id)
            ->update(['checked_at' => $check ? now() : null]);
    
        $this->menu_event_item_has_ingredient
            ->where('menu_event_has_items_id', $menu_event_has_item->id)
            ->update(['checked_at' => $check ? now() : null]);
    
        // // Verifica se todos os ingredientes e materiais foram checados
        $this->is_checked_all_items($menu_event_has_item);

        return response()->json("", 201);
    }
    

    private function is_checked_all_items(MenuEventHasItem $menu_event_has_item)
    {
        // Verifica se todos os ingredientes e materiais estão checados
        $all_matherials_checked = !$this->menu_event_item_has_matherial
            ->where('menu_event_has_items_id', $menu_event_has_item->id)
            ->pluck('checked_at')
            ->contains(null);

        $all_ingredients_checked = !$this->menu_event_item_has_ingredient
            ->where('menu_event_has_items_id', $menu_event_has_item->id)
            ->pluck('checked_at')
            ->contains(null);

        // Atualiza o status do item geral com base na verificação
        $menu_event_has_item->update([
            "checked_at" => ($all_ingredients_checked && $all_matherials_checked) ? now() : null
        ]);
    }

    public function delete_item(Request $request) {
        $event = $this->event->findOrFail($request->event_id);
        $item = $this->item->findOrFail($request->item_id);
    
        // Busca o menu_event associado ao evento
        $menu_event = $this->menu_event->where('event_id', $event->id)->firstOrFail();
    
        // Busca o item dentro do evento do menu
        $menu_event_has_item = $this->menu_event_has_item
            ->where('menu_event_id', $menu_event->id)
            ->where('item_id', $item->id)
            ->firstOrFail();
        $all_matherials_checked = !$this->menu_event_item_has_matherial
            ->where('menu_event_has_items_id', $menu_event_has_item->id)
            ->delete();

        $all_ingredients_checked = !$this->menu_event_item_has_ingredient
            ->where('menu_event_has_items_id', $menu_event_has_item->id)
            ->delete();
        
        $menu_event_has_item->delete();
        return back();
    }

}
