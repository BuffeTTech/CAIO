<?php

namespace App\Models\MenuEvent;

use App\Models\Menu\Ingredient;
use Illuminate\Database\Eloquent\Model;

class MenuEventItemHasIngredient extends Model
{
    protected $guarded = [];

    public function ingredient() {
        return $this->belongsTo(Ingredient::class, "ingredient_id");
    }
}
