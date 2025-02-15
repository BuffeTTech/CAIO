<?php

namespace App\Models\MenuEvent;

use App\Models\Menu\Menu;
use Illuminate\Database\Eloquent\Model;

class MenuEvent extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(MenuEventHasItem::class, 'menu_event_id');
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
