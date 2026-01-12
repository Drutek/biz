<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('LinkedIn Posts')" :subheading="__('Configure AI-generated LinkedIn post suggestions')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            {{-- Enable/Disable --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">LinkedIn Post Suggestions</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Enable AI-generated LinkedIn post suggestions based on your business context
                        </p>
                    </div>
                    <flux:switch wire:model.live="linkedin_posts_enabled" />
                </div>
            </div>

            @if($linkedin_posts_enabled)
                {{-- Generation Frequency --}}
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Generation Frequency</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            How often should new posts be generated automatically
                        </p>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Frequency</flux:label>
                            <flux:select wire:model="linkedin_post_frequency">
                                @foreach($this->frequencies as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="linkedin_post_frequency" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Posts Per Batch</flux:label>
                            <flux:input type="number" wire:model="linkedin_posts_per_generation" min="1" max="5" />
                            <flux:description>How many posts to generate each time (1-5)</flux:description>
                            <flux:error name="linkedin_posts_per_generation" />
                        </flux:field>
                    </div>
                </div>

                {{-- Default Tone --}}
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Default Tone</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Choose the default writing style for your posts
                        </p>
                    </div>

                    <div class="mt-4 grid gap-3">
                        @foreach($this->tones as $value => $tone)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-3 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800 {{ $linkedin_default_tone === $value ? 'bg-blue-50 border-blue-300 dark:bg-blue-900/20 dark:border-blue-700' : '' }}">
                                <input
                                    type="radio"
                                    wire:model="linkedin_default_tone"
                                    value="{{ $value }}"
                                    class="mt-1"
                                >
                                <div>
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $tone['label'] }}</span>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $tone['description'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="linkedin_default_tone" />
                </div>

                {{-- Topics --}}
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Content Topics</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Select the types of content you want to post about
                        </p>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach($this->availableTopics as $value => $label)
                            <label class="flex cursor-pointer items-center gap-3">
                                <flux:checkbox
                                    wire:model="linkedin_topics"
                                    value="{{ $value }}"
                                />
                                <span class="text-zinc-900 dark:text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="linkedin_topics" />
                </div>

                {{-- Additional Options --}}
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <h3 class="font-medium text-zinc-900 dark:text-white">Post Options</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Customize what's included in generated posts
                        </p>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-white">Include Hashtags</span>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Add relevant hashtags to increase post visibility
                                </p>
                            </div>
                            <flux:switch wire:model.live="linkedin_include_hashtags" />
                        </div>

                        <div class="border-t border-zinc-100 pt-4 dark:border-zinc-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-zinc-900 dark:text-white">Include Call-to-Action</span>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        Add an engagement question or CTA at the end
                                    </p>
                                </div>
                                <flux:switch wire:model.live="linkedin_include_cta" />
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save Settings') }}</flux:button>

                <x-action-message class="me-3" on="settings-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
