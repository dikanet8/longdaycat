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
        $type = $request->input('type', 'harian'); // harian, bulanan, tahunan
        $day = $request->input('day', date('j'));
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));

        $date = null;
        if ($type === 'harian' && $day) {
            $date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
        }

        $applyDateFilter = function($q) use ($type, $date, $month, $year) {
            if ($type === 'harian' && $date) {
                $q->whereDate('tanggal', $date);
            } elseif ($type === 'bulanan') {
                $q->whereMonth('tanggal', $month)->whereYear('tanggal', $year);
            } elseif ($type === 'tahunan') {
                $q->whereYear('tanggal', $year);
            }
            return $q;
        };

        if ($request->input('export') === 'csv') {
            $transactions = Transaksi::with(['user', 'details.produk'])
                ->where('status', 'selesai')
                ->where(function($q) use ($applyDateFilter) {
                    $applyDateFilter($q);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $periodLabel = $type === 'tahunan' ? $year : ($type === 'bulanan' ? $month . '-' . $year : $date);
            $filename = 'Laporan_Penjualan_' . $periodLabel . '.csv';
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

        $transactions = Transaksi::with(['user', 'details.produk'])
            ->orderBy('created_at', 'desc')
            ->get();

        $setting = \App\Models\Setting::first();

        return Inertia::render('Reports/Sales', [
            'all_transactions' => $transactions,
            'filters' => [
                'type' => 'harian',
                'day' => date('j'),
                'month' => date('n'),
                'year' => date('Y'),
                'per_page' => 10
            ],
            'setting' => $setting
        ]);
    }
}
