<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInformation extends Model
{
    protected $guarded = [];

    // public function MenuInformation()
    // {
    //     return $this->hasMany(MenuInformation::class, 'event_id', 'id');
    // }
    // public function event()
    // {
    //     return $this->belongsTo(Event::class, 'event_id');
    // }
}
