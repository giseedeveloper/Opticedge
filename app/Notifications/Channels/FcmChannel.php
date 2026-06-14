<?php

namespace App\Notifications\Channels;

use App\Services\FcmPushService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function __construct(
        private readonly FcmPushService $fcm,
    ) {}

    /**
     * @param  \App\Models\User  $notifiable
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $message = $notification->toFcm($notifiable);
        if (! is_array($message)) {
            return;
        }

        $tokens = $notifiable->deviceTokens()->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $this->fcm->sendToTokens(
            $tokens,
            (string) ($message['title'] ?? 'Optic'),
            (string) ($message['body'] ?? ''),
            (array) ($message['data'] ?? []),
        );
    }
}
