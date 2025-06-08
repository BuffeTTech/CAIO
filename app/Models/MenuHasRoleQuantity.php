<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuHasRoleQuantity extends Model
{
    public function quantity() {
        return $this->hasOne(RoleQuantities::class, 'id', 'role_quantity_id');
    }
}
