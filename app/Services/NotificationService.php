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
     * Called after the booking/cancellation transaction has already
     * committed (see BookingService), so a failure here can no longer
     * threaten a real booking — but on SQLite (single writer for the whole
     * database file) a momentary lock conflict is still possible, so both
     * the in-app row and the mail send are logged and swallowed rather than
     * thrown. Losing the notification is far better than turning it into a
     * user-facing error for an appointment that already succeeded.
     */
    public function send(User $user, string $type, string $title, string $message): ?Notification
    {
        try {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to record in-app notification.', [
                'user_id' => $user->id,
                'type' => $type,
                'exception' => $e->getMessage(),
            ]);

            $notification = null;
        }

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
