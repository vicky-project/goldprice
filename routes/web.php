<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldPrice\Http\Controllers\GoldPriceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('goldprices', GoldPriceController::class)->names('goldprice');
});
