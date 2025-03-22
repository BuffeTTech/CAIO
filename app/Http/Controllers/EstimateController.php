<?php

namespace App\Http\Controllers;

use App\Enums\FoodType;
use App\Enums\MenuInformationType;
use App\Models\Menu\Item;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use App\Models\MenuHasRoleQuantity;
use App\Models\MenuInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class EstimateController extends Controller
{
    private $expiresAt = 60 * 60; // 1 hora
    public function __construct(
        protected Menu $menu,
        protected Item $items,
        protected MenuHasItem $menu_has_item,
        protected MenuHasRoleQuantity $menu_has_role_quantity,
        protected MenuInformation $menu_information
    )
    {
    }

    public function create_session(Request $request) {
        $id = $request->user_id;
        if(!$id) {
            return response()->json([
                'message' => 'Please provide an id'
            ], 400);
        }

        $data = Redis::get('session:'.$id);
        $data = json_decode($data, true);

        if(!$data) {
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
            $data = [
                'menu'=>$menu->slug,
                'items'=>[],
            ];
            Redis::set('session:'.$id, json_encode($data), 'EX', $this->expiresAt);
        }

        $menu = $this->menu->where('slug', $data['menu'])->first();

        return response()->json([
            'message' => 'Session created successfully',
            'data'=>[
                'menu'=>$menu
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

        $data = Redis::get('session:'.$id);
        if(!$data) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $data = json_decode($data, true);

        $menu = $request->menu;
        if(!$menu && $data['menu'] == "") {
            return response()->json([
                'message' => 'Please provide a menu'
            ], 400);
        }

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
        $exist_in_redis = in_array($item_id, array_column($data['items'], 'id'));
        if($exist_in_original_menu || $exist_in_redis) {
            return response()->json([
                'message' => 'Item already exists'
            ], 400);
        }

        $data['menu'] = $menu->slug; // salvando por via das duvidas caso nao exista
        $data['items'][] = [
            'id'=>$item_id,
            "type"=>'add'
        ];

        Redis::set('session:'.$id, json_encode($data), 'EX', $this->expiresAt);

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
    
        $items = $this->menu_has_item
            ->where('menu_id', $menu->id)
            ->whereHas('item', function ($query) {
                $query->where('type', FoodType::ITEM_INSUMO->name);
            })
            ->with('item')
            ->get()
            ->pluck('item');
    
        $data = Redis::get('session:' . $request->user_id);
        if (!$data) {
            return response()->json($items);
        }
    
        $data = json_decode($data, true);
        $add_items = [];
        $remove_items = [];
        $modifies = [];
    
        foreach ($data['items'] as $item) {
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
        $data = Redis::get('session:'.$id);
        if(!$data) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $data = json_decode($data, true);
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
        $items = $items->filter(function ($item) use ($remove_items) {
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

    public function modify_item_session(Request $request) {
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
        $data = Redis::get('session:'.$id);
        if(!$data) {
            return response()->json([
                'message' => 'Session not found'
            ], 404);
        }

        $remove = $request->item_id;
        if(!$remove) {
            return response()->json([
                'message' => 'Please provide an item'
            ], 400);
        }

        $data = json_decode($data, true);
        $menu = $this->menu->where('slug', $data['menu'])->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $item = $this->items->where('id', $remove)->get()->first();
        if(!$item) {
            return response()->json([
                'message' => 'Invalid item id'
            ], 404);
        }
        $data['menu'] = $menu->slug; 

        $exist_in_redis = in_array($item['id'], array_column($data['items'], 'id'));
        if($exist_in_redis) {
            $index = array_search($item['id'], array_column($data['items'], 'id'));
            if ($index !== false) {
                array_splice($data['items'], $index, 1);
            }        
        }

        $exist_in_original_menu = $this->menu_has_item->where('menu_id', $menu->id)->where('item_id', $item->id)->get()->first();
        if($exist_in_original_menu) {
            $data['items'][] = [
                'id'=>$item->id,
                "type"=>'remove'
            ];
        }
        
        if(!$exist_in_original_menu && !$exist_in_redis) {
            return response()->json([
                'message' => 'Item not found'
            ], 400);
        }

        Redis::set('session:'.$id, json_encode($data), 'EX', $this->expiresAt);
        
        return response()->json([
            'message' => 'Item removed successfully',
            'data'=>$data,
        ]);
    }

    public function change_menu_session(Request $request) {
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

        $data = json_decode($data, true);
        $menu = $this->menu->where('slug', $request->menu)->first();
        if (!$menu) {
            return response()->json(["data" => "Invalid menu slug"], 404);
        }

        $data['menu'] = $menu->slug;
        Redis::set('session:'.$id, json_encode($data), 'EX', $this->expiresAt);

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

        $role_quantity = $this->menu_has_role_quantity
                                    ->where('menu_id', $menu->id)
                                    ->whereHas('quantity', function ($query) use($quantity) {
                                        $query->where('guests_init', '<=', $quantity)
                                              ->where('guests_end', '>=', $quantity);
                                    })
                                    ->get();
        if(count($role_quantity) == 0) {
            return response()->json([
                'message' => 'Invalid number of guests'
            ], 404);
        }
        $informations = $this->menu_information->get();

        $role_information = collect($role_quantity->map(function($information) {
            return [
                // 'id' => $information->quantity->id,
                'name' => $information->quantity->role->name,
                'unit_price' => 0,
                'quantity' => $information->quantity->role->price,
                'created_at' => $information->quantity->created_at,
                'updated_at' => $information->quantity->updated_at,
                'type' => MenuInformationType::EMPLOYEES->name
            ];
        }));
        
        $merged = collect($role_information)->merge($informations);
        
        return response()->json($merged);
    }
}
