<?php

namespace Dwoodard\A2aLaravel\Jobs;

use Dwoodard\A2aLaravel\Services\SkillRegistry;
use Dwoodard\A2aLaravel\Services\TaskManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteSkillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $taskId;

    public string $messageId;

    public string $skillId;

    public function __construct(string $taskId, string $messageId, string $skillId)
    {
        $this->taskId = $taskId;
        $this->messageId = $messageId;
        $this->skillId = $skillId;
    }

    public function handle(TaskManager $taskManager, SkillRegistry $skillRegistry)
    {
        $task = $taskManager->findTask($this->taskId);
        $message = $task ? $task->messages()->find($this->messageId) : null;
        $skill = $skillRegistry->getSkillById($this->skillId);
        if ($task && $message && $skill) {
            try {
                $result = $skill->execute($task, $message);
                $taskManager->markCompleted($task, $result);
            } catch (\Throwable $e) {
                $taskManager->markFailed($task, $e->getMessage());
            }
        } elseif ($task) {
            $taskManager->markFailed($task, 'Skill or message not found');
        }
    }
}
