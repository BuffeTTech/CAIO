<?php

namespace App\Http\Controllers;

use App\Enums\FoodCategory;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu\Item;
use App\Models\FixedItems;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuHasItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(
        protected Menu $menu,
        protected Item $items,
        protected FixedItems $fixedItems,
        protected MenuHasItem $menu_has_item,
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menus = $this->menu->all();
        $items =$this->items->where('type',FoodCategory::getEnumByName("ITEM_INSUMO"))->get();
        $fixedItems =$this->items->where('type',FoodCategory::getEnumByName("ITEM_FIXO"))->get();

        return view('menu.index', compact('menus'));
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
        $menu = $this->menu->find($request->menu_id);
        $items = $this->menu_has_item->where('menu_id', $menu->id)->get();
        $itemIds = $items->pluck('item_id');
        $menuItems = Item::whereIn('id', $itemIds)
            ->where('type', FoodCategory::getEnumByName("ITEM_INSUMO"))
            ->get();
            
        $menuFixedItems = Item::whereIn('id', $itemIds)
        ->where('type', FoodCategory::getEnumByName("ITEM_FIXO"))
        ->get();
        return view("menu.show",["menu"=> $menu,"items"=> $menuItems,"fixedItems"=> $menuFixedItems]);
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
        $menu = $this->menu->find($request->menu_id);
        if (!$menu) {
            dd("Menu não encontrado");
        }

        $item = $this->items->find($request->item_id);
        if (!$item) {
            dd("Item não encontrado");
        }
        $item_exists = $this->menu_has_item->where('item_id', $item->id)
                                    ->where('menu_id', $menu->id)
                                    ->get()
                                    ->first();
        
        if($item_exists) {
            return dd("Este item já está no menu");
        }

        $item = $this->menu_has_item->create([
            "menu_id"=>$request->menu_id,
            "item_id"=>$request->item_id
        ]);

        return redirect()->back();
    }

    public function add_item_to_menu(Request $request) {
        // Recupera o menu
        $menu = $this->menu->find($request->menu_id);
        if (!$menu) {
            dd("Menu não encontrado");
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
    
        return view('menu.add_item', compact('menu', 'items'));
    }

    public function remove_item_from_menu(Request $request) {
        $menu = $this->menu->find($request->menu_id);
        if(!$menu) {
            return back()->withErrors(['menu'=> "Menu não existe"]);
        }
        $item = $this->items->find($request->item_id);
        if(!$item) {
            return back()->withErrors(['item'=> "Item não existe"]);
        }
        
        $menu_has_item = $this->menu_has_item->where('menu_id', $menu->id)
                                             ->where('item_id', $item->id)->get()->first();
        if(!$menu_has_item) {
            return back()->withErrors(['menu_has_item'=> "Relacionamento não existe"]);
        }
        $menu_has_item->delete();

        return back()->with('message', "Material deletado com sucesso!");
    }
    
}
