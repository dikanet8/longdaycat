<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Simpan atau update subscription push notification.
     */
    public function store(Request $request)
    {
        \Log::info('Menerima request subscription: ', $request->all());
        $request->validate([
            'endpoint'  => 'required|string',
            'p256dh'    => 'required|string',
            'auth'      => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id'  => auth()->id(),
                'endpoint' => $request->endpoint,
            ],
            [
                'p256dh_key' => $request->p256dh,
                'auth_token' => $request->auth,
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    /**
     * Hapus subscription (unsubscribe).
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    /**
     * Return VAPID public key untuk frontend.
     */
    public function publicKey()
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }
}
