<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleQuantities extends Model
{
    public function role() {
        return $this->hasOne(RoleInformations::class, 'id', 'role_information_id');
    }
}
