<?php
namespace Modules\GoldPrice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\GoldPrice\Models\GoldPrice;
use Illuminate\Support\Facades\DB;

class GoldPriceController extends Controller
{
  /**
  * Daftar semua mata uang yang tersedia
  */
  public function currencies() {
    $currencies = GoldPrice::select('currency')
    ->distinct()
    ->orderBy('currency')
    ->pluck('currency');
    return response()->json($currencies);
  }

  /**
  * Data harga terbaru untuk semua mata uang (atau satu jika diminta)
  */
  public function latest(Request $request) {
    $currency = $request->input('currency');
    $query = GoldPrice::whereRaw('(currency, price_date) in (select currency, max(price_date) from gold_prices group by currency)');
    if ($currency) {
      $query->where('currency', $currency);
    }
    $data = $query->orderBy('currency')->get();
    return response()->json($data);
  }

  /**
  * History harga untuk chart berdasarkan mata uang dan rentang waktu
  */
  public function history(Request $request) {
    $request->validate([
      'currency' => 'required|string|size:3',
      'days' => 'nullable|integer|min:1|max:365',
    ]);

    $days = $request->input('days', 30);
    $currency = $request->input('currency');

    $data = GoldPrice::where('currency', $currency)
    ->where('price_date', '>=', now()->subDays($days))
    ->orderBy('price_date', 'asc')
    ->get(['price_date', 'ounce', 'gram', 'tola']);

    return response()->json($data);
  }
}