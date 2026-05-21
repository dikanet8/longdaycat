<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekomendasi_stok', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk', 100);
            $table->foreign('kode_produk')->references('kode_produk')->on('produk')->cascadeOnDelete();
            $table->integer('stok_sekarang');
            $table->integer('rekomendasi_tambah');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_stok');
    }
};
