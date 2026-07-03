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
            'subtotal' => 'nullable|numeric',
            'diskon' => 'nullable|numeric',
            'metode_bayar' => 'required|string',
            'jumlah_bayar' => 'required|numeric|min:'.$request->total_harga,
        ]);

        return DB::transaction(function () use ($request) {
            $status = 'selesai';

            $totalHarga = $request->total_harga;
            $kodeUnik = 0;
            if ($request->metode_bayar === 'qris') {
                $kodeUnik = rand(1, 999);
                $totalHarga += $kodeUnik;
            }

            // 1. Create Transaction
            $transaksi = Transaksi::create([
                'users_id' => $request->user()->id,
                'kode_transaksi' => 'TRX-' . strtoupper(Str::random(8)),
                'tanggal' => now(),
                'subtotal' => $request->subtotal ?? $request->total_harga,
                'diskon' => $request->diskon ?? 0,
                'total_harga' => $totalHarga,
                'metode_bayar' => $request->metode_bayar,
                'status' => $status
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
            $paymentStatus = 'berhasil';
            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'metode_pembayaran' => $request->metode_bayar,
                'jumlah_bayar' => $request->metode_bayar === 'qris' ? $totalHarga : $request->jumlah_bayar,
                'status' => $paymentStatus,
                'tanggal_pembayaran' => now(),
            ]);

            if ($status === 'selesai') {
                ActivityLog::log('Checkout POS', "Transaksi berhasil dilakukan: {$transaksi->kode_transaksi} dengan total Rp " . number_format($transaksi->total_harga, 0, ',', '.'), $transaksi);

                // Send Notifications to all users
                try {
                    $users = \App\Models\User::all();
                    
                    // Transaction Notification
                    $transaksiTitle = 'Transaksi Berhasil';
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
                            // Calculate SMA for this product
                            $setting = \App\Models\Setting::first();
                            $sma_periode = $setting && $setting->sma_periode ? (int) $setting->sma_periode : 7;
                            $now = now();
                            $start_date = $now->copy()->subDays($sma_periode);

                            $sales = DetailTransaksi::join('transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
                                ->where('transaksi.status', 'selesai')
                                ->where('detail_transaksi.kode_produk', $product->kode_produk)
                                ->where('transaksi.tanggal', '>=', $start_date)
                                ->selectRaw('SUM(detail_transaksi.jumlah) as total_sales')
                                ->first();

                            $total_sales = $sales ? (int)$sales->total_sales : 0;
                            $sma = (int) ceil($total_sales / max(1, $sma_periode));

                            $targetStock = max($product->stok_minimal, $sma);

                            if ($product->stok <= $product->stok_minimal) {
                                $stockTitle = 'Rekomendasi Stok';
                                $rekomendasi_tambah = max(0, $targetStock - $product->stok);
                                $stockMsg = "Stok {$product->nama_produk} sangat rendah (Sisa: {$product->stok}). Disarankan restok segera +{$rekomendasi_tambah} unit.";
                            } elseif ($product->stok < $sma) {
                                $stockTitle = 'Rekomendasi Stok';
                                $rekomendasi_tambah = max(0, $targetStock - $product->stok);
                                $stockMsg = "Stok {$product->nama_produk} ({$product->stok}) di bawah prediksi penjualan ({$sma} unit). Disarankan restok +{$rekomendasi_tambah} unit.";
                            } else {
                                $stockTitle = 'Manajemen Stok Keluar';
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
            }

            $qrisString = '';
            if ($request->metode_bayar === 'qris') {
                $staticQris = env('QRIS_STATIC_PAYLOAD', '');
                if (!empty($staticQris)) {
                    $qrisString = \App\Services\QrisService::generateDynamicQris($staticQris, $totalHarga);
                }
            }

            return redirect()->route('pos.index')->with([
                'success' => 'Transaksi Berhasil!',
                'completed_transaction_id' => $transaksi->id,
                'payment_method' => $request->metode_bayar,
                'qris_string' => $qrisString,
                'kode_unik' => $kodeUnik,
                'total_harga' => $totalHarga
            ]);
        });
    }

    public function confirmPayment($id){
        $transaksi = Transaksi::with('details')->findOrFail($id);

        if ($transaksi->status === 'selesai') {
            return redirect()->route('pos.index')->with([
                'success' => 'Transaksi Berhasil!',
                'completed_transaction_id' => $transaksi->id,
                'payment_method' => $transaksi->metode_bayar
            ]);
        }

        return DB::transaction(function () use ($transaksi) {
            $transaksi->status = 'selesai';
            $transaksi->save();

            $payment = Pembayaran::where('transaksi_id', $transaksi->id)->first();
            if ($payment) {
                $payment->status = 'berhasil';
                $payment->save();
            }

            ActivityLog::log('Konfirmasi Pembayaran', "Transaksi berhasil dikonfirmasi: {$transaksi->kode_transaksi} dengan total Rp " . number_format($transaksi->total_harga, 0, ',', '.'), $transaksi);

            // Send Notifications to all users
            try {
                $users = \App\Models\User::all();
                
                // Transaction Notification
                $transaksiTitle = 'Transaksi Berhasil';
                $transaksiMsg = "Transaksi {$transaksi->kode_transaksi} berhasil dibayar sebesar Rp " . number_format($transaksi->total_harga, 0, ',', '.') . " via {$transaksi->metode_bayar}.";
                
                foreach ($users as $u) {
                    $u->notify(new \App\Notifications\SystemNotification(
                        $transaksiTitle,
                        $transaksiMsg,
                        'transaksi',
                        route('payments.show', $transaksi->id)
                    ));
                }
            } catch (\Exception $e) {
                \Log::error('Gagal mengirim notifikasi konfirmasi pembayaran: ' . $e->getMessage());
            }

            return redirect()->route('pos.index')->with([
                'success' => 'Transaksi Berhasil!',
                'completed_transaction_id' => $transaksi->id,
                'payment_method' => $transaksi->metode_bayar
            ]);
        });
    }
}
