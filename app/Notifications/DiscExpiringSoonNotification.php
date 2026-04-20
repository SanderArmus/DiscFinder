<?php

namespace App\Notifications;

use App\Models\Disc;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DiscExpiringSoonNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Disc $disc) {}

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
        $name = $this->disc->model_name ?: ($this->disc->manufacturer ?: 'Disc');
        $status = $this->disc->status === 'lost' ? 'lost' : 'found';
        $expiresAt = $this->disc->expires_at?->format('Y-m-d H:i') ?? '';

        return (new MailMessage)
            ->subject("Your {$status} disc report is expiring soon")
            ->greeting('Heads up!')
            ->line("Your disc report \"{$name}\" will expire soon.")
            ->line($expiresAt !== '' ? "Expires at: {$expiresAt}" : '')
            ->action('Open disc details', url("/discs/{$this->disc->id}"))
            ->line('You can renew it for another 90 days from the disc details page.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'disc_id' => $this->disc->id,
        ];
    }
}
