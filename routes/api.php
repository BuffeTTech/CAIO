<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\MatherialController;
use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

# Menu Routes
Route::get('/menu',  [MenuController::class, 'index']);
Route::get('/menu/{menu_slug}',  [MenuController::class, 'show'])->name('menu.show');
Route::delete('/menu/items/{item_id}/ingredients/{id}',  [IngredientController::class, 'destroy'])->name('ingredient.destroy');
Route::delete('/menu/items/{item_id}/matherials/{id}',  [MatherialController::class, 'destroy'])->name('matherial.destroy');
Route::delete('/menu/{menu_slug}/item/{item_id}',  [MenuController::class, 'remove_item_from_menu'])->name('menu.remove_item_from_menu');
Route::get('/menu/{menu_slug}/item/add',  [MenuController::class, 'add_item_to_menu'])->name('menu.add_item_to_menu');
Route::post('/menu/{menu_slug}/item',  [MenuController::class, 'store_item_to_menu'])->name('menu.store_item_to_menu');

# Event Routes
Route::get('/event', [EventController::class, 'index'])->name('event.index');
Route::post('/event', [EventController::class, 'store'])->name('event.store');
Route::get('/event/{event_id}',  [EventController::class, 'show'])->name('event.show');
Route::get('/event/{event_id}/item/add',  [EventController::class, 'add_item_to_event'])->name('event.add_item_to_event');
Route::post('/event/{event_id}/item',  [EventController::class, 'store_item_to_event'])->name('event.store_item_to_event');
Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist'])->name('event.checklist');
