<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AirController;

Route::get('/air/{order_id}', [AirController::class, 'show']);
Route::get('/air/latest', [AirController::class, 'latest']);

// optional (see note): mark as consumed — use with caution.
Route::post('/air/consume/{order_id}', [AirController::class, 'consume']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
