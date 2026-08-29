<?php

namespace App\View\Components;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class NotificationBell extends Component
{
    public int $unreadCount;

    public $recentNotifications;

    public function __construct()
    {
        /** @var User|Authenticatable $user */
        $user = Auth::user();

        $this->recentNotifications = $user->notifications()->limit(5)->get();
        $this->unreadCount = $user->notifications()->whereNull('read_at')->count();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.notification-bell');
    }
}
