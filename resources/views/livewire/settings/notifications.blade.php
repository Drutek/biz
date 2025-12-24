<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Manage your notification preferences')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            {{-- Daily Standup Email --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Daily Standup Email</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Receive a daily email with your business briefing
                        </p>
                    </div>
                    <flux:switch wire:model.live="standup_email_enabled" />
                </div>

                @if($standup_email_enabled)
                    <div class="mt-4 grid gap-4 border-t border-zinc-100 pt-4 dark:border-zinc-700 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Delivery Time</flux:label>
                            <flux:input type="time" wire:model="standup_email_time" />
                            <flux:error name="standup_email_time" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Timezone</flux:label>
                            <flux:select wire:model="standup_email_timezone">
                                @foreach($this->timezones as $tz)
                                    <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="standup_email_timezone" />
                        </flux:field>
                    </div>
                @endif
            </div>

            {{-- In-App Notifications --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">In-App Notifications</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Show notifications in the app sidebar
                        </p>
                    </div>
                    <flux:switch wire:model.live="in_app_notifications_enabled" />
                </div>
            </div>

            {{-- Proactive AI Insights --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Proactive AI Insights</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Allow AI to generate insights and recommendations automatically
                        </p>
                    </div>
                    <flux:switch wire:model.live="proactive_insights_enabled" />
                </div>
            </div>

            {{-- Runway Alert Threshold --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white">Runway Alert Threshold</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Get alerted when your runway drops below this many months
                    </p>
                </div>

                <div class="mt-4 max-w-xs">
                    <flux:field>
                        <flux:input type="number" wire:model="runway_alert_threshold" min="1" max="24" suffix="months" />
                        <flux:error name="runway_alert_threshold" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save Preferences') }}</flux:button>

                <x-action-message class="me-3" on="preferences-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
