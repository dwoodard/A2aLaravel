<?php

namespace Dwoodard\A2aLaravel\Http\Controllers;

use Dwoodard\A2aLaravel\Enums\TaskState;
use Dwoodard\A2aLaravel\Jobs\ExecuteSkillJob;
use Dwoodard\A2aLaravel\Services\SkillRegistry;
use Dwoodard\A2aLaravel\Services\TaskManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\StreamedResponse;

class A2aSseController extends Controller
{
    public function sendSubscribe(Request $request)
    {
        $data = $request->json()->all();
        $id = $data['id'] ?? null;
        $params = $data['params'] ?? [];
        $taskManager = App::make(TaskManager::class);
        $skillRegistry = App::make(SkillRegistry::class);
        $isAsync = true;

        return new StreamedResponse(function () use ($params, $taskManager, $id) {
            ob_implicit_flush(true);
            // Create or continue task
            $task = $taskManager->findTask($params['id'] ?? '');
            if ($task) {
                $message = $taskManager->addMessage($task, [
                    'role' => 'user',
                    'content' => $params['message'] ?? '',
                ]);
                $taskManager->markWorking($task);
            } else {
                $task = $taskManager->createTask($params);
                $message = $task->messages()->latest()->first();
                $taskManager->markWorking($task);
            }
            $skillId = $params['skill_id'] ?? ($task->metadata['skill_id'] ?? 'echo');
            // Dispatch to queue
            ExecuteSkillJob::dispatch($task->id, $message->id, $skillId);
            // Stream events (polling for demo; in production, use event broadcasting)
            $lastState = null;
            $maxWait = 30; // seconds
            $start = time();
            while (true) {
                $task->refresh();
                if ($task->state !== $lastState) {
                    $event = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $task->toProtocolArray(),
                        'event' => 'state',
                        'state' => $task->state instanceof TaskState ? $task->state->value : $task->state,
                    ];
                    echo 'data: '.json_encode($event)."\n\n";
                    $lastState = $task->state;
                }
                if (in_array($task->state instanceof TaskState ? $task->state->value : $task->state, [TaskState::COMPLETED->value, TaskState::FAILED->value, TaskState::CANCELED->value])) {
                    $event = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $task->toProtocolArray(),
                        'event' => 'final',
                        'final' => true,
                    ];
                    echo 'data: '.json_encode($event)."\n\n";
                    break;
                }
                if ((time() - $start) > $maxWait) {
                    break;
                }
                usleep(500000); // 0.5s
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    public function resubscribe(Request $request)
    {
        // For demo, just call sendSubscribe logic (in production, resume from last event)
        return $this->sendSubscribe($request);
    }
}
