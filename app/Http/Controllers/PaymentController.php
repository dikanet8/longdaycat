<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Setting;
use App\Models\User;
use App\Models\Produk;
use App\Models\ManajemenStok;
use App\Models\ActivityLog;

class PaymentController extends Controller
{
    public function show($id)
    {
        $transaction = Transaksi::with(['details.produk', 'details', 'user'])->findOrFail($id);
        $payment = Pembayaran::where('transaksi_id', $id)->first();

        return Inertia::render('Payments/Show', [
            'transaction' => $transaction,
            'payment' => $payment,
            'setting' => Setting::first()
        ]);
    }

    public function cancel($id, Request $request)
    {
        $transaction = Transaksi::with('details')->findOrFail($id);

        if ($transaction->status === 'dibatalkan') {
            return redirect()->back()->with('error', 'Transaksi sudah dibatalkan.');
        }

        // Restore stock
        foreach ($transaction->details as $detail) {
            $product = Produk::where('kode_produk', $detail->kode_produk)->first();
            if ($product) {
                $product->increment('stok', $detail->jumlah);
                
                ManajemenStok::create([
                    'kode_produk' => $detail->kode_produk,
                    'user_id' => $request->user()->id,
                    'jumlah' => $detail->jumlah,
                    'jenis_aktivitas' => 'masuk',
                    'keterangan' => 'Pembatalan Transaksi ' . $transaction->kode_transaksi
                ]);
            }
        }

        $transaction->status = 'dibatalkan';
        $transaction->save();

        $payment = Pembayaran::where('transaksi_id', $id)->first();
        if ($payment) {
            $payment->status = 'gagal';
            $payment->tanggal_pembayaran = null;
            $payment->save();
        }

        ActivityLog::log('Pembatalan Transaksi', "Transaksi {$transaction->kode_transaksi} dibatalkan.", $transaction);

        return redirect()->back()->with('success', 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
    }
}
