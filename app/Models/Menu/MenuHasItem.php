<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Model;

class MenuHasItem extends Model
{
    protected $guarded = [];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, "item_id");
    }
}
