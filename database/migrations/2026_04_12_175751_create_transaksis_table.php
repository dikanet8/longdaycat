<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 100);
            $table->dateTime('tanggal');
            $table->foreignId('users_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0)->after('users_id');
            $table->decimal('diskon', 12, 2)->default(0)->after('subtotal');
            $table->decimal('total_harga', 12, 2);
            $table->enum('metode_bayar', ['cash', 'qris'])->default('cash');
            $table->enum('status', ['pending', 'selesai', 'dibatalkan'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
