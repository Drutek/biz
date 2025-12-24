<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class BusinessProfile extends Component
{
    public string $business_description = '';

    public string $business_industry = '';

    public string $business_target_market = '';

    public string $business_key_services = '';

    public function mount(): void
    {
        $this->business_description = Setting::get(Setting::KEY_BUSINESS_DESCRIPTION, '');
        $this->business_industry = Setting::get(Setting::KEY_BUSINESS_INDUSTRY, '');
        $this->business_target_market = Setting::get(Setting::KEY_BUSINESS_TARGET_MARKET, '');
        $this->business_key_services = Setting::get(Setting::KEY_BUSINESS_KEY_SERVICES, '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'business_description' => 'nullable|string|max:2000',
            'business_industry' => 'nullable|string|max:255',
            'business_target_market' => 'nullable|string|max:1000',
            'business_key_services' => 'nullable|string|max:1000',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set(Setting::KEY_BUSINESS_DESCRIPTION, $this->business_description);
        Setting::set(Setting::KEY_BUSINESS_INDUSTRY, $this->business_industry);
        Setting::set(Setting::KEY_BUSINESS_TARGET_MARKET, $this->business_target_market);
        Setting::set(Setting::KEY_BUSINESS_KEY_SERVICES, $this->business_key_services);

        $this->dispatch('settings-saved');
    }

    public function render()
    {
        return view('livewire.settings.business-profile');
    }
}
