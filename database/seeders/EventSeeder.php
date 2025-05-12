<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
use App\Models\EventPricing;
use App\Models\Menu\Menu;
use App\Services\CreateMenuEventService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client_event = Client::find(1);
        $menu_event = Menu::find(1);
        $event = Event::create([
            "client_id"=>$client_event->id,
            "menu_id"=>$menu_event->id,
            'type' => EventType::CLOSED_ESTIMATE->name,
            "date"=>fake()->dateTimeBetween('now', '+4 months'),
            "time"=>fake()->time(),
            "address_id" => random_int(0, 1) == 0 ? $client_event->address_id : Address::factory()->create()->id,
            'guests_amount'=>random_int(30, 100),
        ]);

        $ingredientService = new CreateMenuEventService();
        $ingredientService->handle($event, $menu_event);


        $client_estimate = Client::find(1);
        $menu_estimate = Menu::find(2);
        $event = Event::create([
            "client_id"=>$client_estimate->id,
            "menu_id"=>$menu_estimate->id,
            'type' => EventType::OPEN_ESTIMATE->name,
            "date"=>fake()->dateTimeBetween('now', '+4 months'),
            "time"=>fake()->time(),
            "address_id" => random_int(0, 1) == 0 ? $client_estimate->address_id : Address::factory()->create()->id,
            'guests_amount'=>random_int(30, 100),
        ]);
        $ingredientService = new CreateMenuEventService();
        $ingredientService->handle($event, $menu_estimate);



    }
}
