<?php

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Notifications extends Component
{
    public bool $standup_email_enabled = true;

    public string $standup_email_time = '07:00';

    public string $standup_email_timezone = 'America/New_York';

    public bool $in_app_notifications_enabled = true;

    public bool $proactive_insights_enabled = true;

    public int $runway_alert_threshold = 3;

    public function mount(): void
    {
        $preferences = Auth::user()->getOrCreatePreferences();

        $this->standup_email_enabled = $preferences->standup_email_enabled;
        $this->standup_email_time = $preferences->standup_email_time ?? '07:00';
        $this->standup_email_timezone = $preferences->standup_email_timezone;
        $this->in_app_notifications_enabled = $preferences->in_app_notifications_enabled;
        $this->proactive_insights_enabled = $preferences->proactive_insights_enabled;
        $this->runway_alert_threshold = $preferences->runway_alert_threshold;
    }

    public function save(): void
    {
        $this->validate([
            'standup_email_time' => 'required|string|regex:/^\d{2}:\d{2}$/',
            'standup_email_timezone' => 'required|timezone',
            'runway_alert_threshold' => 'required|integer|min:1|max:24',
        ]);

        $preferences = Auth::user()->getOrCreatePreferences();

        $preferences->update([
            'standup_email_enabled' => $this->standup_email_enabled,
            'standup_email_time' => $this->standup_email_time,
            'standup_email_timezone' => $this->standup_email_timezone,
            'in_app_notifications_enabled' => $this->in_app_notifications_enabled,
            'proactive_insights_enabled' => $this->proactive_insights_enabled,
            'runway_alert_threshold' => $this->runway_alert_threshold,
        ]);

        $this->dispatch('preferences-updated');
    }

    /**
     * @return array<string, string>
     */
    public function getTimezonesProperty(): array
    {
        $timezones = [];
        foreach (\DateTimeZone::listIdentifiers() as $tz) {
            $timezones[$tz] = $tz;
        }

        return $timezones;
    }

    public function render(): View
    {
        return view('livewire.settings.notifications');
    }
}
