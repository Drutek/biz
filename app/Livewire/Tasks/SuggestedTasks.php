<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\TaskSuggestion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SuggestedTasks extends Component
{
    public string $rejectionReason = '';

    public ?int $rejectingTaskId = null;

    public function acceptTask(int $taskId): void
    {
        $task = Task::where('user_id', Auth::id())->findOrFail($taskId);
        $task->accept();

        $this->markSuggestionAccepted($task);
    }

    public function showRejectForm(int $taskId): void
    {
        $this->rejectingTaskId = $taskId;
        $this->rejectionReason = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingTaskId = null;
        $this->rejectionReason = '';
    }

    public function rejectTask(): void
    {
        if (! $this->rejectingTaskId) {
            return;
        }

        $task = Task::where('user_id', Auth::id())->findOrFail($this->rejectingTaskId);
        $task->reject($this->rejectionReason ?: null);

        $this->markSuggestionRejected($task);

        $this->rejectingTaskId = null;
        $this->rejectionReason = '';
    }

    protected function markSuggestionAccepted(Task $task): void
    {
        if ($task->proactive_insight_id) {
            TaskSuggestion::query()
                ->where('user_id', $task->user_id)
                ->where('proactive_insight_id', $task->proactive_insight_id)
                ->update(['was_accepted' => true]);
        }
    }

    protected function markSuggestionRejected(Task $task): void
    {
        if ($task->proactive_insight_id) {
            TaskSuggestion::query()
                ->where('user_id', $task->user_id)
                ->where('proactive_insight_id', $task->proactive_insight_id)
                ->update(['was_rejected' => true]);
        }
    }

    public function render(): View
    {
        $suggestedTasks = Task::where('user_id', Auth::id())
            ->suggested()
            ->orderByDesc('priority')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        return view('livewire.tasks.suggested-tasks', [
            'suggestedTasks' => $suggestedTasks,
        ]);
    }
}
