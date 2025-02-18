<?php

namespace App\Http\Controllers;

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
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $menus = $this->menu->all();

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
    public function show(Menu $menu)
    {
        //
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
        $menuItems = MenuHasItem::where('menu_id', $request->menu_id)->get();

        // foreach ($menuItems as $item) {
        //     $usedItem = [];
        //     $numItem = random_int(0, 7);

        //     for ($i = 0; $i < $numItem; $i++) {
        //         do {
        //             $randomIngredientId = $allIngredientIds[array_rand($allIngredientIds)];
        //         } while (in_array($randomIngredientId, $usedItem));

        //         $usedItem[] = $randomIngredientId;

        //         // Adiciona relação para inserção em lote
        //         $batchInsertRelations[] = [
        //             "item_id" => $ingredientId,
        //             "ingredient_id" => $randomIngredientId,
        //             "observation" => "",
        //             "quantity" => random_int(1, 3),
        //         ];
        //     }
        // }

        $item = MenuHasItem::create([
            "menu_id"=>$request->menu_id,
            "item_id"=>$request->item_id
        ]);

        // $client = Client::inRandomOrder()->first();
        // $menu = Menu::inRandomOrder()->first();
        // $event = Event::create([
        //     "client_id"=>$client->id,
        //     "menu_id"=>$menu->id,
        //     "date"=>fake()->dateTimeBetween('now', '+4 months'),
        //     "address_id" => random_int(0, 1) == 0 ? $client->address_id : Address::factory()->create()->id
        // ]);

        // $ingredientService = new CreateMenuEventService();
        // $ingredientService->handle($event, $menu);
        return redirect()->back();
    }

    public function add_item_to_menu(Request $request) {
        $menu = $this->menu->find($request->menu_id);
        if(!$menu) {
            dd("Menu nao encontrado");
        }

        $items = null;
        if($request->query('query')) {
            $items = $this->items::when($request->has('query'), function ($whenQuery) use ($request) {
                $whenQuery->where('name', 'like', '%' . $request->query('query') . '%');
            })->orderByDesc('created_at')->paginate(10)->withQueryString();
        }

        return view('menu.add_item', compact('menu', 'items'));
    }
}
