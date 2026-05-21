<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->string('metode_pembayaran', 100)->nullable();
            $table->decimal('jumlah_bayar', 12, 2);
            $table->enum('status', ['menunggu', 'berhasil', 'gagal'])->default('menunggu');
            $table->dateTime('tanggal_pembayaran')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
