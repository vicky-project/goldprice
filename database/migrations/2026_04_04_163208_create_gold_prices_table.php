<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoldPricesTable extends Migration
{
  public function up() {
    Schema::create('gold_prices', function (Blueprint $table) {
      $table->id();
      $table->string('currency', 3)->index(); // e.g. USD, IDR, JPY
      $table->decimal('ounce', 20, 3); // harga per ounce
      $table->decimal('gram', 20, 3); // harga per gram
      $table->decimal('tola', 20, 3); // harga per tola
      $table->timestamp('price_date')->index(); // waktu update dari sumber
      $table->timestamps();
    });
  }

  public function down() {
    Schema::dropIfExists('gold_prices');
  }
}