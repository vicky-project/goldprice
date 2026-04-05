<?php
namespace Modules\GoldPrice\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPriceCurrent extends Model
{
  protected $table = 'gold_prices_current';
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