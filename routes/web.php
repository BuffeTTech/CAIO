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

Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist'])->name('event.checklist');
Route::patch('/event/{event_id}/checklist/item/{item_id}/ingredient/{ingredient_id}', [EventController::class, 'check_ingredient'])->name('event.checklist.check_ingredient');
Route::patch('/event/{event_id}/checklist/item/{item_id}/matherial/{matherial_id}', [EventController::class, 'check_matherial'])->name('event.checklist.check_matherial');
Route::patch('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'check_item'])->name('event.checklist.check_item');
Route::delete('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'delete_item'])->name('event.checklist.delete_item');
Route::get('/event/{event_id}/checklist/item',  [EventController::class, 'add_item_to_checklist'])->name('event.add_item_to_checklist');
Route::post('/event/{event_id}/checklist/item',  [EventController::class, 'store_item_to_checklist'])->name('event.store_item_to_checklist');