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
        Schema::table('panens', function (Blueprint $table) {
            $table->decimal('berat_kg', 10, 2)->nullable()->change();
        });

        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->unsignedBigInteger('panen_id')->nullable()->change();
            $table->decimal('persentase_kas', 5, 2)->nullable()->change();
            $table->decimal('kas_operasional', 15, 2)->nullable()->change();
            $table->decimal('laba_bersih', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panens', function (Blueprint $table) {
            $table->decimal('berat_kg', 10, 2)->nullable(false)->change();
        });

        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->unsignedBigInteger('panen_id')->nullable(false)->change();
            $table->decimal('persentase_kas', 5, 2)->nullable(false)->change();
            $table->decimal('kas_operasional', 15, 2)->nullable(false)->change();
            $table->decimal('laba_bersih', 15, 2)->nullable(false)->change();
        });
    }
};
