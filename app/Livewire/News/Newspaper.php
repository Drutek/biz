<?php

namespace App\Livewire\News;

use App\Models\NewsItem;
use App\Models\NewspaperEdition;
use App\Models\TrackedEntity;
use App\Services\News\NewspaperService;
use App\Services\News\SerpApiService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Newspaper extends Component
{
    public ?NewspaperEdition $edition = null;

    public string $error = '';

    public bool $hasTrackedEntities = false;

    public bool $hasRecentNews = false;

    public function mount(): void
    {
        $this->loadEdition();
        $this->checkPrerequisites();
    }

    public function render(): View
    {
        return view('livewire.news.newspaper');
    }

    public function regenerate(): void
    {
        $this->error = '';

        if (! $this->hasTrackedEntities) {
            $this->error = 'Please add tracked entities first in Settings > Tracked Entities.';

            return;
        }

        if (! $this->hasRecentNews) {
            $this->error = 'No recent news available. News is fetched every 4 hours.';

            return;
        }

        try {
            $user = Auth::user();
            $service = app(NewspaperService::class);

            $this->edition = $service->regenerateForUser($user);

            if (! $this->edition) {
                $this->error = 'Could not generate edition. Please check your API keys are configured.';
            }
        } catch (\Throwable $e) {
            Log::error('Newspaper generation failed', ['error' => $e->getMessage()]);
            $this->error = 'Generation failed: '.$e->getMessage();
        }
    }

    public function markArticleRead(int $newsItemId): void
    {
        NewsItem::where('id', $newsItemId)->update(['is_read' => true]);
    }

    #[On('fetch-news')]
    public function fetchNews(): void
    {
        $this->error = '';

        try {
            $service = app(SerpApiService::class);
            $service->fetchAll();

            $this->checkPrerequisites();

            if ($this->hasRecentNews) {
                $this->error = '';
            } else {
                $this->error = 'No news found. Check that your SerpAPI key is configured in Settings > API Keys.';
            }
        } catch (\Throwable $e) {
            Log::error('News fetch failed', ['error' => $e->getMessage()]);
            $this->error = 'Failed to fetch news: '.$e->getMessage();
        }
    }

    protected function loadEdition(): void
    {
        $user = Auth::user();
        $this->edition = NewspaperEdition::todayForUser($user);
    }

    protected function checkPrerequisites(): void
    {
        $this->hasTrackedEntities = TrackedEntity::active()->exists();

        $this->hasRecentNews = NewsItem::query()
            ->relevant()
            ->where('fetched_at', '>=', now()->subHours(48))
            ->exists();
    }
}
