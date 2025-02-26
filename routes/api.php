<?php

use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/menu',  [MenuController::class, 'index']);
Route::get('/menu/{menu_slug}',  [MenuController::class, 'show'])->name('menu.show');
