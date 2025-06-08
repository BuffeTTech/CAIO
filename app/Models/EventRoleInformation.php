<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRoleInformation extends Model
{
    protected $guarded = [];

    public function menu_has_role_quantities()
    {
        return $this->belongsTo(MenuHasRoleQuantity::class, 'menu_has_role_quantities_id');
    }
}
