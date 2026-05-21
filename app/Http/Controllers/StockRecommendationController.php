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
        // 1. Produk Stok Rendah (Kritis)
        $lowStockProducts = Produk::whereColumn('stok', '<=', 'stok_minimal')
            ->orderBy('stok', 'asc')
            ->get();

        // 2. Produk Terlaris (Fast Moving) - dalam 30 hari terakhir
        $fastMovingIds = DetailTransaksi::select('kode_produk', DB::raw('SUM(jumlah) as total_sold'))
            ->whereHas('transaksi', function($q) {
                $q->where('status', 'selesai')
                  ->where('created_at', '>=', now()->subDays(30));
            })
            ->groupBy('kode_produk')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->pluck('kode_produk');

        $fastMovingProducts = Produk::whereIn('kode_produk', $fastMovingIds)->get();

        return Inertia::render('Stock/Recommendations', [
            'lowStock' => $lowStockProducts,
            'fastMoving' => $fastMovingProducts,
        ]);
    }
}
