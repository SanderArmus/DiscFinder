<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Message $message, public string $senderName, public string $openUrl) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $preview = trim((string) $this->message->content);
        if (mb_strlen($preview) > 160) {
            $preview = mb_substr($preview, 0, 160).'…';
        }

        return (new MailMessage)
            ->subject('New message')
            ->greeting('You have a new message')
            ->line("From: {$this->senderName}")
            ->line($preview !== '' ? "\"{$preview}\"" : '')
            ->action('Open chat', url($this->openUrl));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
        ];
    }
}
