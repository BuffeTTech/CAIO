<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\FoodType;
use App\Enums\ItemFlowType;
use App\Enums\MenuInformationType;
use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventInformation;
use App\Models\EventItemsFlow;
use App\Models\EventPricing;
use App\Models\EventRoleInformation;
use App\Models\Menu\Item;
use App\Models\Menu\ItemHasIngredient;
use App\Models\Menu\ItemHasMatherial;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use App\Models\MenuEvent\MenuEvent;
use App\Models\MenuEvent\MenuEventHasItem;
use App\Models\MenuEvent\MenuEventItemHasIngredient;
use App\Models\MenuEvent\MenuEventItemHasMatherial;
use App\Models\MenuHasRoleQuantity;
use App\Models\MenuInformation;
use App\Models\Prices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

use function PHPUnit\Framework\isArray;
use function PHPUnit\Framework\isNull;

class EstimateController extends Controller
{
    private $expiresAt = 60 * 60; // 1 hora
    private $minGuests = 25;
    private $initialGuests = 25;

    public function __construct(
        protected Menu $menu,
        protected Item $items,
        protected Event $event,
        protected MenuHasItem $menu_has_item,
        protected MenuHasRoleQuantity $menu_has_role_quantity,
        protected EventRoleInformation $event_role_information,
        protected EventInformation $event_information,
        protected MenuInformation $menu_information,
        protected MenuEventItemHasIngredient $menu_event_item_has_ingredient,
        protected MenuEventItemHasMatherial $menu_event_item_has_matherial,
        protected MenuEventHasItem $menu_event_has_item,
        protected MenuEvent $menu_event,
        protected ItemHasMatherial $item_has_matherial,
        protected ItemHasIngredient $item_has_ingredient,
        protected EventPricing $event_pricing,
        protected EventItemsFlow $event_items_flow,
        protected Prices $prices,

        protected Client $client,
        protected Address $address,
    ) {}
    public function index()
    {
        $allEstimates = $this->event
            ->where('type', EventType::OPEN_ESTIMATE->name)
            ->with('menu')
            ->with('client')
            ->with('address')
            ->with('event_pricing')
            ->get();

        $allEstimates = $this->group_estimates_by_person($allEstimates);
        return response()->json($allEstimates);
    }
    public function edit(Request $request)
    {
        $estimate = $this->event->where('id', $request->estimate_id)
            ->with('menu')
            ->with('client')
            ->with('address')
            // ->with('menu_event.items.ingredients.ingredient')
            // ->with('menu_event.items.matherials.matherial')
            ->with('menu_event.items.item')
            ->with('event_pricing')
            // ->with('menu_event')
            ->where('id', $request->estimate_id)
            ->get()
            ->first();

        return response()->json($estimate);
    }

    public function store_item_to_menu_event(Request $request)
    {
        $item = $this->items->where('id', $request->item_id)
            // ->with('ingredients.ingredient')
            // ->with('ingredients.ingredient')
            ->get()
            ->first();
        $menu_event = $this->menu_event->where("event_id", $request->estimate_id)
            ->get()
            ->first();
        $menu_event_has_new_item = MenuEventHasItem::create([
            "menu_event_id" => $menu_event->id,
            "item_id" => $item->id,
            "cost" => $item->cost,
            "consumed_per_client" => $item->consumed_per_client,
            "unit" => $item->unit
        ]);
        return response()->json($item);
    }

    public function delete_item_from_menu_event(Request $request)
    {
        $menu_event = $this->menu_event->where('event_id', $request->estimate_id)
            ->get()
            ->first();

        MenuEventHasItem::where('menu_event_id', $menu_event->id)
            ->where('item_id', $request->item_id)
            ->delete();

        return response()->json(['message' => 'Item removido']);
    }
    public function create_multiple_estimates()
    {
        $menus = $this->menu->get()->all();

        return response()->json($menus);
    }

    public function store_multiple_estimates(Request $request)
    {
        $profits = $request->menuProfits;
        $client = $this->client->where("id", $request->client_id)->with("address")
            ->get()
            ->first();

        if (!$client->address_id) {
            return response()->json([
                'message' => 'Cliente sem Endereço Cadastrado'
            ], 400);
        }

        $menusSlugs = $request->menus;
        $date = $request->estimateDate;
        $date = substr($date, 0, -14);

        foreach ($menusSlugs as $menuSlug) {
            $menuProfit = 0;
            $menu = $this->menu->where("slug", $menuSlug)
                ->get()
                ->first();

            if (!$menu)
                return response()->json(['data' => 'Invalid Slug']);

            $estimate = Event::create([
                "menu_id" => $menu->id,
                "client_id" => $client["id"],
                "address_id" => $client["address_id"],
                "type" => EventType::OPEN_ESTIMATE->name,
                "guests_amount" => $request->guestsAmount,
                "date" => $date,
                "time" => $request->estimateTime
            ]);

            if (!$estimate) {
                return response()->json(["data" => "Erro no salvamento do orçamento"], 404);
            }

            $menu_event = MenuEvent::create([
                "menu_id" => $menu->id,
                'event_id' => $estimate->id
            ]);

            $data_cost = 0;

            foreach ($menu->items as $item1) {

                $item = $item1['item'];
                $item = $this->items->where('id', $item['id'])
                    ->with("ingredients.ingredient")
                    ->with("matherials.matherial")
                    ->get()
                    ->first();

                $costPerGuest = $item->cost * $item->consumed_per_client; // Custo por convidado
                $data_cost += $costPerGuest * $request->guestsAmount; // Custo total para todos os convidados

                $menu_event_has_items = MenuEventHasItem::create([
                    "menu_event_id" => $menu_event->id,
                    "item_id" => $item->id,
                    "cost" => $item->cost,
                    "consumed_per_client" => $item->consumed_per_client,
                    "unit" => $item->unit
                ]);

                foreach ($item->ingredients as $ingredient) {
                    $menu_event_has_ingredients = MenuEventItemHasIngredient::create([
                        "menu_event_has_items_id" => $menu_event_has_items->id,
                        "ingredient_id" => $ingredient->ingredient_id,
                        "proportion_per_item" => $ingredient->proportion_per_item,
                        "unit" => $ingredient->unit
                    ]);
                }

                foreach ($item->matherials as $matherial) {
                    $menu_event_has_matherials = MenuEventItemHasMatherial::create([
                        "menu_event_has_items_id" => $menu_event_has_items->id,
                        "matherial_id" => $matherial->matherial_id,
                    ]);
                }
            }

            foreach ($profits as $key => $profit) {
                if ($menu->slug == $key)
                    $menuProfit = $profit;
            }
            $fixed_cost = 0;
            $fixed_costs = $this->mount_costs($menu, $request->guestsAmount);
            foreach ($fixed_costs as $cost) {
                if ($cost['type'] !== MenuInformationType::SERVICES->name) {
                    $fixed_cost += $cost['unit_price'] * $cost['quantity'];
                } else {
                    $fixed_cost += $cost['quantity'] * ($data_cost + $menuProfit);
                }
                if ($cost['type'] == MenuInformationType::EMPLOYEES->name) {
                    EventRoleInformation::create([
                        "event_id" => $estimate->id,
                        "unit_price" => $cost['unit_price'],
                        "quantity" => $cost['quantity'],
                        "menu_has_role_quantities_id" => $cost['id']
                    ]);
                } else {
                    EventInformation::create([
                        "event_id" => $estimate->id,
                        "unit_price" => $cost['unit_price'],
                        "quantity" => $cost['quantity'],
                        "menu_information_id" => $cost['id']
                    ]);
                }
            }

            $total = $fixed_cost + $data_cost + $menuProfit;

            $estimate_pricing = EventPricing::create([
                "event_id" => $estimate->id,
                "profit" => $menuProfit,
                "agency" => 0,
                "data_cost" => $data_cost,
                "fixed_cost" => $fixed_cost,
                "total" => $total
            ]);

            if (!$estimate_pricing) {
                return response()->json(["data" => "Erro no salvamento das precificaçoes do orçamento"], 404);
            }
        }
        return response()->json(["data" => "Sucesso no Cadastro!"]);
    }
    public function multiple_estimates_menus(Request $request)
    {
        $slugs = $request->menuSlugs; // ['menu-a', 'menu-b']
        if (!$slugs) {
            return response()->json([
                'message' => 'Please provide a menu slug'
            ], 400);
        }
        $quantity = $request->num_guests;
        if (!$quantity) {
            return response()->json([
                'message' => 'Please provide a quantity'
            ], 400);
        }
        if (!is_numeric($quantity)) {
            return response()->json([
                'message' => 'Please provide a valid quantity'
            ], 400);
        }
        if ($quantity < $this->minGuests) {
            return response()->json([
                'message' => 'Please provide a quantity greater than or equal ' . $this->minGuests
            ], 400);
        }

        // Busca os orçamentos no banco com base nos slugs
        $estimates = Menu::whereIn('slug', $slugs)
            ->with('items.item')
            ->get();
        foreach ($estimates as $estimate) {
            $dataCost = 0;

            // Itera sobre os itens do menu e calcula o custo total
            foreach ($estimate->items as $menuItem) {
                $item = $menuItem->item; // Acessa o item relacionado
                $costPerGuest = $item->cost * $item->consumed_per_client; // Custo por convidado
                $dataCost += $costPerGuest * $quantity; // Custo total para todos os convidados
            }

            $estimate->data_cost = $dataCost;
            $estimate->makeHidden('items');

            $costs = $this->mount_costs($estimate, $quantity);
            $estimate->costs = $costs;
        }
        return response()->json($estimates);
    }
    public function add_client_session(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $client = $request->client;
        if (!$client) {
            return response()->json([
                'message' => 'Please provide a client'
            ], 400);
        }

        $client = $this->client->where('id', $client)->first();
        if (!$client) {
            return response()->json([
                'message' => 'Invalid client id'
            ], 404);
        }

        $key = 'session:' . $id;

        Redis::hset($key, "client", json_encode($client));
        Redis::expire($key, $this->expiresAt);

        return response()->json([
            'message' => 'Client added successfully',
        ]);
    }
    public function show(Request $request)
    {
        $estimate_id = $request->estimate_id;

        $event = $this->event
            ->with('menu')
            ->with('client')
            ->with('address')
            ->where('id', $estimate_id)
            ->get()
            ->first();

        if (!$event) {
            return response()->json(["data" => "Invalid event id"], 404);
        }

        return response()->json([
            'event' => $event,
        ]);
    }

    public function items(Request $request)
    {
        $id = $request->estimate_id;

        $estimate = $this->event
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

        $items = $this->event_items_flow
            ->where('event_id', $id)
            ->with('item')
            ->get();

        if (!$estimate) {
            return response()->json(["data" => "Invalid event id"], 404);
        }

        return response()->json([
            'estimate' => $estimate,
            'changedItems' => $items
        ]);
    }
    public function search_items(Request $request)
    {
        $estimateId = $request->estimate_id;
        $filteredItems = Item::whereNotIn('id', function ($query) use ($estimateId) {
            $query->select('item_id')
                ->from('menu_event_has_items')
                ->where('menu_event_id', $estimateId);
        })->get();

        return response()->json($filteredItems);
    }

    public function update(Request $request)
    {
        $estimate = $this->event->where('id', $request->estimate_id)
            ->with('menu')
            ->with('client')
            ->with('address')
            ->with('menu_event.items.ingredients.ingredient')
            ->with('menu_event.items.matherials.matherial')
            ->with('menu_event.items.item')
            ->with("event_pricing")
            ->get()
            ->first();

        if ($request->observation)
            $estimate->update([
                "observation" => $request->observation
            ]);

        $menu_event = $this->menu_event->where("event_id", $estimate->id)
            ->get()
            ->first();

        if ($request->changedItems['itemsToInsert']) {
            foreach ($request->changedItems['itemsToInsert'] as $item) {
                if (! Item::where('id', $item['id'])->exists()) {
                    // logue um warning, pule este fluxo, lance uma Exception customizada, etc.
                    continue;
                }

                $menu_event_has_item = MenuEventHasItem::firstOrCreate([
                    'menu_event_id' => $menu_event->id,
                    'item_id' => $item['id'],
                ], [
                    'cost' => $item['cost'],
                    'consumed_per_client' => $item["consumed_per_client"],
                    'unit' => $item["unit"]
                ]);
                $collection_item = $this->items->where('id', $item['id'])
                    ->with("ingredients.ingredient")
                    ->with("matherials.matherial")
                    ->get()
                    ->first();

                if (!empty($collection_item['ingredients'])) {
                    foreach ($collection_item['ingredients'] as $ingredient) {
                        MenuEventItemHasIngredient::create([
                            'menu_event_has_items_id' => $menu_event_has_item->id,
                            'ingredient_id' => $ingredient['ingredient_id'],
                            'proportion_per_item' => $ingredient['proportion_per_item'],
                            'unit' => $ingredient['unit'],
                        ]);
                    }
                }

                if (!empty($collection_item['matherials'])) {
                    foreach ($collection_item['matherials'] as $matherial) {
                        MenuEventItemHasMatherial::create([
                            'menu_event_has_items_id' => $menu_event_has_item->id,
                            'matherial_id' => $matherial['matherial_id'],
                        ]);
                    }
                }
                $flow_exists = EventItemsFlow::where('event_id', $estimate->id)
                    ->where('item_id', $item['id'])
                    ->delete();

                $flowAddItem = EventItemsFlow::create([
                    'item_id' => $item["id"],
                    'event_id' => $estimate->id,
                    "status" => ItemFlowType::INSERTED->name,
                ]);
            }
        }

        if ($request->changedItems['itemsToRemove']) {
            foreach ($request->changedItems['itemsToRemove'] as $item) {
                // if (! Item::where('id', $item->item['id'])->exists()) {
                //     // logue um warning, pule este fluxo, lance uma Exception customizada, etc.
                //     continue;
                // }
                $menu_event_has_item = $this->menu_event_has_item
                    ->where('menu_event_id', $menu_event->id)
                    ->where('item_id', $item['item_id'])
                    ->get()
                    ->first();
                if (!$menu_event_has_item) {
                    continue;
                }
                $menu_event_has_item = $this->menu_event_has_item
                    ->where('item_id', $item['item_id'])
                    ->where('menu_event_id', $menu_event->id)
                    ->delete();

                $flowRemovedItem = EventItemsFlow::create([
                    'item_id' => $item['item_id'],
                    'event_id' => $estimate->id,
                    "status" => ItemFlowType::REMOVED->name,
                ]);
            }
        }

        // TODO: recalcular os custos e itens

        $pricing = $request->estimate_pricing;
        $estimate_pricing = $this->event_pricing->where('event_id', $estimate->id)->update([
            "event_id" => $estimate->id,
            "profit" => $pricing["profit"],
            "agency" => $pricing["agency"],
            "data_cost" => $pricing["data_cost"],
            "fixed_cost" => $pricing["fixed_cost"],
            "total" => $pricing["total"]
        ]);


        return response()->json(["data" => "Orçamento Atualizado com Sucesso!"]);
    }

    public function create_session(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $key = 'session:' . $id;

        $data = Redis::hgetall($key);

        if (count($data) == 0) {
            $menu = $request->menu;
            if (!$menu) {
                return response()->json([
                    'message' => 'Please provide a menu',
                ], 400);
            }
            $menu = $this->menu->where('slug', $menu)->first();
            if (!$menu) {
                return response()->json([
                    'message' => 'Invalid menu slug'
                ], 404);
            }
            $costs = [];
            try {
                $costs = $this->mount_costs($menu, $this->initialGuests);
            } catch (Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }


            $prices = $this->get_base_prices();

            Redis::hset($key, "menu", $menu->slug);
            Redis::hset($key, "guests", $this->minGuests);
            Redis::hset($key, "items", json_encode([]));
            Redis::hset($key, "client", null);
            Redis::hset($key, "costs", json_encode($costs));
            Redis::hset($key, "prices", json_encode($prices));
            // Redis::hset($key, "items", json_encode([]));
            Redis::expire($key, $this->expiresAt);

            // $data = [
            //     'menu'=>$menu->slug,
            //     'initial_guests'=>$this->initialGuests,
            //     'min_guests'=>$this->minGuests,
            //     'costs'=>$costs,
            // ];

            $data = Redis::hgetall($key);
        }

        $menu = $this->menu->where('slug', $data['menu'])->first();

        return response()->json([
            'message' => 'Session created successfully',
            'data' => [
                'menu' => $menu,
                'initial_guests' => $data['guests'],
                'min_guests' => $this->minGuests,
                'client' => json_decode($data['client'], true),
                "prices" => json_decode($data['prices'], true),
                // 'costs'=>json_decode($data['costs'], true),
            ],
        ]);
    }

    public function store_item_session(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $item_id = $request->item_id;
        if (!$item_id) {
            return response()->json([
                'message' => 'Please provide an item_id'
            ], 400);
        }

        $existsInRedis = Redis::exists('session:' . $id);
        if (!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $menu = Redis::hget('session:' . $id, 'menu');
        if (!$menu) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $items = Redis::hget('session:' . $id, 'items');
        $items = json_decode($items, true);
        if (!is_array($items)) {
            Redis::hset('session:' . $id, 'items', json_encode([]));
            $items = [];
        }

        // $data = json_decode($data, true);
        $menu = $this->menu->where('slug', $menu)->first();
        if (!$menu) {
            return response()->json([
                'message' => 'Invalid menu slug'
            ], 404);
        }

        $item = $this->items->find($item_id);
        if (!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item_id)->get()->first();
        $exist_in_redis = in_array($item_id, array_column($items, 'id'));
        if ($exist_in_original_menu || $exist_in_redis) {
            return response()->json([
                'message' => 'Item already exists'
            ], 400);
        }

        $items[] = [
            'id' => $item_id,
            "type" => 'add'
        ];

        Redis::hset('session:' . $id, 'items', json_encode($items));

        return response()->json([
            'message' => 'Item added to session successfully',
        ]);
    }

    public function show_menu_items(Request $request)
    {
        $menu_slug = $request->menu_slug;
        if (!$menu_slug) {
            return response()->json([
                'message' => 'Please provide a menu_slug'
            ], 400);
        }

        $menu = $this->menu->where('slug', $menu_slug)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $items = $this->menu_has_item
            ->where('menu_id', $menu->id)
            ->whereHas('item', function ($query) {
                $query->where('type', FoodType::ITEM_INSUMO->name);
            })
            ->with('item')
            ->get()
            ->pluck('item');


        $data = json_decode(Redis::hget('session:' . $id, 'items'), true);
        if (!$data && count($items) == 0) {
            return response()->json($items);
        }
        $add_items = [];
        $remove_items = [];
        $modifies = [];

        foreach ($data as $item) {
            switch ($item['type']) {
                case 'add':
                    $add_items[] = $item['id'];
                    break;
                case 'remove':
                    $remove_items[] = $item['id'];
                    break;
                case 'modify':
                    $modifies[$item['id']] = $item['value'];
                    break;
            }
        }

        // Remove items
        $items = $items->filter(function ($item) use ($remove_items) {
            return !in_array($item->id, $remove_items);
        });

        // Add new items
        $new_items = $this->items->whereIn('id', $add_items)->get();
        $items = $items->merge($new_items);

        // Modify items
        $items = $items->map(function ($item) use ($modifies) {
            if (isset($modifies[$item->id])) {
                $item->consumed_per_client = $modifies[$item->id];
            }
            return $item;
        });

        return response()->json($items);
    }

    public function add_item_session(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $data = Redis::hgetall('session:' . $id);
        if (!$data) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $data['items'] = json_decode($data['items'], true);
        $menu = $this->menu->where('slug', $data['menu'])->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $items = [];

        if ($request->has('query')) {
            $query = $request->query('query');

            $itemIdsAlreadyInMenu = $menu->items()->pluck('item_id')->toArray();

            $items = $this->items::where('name', 'like', '%' . $query . '%')
                ->whereNotIn('id', $itemIdsAlreadyInMenu)
                ->orderByDesc('created_at')
                // ->paginate(10)
                ->get();
            // ->withQueryString();
        }
        $add_items = [];
        $remove_items = [];

        foreach ($data['items'] as $item) {
            switch ($item['type']) {
                case 'add':
                    $add_items[] = $item['id'];
                    break;
                case 'remove':
                    $remove_items[] = $item['id'];
                    break;
            }
        }

        // Remove items
        $items = collect($items ?? [])->filter(function ($item) use ($remove_items) {
            return !in_array($item->id, $remove_items);
        });
        // Add new items
        $new_items_query = $this->items->whereIn('id', $add_items)->get();
        $ids = array_column($new_items_query->toArray(), 'id');

        if ($request->has('query')) {
            $query = $request->query('query');
            $items = $items->filter(function ($item) use ($ids) {
                return !in_array($item->id, $ids);
                // return stripos($item->name, $query) !== false && in_array($item, (array)$items);
            });
        }

        // $items = $items->merge($new_items);
        return response()->json(['menu' => $menu, 'items' => array_values($items->toArray())]);
    }

    public function modify_item_session(Request $request)
    { // fazer dnv depois
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $data = Redis::get('session:' . $id);
        if (!$data) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $modify = $request->item;
        if (!$modify['id'] || !$modify['value']) {
            return response()->json([
                'message' => 'Please provide an item and a value'
            ], 400);
        }

        $data = json_decode($data, true);
        $menu = $this->menu->where('slug', $data['menu'])->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $item = $this->items->where('id', $modify->id)->get()->first();
        if (!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }
        $data['menu'] = $menu->slug;

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item->id)->get()->first();
        $exist_in_redis = in_array($item['id'], array_column($data['items'], 'id'));
        if (!$exist_in_original_menu || !$exist_in_redis) {
            return response()->json([
                'message' => 'Item not found'
            ], 400);
        }
        $data['items'][] = [
            'id' => $item->id,
            "type" => 'modify',
            "value" => $modify['value']
        ];

        Redis::set('session:' . $id, json_encode($data), 'EX', $this->expiresAt);

        return response()->json([
            'message' => 'Item modified successfully',
        ]);
    }

    public function remove_item_session(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $existsInRedis = Redis::exists('session:' . $id);
        if (!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }
        $menu_slug = Redis::hget('session:' . $id, 'menu');
        if (!$menu_slug) {
            return response()->json([
                'message' => 'Menu not found'
            ], 404);
        }
        $items = Redis::hget('session:' . $id, 'items');
        $items = json_decode($items, true);
        if (!is_array($items)) {
            Redis::hset('session:' . $id, 'items', json_encode([]));
            $items = [];
        }

        $remove = $request->item_id;
        if (!$remove) {
            return response()->json([
                'message' => 'Please provide an item'
            ], 400);
        }

        $menu = $this->menu->where('slug', $menu_slug)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $item = $this->items->where('id', $remove)->get()->first();
        if (!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }

        $exist_in_redis = in_array($item['id'], array_column($items, 'id'));
        if ($exist_in_redis) {
            $index = array_search($item['id'], array_column($items, 'id'));
            if ($index !== false) {
                array_splice($items, $index, 1);
            }
        }

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item->id)->get()->first();
        if ($exist_in_original_menu) {
            $items[] = [
                'id' => $item->id,
                "type" => 'remove'
            ];
        }

        if (!$exist_in_original_menu && !$exist_in_redis) {
            return response()->json([
                'message' => 'Item not found'
            ], 400);
        }

        Redis::hset('session:' . $id, 'items', json_encode($items));

        return response()->json([
            'message' => 'Item removed successfully',
            // 'data'=>$data,
        ]);
    }

    public function change_menu_session(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $existsInRedis = Redis::exists('session:' . $id);
        if (!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $menu = $this->menu->where('slug', $request->menu)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $key = 'session:' . $id;

        Redis::hset($key, 'menu', $menu->slug);
        Redis::hset($key, 'items', json_encode([]));
        Redis::expire($key, $this->expiresAt);

        return response()->json([
            'message' => 'Menu changed successfully',
        ]);
    }

    public function get_menu_costs(Request $request)
    {
        $quantity = $request->quantity;
        if (!$quantity) {
            return response()->json([
                'message' => 'Please provide a quantity'
            ], 400);
        }
        if ($quantity < $this->minGuests) {
            return response()->json([
                'message' => 'Invalid number of guests'
            ], 400);
        }
        $menu_slug = $request->menu_slug;
        if (!$menu_slug) {
            return response()->json([
                'message' => 'Please provide a menu_slug'
            ], 400);
        }

        $menu = $this->menu->where('slug', $menu_slug)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }
        $id = $request->user_id;
        if ($id) {
            $key = 'session:' . $id;
            $existsInRedis = Redis::exists($key);
            if (!$existsInRedis) {
                return response()->json([
                    'message' => 'Session not found'
                ], 404);
            }

            $data = Redis::hget($key, 'costs');
            $guests = Redis::hget($key, 'guests');
            $data = json_decode($data, true);
            if ($data) {
                if ($guests == $quantity)
                    return response()->json($data);
                // $merged = $this->mount_costs($menu, $quantity);

                Redis::hset($key, 'guests', $quantity);

                $data = array_filter($data, function ($cost) use ($quantity) {
                    return $cost['type'] != MenuInformationType::EMPLOYEES->name;
                });

                $role_quantity = $this->menu_has_role_quantity
                    ->where('menu_id', $menu->id)
                    ->whereHas('quantity', function ($query) use ($quantity) {
                        $query->where('guests_init', '<=', $quantity)
                            ->where('guests_end', '>=', $quantity);
                    })
                    ->get();
                if (count($role_quantity) == 0) {
                    throw new \Exception('Invalid number of guests');
                }
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
                $merged = collect($role_information)->merge($data);
                Redis::hset($key, 'costs', json_encode($merged));
                return response()->json($merged);
            }
        }

        try {
            $merged = $this->mount_costs($menu, $quantity);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        return response()->json($merged);
    }

    private function mount_costs(Menu $menu, int $quantity)
    {
        $role_quantity = $this->menu_has_role_quantity
            ->where('menu_id', $menu->id)
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
        $informations = $this->menu_information->get();


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

    public function change_cost_data(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $cost_id = $request->cost_id;
        if (!$cost_id) {
            return response()->json([
                'message' => 'Please provide an cost_id'
            ], 400);
        }
        $row = $request->row;
        if (!$row) {
            return response()->json([
                'message' => 'Please provide an row'
            ], 400);
        }
        if (!in_array($row, ['unit_price', 'quantity'])) {
            return response()->json([
                'message' => 'Invalid row'
            ], 400);
        }

        $value = $request->value;
        if (!$value) {
            return response()->json([
                'message' => 'Please provide a new value'
            ], 400);
        }
        if (!is_numeric($value) || $value < 0) {
            return response()->json([
                'message' => 'Invalid value'
            ], 422);
        }
        $type = $request->type;
        if (!$type) {
            return response()->json([
                'message' => 'Please provide the type'
            ], 400);
        }
        $key = 'session:' . $id;

        $existsInRedis = Redis::exists($key);
        if (!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 422);
        }
        $costs = Redis::hget($key, 'costs');
        $costs = json_decode($costs, true);

        if (!is_array($costs)) {
            return response()->json([
                'message' => 'Invalid costs data'
            ], 400);
        }

        $found = false;
        foreach ($costs as &$cost) {
            if ($cost['id'] === $cost_id && $cost['type'] === $type) {
                if ($row == 'unit_price') {
                    $cost['unit_price'] = $value; // Atualiza o valor
                } else if ($row == 'quantity') {
                    $cost['quantity'] = $value; // Atualiza o valor
                }
                $found = true;
                break; // Interrompe o loop
            }
        }

        if (!$found) {
            return response()->json([
                'message' => 'Cost not found'
            ], 404);
        }

        Redis::hset($key, 'costs', json_encode($costs));

        return response()->json("Adicionado com sucesso");
    }

    public function get_session_by_user(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $key = 'session:' . $id;

        $data = Redis::hgetall($key);

        if (count($data) == 0) {
            return response()->json([
                'message' => 'Session not found'
            ], 400);
        }

        $menu = $this->menu->where('slug', $data['menu'])->first();

        return response()->json([
            'message' => 'Session created successfully',
            'data' => [
                'menu' => $menu,
                'initial_guests' => $data['guests'],
                'min_guests' => $this->minGuests,
                'client' => json_decode($data['client'], true),
                "prices" => json_decode($data['prices'], true),
                // 'costs'=>json_decode($data['costs'], true),
            ],
        ]);
    }

    public function save_estimate(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'address.zipcode' => ['required', 'regex:/^\d{5}-\d{3}$/'], // Valida o formato do CEP (xxxxx-xxx)
            'address.street' => ['required', 'string', 'max:255'],
            'address.number' => ['required', 'string', 'max:10'],
            'address.neighborhood' => ['required', 'string', 'max:255'],
            'address.state' => ['required', 'string', 'size:2'], // Valida o estado com 2 caracteres
            'address.city' => ['required', 'string', 'max:255'],
            'address.complement' => ['nullable', 'string', 'max:255'], // Campo opcional

            // 'details.name' => ['required', 'string', 'max:255'],
            // 'details.email' => ['required', 'email', 'max:255'], // Valida o formato de email
            // 'details.phone' => ['required', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'], // Valida o formato do telefone (xx) xxxxx-xxxx

            'event.date' => ['required', 'date'], // Valida se é uma data válida
            'event.time' => ['required', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'], // Valida o formato HH:mm
            // 'event.num_guests' => ['required', 'integer', 'min:1'], // Número de convidados deve ser maior que 0

            'user_id' => ['required', 'uuid'], // Valida o formato UUID
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $prices = $request->prices;
        if (!$prices) {
            return response()->json([
                'message' => 'Please provide the prices'
            ], 400);
        }
        if (!is_array($prices) || !isset($prices['profit']) || !isset($prices['agency']) || !isset($prices['cost']) || !isset($prices['data_cost'])) {
            return response()->json([
                'message' => 'Invalid prices'
            ], 400);
        }

        $address = $request->address;
        // $details = $request->details;
        $event = $request->event;

        $key = 'session:' . $id;

        $data = Redis::hgetall($key);

        if (count($data) == 0) {
            return response()->json([
                'message' => 'Session not found'
            ], 400);
        }

        $client = json_decode($data['client'], true);
        if (!$client) {
            return response()->json([
                'message' => 'Please provide a client using the route PATCH /api/estimate/client'
            ], 400);
        }
        $client = $this->client->where('id', $client['id'])->first();
        if (!$client) {
            return response()->json([
                'message' => 'Invalid client id'
            ], 404);
        }

        $menu = $this->menu->where('slug', $data['menu'])->first();

        $address = $this->address->create([
            'zipcode' => $address['zipcode'],
            'street' => $address['street'],
            'number' => $address['number'],
            'neighborhood' => $address['neighborhood'],
            'state' => $address['state'],
            'city' => $address['city'],
            'complement' => $address['complement'],
            "country" => "Brasil",
        ]);

        $eventDate = date('Y-m-d', strtotime($event['date']));

        $event = $this->event->create([
            'date' => $eventDate,
            'time' => $event['time'],
            // 'guests_amount'=>$event['num_guests'],
            'guests_amount' => $data['guests'],
            'client_id' => $client->id,
            "menu_id" => $menu->id,
            "address_id" => $address->id,
            "type" => EventType::OPEN_ESTIMATE->name,
        ]);

        $menu_event = $this->menu_event->create([
            "menu_id" => $menu->id,
            "event_id" => $event->id
        ]);

        // pegando os itens
        $redisItemsRaw = Redis::hget('session:' . $id, 'items');
        $redisItems = json_decode($redisItemsRaw, true);

        // Busca os itens originais do menu (somente os do tipo ITEM_INSUMO)
        $menuItems = $this->menu_has_item
            ->where('menu_id', $menu->id)
            ->whereHas('item', function ($query) {
                $query->where('type', FoodType::ITEM_INSUMO->name);
            })
            ->with('item')
            ->get()
            ->pluck('item');

        // Organiza os dados do Redis
        $addIds = collect();
        $removeIds = collect();
        $modifies = collect();

        foreach ($redisItems as $item) {
            match ($item['type']) {
                'add'    => $addIds->push($item['id']),
                'remove' => $removeIds->push($item['id']),
                'modify' => $modifies->put($item['id'], $item['value']),
            };
        }

        // Indica os itens que foram removidos e adicionados no menu
        foreach ($removeIds as $removeId) {
            EventItemsFlow::create([
                'item_id' => $removeId,
                'event_id' => $event->id,
                "status" => ItemFlowType::REMOVED->name,
            ]);
        }
        foreach ($addIds as $item) {
            EventItemsFlow::create([
                'item_id' => $item,
                'event_id' => $event->id,
                "status" => ItemFlowType::INSERTED->name,
            ]);
        }
        foreach ($modifies as $item) {
            EventItemsFlow::create([
                'item_id' => $item,
                'event_id' => $event->id,
                "status" => ItemFlowType::MODIFIED->name,
            ]);
        }


        $items = $menuItems->filter(fn($item) => !$removeIds->contains($item->id));
        // Adiciona novos itens (que não estavam originalmente no menu)
        if ($addIds->isNotEmpty()) {
            $newItems = $this->items->whereIn('id', $addIds)->get();
            $items = $items->merge($newItems);
        }


        // Aplica modificações (preço, quantidade, etc.)
        $items = $items->map(function ($item) use ($modifies) {
            if ($modifies->has($item->id)) {
                $item->consumed_per_client = $modifies->get($item->id); // ou outro campo a ser modificado
            }
            return $item;
        });

        // salva no banco
        foreach ($items as $item) {
            // 1. Salvar na menu_event_has_items
            $menuEventItem = $this->menu_event_has_item->create([
                'item_id' => $item->id,
                'menu_event_id' => $menu_event->id,
                'consumed_per_client' => $item->consumed_per_client ?? 0, // ajustar conforme necessário
                'unit' => $item->unit ?? 'UNIT', // usa um valor default se necessário
                'cost' => $item->cost
            ]);

            // 2. Buscar materiais do item
            $materials = $item->matherials ?? $this->item_has_matherial
                ->where('item_id', $item->id)
                ->get();

            foreach ($materials as $matherial) {
                $this->menu_event_item_has_matherial->create([
                    'menu_event_has_items_id' => $menuEventItem->id,
                    'matherial_id' => $matherial->matherial_id,
                    // 'checked_at' permanece null por padrão
                ]);
            }

            // 3. Buscar ingredientes do item
            $ingredients = $item->ingredients ?? $this->item_has_ingredient
                ->where('item_id', $item->id)
                ->get();

            foreach ($ingredients as $ingredient) {
                $this->menu_event_item_has_ingredient->create([
                    'menu_event_has_items_id' => $menuEventItem->id,
                    'ingredient_id' => $ingredient->ingredient_id,
                    'proportion_per_item' => $ingredient->proportion_per_item,
                    'unit' => $ingredient->unit,
                    // 'checked_at' permanece null por padrão
                ]);
            }
        }

        $redisCostsRaw = Redis::hget('session:' . $id, 'costs');
        $redisCosts = json_decode($redisCostsRaw, true);

        $employeeCost = [];
        $generalCost = [];
        foreach ($redisCosts as $cost) {
            if ($cost['type'] == MenuInformationType::EMPLOYEES->name) {
                array_push($employeeCost, $cost);
            } else {
                array_push($generalCost, $cost);
            }
        }

        foreach ($employeeCost as $cost) {
            $this->event_role_information->create([
                'event_id' => $event->id, // ID do evento
                'menu_has_role_quantities_id' => $cost['id'], // ID do papel (role)
                'quantity' => $cost['quantity'], // Quantidade
                'unit_price' => $cost['unit_price'], // Preço unitário
            ]);
        }

        // Inserir os dados do generalCost no event_information
        foreach ($generalCost as $cost) {
            $this->event_information->create([
                'event_id' => $event->id, // ID do evento
                'menu_information_id' => $cost['id'], // ID da informação
                'quantity' => $cost['quantity'], // Quantidade
                'unit_price' => $cost['unit_price'], // Preço unitário
            ]);
        }

        $fixed_cost = $prices['cost'] - $prices['data_cost'];
        $staff = $prices['staff_amount'] * $prices['staff_value'];
        $pre_total = $prices['cost'] + $prices['profit'] + $staff;

        $total = ($pre_total * $prices['agency'] / 100) + $pre_total;
        $this->event_pricing->create([
            'event_id' => $event->id,
            'profit' => $prices['profit'],
            'agency' => $prices['agency'],
            "staff_amount" => $prices['staff_amount'],
            "staff_value" => $prices['staff_value'],
            'data_cost' => $prices['data_cost'],
            'fixed_cost' => $fixed_cost,
            'total' => $total,
        ]);

        Redis::del('session:' . $id);

        return response()->json([
            'message' => 'Estimate saved successfully',
            // 'data'=>[
            //     'menu'=>$menu,
            //     'address'=>$address,
            //     'client'=>$client,
            //     'event'=>$event,
            // ],
        ]);
    }

    public function close_estimate(Request $request)
    {
        $estimate = $this->event
            ->where('id', $request->estimate_id)
            ->first();

        if (!$estimate) {
            return response()->json(["data" => "Invalid event id"], 404);
        }

        $estimates = $this->event
            ->where('client_id', $estimate->client_id)
            ->where('address_id', $estimate->address_id)
            ->where('id', '!=', $estimate->id)
            ->where('date', $estimate->date)
            ->where('time', $estimate->time)
            ->where('guests_amount', $estimate->guests_amount)
            ->where('type', EventType::OPEN_ESTIMATE->name)
            ->get();

        $estimate->update([
            "type" => EventType::CLOSED_ESTIMATE->name
        ]);
        foreach ($estimates as $groupedEstimates) {
            // $groupedEstimates->update([
            //     "type" => EventType::CLOSED_ESTIMATE->name
            // ]);
            $groupedEstimates->delete();
        }

        return response()->json([
            "message" => "Orçamento Fechado"
        ]);
    }

    public function change_item_consumed_per_client(Request $request)
    {
        $id = $request->user_id;
        if (!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $item = $request->item_id;
        if (!$item) {
            return response()->json([
                'message' => 'Please provide an item'
            ], 400);
        }
        $item = $this->items->where('id', $item)->get()->first();
        if (!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }
        $consumed_per_client = $request->value;
        if (!$consumed_per_client) {
            return response()->json([
                'message' => 'Please provide a consumed_per_client'
            ], 400);
        }
        if (!is_numeric($consumed_per_client) || $consumed_per_client < 0) {
            return response()->json([
                'message' => 'Invalid consumed_per_client'
            ], 422);
        }
        $key = 'session:' . $id;
        $existsInRedis = Redis::exists($key);
        if (!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 422);
        }
        $data = Redis::hget($key, 'items');
        $data = json_decode($data, true);
        if (!is_array($data)) {
            return response()->json([
                'message' => 'Invalid items data'
            ], 400);
        }
        $found = false;
        foreach ($data as &$itemData) {
            if ($itemData['id'] === $item->id && $itemData['type'] === 'remove') {
                // remover o item 
                $index = array_search($itemData['id'], array_column($data, 'id'));
                if ($index !== false) {
                    array_splice($data, $index, 1);
                }
                $itemData['type'] = 'modify'; // Atualiza o tipo para 'modify'
                $itemData['value'] = $consumed_per_client; // Atualiza o valor
                $found = true;
                break; // Interrompe o loop
            }
            if ($itemData['id'] === $item && $itemData['type'] === 'modify') {
                $itemData['value'] = $consumed_per_client; // Atualiza o valor
                $found = true;
                break; // Interrompe o loop
            }
        }
        if ($found) {
            Redis::hset($key, 'items', json_encode($data));
            return response()->json([
                'message' => 'Item modified successfully',
            ]);
        }

        $menu_slug = Redis::hget($key, 'menu');
        if (!$menu_slug) {
            return response()->json([
                'message' => 'Menu not found'
            ], 404);
        }
        $menu = $this->menu->where('slug', $menu_slug)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item->id)->get()->first();
        $exist_in_redis = in_array($item['id'], array_column($data, 'id'));
        if (!$exist_in_original_menu && !$exist_in_redis) {
            return response()->json([
                'message' => 'Item not found'
            ], 400);
        }
        $data[] = [
            'id' => $item->id,
            "type" => 'modify',
            "value" => $consumed_per_client
        ];

        Redis::hset($key, 'items', json_encode($data));
        return response()->json([
            'message' => 'Item modified successfully',
        ]);
    }

    public function get_estimate_costs(Request $request)
    {
        $estimate_id = $request->estimate_id;
        if (!$estimate_id) {
            return response()->json([
                'message' => 'Please provide an estimate_id'
            ], 400);
        }
        $estimate = $this->event
            ->where('id', $estimate_id)
            ->where('type', EventType::OPEN_ESTIMATE->name)
            ->first();
        if (!$estimate) {
            return response()->json([
                'message' => 'Invalid estimate id'
            ], 404);
        }
        $event_role_information = $this->event_role_information
            ->where('event_id', $estimate->id)
            ->with('menu_has_role_quantities.quantity.role')
            ->get();
        $event_information = $this->event_information
            ->where('event_id', $estimate->id)
            ->with('menu_information')
            ->get();

        $event_information = collect($event_information->map(function ($information) {
            return [
                'id' => $information->menu_information->id, // id do relacionamento
                'name' => $information->menu_information->name,
                'unit_price' => $information->unit_price,
                'quantity' => $information->quantity,
                'created_at' => $information->menu_information->created_at,
                'updated_at' => $information->menu_information->updated_at,
                'type' => $information->menu_information->type
            ];
        }));
        $event_role_information = collect($event_role_information->map(function ($information) {
            return [
                'id' => $information->menu_has_role_quantities->id, // id do relacionamento
                'name' => $information->menu_has_role_quantities->quantity->role->name,
                'unit_price' => $information->unit_price,
                'quantity' => $information->quantity,
                'created_at' => $information->menu_has_role_quantities->created_at,
                'updated_at' => $information->menu_has_role_quantities->updated_at,
                'type' => MenuInformationType::EMPLOYEES->name
            ];
        }));

        $event_costs = $event_role_information->merge($event_information);

        return response()->json([
            'message' => 'Estimate found successfully',
            'data' => [
                'costs' => $event_costs,
                'prices' => [
                    'profit' => $estimate->event_pricing->profit,
                    'agency' => $estimate->event_pricing->agency,
                    'data_cost' => $estimate->event_pricing->data_cost,
                    'fixed_cost' => $estimate->event_pricing->fixed_cost,
                    'total' => $estimate->event_pricing->total,
                    'staff_amount' => $estimate->event_pricing->staff_amount,
                    'staff_value' => $estimate->event_pricing->staff_value

                ],
                "general" => [
                    "num_guests" => $estimate->guests_amount,
                ]
            ],
        ]);
    }
    public function group_estimates_by_person($estimates)
    {
        $groupMap = [];
        $groupedEstimates = [];

        foreach ($estimates as $estimate) {
            $groupKey = $estimate->client->name . "_" . $estimate->guests_amount . "_" . $estimate->date;

            if (!isset($groupMap[$groupKey])) {
                $groupMap[$groupKey] = count($groupedEstimates);
                $groupedEstimates[] = [
                    "name" => $estimate->client->name,
                    "guests_amount" => $estimate->guests_amount,
                    "date" => $estimate->date,
                    "items" => []
                ];
            }
            $index = $groupMap[$groupKey];
            $groupedEstimates[$index]['items'][] = $estimate;
        }


        return $groupedEstimates;
    }

    public function change_prices(Request $request)
    {
        $user_id = $request->user_id;
        if (!$user_id) {
            return response()->json([
                'message' => 'Please provide an user_id'
            ], 400);
        }
        $type = $request->type;
        if (!$type) {
            return response()->json([
                'message' => 'Please provide a type'
            ], 400);
        }
        if (!in_array($type, ['profit', 'agency', 'staff_amount', 'staff_value'])) {
            return response()->json([
                'message' => 'Invalid type'
            ], 400);
        }
        $value = $request->value;
        if ($value < 0 || is_null($value)) {
            return response()->json([
                'message' => 'Please provide a value'
            ], 400);
        }
        if (!is_numeric($value) || $value < 0) {
            return response()->json([
                'message' => 'Invalid value'
            ], 422);
        }
        $key = 'session:' . $user_id;
        $existsInRedis = Redis::exists($key);
        if (!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 422);
        }
        $data = Redis::hget($key, 'prices');
        $data = json_decode($data, true);
        if (!is_array($data)) {
            return response()->json([
                'message' => 'Invalid prices data'
            ], 400);
        }
        if (!isset($data[$type])) {
            return response()->json([
                'message' => 'Invalid type'
            ], 400);
        }
        $data[$type] = $value; // Atualiza o valor
        Redis::hset($key, 'prices', json_encode($data));
        return response()->json([
            'message' => 'Price modified successfully',
        ]);
    }

    private function get_base_prices()
    {
        $base_prices = $this->prices->first();
        if (!$base_prices) {
            $base_prices = $this->prices->create([
                'profit' => 0,
                'agency' => 0,
                'staff_amount' => 0,
                'staff_value' => 0,
            ]);
        }
        return $base_prices;
    }
}
