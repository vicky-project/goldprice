<?php
namespace Modules\GoldPrice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\GoldPrice\Models\GoldPriceCurrent;
use Modules\GoldPrice\Models\GoldPriceHistory;
use Modules\GoldPrice\Models\GoldPriceArchive;

class GoldPriceController extends Controller
{
  /**
  * Daftar semua mata uang yang tersedia (dari current)
  */
  public function currencies() {
    $currencies = GoldPriceCurrent::select('currency')
    ->distinct()
    ->orderBy('currency')
    ->pluck('currency');
    return response()->json($currencies);
  }

  /**
  * Harga terbaru untuk semua atau satu mata uang (dari current)
  */
  public function latest(Request $request) {
    $currency = $request->input('currency');
    $query = GoldPriceCurrent::query();
    if ($currency) {
      $query->where('currency', $currency);
    }
    $data = $query->orderBy('currency')->get();
    return response()->json($data);
  }

  /**
  * History untuk chart (dari history, default 30 hari terakhir)
  */
  public function history(Request $request) {
    $request->validate([
      'currency' => 'required|string|max:10',
      'days' => 'nullable|integer|min:1|max:3650',
    ]);

    $currency = $request->input('currency');
    $days = $request->input('days', 30);

    $data = GoldPriceHistory::where('currency', $currency)
    ->where('price_date', '>=', now()->subDays($days))
    ->orderBy('price_date', 'asc')
    ->get(['price_date', 'ounce', 'gram', 'tola']);

    return response()->json($data);
  }

  /**
  * Data archive (opsional, dengan pagination)
  */
  public function archive(Request $request) {
    $request->validate([
      'currency' => 'nullable|string|max:10',
      'start_date' => 'nullable|date',
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'per_page' => 'nullable|integer|min:1|max:100',
    ]);

    $query = GoldPriceArchive::query();
    if ($request->filled('currency')) {
      $query->where('currency', $request->currency);
    }
    if ($request->filled('start_date')) {
      $query->where('price_date', '>=', $request->start_date);
    }
    if ($request->filled('end_date')) {
      $query->where('price_date', '<=', $request->end_date);
    }

    $perPage = $request->input('per_page', 50);
    $data = $query->orderBy('price_date', 'desc')->paginate($perPage);
    return response()->json($data);
  }
}