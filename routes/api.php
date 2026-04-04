<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldPrice\Http\Controllers\GoldPriceController;

Route::prefix('gold-price')
->name('gold-price.')
->group(function () {
  Route::get('currencies', [GoldPriceController::class, "currencies"])->name('currencies');
  Route::get('latest', [GoldPriceController::class, "latest"])->name('latest');
  Route::get('history', [GoldPriceController::class, "history"])->name('history');
});