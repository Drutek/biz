<?php

namespace App\Livewire\Standup;

use App\Models\DailyStandup;
use App\Services\StandupGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Today extends Component
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

        if ($this->standup && ! $this->standup->viewed_at) {
            $this->standup->markAsViewed();
        }
    }

    public function render(): View
    {
        return view('livewire.standup.today');
    }
}
