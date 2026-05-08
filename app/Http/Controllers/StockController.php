<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\ManajemenStok;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $products = Produk::orderBy('nama_produk')->paginate($perPage, ['*'], 'products_page')->withQueryString();
        $history = ManajemenStok::with(['produk', 'user'])->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'history_page')->withQueryString();

        return Inertia::render('Stock/Index', [
            'products' => $products,
            'history' => $history,
            'users' => User::all(),
            'filters' => [
                'per_page' => (int)$perPage
            ]
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'kode_produk' => 'required|exists:produk,kode_produk',
            'jumlah' => 'required|integer|min:1',
            'jenis_aktivitas' => 'required|in:masuk,keluar',
            'keterangan' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $product = Produk::where('kode_produk', $request->kode_produk)->first();
            
            if ($request->jenis_aktivitas === 'masuk') {
                $product->increment('stok', $request->jumlah);
            } else {
                $product->decrement('stok', $request->jumlah);
            }

            ManajemenStok::create([
                'kode_produk' => $request->kode_produk,
                'user_id' => $request->user()->id,
                'jumlah' => $request->jumlah,
                'jenis_aktivitas' => $request->jenis_aktivitas,
                'keterangan' => $request->keterangan
            ]);
            ActivityLog::log(
                'Update Stok', 
                "Menyesuaikan stok produk: {$product->nama_produk}. Jenis: " . ucfirst($request->jenis_aktivitas) . " sejumlah {$request->jumlah}",
                $product
            );
        });

        return redirect()->back()->with('success', 'Stok berhasil diperbarui.');
    }
}
