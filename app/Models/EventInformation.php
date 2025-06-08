<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInformation extends Model
{
    protected $guarded = [];

    public function menu_information()
    {
        return $this->belongsTo(MenuInformation::class, 'menu_information_id');
    }
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
