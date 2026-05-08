<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    const UPDATED_AT = null;

    protected $fillable = [
        'transaksi_id',
        'metode_pembayaran',
        'jumlah_bayar',
        'status',
        'tanggal_pembayaran',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }
}
