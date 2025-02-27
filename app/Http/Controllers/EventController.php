<?php

namespace App\Http\Controllers;

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
    public function show(Event $event)
    {
        //
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

    public function add_item_to_checklist(Request $request){
        $id = $request->event_id;

        $event = $this->event->find($id);
        if(!$event) dd("Evento nao encontrado");

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

        return view('event.add_item_checklist', compact('event', 'items'));
    }
    public function store_item_to_checklist(Request $request){
        
        $event = $this->event->find($request->event_id);
        if (!$event) {
            dd("event não encontrado");
        }

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if (!$menu_event) {
            dd("menu não encontrado");
        }

        $item = $this->item->find($request->item_id);
        if (!$item) {
            dd("Item não encontrado");
        }
        $item_exists = $this->menu_event_has_item->where('item_id', $item->id)
                                    ->where('menu_event_id', $menu_event->id)
                                    ->get()
                                    ->first();
        
        if($item_exists) {
            return dd("Este item já está no event");
        }

        $item = $this->menu_event_has_item->create([
            "menu_event_id"=>$menu_event->id,
            "item_id"=>$request->item_id,
            "checked_at"=>null
        ]);


        return back();
    }
    public function checklist(Request $request) {
        $id = $request->event_id;

        $event = $this->event->find($id);
        if(!$event) dd("Evento nao encontrado");

        return view('event.checklist', compact('event'));
    }

    public function check_ingredient(Request $request) {
        $check = $request->check ?? false;

        $event = $this->event->find($request->event_id);
        $ingredient = $this->ingredient->find($request->ingredient_id);
        $item = $this->item->find($request->item_id);

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if(!$menu_event) dd("Evento nao encontrado");

        $menu_event_has_item = $this->menu_event_has_item->where('menu_event_id', $menu_event->id)
                                                         ->where('item_id', $item->id)->get()->first();
        if(!$menu_event_has_item) dd("Item nao encontrado");
        

        $menu_event_item_has_ingredient = $this->menu_event_item_has_ingredient->where('menu_event_has_items_id', $menu_event_has_item->id)
                                                                               ->where('ingredient_id', $ingredient->id)->get()->first();
        if(!$menu_event_item_has_ingredient) dd("Ingrediente nao encontrado");

        $menu_event_item_has_ingredient->update([
            "checked_at"=>$check == true ? now() : null
        ]);

        $this->is_checked_all_items($menu_event_has_item);
        return back()->with("message", 'Alterado com sucesso');
    }
    public function check_matherial(Request $request) {
        $check = $request->check ?? false;

        $event = $this->event->find($request->event_id);
        $matherial = $this->matherial->find($request->matherial_id);
        $item = $this->item->find($request->item_id);

        $menu_event = $this->menu_event->where('event_id', $event->id)->get()->first();
        if(!$menu_event) dd("Evento nao encontrado");

        $menu_event_has_item = $this->menu_event_has_item->where('menu_event_id', $menu_event->id)
                                                         ->where('item_id', $item->id)->get()->first();
        if(!$menu_event_has_item) dd("Item nao encontrado");
        

        $menu_event_item_has_matherial = $this->menu_event_item_has_matherial->where('menu_event_has_items_id', $menu_event_has_item->id)->where('matherial_id', $matherial->id)->get()->first();
        if(!$menu_event_item_has_matherial) dd("Material nao encontrado");

        $menu_event_item_has_matherial->update([
            "checked_at"=>$check == true ? now() : null
        ]);

        $this->is_checked_all_items($menu_event_has_item);
        return back()->with("message", 'Alterado com sucesso');
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

        return back();
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
