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

Route::get('/menu',  [MenuController::class, 'index']);
Route::post('/menu/{menu_id}',  [MenuController::class, 'store_item_to_menu'])->name('menu.store_item_to_menu');
Route::get('/menu/{menu_id}',  [MenuController::class, 'add_item_to_menu'])->name('menu.add_item_to_menu');


Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist'])->name('event.checklist');
Route::delete('/menu/items/{item_id}/ingredients/{id}',  [IngredientController::class, 'destroy'])->name('ingredient.destroy');
Route::delete('/menu/items/{item_id}/matherial/{id}',  [MatherialController::class, 'destroy'])->name('matherial.destroy');
Route::get('/event/create', [EventController::class, 'create'])->name('event.create');
Route::post('/event/store', [EventController::class, 'store'])->name('event.store');
Route::get('/event', [EventController::class, 'index'])->name('event.index');

Route::patch('/event/{event_id}/checklist/item/{item_id}/ingredient/{ingredient_id}', [EventController::class, 'check_ingredient'])->name('event.checklist.check_ingredient');
Route::patch('/event/{event_id}/checklist/item/{item_id}/matherial/{matherial_id}', [EventController::class, 'check_matherial'])->name('event.checklist.check_matherial');
Route::patch('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'check_item'])->name('event.checklist.check_item');
Route::delete('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'delete_item'])->name('event.checklist.delete_item');

Route::get('/teste', function() {
    $ingredientService = new CreateMenuEventService();
    $menu = Menu::find(1);
    $event = Event::find(1);
    $ingredientService->handle($event, $menu);
});