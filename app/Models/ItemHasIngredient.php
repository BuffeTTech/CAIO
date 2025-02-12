<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemHasIngredient extends Model
{
    //
    protected $guarded = [];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, "item_id");
    }
}
