<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/menu',  [MenuController::class, 'index']);
Route::get('/event/{event_id}/checklist',  [EventController::class, 'checklist']);