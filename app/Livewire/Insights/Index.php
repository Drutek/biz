<?php

namespace App\Livewire\Insights;

use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Models\ProactiveInsight;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $status = 'active';

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedPriority(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function markAsRead(int $insightId): void
    {
        $insight = Auth::user()->proactiveInsights()->find($insightId);
        $insight?->markAsRead();
    }

    public function dismiss(int $insightId): void
    {
        $insight = Auth::user()->proactiveInsights()->find($insightId);
        $insight?->dismiss();
    }

    /**
     * @return LengthAwarePaginator<ProactiveInsight>
     */
    public function getInsightsProperty(): LengthAwarePaginator
    {
        $query = Auth::user()->proactiveInsights();

        if ($this->type) {
            $query->byType(InsightType::from($this->type));
        }

        if ($this->priority) {
            $query->where('priority', $this->priority);
        }

        if ($this->status === 'active') {
            $query->active();
        } elseif ($this->status === 'dismissed') {
            $query->where('is_dismissed', true);
        }

        return $query->orderByDesc('created_at')->paginate(10);
    }

    public function getUnreadCountProperty(): int
    {
        return Auth::user()->proactiveInsights()->unread()->count();
    }

    public function render(): View
    {
        return view('livewire.insights.index', [
            'types' => InsightType::cases(),
            'priorities' => InsightPriority::cases(),
        ]);
    }
}
