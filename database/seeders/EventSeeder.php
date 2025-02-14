<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Client;
use App\Models\Event;
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
        $client = Client::find(1);
        $menu = Menu::find(1);
        $event = Event::create([
            "client_id"=>$client->id,
            "menu_id"=>$menu->id,
            "date"=>fake()->dateTimeBetween('now', '+4 months'),
            "address_id" => random_int(0, 1) == 0 ? $client->address_id : Address::factory()->create()->id
        ]);

        $ingredientService = new CreateMenuEventService();
        $ingredientService->handle($event, $menu);
    }
}
