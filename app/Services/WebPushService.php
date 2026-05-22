<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject'    => config('webpush.vapid.subject'),
                'publicKey'  => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    /**
     * Kirim push notification ke semua subscription milik user.
     */
    public function sendToUser(int $userId, string $title, string $message, ?string $url = null): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title'   => $title,
            'message' => $message,
            'url'     => $url ?? '/',
            'icon'    => '/logo.svg',
            'badge'   => '/logo.svg',
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'keys' => [
                    'p256dh' => $sub->p256dh_key,
                    'auth'   => $sub->auth_token,
                ],
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        // Flush & handle expired subscriptions
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                // Hapus subscription yang sudah expired/tidak valid
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
                }
            }
        }
    }

    /**
     * Kirim push ke semua user (broadcast).
     */
    public function sendToAll(string $title, string $message, ?string $url = null): void
    {
        $userIds = PushSubscription::distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $this->sendToUser($userId, $title, $message, $url);
        }
    }
}
