<?php
namespace Modules\GoldPrice\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Modules\GoldPrice\Entities\GoldPrice;

class GoldPriceService
{
  /**
  * Ambil data harga emas terbaru dari API eksternal.
  * Return array dengan key
  */
  public function fetchLatestPrices(): array
  {
    // Mengambil data
    $response = Http::timeout(10)->get('https://goldprice.today/api.php?data=live');
    if ($response->failed()) {
      throw new \Exception('Gagal mengambil data harga emas: '.$response->body());
    }
    return $response->json();
  }

  /**
  * Simpan harga jika berbeda dengan record terakhir untuk currency yang sama.
  * Return jumlah yang tersimpan.
  */
  public function updatePricesIfChanged(): int
  {
    $newPrices = $this->fetchLatestPrices();
    $saved = 0;
    $now = now();

    foreach ($newPrices as $currency => $values) {
      $last = GoldPrice::where('currency', $currency)
      ->orderBy('price_date', 'desc')
      ->first();

      // Jika tidak ada record sebelumnya, atau ada perubahan nilai
      if (!$last ||
        $last->ounce != $values['ounce'] ||
        $last->gram != $values['gram'] ||
        $last->tola != $values['tola']) {

        GoldPrice::create([
          'currency' => $currency,
          'ounce' => $values['ounce'],
          'gram' => $values['gram'],
          'tola' => $values['tola'],
          'price_date' => $now,
        ]);
        $saved++;
      }
    }
    return $saved;
  }
}