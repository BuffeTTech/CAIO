<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
use App\Models\Menu\Ingredient;
use App\Models\Menu\Item;
use App\Models\Menu\Menu;
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
        protected Ingredient $ingredient,
        protected MenuEventItemHasIngredient $menu_event_item_has_ingredient,
        protected MenuEventItemHasMatherial $menu_event_item_has_matherial,
        protected MenuEventHasItem $menu_event_has_item,
    ){}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = $this->event->all();
        return view('event.index',['events'=>$events]);
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

        return redirect()->back();
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

        // $menu_event_item_has_ingredient->where('event_id')
    }
}
