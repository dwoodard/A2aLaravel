<?php

namespace Dwoodard\A2aLaravel\Listeners;

use Dwoodard\A2aLaravel\Events\TaskStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class PushNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TaskStatusUpdated $event)
    {
        $task = $event->task;
        $config = $task->pushNotificationConfig;
        if (! $config || ! $config->target_url) {
            return;
        }
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $task->id,
            'method' => 'tasks/pushNotification/update',
            'params' => [
                'task' => $task->toArray(),
                'state' => $event->state,
                'message' => $event->message ? $event->message->toArray() : null,
                'timestamp' => $event->timestamp,
            ],
        ];
        $headers = [
            'Content-Type' => 'application/json',
        ];
        if ($config->token) {
            $headers['X-A2A-Notification-Token'] = $config->token;
        }
        Http::withHeaders($headers)->post($config->target_url, $payload);
    }
}
