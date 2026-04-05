<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up() {
    Schema::create('gold_prices_archive', function (Blueprint $table) {
      $table->id();
      $table->string('currency', 10);
      $table->decimal('ounce', 20, 3);
      $table->decimal('gram', 20, 3);
      $table->decimal('tola', 20, 3);
      $table->timestamp('price_date');
      $table->timestamps();

      $table->index(['currency', 'price_date']);
    });
  }

  public function down() {
    Schema::dropIfExists('gold_prices_archive');
  }
};