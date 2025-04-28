<?php

namespace App\Models;

use App\Models\Menu\Item;
use App\Models\MenuEvent\MenuEventHasItem;
use Illuminate\Database\Eloquent\Model;

class EventItemsFlow extends Model
{
    protected $guarded = [];

    public function Event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
    public function Item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
    
}
