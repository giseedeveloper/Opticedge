<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function __construct(
        private readonly FcmPushService $fcm,
    ) {}

    /**
     * @param  User  $notifiable
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $userContext = $this->userContext($notifiable);

        if (! method_exists($notification, 'toFcm')) {
            Log::info('FCM skipped: notification has no toFcm payload', $userContext);

            return;
        }

        $message = $notification->toFcm($notifiable);
        if (! is_array($message)) {
            Log::info('FCM skipped: empty toFcm payload', $userContext);

            return;
        }

        $notificationType = null;
        if (property_exists($notification, 'type')) {
            $notificationType = $notification->type;
        }
        $data = (array) ($message['data'] ?? []);
        $notificationType ??= $data['type'] ?? null;

        $tokens = $notifiable->deviceTokens()->pluck('token')->all();
        if ($tokens === []) {
            Log::info('FCM skipped: user has no registered device tokens', array_merge($userContext, [
                'notification_type' => $notificationType,
                'title' => (string) ($message['title'] ?? ''),
            ]));

            return;
        }

        Log::info('FCM send queued for user', array_merge($userContext, [
            'notification_type' => $notificationType,
            'title' => (string) ($message['title'] ?? ''),
            'token_count' => count($tokens),
        ]));

        $this->fcm->sendToTokens(
            $tokens,
            (string) ($message['title'] ?? 'Optic'),
            (string) ($message['body'] ?? ''),
            $data,
            $userContext,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function userContext(object $notifiable): array
    {
        if ($notifiable instanceof User) {
            return [
                'user_id' => $notifiable->id,
                'user_email' => $notifiable->email,
                'user_name' => $notifiable->name,
                'user_role' => $notifiable->role,
            ];
        }

        return [
            'user_id' => $notifiable->getKey(),
        ];
    }
}
