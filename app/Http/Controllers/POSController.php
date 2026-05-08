<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Pembayaran;
use App\Models\ManajemenStok;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSController extends Controller
{
    public function index()
    {
        return Inertia::render('POS/Index', [
            'products' => Produk::where('stok', '>', 0)->get()
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'total_harga' => 'required|numeric',
            'metode_bayar' => 'required|string',
            'jumlah_bayar' => 'required|numeric|min:'.$request->total_harga,
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Create Transaction
            $transaksi = Transaksi::create([
                'users_id' => $request->user()->id,
                'kode_transaksi' => 'TRX-' . strtoupper(Str::random(8)),
                'tanggal' => now(),
                'total_harga' => $request->total_harga,
                'metode_bayar' => $request->metode_bayar,
                'status' => 'selesai'
            ]);

            // 2. Create Details & Update Stock
            foreach ($request->items as $item) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'kode_produk' => $item['kode_produk'],
                    'jumlah' => $item['quantity'],
                    'harga_satuan' => $item['harga'],
                    'subtotal' => $item['harga'] * $item['quantity']
                ]);

                $product = Produk::where('kode_produk', $item['kode_produk'])->first();
                $product->decrement('stok', $item['quantity']);

                // Log Stock Movement
                ManajemenStok::create([
                    'kode_produk' => $item['kode_produk'],
                    'user_id' => $request->user()->id,
                    'jumlah' => $item['quantity'],
                    'jenis_aktivitas' => 'keluar',
                    'keterangan' => 'Transaksi ' . $transaksi->kode_transaksi
                ]);
            }

            // 3. Create Payment record
            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'metode_pembayaran' => $request->metode_bayar,
                'jumlah_bayar' => $request->jumlah_bayar,
                'status' => 'berhasil',
                'tanggal_pembayaran' => now(),
            ]);

            ActivityLog::log('Checkout POS', "Transaksi berhasil dilakukan: {$transaksi->kode_transaksi} dengan total Rp " . number_format($transaksi->total_harga, 0, ',', '.'), $transaksi);

            return redirect()->route('payments.show', $transaksi->id)->with('success', 'Transaksi Berhasil!');
        });
    }
}
