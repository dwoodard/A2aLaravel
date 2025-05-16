<?php

namespace Dwoodard\A2aLaravel\Services;

use Carbon\Carbon;
use Dwoodard\A2aLaravel\Events\TaskStatusUpdated;
use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Models\Task;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class AgentService
{
    /**
     * Get the Agent Card for discovery endpoint.
     */
    public function getAgentCard(): array
    {
        $config = Config::get('a2a');
        $skillRegistry = app(SkillRegistry::class);
        $skills = [];
        foreach ($skillRegistry->allSkills() as $skill) {
            $skills[] = [
                'id' => $skill->id(),
                'name' => $skill->name(),
                'description' => $skill->description(),
                'inputSchema' => $skill->inputSchema(),
                'outputSchema' => $skill->outputSchema(),
            ];
        }

        return [
            'name' => $config['name'],
            'description' => $config['description'],
            'version' => $config['version'],
            'provider' => $config['provider'],
            'documentationUrl' => $config['documentation_url'],
            'url' => $config['service_url'] ?? url('/a2a'),
            'capabilities' => $config['capabilities'],
            'authentication' => $config['authentication'],
            'skills' => $skills,
        ];
    }

    /**
     * Discover a remote agent's Agent Card.
     */
    public function discoverAgent(string $url): ?array
    {
        $response = Http::get(rtrim($url, '/').'/.well-known/agent.json');
        if ($response->ok()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Send a task to a remote agent (A2A client role).
     * $params should match A2A spec for tasks/send or tasks/sendSubscribe.
     * $method is typically 'tasks/send' or 'tasks/sendSubscribe'.
     */
    public function sendTaskToAgent(string $agentUrl, string $method, array $params, ?string $id = null, array $headers = []): ?array
    {
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $id ?? $params['id'] ?? uniqid('task-', true),
            'method' => $method,
            'params' => $params,
        ];
        $response = Http::withHeaders($headers)->post($agentUrl, $payload);
        if ($response->ok()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Handle incoming push webhook from a remote agent (to be called from a controller).
     * This is a stub; actual implementation should verify token, update Task, fire events, etc.
     */
    public function handlePushWebhook(array $payload): void
    {
        // Validate payload structure
        if (! isset($payload['params']['task']['id'])) {
            return;
        }
        $taskData = $payload['params']['task'];
        $state = $payload['params']['state'] ?? null;
        $message = $payload['params']['message'] ?? null;
        $task = Task::find($taskData['id']);
        if ($task) {
            $task->fill($taskData);
            $task->save();
        } else {
            $task = Task::create($taskData);
        }

        $messageModel = null;
        if ($message) {
            // Try to find the message by content and task, fallback to null
            $messageModel = $task->messages()->where('content', $message)->latest()->first();
        }
        $timestamp = Carbon::now('UTC')->toIso8601String();
        event(new TaskStatusUpdated($task, $state, $messageModel, $timestamp));
    }
}
