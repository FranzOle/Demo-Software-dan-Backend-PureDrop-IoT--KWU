<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AirController;

Route::get('/air/latest', [AirController::class, 'latest']);
Route::get('/air/{order_id}', [AirController::class, 'show']);
Route::post('/air/consume/{order_id}', [AirController::class, 'consume']);
Route::get('/esp/payment', [AirController::class, 'espLatest']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
