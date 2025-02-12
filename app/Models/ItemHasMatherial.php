<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemHasMatherial extends Model
{
    //
    protected $guarded = [];

    public function matherial()
    {
        return $this->belongsTo(Matherial::class, 'matherial_id');
    }

    public function item() {
        return $this->belongsTo(Item::class, "item_id");
    }
}
