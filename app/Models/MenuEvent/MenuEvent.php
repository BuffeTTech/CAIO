<?php

namespace App\Models\MenuEvent;

use Illuminate\Database\Eloquent\Model;

class MenuEvent extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(MenuEventHasItem::class, 'menu_event_id');
    }
}
