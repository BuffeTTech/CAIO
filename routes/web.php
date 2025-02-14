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
Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist']);
Route::delete('/menu/items/{item_id}/ingredients/{id}',  [IngredientController::class, 'destroy'])->name('ingredient.destroy');
Route::delete('/menu/items/{item_id}/matherial/{id}',  [MatherialController::class, 'destroy'])->name('matherial.destroy');
Route::get('/event/create', [EventController::class, 'create'])->name('event.create');
Route::post('/event/store', [EventController::class, 'store'])->name('event.store');
Route::get('/event', [EventController::class, 'index'])->name('event.index');


Route::get('/teste', function() {
    $ingredientService = new CreateMenuEventService();
    $menu = Menu::find(1);
    $event = Event::find(1);
    $ingredientService->handle($event, $menu);
});