<?php
namespace Modules\GoldPrice\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\GoldPrice\Services\GoldPriceService;

class ArchiveGoldPrices extends Command
{
  protected $signature = 'app:goldprice-archive {--months=120 : Arsipkan data lebih dari N bulan}';
  protected $description = 'Pindahkan data history lama ke tabel archive';

  protected $service;

  public function __construct(GoldPriceService $service) {
    parent::__construct();
    $this->service = $service;
  }

  public function handle() {
    $months = (int) $this->option('months');
    $this->info("Mengarsipkan data lebih dari {$months} bulan...");
    try {
      $archived = $this->service->archiveOldData($months);
      $this->info("Selesai. {$archived} record dipindahkan ke archive.");
    } catch (\Exception $e) {
      $this->error('Gagal: ' . $e->getMessage());
      Log::error('GoldPrice archive failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString()
      ]);
      return 1;
    }
    return 0;
  }
}