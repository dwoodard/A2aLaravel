<?php

namespace Dwoodard\A2aLaravel\Services;

use Carbon\Carbon;
use Dwoodard\A2aLaravel\Enums\TaskState;
use Dwoodard\A2aLaravel\Events\TaskStatusUpdated;
use Dwoodard\A2aLaravel\Models\Artifact;
use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Models\PushNotificationConfig;
use Dwoodard\A2aLaravel\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class TaskManager
{
    public function createTask(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $task = Task::create([
                'id' => $data['id'],
                'session_id' => $data['session_id'] ?? null,
                'state' => TaskState::SUBMITTED,
                'metadata' => $data['metadata'] ?? [],
            ]);

            if (isset($data['message'])) {
                $task->messages()->create([
                    'role' => 'user',
                    'content' => $data['message'],
                ]);
            }

            // ...event firing, etc.
            return $task;
        });
    }

    /**
     * Transition a task to 'working' state.
     */
    public function markWorking(Task $task): Task
    {
        $task->state = TaskState::WORKING;
        $task->save();
        $message = $task->messages()->latest()->first();
        $timestamp = Carbon::now('UTC')->toIso8601String();
        event(new TaskStatusUpdated($task, TaskState::WORKING->value, $message, $timestamp));

        return $task;
    }

    /**
     * Transition a task to 'input-required' state with a prompt message.
     */
    public function markInputRequired(Task $task, $promptMessage): Task
    {
        $task->state = TaskState::INPUT_REQUIRED;
        $task->save();
        $message = $task->messages()->create([
            'role' => 'agent',
            'content' => $promptMessage,
        ]);
        $timestamp = Carbon::now('UTC')->toIso8601String();
        event(new TaskStatusUpdated($task, TaskState::INPUT_REQUIRED->value, $message, $timestamp));

        return $task;
    }

    /**
     * Transition a task to 'completed' state, add result message and artifacts.
     */
    public function markCompleted(Task $task, $resultMessage, array $artifacts = []): Task
    {
        $task->state = TaskState::COMPLETED;
        $task->save();
        $message = $task->messages()->create([
            'role' => 'agent',
            'content' => $resultMessage,
        ]);
        foreach ($artifacts as $artifactData) {
            $task->artifacts()->create($artifactData);
        }
        $timestamp = Carbon::now('UTC')->toIso8601String();
        event(new TaskStatusUpdated($task, TaskState::COMPLETED->value, $message, $timestamp));

        return $task;
    }

    /**
     * Transition a task to 'failed' state with an error message.
     */
    public function markFailed(Task $task, $errorMessage): Task
    {
        $task->state = TaskState::FAILED;
        $task->save();
        $message = $task->messages()->create([
            'role' => 'agent',
            'content' => $errorMessage,
        ]);
        $timestamp = Carbon::now('UTC')->toIso8601String();
        event(new TaskStatusUpdated($task, TaskState::FAILED->value, $message, $timestamp));

        return $task;
    }

    /**
     * Transition a task to 'canceled' state.
     */
    public function markCanceled(Task $task): Task
    {
        $task->state = TaskState::CANCELED;
        $task->save();
        $message = $task->messages()->latest()->first();
        $timestamp = Carbon::now('UTC')->toIso8601String();
        event(new TaskStatusUpdated($task, TaskState::CANCELED->value, $message, $timestamp));

        return $task;
    }

    /**
     * Lookup a task by ID, eager loading messages and artifacts.
     */
    public function findTask(string $id): ?Task
    {
        return Task::with(['messages', 'artifacts', 'pushNotificationConfig'])->find($id);
    }

    /**
     * Cancel a task by ID (if not already terminal).
     */
    public function cancelTask(string $id): ?Task
    {
        $task = $this->findTask($id);
        if ($task && ! in_array($task->state, [TaskState::COMPLETED, TaskState::FAILED, TaskState::CANCELED], true)) {
            return $this->markCanceled($task);
        }

        return $task;
    }

    /**
     * Add an artifact to a task.
     */
    public function addArtifact(Task $task, array $artifactData): Artifact
    {
        return $task->artifacts()->create($artifactData);
    }

    /**
     * Add a message to a task.
     */
    public function addMessage(Task $task, array $messageData): Message
    {
        return $task->messages()->create($messageData);
    }

    /**
     * Set or update push notification config for a task.
     */
    public function setPushNotificationConfig(Task $task, array $config): PushNotificationConfig
    {
        return $task->pushNotificationConfig()->updateOrCreate([], $config);
    }

    /**
     * Get push notification config for a task.
     */
    public function getPushNotificationConfig(Task $task): ?PushNotificationConfig
    {
        return $task->pushNotificationConfig;
    }

    /**
     * Return protocol-compliant TaskPushNotificationConfig object.
     */
    public function toTaskPushNotificationConfig(Task $task, ?PushNotificationConfig $config): array
    {
        return [
            'id' => $task->id,
            'pushNotificationConfig' => $config ? $config->toProtocolArray() : null,
        ];
    }
}
