<?php

use App\Http\Controllers\BinController;
use App\Http\Controllers\WasteObjectController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\ArmActionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::match(['get', 'post'], '/bin-reading-read', [BinController::class, 'binReadingRead']);

Route::get('/bin-status', [BinController::class, 'binStatus']);

Route::match(['get', 'post'], '/arm-actions', [ArmActionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/waste-objects', [WasteObjectController::class, 'store']);
    Route::get('/waste-objects', [WasteObjectController::class, 'index']);
});

Route::post('/waste-objects/webhook', [WebhookController::class, 'receive']);
