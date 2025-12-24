<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class ApiKeys extends Component
{
    public string $company_name = '';

    public string $anthropic_api_key = '';

    public string $openai_api_key = '';

    public string $serpapi_key = '';

    public string $preferred_llm_provider = 'claude';

    public function mount(): void
    {
        $this->company_name = Setting::get(Setting::KEY_COMPANY_NAME, '');
        $this->anthropic_api_key = Setting::get(Setting::KEY_ANTHROPIC_API_KEY, '');
        $this->openai_api_key = Setting::get(Setting::KEY_OPENAI_API_KEY, '');
        $this->serpapi_key = Setting::get(Setting::KEY_SERPAPI_KEY, '');
        $this->preferred_llm_provider = Setting::get(Setting::KEY_PREFERRED_LLM_PROVIDER, 'claude');
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

        $this->dispatch('settings-saved');
    }

    public function render()
    {
        return view('livewire.settings.api-keys');
    }
}
