<?php

namespace App\Models\Menu;

use App\Models\EventItemsFlow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;
    protected $guarded = [];

    public function ingredients()
    {
        return $this->hasMany(ItemHasIngredient::class, 'item_id');
    }

    public function matherials()
    {
        return $this->hasMany(ItemHasMatherial::class, 'item_id');
    }

    public function eventItemsFlow()
    {
        return $this->hasMany(EventItemsFlow::class, 'item_id');
    }
}
