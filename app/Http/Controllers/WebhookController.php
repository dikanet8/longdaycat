<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\Pembayaran;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle mutation webhook from payment gateways or mutation services
     * like Moota, CekMutasi, etc.
     */
    public function mutasi(Request $request)
    {
        // Example structure from a typical mutation service webhook
        // It usually sends an array of mutations
        $payload = $request->all();
        Log::info('Webhook Mutasi Received', ['payload' => $payload]);

        // A very basic handling logic assuming the payload sends 'amount' or an array of transactions.
        // We will loop through pending QRIS transactions and match the total_harga.
        
        $mutations = isset($payload['mutations']) ? $payload['mutations'] : [$payload];

        foreach ($mutations as $mutasi) {
            $amount = $mutasi['amount'] ?? null;
            $type = $mutasi['type'] ?? 'CR'; // CR = Credit (uang masuk)
            
            // Only process incoming money
            if ($amount && strtoupper($type) === 'CR') {
                $this->processPaymentByAmount((float)$amount);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook processed']);
    }

    private function processPaymentByAmount($amount)
    {
        // Find the oldest pending qris transaction matching this exact amount
        $transaksi = Transaksi::where('metode_bayar', 'qris')
            ->where('status', 'pending')
            ->where('total_harga', $amount)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($transaksi) {
            DB::transaction(function () use ($transaksi) {
                $transaksi->status = 'selesai';
                $transaksi->save();

                $payment = Pembayaran::where('transaksi_id', $transaksi->id)->first();
                if ($payment) {
                    $payment->status = 'berhasil';
                    $payment->save();
                }

                ActivityLog::log('Webhook Pembayaran', "Transaksi berhasil dikonfirmasi otomatis (Webhook): {$transaksi->kode_transaksi} dengan total Rp " . number_format($transaksi->total_harga, 0, ',', '.'), $transaksi);

                // Send Notifications
                try {
                    $users = \App\Models\User::all();
                    $transaksiTitle = 'Pembayaran QRIS Berhasil';
                    $transaksiMsg = "Transaksi {$transaksi->kode_transaksi} berhasil dibayar sebesar Rp " . number_format($transaksi->total_harga, 0, ',', '.') . " secara otomatis.";
                    
                    foreach ($users as $u) {
                        $u->notify(new \App\Notifications\SystemNotification(
                            $transaksiTitle,
                            $transaksiMsg,
                            'transaksi',
                            route('payments.show', $transaksi->id)
                        ));
                    }
                } catch (\Exception $e) {
                    Log::error('Gagal mengirim notifikasi webhook: ' . $e->getMessage());
                }
            });
        }
    }
}
