<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\EstimateController;
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
Route::get('/menu/{menu_slug}/items',  [MenuController::class, 'show_items'])->name('menu.show_items');
Route::delete('/menu/items/{item_id}/ingredients/{id}',  [IngredientController::class, 'destroy'])->name('ingredient.destroy');
Route::delete('/menu/items/{item_id}/matherials/{id}',  [MatherialController::class, 'destroy'])->name('matherial.destroy');
Route::delete('/menu/{menu_slug}/item/{item_id}',  [MenuController::class, 'remove_item_from_menu'])->name('menu.remove_item_from_menu');
Route::get('/menu/{menu_slug}/item/add',  [MenuController::class, 'add_item_to_menu'])->name('menu.add_item_to_menu');
Route::post('/menu/{menu_slug}/item',  [MenuController::class, 'store_item_to_menu'])->name('menu.store_item_to_menu');

# Event Routes
Route::get('/event', [EventController::class, 'index'])->name('event.index');
Route::get('/event/closed_events', [EventController::class, 'index_closed'])->name('event.index_closed');
Route::post('/event', [EventController::class, 'store'])->name('event.store');
Route::get('/event/{event_id}',  [EventController::class, 'show'])->name('event.show');
Route::get('/event/{event_id}/item/add',  [EventController::class, 'add_item_to_event'])->name('event.add_item_to_event');
Route::post('/event/{event_id}/item',  [EventController::class, 'store_item_to_event'])->name('event.store_item_to_event');
Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist'])->name('event.checklist');
Route::get('/event/{event_id}/shopping_list',  [EventController::class, 'shopping_list'])->name('event.shopping_list');
Route::delete('/event/{event_id}/close_event',  [EventController::class, 'close_event'])->name('event.close_event');

Route::patch('/event/{event_id}/checklist/item/{item_id}/ingredient/{ingredient_id}', [EventController::class, 'check_ingredient'])->name('event.checklist.check_ingredient');
Route::patch('/event/{event_id}/checklist/item/{item_id}/matherial/{matherial_id}', [EventController::class, 'check_matherial'])->name('event.checklist.check_matherial');
Route::patch('/event/{event_id}/checklist/item/{item_id}', [EventController::class, 'check_item'])->name('event.checklist.check_item');
Route::get('/event/{event_id}/equipment_list',  [EventController::class, 'equipment_list'])->name('event.equipment_list');

Route::delete('/event/{event_id}/item/{item_id}', [EventController::class, 'remove_item_from_event'])->name('event.item.remove_item');
Route::delete('/event/{event_id}/item/{item_id}/ingredient/{ingredient_id}', [EventController::class, 'remove_ingredient_from_item_event'])->name('event.item.ingredient.remove_item');
Route::delete('/event/{event_id}/item/{item_id}/matherial/{matherial_id}', [EventController::class, 'remove_matherial_from_item_event'])->name('event.item.matherial.remove_item');


# Rotas do Orçamento
Route::get('/all_estimates', [EstimateController::class, 'index'])->name('all_estimates.index');
Route::get( '/all_estimates/{estimate_id}', [EstimateController::class, 'show'])->name('all_estimates.show');
Route::get( '/all_estimates/{estimate_id}/items', [EstimateController::class, 'items'])->name('all_estimates.items');
Route::get('/all_estimates/{estimate_id}/edit', [EstimateController::class, 'edit'])->name('all_estimates.edit');
Route::get('/all_estimates/{estimate_id}/edit/search_items', [EstimateController::class, 'search_items'])->name('all_estimates.search_items');
Route::post('/all_estimates/{estimate_id}/edit/update', [EstimateController::class, 'update'])->name('all_estimates.update');
Route::post('/all_estimates/store_item_to_menu_event', [EstimateController::class, 'store_item_to_menu_event'])->name('all_estimates.store_item_to_menu_event');
Route::delete('/all_estimates/delete_item_from_menu_event', [EstimateController::class, 'delete_item_from_menu_event'])->name('all_estimates.delete_item_from_menu_event');

Route::get('/multiple_estimates', [EstimateController::class, 'create_multiple_estimates'])->name('multiple_estimates.create');
Route::post('/multiple_estimates', [EstimateController::class, 'store_multiple_estimates'])->name('multiple_estimates.store');
Route::post('/multiple_estimates_menus', [EstimateController::class, 'multiple_estimates_menus'])->name('multiple_estimates_menus.index');

Route::delete( '/all_estimates/{estimate_id}/close_estimate', [EstimateController::class, 'close_estimate'])->name('all_estimates.close_estimate');


Route::post('/estimate/create-session', [EstimateController::class, 'create_session'])->name('estimate.create_session');
Route::patch('/estimate/client', [EstimateController::class, 'add_client_session'])->name('estimate.add_client_session');
Route::get('/estimate/add-item',  [EstimateController::class, 'add_item_session'])->name('estimate.add_item_session');
Route::get('/estimate/menu/{menu_slug}/costs',  [EstimateController::class, 'get_menu_costs'])->name('estimate.get_menu_costs');
Route::patch('/estimate/change-guests',  [EstimateController::class, 'change_guests'])->name('estimate.change_guests');
Route::get('/estimate/costs/{estimate_id}',  [EstimateController::class, 'get_estimate_costs'])->name('estimate.get_estimate_costs');
Route::patch('/estimate/costs',  [EstimateController::class, 'change_cost_data'])->name('estimate.change_cost_data');
Route::post('/estimate/change-menu',  [EstimateController::class, 'change_menu_session'])->name('estimate.change_menu_session');
Route::post('/estimate/add-item', [EstimateController::class, 'store_item_session'])->name('estimate.store_item_session');
Route::delete('/estimate/item/{item_id}', [EstimateController::class, 'remove_item_session'])->name('estimate.remove_item_session');
Route::put('/estimate/item/{item_id}', [EstimateController::class, 'change_item_consumed_per_client'])->name('estimate.change_item_consumed_per_client');
Route::get('/estimate/menu/{menu_slug}',  [EstimateController::class, 'show_menu_items'])->name('estimate.show_menu_items');

Route::post('/estimate/save', [EstimateController::class, 'save_estimate'])->name('estimate.save_estimate');

Route::get('/estimate/user/{user_id}',  [EstimateController::class, 'get_session_by_user'])->name('estimate.get_session_by_user');
Route::get('/event/{event_id}/production_list',  [EventController::class, 'production_list'])->name('event.production_list');

# Clientes
Route::post('/client', [ClientController::class, 'store'])->name('client.store');
Route::post('/address', [ClientController::class, 'store_address'])->name('address.store');

Route::get('/client', [ClientController::class, 'index'])->name('client.index');
Route::get('/client/{client_id}', [ClientController::class, 'show'])->name('client.show');
Route::get('/client/create', [ClientController::class, 'create'])->name('client.create');

