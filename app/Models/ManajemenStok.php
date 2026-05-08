<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManajemenStok extends Model
{
    use HasFactory;

    protected $table = 'manajemen_stok';

    const UPDATED_AT = null;

    protected $fillable = [
        'kode_produk',
        'user_id',
        'jenis_aktivitas',
        'jumlah',
        'keterangan',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'kode_produk', 'kode_produk');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
