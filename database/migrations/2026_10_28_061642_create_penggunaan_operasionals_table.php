<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penggunaan_operasionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('stok_id');
            $table->date('tanggal');
            $table->decimal('jumlah', 10, 2);
            $table->string('keterangan', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggunaan_operasionals');
    }
};
