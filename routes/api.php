<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldPrice\Http\Controllers\GoldPriceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('goldprices', GoldPriceController::class)->names('goldprice');
});
