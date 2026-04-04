<?php

use Illuminate\Support\Facades\Route;
use Modules\GoldPrice\Http\Controllers\GoldPriceController;

Route::prefix('apps')
->middleware(['telegram.miniapp'])
->name("apps.")
->group(function () {
  Route::view('gold-prices', 'goldprice::index')->name("gold-prices");
});