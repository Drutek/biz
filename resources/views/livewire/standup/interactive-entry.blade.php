<div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
    @if($isSkipped)
        <div class="text-center text-zinc-500 dark:text-zinc-400">
            <p>Check-in skipped for today.</p>
        </div>
    @elseif($entry && $entry->isComplete())
        {{-- Show completed entry --}}
        <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">
            <flux:icon.check-circle class="inline h-5 w-5 text-green-500 mr-1" />
            Today's Check-in Complete
        </h3>

        <div class="space-y-4">
            <div>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Yesterday</p>
                <p class="text-zinc-900 dark:text-white">{{ $yesterdayAccomplished ?: 'No update' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Today's Plan</p>
                <p class="text-zinc-900 dark:text-white">{{ $todayPlanned ?: 'No update' }}</p>
            </div>
            @if($blockers)
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Blockers</p>
                    <p class="text-zinc-900 dark:text-white">{{ $blockers }}</p>
                </div>
            @endif

            @if($aiAnalysis)
                <div class="mt-4 rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">
                        <flux:icon.sparkles class="inline h-4 w-4 mr-1" />
                        AI Analysis
                    </p>
                    <div class="prose prose-sm prose-zinc max-w-none dark:prose-invert">
                        {!! Str::markdown($aiAnalysis) !!}
                    </div>
                </div>
            @endif
        </div>
    @elseif($showFollowUp)
        {{-- Follow-up Questions --}}
        <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">
            <flux:icon.chat-bubble-left-ellipsis class="inline h-5 w-5 text-blue-500 mr-1" />
            Follow-up Questions
        </h3>

        <form wire:submit="submitFollowUp" class="space-y-4">
            @foreach($followUpQuestions as $index => $question)
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        {{ $question }}
                    </label>
                    <flux:textarea
                        wire:model="followUpResponses.{{ $index }}"
                        rows="2"
                        placeholder="Your response..."
                    />
                </div>
            @endforeach

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="skipFollowUp">
                    Skip
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Submit</span>
                    <span wire:loading>Processing...</span>
                </flux:button>
            </div>
        </form>
    @else
        {{-- Initial Entry Form --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-zinc-900 dark:text-white">
                <flux:icon.clipboard-document-check class="inline h-5 w-5 text-amber-500 mr-1" />
                Daily Check-in
            </h3>
            <flux:button size="sm" variant="ghost" wire:click="skip">
                Skip for today
            </flux:button>
        </div>

        <form wire:submit="submitEntry" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    What did you accomplish yesterday?
                </label>
                <flux:textarea
                    wire:model="yesterdayAccomplished"
                    rows="2"
                    placeholder="Completed the contract proposal, had client call..."
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    What are you planning to do today?
                </label>
                <flux:textarea
                    wire:model="todayPlanned"
                    rows="2"
                    placeholder="Follow up on leads, review financials..."
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Any blockers or concerns? (optional)
                </label>
                <flux:textarea
                    wire:model="blockers"
                    rows="2"
                    placeholder="Waiting on client response, need more info about..."
                />
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Submit Check-in</span>
                    <span wire:loading>Processing...</span>
                </flux:button>
            </div>
        </form>
    @endif
</div>
