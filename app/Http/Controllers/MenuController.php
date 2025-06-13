<?php

namespace App\Http\Controllers;

use App\Enums\FoodType;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu\Item;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(
        protected Menu $menu,
        protected Item $items,
        protected MenuHasItem $menu_has_item,
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menus = $this->menu->with([
            // 'items.item.ingredients.ingredient',
            // 'items.item.matherials.matherial',
        ])->get();

        return response()->json($menus);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        // dd($request);
        $menu = $this->menu->where('slug', $request->menu_slug)->get()->first();
        if(!$menu) {
            return response()->json(["data"=>"Invalid menu slug"], 404);
        }
        $fixedItems = $this->menu_has_item
            ->where('menu_id', $menu->id)
            ->whereHas('item', function($query) {
                $query->where('type', FoodType::ITEM_FIXO->name);
            })
            ->with([
                'item.ingredients.ingredient',
                'item.matherials.matherial'
            ])
            ->paginate($request->get('fixed_count', 5), ['*'], 'fixed', $request->get('fixed', 1));
            // ->get();

        $menuItems = $this->menu_has_item
            ->where('menu_id', $menu->id)
            ->whereHas('item', function($query) {
                $query->where('type', FoodType::ITEM_INSUMO->name);
            })
            ->with([
                'item.ingredients.ingredient',
                'item.matherials.matherial'
            ])
            ->paginate($request->get('common_count', 5), ['*'], 'common', $request->get('common', 1));
            // ->get();


        return response()->json(["fixed"=>$fixedItems, "common"=>$menuItems]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        //
    }

    public function store_item_to_menu(Request $request) {
        $menu = $this->menu->where('slug', $request->menu_slug)->get()->first();
        if(!$menu) {
            return response()->json(["data"=>"Invalid menu slug"], 404);
        }

        $item = $this->items->find($request->item_id);
        if(!$item) {
            return response()->json(["data"=>"Invalid item id"], 404);
        }
        
        $item_exists = $this->menu_has_item->where('item_id', $item->id)
                                    ->where('menu_id', $menu->id)
                                    ->get()
                                    ->first();
        
        if($item_exists) {
            return response()->json(["data"=>"Item was already in menu"], 409);
        }

        $relation = $this->menu_has_item->create([
            "menu_id"=>$menu->id,
            "item_id"=>$item->id
        ]);

        return response()->json("",201);
    }

    public function add_item_to_menu(Request $request) {
        // Recupera o menu
        $menu = $this->menu->where('slug', $request->menu_slug)->get()->first();
        if(!$menu) {
            return response()->json(["data"=>"Invalid menu slug"], 404);
        }

        // Inicializa a variável $items
        $items = null;
    
        if ($request->has('query')) {
            $query = $request->query('query');
    
            $itemIdsAlreadyInMenu = $menu->items()->pluck('item_id')->toArray();
    
            $items = $this->items::where('name', 'like', '%' . $query . '%')
                ->whereNotIn('id', $itemIdsAlreadyInMenu)
                ->orderByDesc('created_at') 
                ->paginate(10)
                ->withQueryString();
        }

        return response()->json(['menu'=>$menu, 'items'=>$items]);
    
        // return view('menu.add_item', compact('menu', 'items'));
    }

    public function remove_item_from_menu(Request $request) {
        $menu = $this->menu->where('slug', $request->menu_slug)->get()->first();
        if(!$menu) {
            return response()->json(["data"=>"Invalid menu slug"], 404);
        }
        $item = $this->items->find($request->item_id);
        if(!$item) {
            return response()->json(["data"=>"Invalid item id"], 404);
        }
        
        $menu_has_item = $this->menu_has_item->where('menu_id', $menu->id)
        ->where('item_id', $item->id)->get()->first();
        if(!$menu_has_item) {
            return response()->json(["data"=>"Invalid relationship"], 404);
        }
        $menu_has_item->delete();
        
        return response()->json('deletado com sucesso!');
        return back()->with('message', "Material deletado com sucesso!");
    }

    # Items
    public function show_items(Request $request) {
        $menu = $this->menu->where('slug', $request->menu_slug)->get()->first();
        if(!$menu) {
            return response()->json(["data"=>"Invalid menu slug"], 404);
        }
        $items = $this->menu_has_item
            ->where('menu_id', $menu->id)
            ->whereHas('item', function($query) {
                $query->where('type', FoodType::ITEM_INSUMO->name);
            })
            ->with([
                'item',
            ])
            ->get()
            ->pluck('item');
        

        return response()->json($items);
    }
    
}
