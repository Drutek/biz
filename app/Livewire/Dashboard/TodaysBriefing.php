<?php

namespace App\Livewire\Dashboard;

use App\Models\DailyStandup;
use App\Services\StandupGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TodaysBriefing extends Component
{
    public ?DailyStandup $standup = null;

    public function mount(StandupGenerator $generator): void
    {
        $user = Auth::user();

        $this->standup = $user->standups()
            ->forDate(now())
            ->first();

        if (! $this->standup) {
            $this->standup = $generator->generate($user);
        }
    }

    public function getUnreadInsightsCountProperty(): int
    {
        return Auth::user()->proactiveInsights()->unread()->count();
    }

    public function render(): View
    {
        return view('livewire.dashboard.todays-briefing');
    }
}
