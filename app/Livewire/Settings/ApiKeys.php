<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Services\LLM\ModelFetcher;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ApiKeys extends Component
{
    public string $company_name = '';

    public string $anthropic_api_key = '';

    public string $openai_api_key = '';

    public string $serpapi_key = '';

    public string $preferred_llm_provider = 'claude';

    public string $news_recency = '';

    public string $anthropic_model = '';

    public string $openai_model = '';

    /** @var array<array{id: string, name: string}> */
    public array $availableAnthropicModels = [];

    /** @var array<array{id: string, name: string}> */
    public array $availableOpenAIModels = [];

    public bool $fetchingAnthropicModels = false;

    public bool $fetchingOpenAIModels = false;

    public string $anthropicModelError = '';

    public string $openaiModelError = '';

    public function mount(): void
    {
        $this->company_name = Setting::get(Setting::KEY_COMPANY_NAME, '');
        $this->anthropic_api_key = Setting::get(Setting::KEY_ANTHROPIC_API_KEY, '');
        $this->openai_api_key = Setting::get(Setting::KEY_OPENAI_API_KEY, '');
        $this->serpapi_key = Setting::get(Setting::KEY_SERPAPI_KEY, '');
        $this->preferred_llm_provider = Setting::get(Setting::KEY_PREFERRED_LLM_PROVIDER, 'claude');
        $this->news_recency = Setting::get(Setting::KEY_NEWS_RECENCY, Setting::DEFAULT_NEWS_RECENCY);
        $this->anthropic_model = Setting::get(Setting::KEY_ANTHROPIC_MODEL, '');
        $this->openai_model = Setting::get(Setting::KEY_OPENAI_MODEL, '');

        // Fetch models on mount if API keys exist
        if ($this->anthropic_api_key) {
            $this->fetchAnthropicModels();
        }
        if ($this->openai_api_key) {
            $this->fetchOpenAIModels();
        }
    }

    public function updatedAnthropicApiKey(): void
    {
        $this->anthropicModelError = '';
        if ($this->anthropic_api_key) {
            $this->fetchAnthropicModels();
        } else {
            $this->availableAnthropicModels = [];
            $this->anthropic_model = '';
        }
    }

    public function updatedOpenaiApiKey(): void
    {
        $this->openaiModelError = '';
        if ($this->openai_api_key) {
            $this->fetchOpenAIModels();
        } else {
            $this->availableOpenAIModels = [];
            $this->openai_model = '';
        }
    }

    public function fetchAnthropicModels(): void
    {
        $this->fetchingAnthropicModels = true;
        $this->anthropicModelError = '';

        try {
            $fetcher = app(ModelFetcher::class);
            $this->availableAnthropicModels = $fetcher->fetchAnthropicModels($this->anthropic_api_key);

            // If no model selected and we have options, select the first (best) one
            if (empty($this->anthropic_model) && ! empty($this->availableAnthropicModels)) {
                $this->anthropic_model = $this->availableAnthropicModels[0]['id'];
            }
        } catch (\Throwable $e) {
            $this->anthropicModelError = 'Failed to fetch models. Please check your API key.';
            $this->availableAnthropicModels = [];
        } finally {
            $this->fetchingAnthropicModels = false;
        }
    }

    public function fetchOpenAIModels(): void
    {
        $this->fetchingOpenAIModels = true;
        $this->openaiModelError = '';

        try {
            $fetcher = app(ModelFetcher::class);
            $this->availableOpenAIModels = $fetcher->fetchOpenAIModels($this->openai_api_key);

            // If no model selected and we have options, select the first (best) one
            if (empty($this->openai_model) && ! empty($this->availableOpenAIModels)) {
                $this->openai_model = $this->availableOpenAIModels[0]['id'];
            }
        } catch (\Throwable $e) {
            $this->openaiModelError = 'Failed to fetch models. Please check your API key.';
            $this->availableOpenAIModels = [];
        } finally {
            $this->fetchingOpenAIModels = false;
        }
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function getNewsRecencyOptionsProperty(): array
    {
        return Setting::newsRecencyOptions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'company_name' => 'nullable|string|max:255',
            'anthropic_api_key' => 'nullable|string',
            'openai_api_key' => 'nullable|string',
            'serpapi_key' => 'nullable|string',
            'preferred_llm_provider' => 'required|in:claude,openai',
            'news_recency' => [Rule::in(['h', 'd', 'w', 'm', ''])],
            'anthropic_model' => 'nullable|string',
            'openai_model' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set(Setting::KEY_COMPANY_NAME, $this->company_name);
        Setting::set(Setting::KEY_ANTHROPIC_API_KEY, $this->anthropic_api_key);
        Setting::set(Setting::KEY_OPENAI_API_KEY, $this->openai_api_key);
        Setting::set(Setting::KEY_SERPAPI_KEY, $this->serpapi_key);
        Setting::set(Setting::KEY_PREFERRED_LLM_PROVIDER, $this->preferred_llm_provider);
        Setting::set(Setting::KEY_NEWS_RECENCY, $this->news_recency);
        Setting::set(Setting::KEY_ANTHROPIC_MODEL, $this->anthropic_model);
        Setting::set(Setting::KEY_OPENAI_MODEL, $this->openai_model);

        $this->dispatch('settings-saved');
    }

    public function render()
    {
        return view('livewire.settings.api-keys');
    }
}
