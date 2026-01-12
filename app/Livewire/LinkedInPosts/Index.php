<?php

namespace App\Livewire\LinkedInPosts;

use App\Enums\LinkedInPostType;
use App\Models\LinkedInPost;
use App\Services\Social\LinkedInPostService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $typeFilter = '';

    public string $statusFilter = 'active';

    public string $error = '';

    public bool $isGenerating = false;

    public function render(): View
    {
        $query = Auth::user()->linkedInPosts()->latest('generated_at');

        if ($this->typeFilter) {
            $query->where('post_type', $this->typeFilter);
        }

        $query = match ($this->statusFilter) {
            'active' => $query->active(),
            'used' => $query->where('is_used', true),
            'dismissed' => $query->where('is_dismissed', true),
            default => $query,
        };

        return view('livewire.linked-in-posts.index', [
            'posts' => $query->paginate(9),
            'postTypes' => LinkedInPostType::cases(),
        ]);
    }

    public function generateNew(): void
    {
        $this->error = '';
        $this->isGenerating = true;

        try {
            $user = Auth::user();
            $service = app(LinkedInPostService::class);

            $posts = $service->generateBatch($user);

            if ($posts->isEmpty()) {
                $this->error = 'Could not generate posts. Please check your API keys are configured.';
            }
        } catch (\Throwable $e) {
            Log::error('LinkedIn post generation failed', ['error' => $e->getMessage()]);
            $this->error = 'Generation failed: '.$e->getMessage();
        } finally {
            $this->isGenerating = false;
        }
    }

    public function copyToClipboard(int $postId): void
    {
        $post = LinkedInPost::find($postId);

        if ($post && $post->user_id === Auth::id()) {
            $content = $post->content;

            if ($post->hashtags && count($post->hashtags) > 0) {
                $hashtags = collect($post->hashtags)
                    ->map(fn ($tag) => str_starts_with($tag, '#') ? $tag : '#'.$tag)
                    ->implode(' ');
                $content .= "\n\n".$hashtags;
            }

            $this->dispatch('copy-to-clipboard', content: $content);
        }
    }

    public function markAsUsed(int $postId): void
    {
        $post = LinkedInPost::find($postId);

        if ($post && $post->user_id === Auth::id()) {
            $post->markAsUsed();
        }
    }

    public function dismiss(int $postId): void
    {
        $post = LinkedInPost::find($postId);

        if ($post && $post->user_id === Auth::id()) {
            $post->dismiss();
        }
    }

    public function regenerate(int $postId): void
    {
        $this->error = '';

        $post = LinkedInPost::find($postId);

        if (! $post || $post->user_id !== Auth::id()) {
            return;
        }

        try {
            $service = app(LinkedInPostService::class);
            $newPost = $service->regenerate($post);

            if (! $newPost) {
                $this->error = 'Could not regenerate post. Please try again.';
            }
        } catch (\Throwable $e) {
            Log::error('LinkedIn post regeneration failed', ['error' => $e->getMessage()]);
            $this->error = 'Regeneration failed: '.$e->getMessage();
        }
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}
