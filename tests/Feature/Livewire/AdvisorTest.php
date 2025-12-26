<?php

use App\Enums\MessageRole;
use App\Livewire\Advisor\Chat;
use App\Models\AdvisoryMessage;
use App\Models\AdvisoryThread;
use App\Models\User;
use App\Services\LLM\LLMManager;
use App\Services\LLM\LLMResponse;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Advisor Chat', function () {
    it('renders the chat page', function () {
        $this->get(route('advisor.index'))
            ->assertSuccessful()
            ->assertSeeLivewire(Chat::class);
    });

    it('displays existing threads', function () {
        $thread = AdvisoryThread::factory()->for($this->user)->create([
            'title' => 'Strategy Discussion',
        ]);

        Livewire::test(Chat::class)
            ->assertSee('Strategy Discussion');
    });

    it('can create a new thread', function () {
        Livewire::test(Chat::class)
            ->call('newThread');

        expect(AdvisoryThread::where('user_id', $this->user->id)->count())->toBe(1);
    });

    it('shows messages for selected thread', function () {
        $thread = AdvisoryThread::factory()->for($this->user)->create();
        AdvisoryMessage::factory()->for($thread)->create([
            'role' => MessageRole::User,
            'content' => 'What is my runway?',
        ]);

        Livewire::test(Chat::class)
            ->call('selectThread', $thread->id)
            ->assertSee('What is my runway?');
    });

    it('can send a message', function () {
        $mockResponse = new LLMResponse(
            content: 'Based on your finances...',
            provider: 'claude',
            model: 'claude-sonnet-4',
            tokensUsed: 100
        );

        $mockProvider = mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chatStream')
            ->once()
            ->andReturnUsing(function ($messages, $callback, $system) use ($mockResponse) {
                $callback('Based on your finances...');

                return $mockResponse;
            });

        $mockManager = mock(LLMManager::class);
        $mockManager->shouldReceive('driver')
            ->once()
            ->andReturn($mockProvider);

        app()->instance(LLMManager::class, $mockManager);

        Livewire::test(Chat::class)
            ->call('newThread')
            ->set('message', 'What is my runway?')
            ->call('sendMessage')
            ->assertSee('What is my runway?')
            ->assertSet('isStreaming', true)
            ->call('streamResponse')
            ->assertSet('isStreaming', false)
            ->assertSee('Based on your finances...');
    });

    it('validates message is not empty', function () {
        Livewire::test(Chat::class)
            ->call('newThread')
            ->set('message', '')
            ->call('sendMessage')
            ->assertHasErrors(['message']);
    });

    it('can delete a thread', function () {
        $thread = AdvisoryThread::factory()->for($this->user)->create();

        Livewire::test(Chat::class)
            ->call('deleteThread', $thread->id);

        expect(AdvisoryThread::find($thread->id))->toBeNull();
    });
});
