<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\NotificationMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    /**
     * Record an in-app notification and email it in parallel.
     *
     * Mail delivery failures (e.g. a misconfigured or unreachable SMTP
     * server) are logged and swallowed rather than thrown: this is called
     * from inside booking/cancellation database transactions, and losing a
     * real booking because the school's mail server hiccuped would be far
     * worse than a guardian/teacher simply not getting the email copy.
     */
    public function send(User $user, string $type, string $title, string $message): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);

        try {
            $user->notify(new NotificationMail($title, $message));
        } catch (Throwable $e) {
            Log::error('Failed to send notification email.', [
                'user_id' => $user->id,
                'type' => $type,
                'exception' => $e->getMessage(),
            ]);
        }

        return $notification;
    }
}
