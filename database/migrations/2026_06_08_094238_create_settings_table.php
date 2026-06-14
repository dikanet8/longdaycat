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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_toko')->default('Sistem Kasir Pintar');
            $table->text('alamat_toko')->nullable();
            $table->string('telepon_toko')->nullable();
            $table->text('deskripsi_struk')->nullable();
            $table->string('logo_toko')->nullable();
            $table->integer('sma_periode')->default(7)->after('logo_toko');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
