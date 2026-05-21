<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $day = $request->input('day', date('j'));
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        $query = Transaksi::where('status', 'selesai');

        if ($day) {
            $query->whereDay('tanggal', $day);
        }
        $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);

        $totalPendapatan = $query->sum('total_harga');
        $totalTransaksi = $query->count();

        // Produk Terlaris
        $terlaris = DetailTransaksi::select('kode_produk', DB::raw('SUM(jumlah) as total_qty'))
            ->whereHas('transaksi', function($q) use ($day, $month, $year) {
                $q->where('status', 'selesai');
                if ($day) {
                    $q->whereDay('tanggal', $day);
                }
                $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            })
            ->groupBy('kode_produk')
            ->orderBy('total_qty', 'desc')
            ->first();
        
        $produkTerlaris = '-';
        if ($terlaris) {
            $produk = Produk::where('kode_produk', $terlaris->kode_produk)->first();
            $produkTerlaris = $produk ? $produk->nama_produk : $terlaris->kode_produk;
        }

        // Total Produk Terjual
        $totalProdukTerjual = DetailTransaksi::whereHas('transaksi', function($q) use ($day, $month, $year) {
                $q->where('status', 'selesai');
                if ($day) {
                    $q->whereDay('tanggal', $day);
                }
                $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            })->sum('jumlah');

        // Chart Data (Last 7 days)
        $chartData = [];
        $chartLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chartLabels[] = $d->format('D');
            $chartData[] = Transaksi::where('status', 'selesai')
                ->whereDate('tanggal', $d)
                ->sum('total_harga');
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_pendapatan' => $totalPendapatan,
                'total_transaksi' => $totalTransaksi,
                'produk_terlaris' => $produkTerlaris,
                'total_produk_terjual' => (int)$totalProdukTerjual,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ],
            'recent_transactions' => Transaksi::where('status', 'selesai')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'filters' => [
                'day' => $day,
                'month' => (int)$month,
                'year' => (int)$year,
            ]
        ]);
    }
}
