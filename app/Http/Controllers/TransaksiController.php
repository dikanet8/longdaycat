<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\User;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        return Inertia::render('Transactions/Index', [
            'transactions' => Transaksi::with(['details.produk', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->withQueryString(),
            'users' => User::all(),
            'filters' => [
                'per_page' => (int)$perPage
            ]
        ]);
    }

    public function show($id)
    {
        $transaction = Transaksi::with(['details.produk'])->findOrFail($id);
        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction
        ]);
    }
}
