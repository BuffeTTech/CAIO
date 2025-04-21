<?php

namespace App\Models\MenuEvent;

use App\Models\EventItemsFlow;
use App\Models\Menu\Item;
use Illuminate\Database\Eloquent\Model;

class MenuEventHasItem extends Model
{
    protected $guarded = [];

    public function menu()
    {
        return $this->belongsTo(MenuEvent::class, 'menu_event_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, "item_id");
    }

    public function ingredients()
    {
        return $this->hasMany(MenuEventItemHasIngredient::class, 'menu_event_has_items_id');
    }

    public function matherials()
    {
        return $this->hasMany(MenuEventItemHasMatherial::class, 'menu_event_has_items_id');
    }
    public function eventItemsFlow()
    {
        return $this->hasMany(EventItemsFlow::class, 'menu_event_has_item_id'); // Nome correto da coluna
    }
}
