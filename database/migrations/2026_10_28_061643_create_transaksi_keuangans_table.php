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
        Schema::create('transaksi_keuangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('panen_id');
            $table->string('jenis', 30);
            $table->date('tanggal');
            $table->decimal('jumlah', 14, 2);
            $table->decimal('persentase_kas', 5, 2);
            $table->decimal('kas_operasional', 14, 2);
            $table->decimal('laba_bersih', 14, 2);
            $table->string('keterangan', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_keuangans');
    }
};
