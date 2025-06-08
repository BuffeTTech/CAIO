<?php

namespace App\Models\MenuEvent;

use App\Models\Menu\Matherial;
use Illuminate\Database\Eloquent\Model;

class MenuEventItemHasMatherial extends Model
{
    protected $guarded = [];

    public function matherial() {
        return $this->belongsTo(Matherial::class, "matherial_id");
    }
}
