<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/projects', [ProjectController::class, 'index']);
Route::post('/projects', [ProjectController::class, 'store']);
Route::post('/projects/{project}/items', [ItemController::class, 'store']);
Route::post('/projects/{project}/items/{item}/move', [ItemController::class, 'move']);
Route::post('/projects/{project}/items/{item}/complete', [ItemController::class, 'complete']);
