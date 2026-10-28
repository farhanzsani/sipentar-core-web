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
        Schema::create('budidayas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('meja_id');
            $table->date('tanggal_semai');
            $table->integer('jumlah_tanaman');
            $table->string('status', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budidayas');
    }
};
