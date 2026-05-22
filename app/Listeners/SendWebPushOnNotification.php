<?php

namespace App\Listeners;

use App\Notifications\SystemNotification;
use App\Services\WebPushService;
use Illuminate\Notifications\Events\NotificationSent;

class SendWebPushOnNotification
{
    public function __construct(protected WebPushService $webPush) {}

    /**
     * Handle the event — dipanggil setiap kali notifikasi database terkirim.
     */
    public function handle(NotificationSent $event): void
    {
        // Hanya proses SystemNotification yang via channel 'database'
        if (!($event->notification instanceof SystemNotification)) {
            return;
        }

        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        // Pastikan notifiable punya ID (User model)
        if (!isset($notifiable->id)) {
            return;
        }

        $notification = $event->notification;

        try {
            $this->webPush->sendToUser(
                $notifiable->id,
                $notification->title,
                $notification->message,
                $notification->url
            );
        } catch (\Throwable $e) {
            \Log::warning('Web Push gagal untuk user ' . $notifiable->id . ': ' . $e->getMessage());
        }
    }
}
