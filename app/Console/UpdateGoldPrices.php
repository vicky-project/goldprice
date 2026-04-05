<?php
namespace Modules\GoldPrice\Console;

use Illuminate\Console\Command;
use Modules\GoldPrice\Services\GoldPriceService;

class UpdateGoldPrices extends Command
{
  protected $signature = 'app:goldprice';
  protected $description = 'Ambil harga emas terbaru dan simpan ke current + history';

  protected $service;

  public function __construct(GoldPriceService $service) {
    parent::__construct();
    $this->service = $service;
  }

  public function handle() {
    $this->info('Memperbarui data harga emas...');
    try {
      $updated = $this->service->updatePrices();
      $this->info("Selesai. {$updated} mata uang diperbarui.");
    } catch (\Exception $e) {
      $this->error('Gagal: ' . $e->getMessage());
      Log::error('GoldPrice update failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
      ]);
      return 1;
    }
    return 0;
  }
}