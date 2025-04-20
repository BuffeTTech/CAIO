<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\FoodType;
use App\Enums\MenuInformationType;
use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
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
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

use function PHPUnit\Framework\isArray;

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
        protected MenuInformation $menu_information,
        protected MenuEventItemHasIngredient $menu_event_item_has_ingredient,
        protected MenuEventItemHasMatherial $menu_event_item_has_matherial,
        protected MenuEventHasItem $menu_event_has_item,
        protected MenuEvent $menu_event,
        protected ItemHasMatherial $item_has_matherial,
        protected ItemHasIngredient $item_has_ingredient,

        protected Client $client,
        protected Address $address,
    )
    {}
    public function index(){
        $allEstimates = $this->event
            ->where('type',EventType::OPEN_ESTIMATE->name)
            ->with('menu')
            ->with('client')
            ->with('address')
            ->get();
        return response()->json($allEstimates);
    }

    public function show(Request $request){
        $estimate = $this->event
            ->with('menu')
            ->with('client')
            ->with('address')
            ->where('id', $request->estimate_id)
            ->get()
            ->first();

        if(!$estimate) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }
        return response()->json($estimate);
    }

    public function items(Request $request){
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

        if(!$estimate) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }

        return response()->json($estimate);
    }

    public function create_session(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $key = 'session:'.$id;

        $data = Redis::hgetall($key);

        if(count($data) == 0) {
            $menu = $request->menu;
            if(!$menu) {
                return response()->json([
                    'message' => 'Please provide a menu',
                ], 400);
            }
            $menu = $this->menu->where('slug', $menu)->first();
            if(!$menu) {
                return response()->json([
                    'message' => 'Invalid menu slug'
                ], 404);
            }
            $costs = [];
            try {
                $costs = $this->mount_costs($menu, $this->initialGuests);
            } catch(Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 400);
            }            
            Redis::hset($key, "menu", $menu->slug);
            Redis::hset($key, "guests", $this->minGuests);
            Redis::hset($key, "items", json_encode([]));
            Redis::hset($key, "costs", json_encode($costs));
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
            'data'=>[
                'menu'=>$menu,
                'initial_guests'=>$data['guests'],
                'min_guests'=>$this->minGuests,
                // 'costs'=>json_decode($data['costs'], true),
            ],
        ]);
    }

    public function store_item_session(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $item_id = $request->item_id;
        if(!$item_id) {
            return response()->json([
                'message' => 'Please provide an item_id'
            ], 400);
        }

        $existsInRedis = Redis::exists('session:'.$id);
        if(!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $menu = Redis::hget('session:'.$id, 'menu');
        if(!$menu) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }
        
        $items = Redis::hget('session:'.$id, 'items');
        $items = json_decode($items, true);
        if(!is_array($items)) {
            Redis::hset('session:'.$id, 'items', json_encode([]));
            $items = [];
        }

        // $data = json_decode($data, true);
        $menu = $this->menu->where('slug', $menu)->first();
        if(!$menu) {
            return response()->json([
                'message' => 'Invalid menu slug'
            ], 404);
        }

        $item = $this->items->find($item_id);
        if(!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item_id)->get()->first();
        $exist_in_redis = in_array($item_id, array_column($items, 'id'));
        if($exist_in_original_menu || $exist_in_redis) {
            return response()->json([
                'message' => 'Item already exists'
            ], 400);
        }

        $items[] = [
            'id'=>$item_id,
            "type"=>'add'
        ];

        Redis::hset('session:'.$id, 'items', json_encode($items));

        return response()->json([
            'message' => 'Item added to session successfully',
        ]);
    }

    public function show_menu_items(Request $request) {
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
        if(!$id) {
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
                $item->price = $modifies[$item->id];
            }
            return $item;
        });
    
        return response()->json($items);
    }

    public function add_item_session(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $data = Redis::hgetall('session:'.$id);
        if(!$data) {
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

    public function modify_item_session(Request $request) { // fazer dnv depois
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $data = Redis::get('session:'.$id);
        if(!$data) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $modify = $request->item;
        if(!$modify['id'] || !$modify['value']) {
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
        if(!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }
        $data['menu'] = $menu->slug; 

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item->id)->get()->first();
        $exist_in_redis = in_array($item['id'], array_column($data['items'], 'id'));
        if(!$exist_in_original_menu || !$exist_in_redis) {
            return response()->json([
                'message' => 'Item not found'
            ], 400);
        }
        $data['items'][] = [
            'id'=>$item->id,
            "type"=>'modify',
            "value"=>$modify['value']
        ];

        Redis::set('session:'.$id, json_encode($data), 'EX', $this->expiresAt);

        return response()->json([
            'message' => 'Item modified successfully',
        ]);
    }

    public function remove_item_session(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $existsInRedis = Redis::exists('session:'.$id);
        if(!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }
        $menu_slug = Redis::hget('session:'.$id, 'menu');
        if(!$menu_slug) {
            return response()->json([
                'message' => 'Menu not found'
            ], 404);
        }
        $items = Redis::hget('session:'.$id, 'items');
        $items = json_decode($items, true);
        if(!is_array($items)) {
            Redis::hset('session:'.$id, 'items', json_encode([]));
            $items = [];
        }
        
        $remove = $request->item_id;
        if(!$remove) {
            return response()->json([
                'message' => 'Please provide an item'
            ], 400);
        }

        $menu = $this->menu->where('slug', $menu_slug)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $item = $this->items->where('id', $remove)->get()->first();
        if(!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }

        $exist_in_redis = in_array($item['id'], array_column($items, 'id'));
        if($exist_in_redis) {
            $index = array_search($item['id'], array_column($items, 'id'));
            if ($index !== false) {
                array_splice($items, $index, 1);
            }        
        }

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item->id)->get()->first();
        if($exist_in_original_menu) {
            $items[] = [
                'id'=>$item->id,
                "type"=>'remove'
            ];
        }
        
        if(!$exist_in_original_menu && !$exist_in_redis) {
            return response()->json([
                'message' => 'Item not found'
            ], 400);
        }

        Redis::hset('session:'.$id, 'items', json_encode($items));
        
        return response()->json([
            'message' => 'Item removed successfully',
            // 'data'=>$data,
        ]);
    }

    public function change_menu_session(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }
        $existsInRedis = Redis::exists('session:'.$id);
        if(!$existsInRedis) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $menu = $this->menu->where('slug', $request->menu)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $key = 'session:'.$id;

        Redis::hset($key, 'menu', $menu->slug);
        Redis::hset($key, 'items', json_encode([]));
        Redis::expire($key, $this->expiresAt);

        return response()->json([
            'message' => 'Menu changed successfully',
        ]);
    }

    public function get_menu_costs(Request $request) {
        $quantity = $request->quantity;
        if(!$quantity) {
            return response()->json([
                'message' => 'Please provide a quantity'
            ], 400);
        }
        if($quantity < $this->minGuests) {
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
        if($id) {
            $key = 'session:'.$id;
            $existsInRedis = Redis::exists($key);
            if(!$existsInRedis) {
                return response()->json([
                    'message' => 'Session not found'
                ], 404);
            }

            $data = Redis::hget($key, 'costs');
            $guests = Redis::hget($key, 'guests');
            $data = json_decode($data, true);
            if($data) {
                if($guests == $quantity)
                    return response()->json($data);
                // $merged = $this->mount_costs($menu, $quantity);

                Redis::hset($key, 'guests', $quantity);
            }
        }

        try {
            $merged = $this->mount_costs($menu, $quantity);
        } catch(Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
        
        return response()->json($merged);
    }

    private function mount_costs(Menu $menu, int $quantity) {
        $role_quantity = $this->menu_has_role_quantity
                                    ->where('menu_id', $menu->id)
                                    ->whereHas('quantity', function ($query) use($quantity) {
                                        $query->where('guests_init', '<=', $quantity)
                                              ->where('guests_end', '>=', $quantity);
                                    })
                                    ->get();
        if(count($role_quantity) == 0) {
            throw new \Exception('Invalid number of guests');
            // return response()->json([
            //     'message' => 'Invalid number of guests'
            // ], 404);
        }
        $informations = $this->menu_information->get();


        $role_information = collect($role_quantity->map(function($information) {
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

    public function change_cost_data(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $cost_id = $request->cost_id;
        if(!$cost_id) {
            return response()->json([
                'message' => 'Please provide an cost_id'
            ], 400);
        }
        $row = $request->row;
        if(!$row) {
            return response()->json([
                'message' => 'Please provide an row'
            ], 400);
        }
        if(!in_array($row, ['unit_price', 'quantity'])) {
            return response()->json([
                'message' => 'Invalid row'
            ], 400);
        }

        $value = $request->value;
        if(!$value) {
            return response()->json([
                'message' => 'Please provide a new value'
            ], 400);
        }
        if(!is_numeric($value) || $value < 0) {
            return response()->json([
                'message' => 'Invalid value'
            ], 422);
        }
        $type = $request->type;
        if(!$type) {
            return response()->json([
                'message' => 'Please provide the type'
            ], 400);
        }
        $key = 'session:'.$id;

        $existsInRedis = Redis::exists($key);
        if(!$existsInRedis) {
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
                if($row == 'unit_price') {
                    $cost['unit_price'] = $value; // Atualiza o valor
                } else if($row == 'quantity') {
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

    public function get_session_by_user(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $key = 'session:'.$id;

        $data = Redis::hgetall($key);

        if(count($data) == 0) {
            return response()->json([
                'message' => 'Session not found'
            ], 400);
        }

        $menu = $this->menu->where('slug', $data['menu'])->first();

        return response()->json([
            'message' => 'Session created successfully',
            'data'=>[
                'menu'=>$menu,
                'initial_guests'=>$data['guests'],
                'min_guests'=>$this->minGuests,
                // 'costs'=>json_decode($data['costs'], true),
            ],
        ]);
    }

    public function save_estimate(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            // Validações para o endereço
            'address.zipcode' => ['required', 'regex:/^\d{5}-\d{3}$/'], // Valida o formato do CEP (xxxxx-xxx)
            'address.street' => ['required', 'string', 'max:255'],
            'address.number' => ['required', 'string', 'max:10'],
            'address.neighborhood' => ['required', 'string', 'max:255'],
            'address.state' => ['required', 'string', 'size:2'], // Valida o estado com 2 caracteres
            'address.city' => ['required', 'string', 'max:255'],
            'address.complement' => ['nullable', 'string', 'max:255'], // Campo opcional
        
            // Validações para os detalhes do usuário
            'details.name' => ['required', 'string', 'max:255'],
            'details.email' => ['required', 'email', 'max:255'], // Valida o formato de email
            'details.phone' => ['required', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'], // Valida o formato do telefone (xx) xxxxx-xxxx
        
            // Validações para o evento
            'event.date' => ['required', 'date'], // Valida se é uma data válida
            'event.time' => ['required', 'regex:/^([01]\d|2[0-3]):([0-5]\d)$/'], // Valida o formato HH:mm
            'event.num_guests' => ['required', 'integer', 'min:1'], // Número de convidados deve ser maior que 0
        
            // Validação para o ID do usuário
            'user_id' => ['required', 'uuid'], // Valida o formato UUID
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $address = $request->address;
        $details = $request->details;
        $event = $request->event;
        
        $key = 'session:'.$id;

        $data = Redis::hgetall($key);

        if(count($data) == 0) {
            return response()->json([
                'message' => 'Session not found'
            ], 400);
        }

        $menu = $this->menu->where('slug', $data['menu'])->first();

        $address = $this->address->create([
            'zipcode'=>$address['zipcode'],
            'street'=>$address['street'],
            'number'=>$address['number'],
            'neighborhood'=>$address['neighborhood'],
            'state'=>$address['state'],
            'city'=>$address['city'],
            'complement'=>$address['complement'],
            "country"=>"Brasil",
        ]);

        $client = $this->client->create([
            'name'=>$details['name'],
            'email'=>$details['email'],
            'whatsapp'=>$details['phone'],
            'address_id'=>$address->id,
        ]);

        $eventDate = date('Y-m-d', strtotime($event['date']));
        // return response()->json([
        //     'message' => [$eventDate, $event['date'], $event['time'], $event['num_guests']],
        // ], 404);

        $event = $this->event->create([
            'date'=>$eventDate,
            'time'=>$event['time'],
            'guests_amount'=>$event['num_guests'],
            'client_id'=>$client->id,
            "menu_id"=>$menu->id,
            "address_id"=>$address->id,
            "type"=>EventType::OPEN_ESTIMATE->name,
        ]);

        $menu_event = $this->menu_event->create([
            "menu_id"=>$menu->id,
            "event_id"=>$event->id
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

        // Remove os itens
        $items = $menuItems->filter(fn($item) => !$removeIds->contains($item->id));

        // Adiciona novos itens (que não estavam originalmente no menu)
        if ($addIds->isNotEmpty()) {
            $newItems = $this->items->whereIn('id', $addIds)->get();
            $items = $items->merge($newItems);
        }

        // Aplica modificações (preço, quantidade, etc.)
        $items = $items->map(function ($item) use ($modifies) {
            if ($modifies->has($item->id)) {
                $item->price = $modifies->get($item->id); // ou outro campo a ser modificado
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
        
        



        Redis::del('session:'.$id);
        
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

    public function close_estimate(Request $request){
        $estimate = $this->event
        ->where('id', $request->estimate_id)
        ->first();

        if(!$estimate) {
            return response()->json(["data"=>"Invalid event id"], 404);
        }
        $estimate = $estimate->update([
            "type" =>EventType::CLOSED_ESTIMATE->name
        ]);
        return response()->json([
            "message"=>"Orçamento Fechado"
        ]);
        // return redirect()->route("all_estimates.index");
    }
    
}
