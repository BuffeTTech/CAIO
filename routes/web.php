<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MatherialController;
use App\Http\Controllers\MenuController;
use App\Models\Event;
use App\Models\Menu\Menu;
use App\Services\CreateMenuEventService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Menu


// Evento
// Route::get('/event/create', [EventController::class, 'create'])->name('event.create');
// Route::get('/event/{event_id}/checklist/change_catalog',  [EventController::class, 'change_catalog'])->name('event.change_catalog');