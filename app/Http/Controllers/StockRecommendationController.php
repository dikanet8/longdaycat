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
        $start_date = $now->copy()->subDays($sma_periode);

        // Fetch sales data grouped by product for the last $sma_periode days
        $salesData = DetailTransaksi::join('transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.status', 'selesai')
            ->where('transaksi.tanggal', '>=', $start_date)
            ->select('detail_transaksi.kode_produk')
            ->selectRaw('SUM(detail_transaksi.jumlah) as total_sales')
            ->groupBy('detail_transaksi.kode_produk')
            ->get()
            ->keyBy('kode_produk');

        $products = Produk::all();

        $recommendations = $products->map(function ($product) use ($salesData, $sma_periode) {
            $sales = $salesData->get($product->kode_produk);
            
            $total_sales = $sales ? (int)$sales->total_sales : 0;

            // Single Moving Average (SMA) Forecast
            $sma = (int) ceil($total_sales / max(1, $sma_periode));

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
                'total_sales' => $total_sales,
                'sma' => $sma,
                'status' => $status,
                'rekomendasi_tambah' => $rekomendasi_tambah,
                'warna' => $product->warna,
                'ukuran' => $product->ukuran,
            ];
        });

        return Inertia::render('Stock/Recommendations', [
            'recommendations' => $recommendations->values()->all(),
            'lowStock' => $recommendations->where('status', 'Kritis')->values()->all(),
            'sma_periode' => $sma_periode,
        ]);
    }
}
