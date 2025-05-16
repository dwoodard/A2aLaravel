<?php

namespace Dwoodard\A2aLaravel\Events;

use Carbon\Carbon;
use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;

    public string $state;

    public ?Message $message;

    public string $timestamp;

    public function __construct(Task $task, string $state, ?Message $message = null, ?string $timestamp = null)
    {
        $this->task = $task;
        $this->state = $state;
        $this->message = $message;
        $this->timestamp = $timestamp ?? Carbon::now('UTC')->toIso8601String();
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('agent-task.'.$this->task->id)];
    }

    public function broadcastWith()
    {
        return [
            'task' => $this->task->toArray(),
            'state' => $this->state,
            'message' => $this->message ? $this->message->toArray() : null,
            'timestamp' => $this->timestamp,
        ];
    }
}
