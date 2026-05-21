<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manajemen_stok', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk', 100);
            $table->foreign('kode_produk')->references('kode_produk')->on('produk')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis_aktivitas', ['masuk', 'keluar']);
            $table->integer('jumlah');
            $table->string('keterangan', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manajemen_stok');
    }
};
