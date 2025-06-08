<?php

namespace App\Models\Menu;

use App\Models\MenuEvent\MenuEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(MenuHasItem::class, 'menu_id');
    }
    public function menu_event()
    {
        return $this->hasOne(MenuEvent::class, 'menu_id');
    }
}
