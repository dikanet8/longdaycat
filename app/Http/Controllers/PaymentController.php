<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\User;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $transactions = Transaksi::with(['details.produk', 'details', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Payments/Index', [
            'transactions' => $transactions,
            'users' => User::all(),
            'filters' => [
                'per_page' => (int)$perPage
            ]
        ]);
    }

    public function show($id)
    {
        $transaction = Transaksi::with(['details.produk', 'details', 'user'])->findOrFail($id);
        $payment = Pembayaran::where('transaksi_id', $id)->first();

        return Inertia::render('Payments/Show', [
            'transaction' => $transaction,
            'payment' => $payment
        ]);
    }
}
