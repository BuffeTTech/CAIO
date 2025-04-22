<?php

namespace App\Models;

use App\Models\MenuEvent\MenuEventHasItem;
use Illuminate\Database\Eloquent\Model;

class EventItemsFlow extends Model
{
    protected $guarded = [];

    public function MenuEventHasItem()
    {
        return $this->belongsTo(MenuEventHasItem::class, 'menu_event_has_item_id');
    }
    
}
