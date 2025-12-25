<?php

namespace App\Livewire\Advisor;

use App\Models\AdvisoryThread;
use App\Services\AdvisoryContextBuilder;
use App\Services\Embedding\VectorSearchService;
use App\Services\LLM\LLMManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Chat extends Component
{
    public ?int $currentThreadId = null;

    public string $message = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'message' => 'required|string|min:1',
        ];
    }

    public function render(): View
    {
        $threads = AdvisoryThread::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get();

        $currentThread = $this->currentThreadId
            ? AdvisoryThread::with('messages')->find($this->currentThreadId)
            : null;

        return view('livewire.advisor.chat', [
            'threads' => $threads,
            'currentThread' => $currentThread,
        ]);
    }

    public function newThread(): void
    {
        $thread = AdvisoryThread::create([
            'user_id' => Auth::id(),
            'title' => 'New Conversation',
        ]);

        $this->currentThreadId = $thread->id;
    }

    public function selectThread(int $threadId): void
    {
        $thread = AdvisoryThread::where('user_id', Auth::id())
            ->where('id', $threadId)
            ->first();

        if ($thread) {
            $this->currentThreadId = $thread->id;
        }
    }

    public function deleteThread(int $threadId): void
    {
        AdvisoryThread::where('user_id', Auth::id())
            ->where('id', $threadId)
            ->delete();

        if ($this->currentThreadId === $threadId) {
            $this->currentThreadId = null;
        }
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (! $this->currentThreadId) {
            return;
        }

        $thread = AdvisoryThread::with('messages')
            ->where('user_id', Auth::id())
            ->findOrFail($this->currentThreadId);

        $thread->addUserMessage($this->message);

        if ($thread->messages()->count() === 1) {
            $thread->update([
                'title' => \Str::limit($this->message, 50),
            ]);
        }

        $this->message = '';

        try {
            $vectorSearch = app(VectorSearchService::class);
            $contextBuilder = new AdvisoryContextBuilder($vectorSearch);

            $userMessage = $thread->messages()->latest()->first()?->content ?? '';
            $systemPrompt = $contextBuilder->buildWithRAG(Auth::user(), $userMessage);

            $messages = $thread->messages()
                ->orderBy('created_at')
                ->get()
                ->map(fn ($m) => [
                    'role' => $m->role->value,
                    'content' => $m->content,
                ])
                ->toArray();

            $llmManager = app(LLMManager::class);
            $response = $llmManager->driver()->chat($messages, $systemPrompt);

            $thread->addAssistantMessage(
                $response->content,
                $response->provider,
                $response->model,
                $response->tokensUsed
            );

            $thread->update([
                'context_snapshot' => $contextBuilder->snapshot(),
            ]);
        } catch (\Exception $e) {
            $thread->messages()->create([
                'role' => 'assistant',
                'content' => 'I apologize, but I encountered an error: '.$e->getMessage(),
                'provider' => null,
                'model' => null,
                'tokens_used' => null,
            ]);
        }
    }
}
