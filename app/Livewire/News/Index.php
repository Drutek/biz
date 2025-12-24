<?php

namespace App\Livewire\News;

use App\Models\NewsItem;
use App\Models\TrackedEntity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $entityFilter = null;

    public string $readFilter = '';

    public bool $showDismissed = false;

    public function render(): View
    {
        $query = NewsItem::query()
            ->with('trackedEntity')
            ->orderByDesc('fetched_at');

        if (! $this->showDismissed) {
            $query->where('is_relevant', true);
        }

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->entityFilter) {
            $query->where('tracked_entity_id', $this->entityFilter);
        }

        if ($this->readFilter === 'unread') {
            $query->where('is_read', false);
        } elseif ($this->readFilter === 'read') {
            $query->where('is_read', true);
        }

        return view('livewire.news.index', [
            'newsItems' => $query->paginate(20),
            'entities' => TrackedEntity::orderBy('name')->get(),
        ]);
    }

    public function markAsRead(int $newsId): void
    {
        NewsItem::where('id', $newsId)->update(['is_read' => true]);
    }

    public function dismiss(int $newsId): void
    {
        NewsItem::where('id', $newsId)->update(['is_relevant' => false]);
    }

    public function markAllAsRead(): void
    {
        $query = NewsItem::query()->where('is_read', false);

        if ($this->entityFilter) {
            $query->where('tracked_entity_id', $this->entityFilter);
        }

        $query->update(['is_read' => true]);
    }
}
