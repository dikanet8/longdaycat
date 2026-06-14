<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StockRecommendationController extends Controller
{
    public function index()
    {
        $setting = \App\Models\Setting::first();
        $sma_periode = $setting && $setting->sma_periode ? (int) $setting->sma_periode : 7;

        $now = now();
        $w1_start = $now->copy()->subDays($sma_periode);
        $w2_start = $now->copy()->subDays($sma_periode * 2);
        $w3_start = $now->copy()->subDays($sma_periode * 3);

        // Fetch sales data grouped by product for the last 3 weeks (Week 1, Week 2, Week 3)
        $salesData = DetailTransaksi::join('transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.status', 'selesai')
            ->where('transaksi.tanggal', '>=', $w3_start)
            ->select('detail_transaksi.kode_produk')
            ->selectRaw('SUM(CASE WHEN transaksi.tanggal >= ? THEN detail_transaksi.jumlah ELSE 0 END) as sales_w1', [$w1_start])
            ->selectRaw('SUM(CASE WHEN transaksi.tanggal >= ? AND transaksi.tanggal < ? THEN detail_transaksi.jumlah ELSE 0 END) as sales_w2', [$w2_start, $w1_start])
            ->selectRaw('SUM(CASE WHEN transaksi.tanggal >= ? AND transaksi.tanggal < ? THEN detail_transaksi.jumlah ELSE 0 END) as sales_w3', [$w3_start, $w2_start])
            ->groupBy('detail_transaksi.kode_produk')
            ->get()
            ->keyBy('kode_produk');

        $products = Produk::all();

        $recommendations = $products->map(function ($product) use ($salesData) {
            $sales = $salesData->get($product->kode_produk);
            
            $w1 = $sales ? (int)$sales->sales_w1 : 0;
            $w2 = $sales ? (int)$sales->sales_w2 : 0;
            $w3 = $sales ? (int)$sales->sales_w3 : 0;

            // Single Moving Average (SMA) Forecast
            $sma = (int) ceil(($w1 + $w2 + $w3) / 3);

            // Determine status based on current stock, minimum stock, and SMA forecast
            if ($product->stok <= $product->stok_minimal) {
                $status = 'Kritis';
            } elseif ($product->stok < $sma) {
                $status = 'Perlu Restok';
            } else {
                $status = 'Aman';
            }

            // Recommended restock quantity
            $targetStock = max($product->stok_minimal, $sma);
            $rekomendasi_tambah = max(0, $targetStock - $product->stok);

            return [
                'id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'kode_produk' => $product->kode_produk,
                'gambar' => $product->gambar,
                'stok' => $product->stok,
                'stok_minimal' => $product->stok_minimal,
                'harga' => $product->harga,
                'sales_w3' => $w3,
                'sales_w2' => $w2,
                'sales_w1' => $w1,
                'sma' => $sma,
                'status' => $status,
                'rekomendasi_tambah' => $rekomendasi_tambah,
            ];
        });

        return Inertia::render('Stock/Recommendations', [
            'recommendations' => $recommendations->values()->all(),
            'lowStock' => $recommendations->where('status', 'Kritis')->values()->all(),
            'sma_periode' => $sma_periode,
        ]);
    }
}
