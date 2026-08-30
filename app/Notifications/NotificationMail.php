<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The email side of an in-app notification (App\Models\Notification):
 * sent in parallel so teachers/guardians don't have to be looking at the
 * app to learn about a new or cancelled appointment.
 */
class NotificationMail extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $body,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Γεια σας,')
            ->line($this->body)
            ->action('Προβολή στην εφαρμογή', route('notifications.index'))
            ->line('Λαμβάνετε αυτό το email επειδή είστε εγγεγραμμένος χρήστης της πλατφόρμας ραντεβού του 1ου ΓΕΛ Ραφήνας.');
    }
}
