<?php
namespace Modules\GoldPrice\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\GoldPrice\Models\GoldPriceCurrent;
use Modules\GoldPrice\Models\GoldPriceHistory;
use Modules\GoldPrice\Models\GoldPriceArchive;
use Carbon\Carbon;

class GoldPriceService
{
  /**
  * Ambil data harga emas terbaru dari API eksternal.
  * Ganti URL dengan endpoint nyata.
  */
  public function fetchLatestPrices(): array
  {
    $response = Http::timeout(10)->get('https://goldprice.today/api.php?data=live');
    if ($response->failed()) {
      throw new \Exception('Gagal mengambil data harga emas: ' . $response->body());
    }
    return $response->json();
  }

  /**
  * Update harga terbaru (tabel current) dan simpan history jika ada perubahan.
  * Return jumlah mata uang yang berubah.
  */
  public function updatePrices(): int
  {
    $newPrices = $this->fetchLatestPrices();
    $updated = 0;
    $now = now();

    foreach ($newPrices as $currency => $values) {
      $current = GoldPriceCurrent::where('currency', $currency)->first();

      // Cek apakah ada perubahan
      if ($current &&
        $current->ounce == $values['ounce'] &&
        $current->gram == $values['gram'] &&
        $current->tola == $values['tola']) {
        continue; // tidak berubah, skip
      }

      // Simpan ke history
      GoldPriceHistory::create([
        'currency' => $currency,
        'ounce' => $values['ounce'],
        'gram' => $values['gram'],
        'tola' => $values['tola'],
        'price_date' => $now,
      ]);

      // Update atau buat di current
      GoldPriceCurrent::updateOrCreate(
        ['currency' => $currency],
        [
          'ounce' => $values['ounce'],
          'gram' => $values['gram'],
          'tola' => $values['tola'],
          'price_date' => $now,
        ]
      );

      $updated++;
    }

    if ($updated > 0) {
      $this->clearCache();
    }

    Log::info("GoldPrice update: {$updated} currencies changed.");
    return $updated;
  }

  /**
  * Arsipkan data history yang lebih tua dari $months ke tabel archive.
  * Default 120 bulan = 10 tahun.
  */
  public function archiveOldData(int $months = 120): int
  {
    $cutoffDate = Carbon::now()->subMonths($months);
    $oldRecords = GoldPriceHistory::where('price_date', '<', $cutoffDate)->get();

    if ($oldRecords->isEmpty()) {
      return 0;
    }

    // Pindahkan ke archive
    foreach ($oldRecords->chunk(1000) as $chunk) {
      $archiveData = $chunk->map(function ($record) {
        return $record->toArray();
      })->toArray();
      GoldPriceArchive::insert($archiveData);
    }

    // Hapus dari history
    $deleted = GoldPriceHistory::where('price_date', '<', $cutoffDate)->delete();

    Log::info("GoldPrice archive: {$deleted} records moved to archive.");
    return $deleted;
  }

  private function clearCache(): void {
    if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore || Cache::getStore() instanceof \Illuminate\Cache\MemcachedStore) {
      Cache::tags("gold")->flush();
    }

    Cache::forget("gold_currencies");
    Cache::forget("gold_latest_all");
  }
}