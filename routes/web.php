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
Route::get('/menu',  [MenuController::class, 'index']);
Route::get('/menu/{menu_id}',  [MenuController::class, 'show'])->name('menu.show');
Route::get('/menu/{menu_id}/item/',  [MenuController::class, 'add_item_to_menu'])->name('menu.add_item_to_menu');
Route::post('/menu/{menu_id}/item',  [MenuController::class, 'store_item_to_menu'])->name('menu.store_item_to_menu');
Route::delete('/menu/{menu_id}/item/{item_id}',  [MenuController::class, 'remove_item_from_menu'])->name('menu.remove_item_from_menu');
Route::delete('/menu/items/{item_id}/ingredients/{id}',  [IngredientController::class, 'destroy'])->name('ingredient.destroy');
Route::delete('/menu/items/{item_id}/matherial/{id}',  [MatherialController::class, 'destroy'])->name('matherial.destroy');

// Evento
Route::get('/event', [EventController::class, 'index'])->name('event.index');
Route::get('/event/create', [EventController::class, 'create'])->name('event.create');
Route::post('/event/store', [EventController::class, 'store'])->name('event.store');

Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist'])->name('event.checklist');
Route::patch('/event/{event_id}/checklist/item/{item_id}/ingredient/{ingredient_id}', [EventController::class, 'check_ingredient'])->name('event.checklist.check_ingredient');
Route::patch('/event/{event_id}/checklist/item/{item_id}/matherial/{matherial_id}', [EventController::class, 'check_matherial'])->name('event.checklist.check_matherial');
Route::patch('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'check_item'])->name('event.checklist.check_item');
Route::delete('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'delete_item'])->name('event.checklist.delete_item');
Route::get('/event/{event_id}/checklist/item',  [EventController::class, 'add_item_to_checklist'])->name('event.add_item_to_checklist');
Route::get('/event/{event_id}/checklist/shopping_list',  [EventController::class, 'shopping_list'])->name('event.shopping_list');
Route::get('/event/{event_id}/checklist/equipment_list',  [EventController::class, 'equipment_list'])->name('event.equipment_list');
// Route::get('/event/{event_id}/checklist/change_catalog',  [EventController::class, 'change_catalog'])->name('event.change_catalog');


Route::post('/event/{event_id}/checklist/item',  [EventController::class, 'store_item_to_checklist'])->name('event.store_item_to_checklist');