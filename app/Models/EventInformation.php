<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInformation extends Model
{
    protected $guarded = [];

    public function menu_information()
    {
        return $this->hasOne(MenuInformation::class, 'id');
    }
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
