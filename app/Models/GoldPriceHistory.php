<?php
namespace Modules\GoldPrice\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPriceHistory extends Model
{
  protected $table = 'gold_prices_history';
  protected $fillable = [
    'currency',
    'ounce',
    'gram',
    'tola',
    'price_date'
  ];

  protected $casts = [
    'price_date' => 'datetime',
    'ounce' => 'decimal:3',
    'gram' => 'decimal:3',
    'tola' => 'decimal:3',
  ];
}