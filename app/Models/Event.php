<?php

namespace App\Models;

use App\Models\Menu\Menu;
use App\Models\MenuEvent\MenuEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $guarded = [];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
    public function menu_event()
    {
        return $this->belongsTo(MenuEvent::class, 'id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function address() {
        return $this->belongsTo(Address::class, 'address_id');
    }
    public function event_pricing()
    {
        return $this->hasOne(EventPricing::class, 'event_id');
    }
}
