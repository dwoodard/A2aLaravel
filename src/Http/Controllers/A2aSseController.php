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

        // Validate required params before streaming
        if (empty($params['id']) || empty($params['message'])) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32602,
                    'message' => 'Missing required params: id and message are required',
                ],
            ], 400);
        }

        return new StreamedResponse(function () use ($params, $taskManager, $id) {
            ob_implicit_flush(true);
            try {
                $taskId = $params['id'];
                $sessionId = $params['sessionId'] ?? null;
                $message = $params['message'];
                $pushConfig = $params['pushNotification'] ?? null;
                $metadata = $params['metadata'] ?? null;

                // If task exists, treat as continuation; else, create new
                $task = $taskManager->findTask($taskId);
                if ($task) {
                    $msg = $taskManager->addMessage($task, $message);
                    $taskManager->markWorking($task);
                    if ($pushConfig) {
                        $taskManager->setPushNotificationConfig($task, $pushConfig);
                    }
                } else {
                    $createData = [
                        'id' => $taskId,
                        'session_id' => $sessionId,
                        'message' => $message,
                        'metadata' => $metadata,
                    ];
                    $task = $taskManager->createTask($createData);
                    $msg = $task->messages()->latest()->first();
                    $taskManager->markWorking($task);
                    if ($pushConfig) {
                        $taskManager->setPushNotificationConfig($task, $pushConfig);
                    }
                }
                $skillId = $params['skill_id'] ?? ($task->metadata['skill_id'] ?? 'echo');
                // Dispatch to queue
                ExecuteSkillJob::dispatch($task->id, $msg->id, $skillId);

                // Stream events (polling for demo; in production, use event broadcasting)
                $lastState = null;
                $lastArtifactIds = collect($task->artifacts)->pluck('id')->toArray();
                $maxWait = 30; // seconds
                $start = time();
                while (true) {
                    $task->refresh();
                    $currentState = $task->state instanceof TaskState ? $task->state->value : $task->state;
                    // Status update event
                    if ($currentState !== $lastState) {
                        $event = [
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'result' => [
                                'id' => $task->id,
                                'status' => [
                                    'state' => $currentState,
                                    'metadata' => $task->metadata,
                                ],
                                'final' => in_array($currentState, [TaskState::COMPLETED->value, TaskState::FAILED->value, TaskState::CANCELED->value]),
                                'metadata' => null,
                            ],
                        ];
                        echo 'data: '.json_encode($event)."\n\n";
                        $lastState = $currentState;
                        if ($event['result']['final']) {
                            break;
                        }
                    }
                    // Artifact update event (send new artifacts)
                    $currentArtifactIds = collect($task->artifacts)->pluck('id')->toArray();
                    $newArtifactIds = array_diff($currentArtifactIds, $lastArtifactIds);
                    foreach ($newArtifactIds as $artifactId) {
                        $artifact = $task->artifacts->where('id', $artifactId)->first();
                        if ($artifact) {
                            $artifactEvent = [
                                'jsonrpc' => '2.0',
                                'id' => $id,
                                'result' => [
                                    'id' => $task->id,
                                    'artifact' => method_exists($artifact, 'toProtocolArray') ? $artifact->toProtocolArray() : $artifact->toArray(),
                                    'metadata' => null,
                                ],
                            ];
                            echo 'data: '.json_encode($artifactEvent)."\n\n";
                        }
                    }
                    $lastArtifactIds = $currentArtifactIds;
                    if ((time() - $start) > $maxWait) {
                        break;
                    }
                    usleep(500000); // 0.5s
                }
            } catch (\Throwable $e) {
                $error = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32603,
                        'message' => 'Internal error',
                        'data' => $e->getMessage(),
                    ],
                ];
                echo 'data: '.json_encode($error)."\n\n";
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
