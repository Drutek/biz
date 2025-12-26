<?php

namespace App\Livewire\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public string $statusFilter = 'active';

    public function mount(): void
    {
        // Default to active tasks
    }

    public function setStatusFilter(string $filter): void
    {
        $this->statusFilter = $filter;
    }

    public function startTask(int $taskId): void
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($taskId);
        $task->start();
    }

    public function completeTask(int $taskId): void
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($taskId);
        $task->complete();
    }

    public function cancelTask(int $taskId): void
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($taskId);
        $task->cancel();
    }

    public function render(): View
    {
        // Use CASE for ordering status (works with both MySQL and SQLite)
        $statusOrder = "CASE status
            WHEN 'in_progress' THEN 1
            WHEN 'accepted' THEN 2
            WHEN 'suggested' THEN 3
            WHEN 'completed' THEN 4
            WHEN 'rejected' THEN 5
            WHEN 'cancelled' THEN 6
            ELSE 7
        END";

        $query = Task::where('user_id', Auth::id())
            ->orderByRaw($statusOrder)
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('priority');

        if ($this->statusFilter === 'active') {
            $query->whereIn('status', [TaskStatus::Accepted, TaskStatus::InProgress]);
        } elseif ($this->statusFilter === 'suggested') {
            $query->where('status', TaskStatus::Suggested);
        } elseif ($this->statusFilter === 'completed') {
            $query->where('status', TaskStatus::Completed);
        }

        $tasks = $query->get();

        $counts = [
            'active' => Task::where('user_id', Auth::id())->pending()->count(),
            'suggested' => Task::where('user_id', Auth::id())->suggested()->count(),
            'completed' => Task::where('user_id', Auth::id())->completed()->count(),
        ];

        return view('livewire.tasks.index', [
            'tasks' => $tasks,
            'counts' => $counts,
        ]);
    }
}
