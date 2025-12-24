<?php

namespace App\Livewire\Standup;

use App\Models\DailyStandup;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Archive extends Component
{
    use WithPagination;

    public ?string $selectedDate = null;

    public ?DailyStandup $selectedStandup = null;

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedStandup = Auth::user()
            ->standups()
            ->whereDate('standup_date', $date)
            ->first();
    }

    public function clearSelection(): void
    {
        $this->selectedDate = null;
        $this->selectedStandup = null;
    }

    /**
     * @return LengthAwarePaginator<DailyStandup>
     */
    public function getStandupsProperty(): LengthAwarePaginator
    {
        return Auth::user()
            ->standups()
            ->orderByDesc('standup_date')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.standup.archive');
    }
}
