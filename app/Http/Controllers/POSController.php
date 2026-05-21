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
            'products' => \App\Models\Produk::all(),
            'categories' => \App\Models\Kategori::all()
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

            // Send Notifications to all users
            try {
                $users = \App\Models\User::all();
                
                // Transaction Notification
                $transaksiTitle = 'Transaksi Baru';
                $transaksiMsg = "Transaksi {$transaksi->kode_transaksi} berhasil sebesar Rp " . number_format($transaksi->total_harga, 0, ',', '.') . " via {$transaksi->metode_bayar}.";
                
                foreach ($users as $u) {
                    $u->notify(new \App\Notifications\SystemNotification(
                        $transaksiTitle,
                        $transaksiMsg,
                        'transaksi',
                        route('payments.show', $transaksi->id)
                    ));
                }

                // Stock Notifications
                foreach ($request->items as $item) {
                    $product = Produk::where('kode_produk', $item['kode_produk'])->first();
                    if ($product) {
                        // Calculate SMA for this product (last 3 weeks)
                        $now = now();
                        $w1_start = $now->copy()->subDays(7);
                        $w2_start = $now->copy()->subDays(14);
                        $w3_start = $now->copy()->subDays(21);

                        $sales = DetailTransaksi::join('transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
                            ->where('transaksi.status', 'selesai')
                            ->where('detail_transaksi.kode_produk', $product->kode_produk)
                            ->where('transaksi.tanggal', '>=', $w3_start)
                            ->selectRaw('SUM(CASE WHEN transaksi.tanggal >= ? THEN detail_transaksi.jumlah ELSE 0 END) as sales_w1', [$w1_start])
                            ->selectRaw('SUM(CASE WHEN transaksi.tanggal >= ? AND transaksi.tanggal < ? THEN detail_transaksi.jumlah ELSE 0 END) as sales_w2', [$w2_start, $w1_start])
                            ->selectRaw('SUM(CASE WHEN transaksi.tanggal >= ? AND transaksi.tanggal < ? THEN detail_transaksi.jumlah ELSE 0 END) as sales_w3', [$w3_start, $w2_start])
                            ->first();

                        $w1 = $sales ? (int)$sales->sales_w1 : 0;
                        $w2 = $sales ? (int)$sales->sales_w2 : 0;
                        $w3 = $sales ? (int)$sales->sales_w3 : 0;
                        $sma = (int) ceil(($w1 + $w2 + $w3) / 3);

                        $targetStock = max($product->stok_minimal, $sma);

                        if ($product->stok <= $product->stok_minimal) {
                            $stockTitle = 'Stok Kritis';
                            $rekomendasi_tambah = max(0, $targetStock - $product->stok);
                            $stockMsg = "Stok {$product->nama_produk} sangat rendah (Sisa: {$product->stok}). Disarankan restok segera +{$rekomendasi_tambah} unit.";
                        } elseif ($product->stok < $sma) {
                            $stockTitle = 'Rekomendasi Restok (SMA)';
                            $rekomendasi_tambah = max(0, $targetStock - $product->stok);
                            $stockMsg = "Stok {$product->nama_produk} ({$product->stok}) di bawah prediksi penjualan ({$sma} unit). Disarankan restok +{$rekomendasi_tambah} unit.";
                        } else {
                            $stockTitle = 'Stok Berkurang';
                            $stockMsg = "Stok {$product->nama_produk} berkurang -{$item['quantity']} (Sisa: {$product->stok}) oleh transaksi {$transaksi->kode_transaksi}.";
                        }
                        
                        foreach ($users as $u) {
                            $url = $u->role === 'owner' 
                                ? (($product->stok < $targetStock) ? route('reports.stock-recommendations') : route('products.index'))
                                : route('pos.index');

                            $u->notify(new \App\Notifications\SystemNotification(
                                $stockTitle,
                                $stockMsg,
                                'stok',
                                $url
                            ));
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Gagal mengirim notifikasi transaksi/stok: ' . $e->getMessage());
            }

            return redirect()->route('payments.show', $transaksi->id)->with('success', 'Transaksi Berhasil!');
        });
    }
}
