<?php
namespace Modules\GoldPrice\Console;

use Illuminate\Console\Command;
use Modules\GoldPrice\Services\GoldPriceService;
use Illuminate\Support\Facades\Log;

class UpdateGoldPrices extends Command
{
  protected $signature = 'app:goldprice';
  protected $description = 'Ambil harga emas terbaru dan simpan jika berubah';

  protected $service;

  public function __construct(GoldPriceService $service) {
    parent::__construct();
    $this->service = $service;
  }

  public function handle() {
    $this->info('Memperbarui data harga emas...');
    try {
      $saved = $this->service->updatePricesIfChanged();
      $this->info("Selesai. $saved currency diperbarui.");
      Log::info("GoldPrice update completed. {$saved} updated.");
      return 0;
    } catch (\Exception $e) {
      $this->error('Gagal: '.$e->getMessage());
      Log::error('GoldPrice update failed: '.$e->getMessage(), [
        "trace" => $e->getTraceAsString()
      ]);
      return 1;
    }
  }
}