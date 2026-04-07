<?php
namespace Modules\GoldPrice\Models;

use Illuminate\Database\Eloquent\Model;

class GoldPriceArchive extends Model
{
  protected $table = 'gold_prices_archive';
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