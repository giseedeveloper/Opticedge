<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $channels  e.g. ['database', 'fcm', 'mail']
     */
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly array $payload = [],
        public readonly array $channels = ['database', 'fcm'],
    ) {}

    /**
     * @return array<int, string|class-string>
     */
    public function via(object $notifiable): array
    {
        $via = [];

        if (in_array('database', $this->channels, true)) {
            $via[] = 'database';
        }

        if (in_array('fcm', $this->channels, true)) {
            $via[] = FcmChannel::class;
        }

        if (in_array('mail', $this->channels, true) && filled($notifiable->email ?? null)) {
            $via[] = 'mail';
        }

        return $via;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'route' => $this->payload['route'] ?? null,
            'web_url' => $this->payload['web_url'] ?? null,
            'entity_type' => $this->payload['entity_type'] ?? null,
            'entity_id' => $this->payload['entity_id'] ?? null,
            'meta' => $this->payload['meta'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => [
                'type' => $this->type,
                'route' => (string) ($this->payload['route'] ?? ''),
                'entity_type' => (string) ($this->payload['entity_type'] ?? ''),
                'entity_id' => (string) ($this->payload['entity_id'] ?? ''),
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->body);

        $route = $this->payload['route'] ?? null;
        if (is_string($route) && $route !== '') {
            $mail->action('Open Optic', url('/'));
        }

        return $mail;
    }
}
