<?php

use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use App\Enums\TaskStatus;
use App\Livewire\Tasks\Index;
use App\Livewire\Tasks\SuggestedTasks;
use App\Models\Task;
use App\Models\TaskSuggestion;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Task Model', function () {
    it('can create a task with proper attributes', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Task',
            'status' => TaskStatus::Suggested,
            'priority' => TaskPriority::Medium,
            'source' => TaskSource::Ai,
        ]);

        expect($task->title)->toBe('Test Task')
            ->and($task->status)->toBe(TaskStatus::Suggested)
            ->and($task->priority)->toBe(TaskPriority::Medium)
            ->and($task->source)->toBe(TaskSource::Ai);
    });

    it('can accept a suggested task', function () {
        $task = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
        ]);

        $task->accept();

        expect($task->fresh()->status)->toBe(TaskStatus::Accepted)
            ->and($task->fresh()->accepted_at)->not->toBeNull();
    });

    it('can reject a task with reason', function () {
        $task = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
        ]);

        $task->reject('Not relevant');

        expect($task->fresh()->status)->toBe(TaskStatus::Rejected)
            ->and($task->fresh()->rejected_at)->not->toBeNull()
            ->and($task->fresh()->rejection_reason)->toBe('Not relevant');
    });

    it('can start an accepted task', function () {
        $task = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
        ]);

        $task->start();

        expect($task->fresh()->status)->toBe(TaskStatus::InProgress)
            ->and($task->fresh()->started_at)->not->toBeNull();
    });

    it('can complete a task', function () {
        $task = Task::factory()->inProgress()->create([
            'user_id' => $this->user->id,
        ]);

        $task->complete('Done successfully');

        expect($task->fresh()->status)->toBe(TaskStatus::Completed)
            ->and($task->fresh()->completed_at)->not->toBeNull()
            ->and($task->fresh()->completion_notes)->toBe('Done successfully');
    });

    it('detects overdue tasks correctly', function () {
        $overdueTask = Task::factory()->overdue()->create([
            'user_id' => $this->user->id,
        ]);

        $futureTask = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
            'due_date' => now()->addDays(5),
        ]);

        expect($overdueTask->isOverdue())->toBeTrue()
            ->and($futureTask->isOverdue())->toBeFalse();
    });

    it('uses scopes correctly', function () {
        Task::factory()->suggested()->create(['user_id' => $this->user->id]);
        Task::factory()->accepted()->create(['user_id' => $this->user->id]);
        Task::factory()->inProgress()->create(['user_id' => $this->user->id]);
        Task::factory()->completed()->create(['user_id' => $this->user->id]);

        expect(Task::suggested()->count())->toBe(1)
            ->and(Task::accepted()->count())->toBe(1)
            ->and(Task::inProgress()->count())->toBe(1)
            ->and(Task::completed()->count())->toBe(1)
            ->and(Task::pending()->count())->toBe(2);
    });
});

describe('TaskSuggestion Model', function () {
    it('generates consistent hashes', function () {
        $hash1 = TaskSuggestion::generateHash('Review Contract', 'Check the terms');
        $hash2 = TaskSuggestion::generateHash('Review Contract', 'Check the terms');
        $hash3 = TaskSuggestion::generateHash('Different Title', 'Check the terms');

        expect($hash1)->toBe($hash2)
            ->and($hash1)->not->toBe($hash3);
    });

    it('generates case-insensitive hashes', function () {
        $hash1 = TaskSuggestion::generateHash('Review Contract');
        $hash2 = TaskSuggestion::generateHash('review contract');

        expect($hash1)->toBe($hash2);
    });
});

describe('Tasks Index Component', function () {
    it('renders the tasks page', function () {
        Livewire::test(Index::class)
            ->assertStatus(200)
            ->assertSee('Tasks');
    });

    it('shows active tasks by default', function () {
        $activeTask = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
            'title' => 'Active Task',
        ]);

        $suggestedTask = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
            'title' => 'Suggested Task',
        ]);

        Livewire::test(Index::class)
            ->assertSee('Active Task')
            ->assertDontSee('Suggested Task');
    });

    it('can filter by suggested tasks', function () {
        $activeTask = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
            'title' => 'Active Task',
        ]);

        $suggestedTask = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
            'title' => 'Suggested Task',
        ]);

        Livewire::test(Index::class)
            ->call('setStatusFilter', 'suggested')
            ->assertSet('statusFilter', 'suggested');
    });

    it('can start a task', function () {
        $task = Task::factory()->accepted()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(Index::class)
            ->call('startTask', $task->id);

        expect($task->fresh()->status)->toBe(TaskStatus::InProgress);
    });

    it('can complete a task', function () {
        $task = Task::factory()->inProgress()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(Index::class)
            ->call('completeTask', $task->id);

        expect($task->fresh()->status)->toBe(TaskStatus::Completed);
    });
});

describe('SuggestedTasks Component', function () {
    it('renders suggested tasks', function () {
        $task = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
            'title' => 'My Suggestion',
        ]);

        Livewire::test(SuggestedTasks::class)
            ->assertSee('My Suggestion');
    });

    it('can accept a task', function () {
        $task = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(SuggestedTasks::class)
            ->call('acceptTask', $task->id);

        expect($task->fresh()->status)->toBe(TaskStatus::Accepted);
    });

    it('can reject a task', function () {
        $task = Task::factory()->suggested()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(SuggestedTasks::class)
            ->call('showRejectForm', $task->id)
            ->set('rejectionReason', 'Not needed')
            ->call('rejectTask');

        expect($task->fresh()->status)->toBe(TaskStatus::Rejected)
            ->and($task->fresh()->rejection_reason)->toBe('Not needed');
    });
});

describe('Tasks Route', function () {
    it('requires authentication', function () {
        auth()->logout();

        $this->get('/tasks')
            ->assertRedirect('/login');
    });

    it('is accessible when authenticated', function () {
        $this->get('/tasks')
            ->assertStatus(200);
    });
});
