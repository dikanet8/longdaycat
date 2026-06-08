<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function sales(Request $request)
    {
        $day = $request->has('day') ? $request->input('day') : date('j');
        $date = $request->input('date');
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        if ($day && !$date) {
            $date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        }

        if ($request->input('export') === 'csv') {
            $transactions = Transaksi::with(['user', 'details.produk'])
                ->where('status', 'selesai')
                ->when($date, function($q) use ($date) {
                    return $q->whereDate('tanggal', $date);
                })
                ->when(!$date, function($q) use ($month, $year) {
                    return $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $filename = 'Laporan_Penjualan_' . ($date ?: $month . '-' . $year) . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($transactions) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM signature for Microsoft Excel compatibility
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, [
                    'Tanggal & Waktu',
                    'Kode Transaksi',
                    'Nama Produk',
                    'Ukuran',
                    'Warna',
                    'Jumlah Qty',
                    'Harga Satuan',
                    'Subtotal Item',
                    'Total Transaksi',
                    'Metode Pembayaran',
                    'Kasir'
                ]);

                foreach ($transactions as $trx) {
                    $time = Carbon::parse($trx->created_at)->format('Y-m-d H:i:s');
                    $code = $trx->kode_transaksi;
                    $total = $trx->total_harga;
                    $method = $trx->metode_bayar;
                    $cashier = $trx->user ? $trx->user->name : 'Sistem';

                    if ($trx->details->isNotEmpty()) {
                        foreach ($trx->details as $d) {
                            fputcsv($file, [
                                $time,
                                $code,
                                $d->produk ? $d->produk->nama_produk : $d->kode_produk,
                                $d->produk ? $d->produk->ukuran : '-',
                                $d->produk ? $d->produk->warna : '-',
                                $d->jumlah,
                                $d->harga_satuan,
                                $d->subtotal,
                                $total,
                                $method,
                                $cashier
                            ]);
                        }
                    } else {
                        fputcsv($file, [
                            $time,
                            $code,
                            '-',
                            '-',
                            '-',
                            0,
                            0,
                            0,
                            $total,
                            $method,
                            $cashier
                        ]);
                    }
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $query = Transaksi::where('status', 'selesai');

        if ($date) {
            $query->whereDate('tanggal', $date);
        } else {
            $query->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
        }

        $totalPendapatan = $query->sum('total_harga');
        $totalTransaksi = $query->count();

        // Produk Terlaris
        $terlaris = DetailTransaksi::select('kode_produk', DB::raw('SUM(jumlah) as total_qty'))
            ->whereHas('transaksi', function($q) use ($date, $month, $year) {
                $q->where('status', 'selesai');
                if ($date) {
                    $q->whereDate('tanggal', $date);
                } else {
                    $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
                }
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
        $totalProdukTerjual = DetailTransaksi::whereHas('transaksi', function($q) use ($date, $month, $year) {
                $q->where('status', 'selesai');
                if ($date) {
                    $q->whereDate('tanggal', $date);
                } else {
                    $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
                }
            })->sum('jumlah');

        // Chart Data (Last 30 days for detailed report)
        $chartData = [];
        $chartLabels = [];
        $days = $date ? 7 : 30;
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chartLabels[] = $d->format('d M');
            $chartData[] = Transaksi::where('status', 'selesai')
                ->whereDate('tanggal', $d)
                ->sum('total_harga');
        }

        return Inertia::render('Reports/Sales', [
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
            'recent_transactions' => Transaksi::with(['user', 'details.produk'])
                ->where('status', 'selesai')
                ->when($date, function($q) use ($date) {
                    return $q->whereDate('tanggal', $date);
                })
                ->when(!$date, function($q) use ($month, $year) {
                    return $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 10))
                ->withQueryString(),
            'filters' => [
                'day' => $day ? (int)$day : null,
                'date' => $date,
                'month' => (int)$month,
                'year' => (int)$year,
                'per_page' => (int)$request->input('per_page', 10)
            ]
        ]);
    }
}
