<?php

namespace App\Services\LLM;

use App\Models\Setting;

class LLMManager
{
    public function driver(?string $name = null): LLMProvider
    {
        $name = $name ?? Setting::get(Setting::KEY_PREFERRED_LLM_PROVIDER, 'claude');

        return match ($name) {
            'openai' => $this->openai(),
            default => $this->claude(),
        };
    }

    public function claude(): ClaudeProvider
    {
        return new ClaudeProvider;
    }

    public function openai(): OpenAIProvider
    {
        return new OpenAIProvider;
    }
}
