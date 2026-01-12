<?php

namespace App\Livewire\Settings;

use App\Enums\LinkedInPostFrequency;
use App\Enums\LinkedInTone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class LinkedIn extends Component
{
    public bool $linkedin_posts_enabled = true;

    public string $linkedin_post_frequency = 'weekly';

    public int $linkedin_posts_per_generation = 3;

    public string $linkedin_default_tone = 'professional';

    /** @var array<string> */
    public array $linkedin_topics = [];

    public bool $linkedin_include_hashtags = true;

    public bool $linkedin_include_cta = true;

    public function mount(): void
    {
        $preferences = Auth::user()->getOrCreatePreferences();

        $this->linkedin_posts_enabled = $preferences->linkedin_posts_enabled ?? true;
        $this->linkedin_post_frequency = $preferences->linkedin_post_frequency ?? 'weekly';
        $this->linkedin_posts_per_generation = $preferences->linkedin_posts_per_generation ?? 3;
        $this->linkedin_default_tone = $preferences->linkedin_default_tone ?? 'professional';
        $this->linkedin_topics = $preferences->linkedin_topics ?? ['industry_trends', 'thought_leadership', 'news_commentary'];
        $this->linkedin_include_hashtags = $preferences->linkedin_include_hashtags ?? true;
        $this->linkedin_include_cta = $preferences->linkedin_include_cta ?? true;
    }

    public function save(): void
    {
        $this->validate([
            'linkedin_post_frequency' => 'required|in:daily,twice_weekly,weekly',
            'linkedin_posts_per_generation' => 'required|integer|min:1|max:5',
            'linkedin_default_tone' => 'required|in:professional,conversational,thought_leadership,casual',
            'linkedin_topics' => 'required|array|min:1',
            'linkedin_topics.*' => 'in:industry_trends,thought_leadership,news_commentary,company_updates',
        ]);

        $preferences = Auth::user()->getOrCreatePreferences();

        $preferences->update([
            'linkedin_posts_enabled' => $this->linkedin_posts_enabled,
            'linkedin_post_frequency' => $this->linkedin_post_frequency,
            'linkedin_posts_per_generation' => $this->linkedin_posts_per_generation,
            'linkedin_default_tone' => $this->linkedin_default_tone,
            'linkedin_topics' => $this->linkedin_topics,
            'linkedin_include_hashtags' => $this->linkedin_include_hashtags,
            'linkedin_include_cta' => $this->linkedin_include_cta,
        ]);

        $this->dispatch('settings-saved');
    }

    /**
     * @return array<string, string>
     */
    public function getFrequenciesProperty(): array
    {
        return collect(LinkedInPostFrequency::cases())
            ->mapWithKeys(fn ($freq) => [$freq->value => $freq->label()])
            ->all();
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function getTonesProperty(): array
    {
        return collect(LinkedInTone::cases())
            ->mapWithKeys(fn ($tone) => [
                $tone->value => [
                    'label' => $tone->label(),
                    'description' => $tone->description(),
                ],
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableTopicsProperty(): array
    {
        return [
            'industry_trends' => 'Industry Trends',
            'thought_leadership' => 'Thought Leadership',
            'news_commentary' => 'News Commentary',
            'company_updates' => 'Company Updates',
        ];
    }

    public function render(): View
    {
        return view('livewire.settings.linked-in');
    }
}
