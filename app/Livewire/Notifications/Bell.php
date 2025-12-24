<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Bell extends Component
{
    public bool $showDropdown = false;

    public function getUnreadCountProperty(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    public function getRecentNotificationsProperty()
    {
        return Auth::user()
            ->unreadNotifications()
            ->take(5)
            ->get();
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        $this->dispatch('notification-read');
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->dispatch('all-notifications-read');
    }

    public function render(): View
    {
        return view('livewire.notifications.bell');
    }
}
