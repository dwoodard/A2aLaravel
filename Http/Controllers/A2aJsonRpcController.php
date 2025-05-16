<?php

namespace Dwoodard\A2aLaravel\Http\Controllers;

use Dwoodard\A2aLaravel\Jobs\ExecuteSkillJob;
use Dwoodard\A2aLaravel\Services\SkillRegistry;
use Dwoodard\A2aLaravel\Services\TaskManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;

class A2aJsonRpcController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $method = $data['method'] ?? null;
        $id = $data['id'] ?? null;
        $params = $data['params'] ?? [];
        // Strict JSON-RPC version validation
        if (! isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id ?? null,
                'error' => [
                    'code' => -32600,
                    'message' => 'Invalid Request: jsonrpc version must be "2.0"',
                ],
            ], 400);
        }
        $taskManager = App::make(TaskManager::class);
        $skillRegistry = app(SkillRegistry::class);
        $isAsync = $params['async'] ?? false;

        try {
            // Validate required params for tasks/send
            if ($method === 'tasks/send') {
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
            }

            switch ($method) {
                case 'tasks/send':
                    // If task exists, treat as continuation; else, create new
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
                    // Skill dispatch
                    $skillId = $params['skill_id'] ?? ($task->metadata['skill_id'] ?? 'echo');
                    $skill = $skillRegistry->getSkillById($skillId);
                    if ($isAsync) {
                        // Dispatch to queue
                        ExecuteSkillJob::dispatch($task->id, $message->id, $skillId);

                        // Return immediately with working state
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'result' => $task->fresh(['messages', 'artifacts', 'pushNotificationConfig'])->toProtocolArray(),
                        ]);
                    } elseif ($skill) {
                        try {
                            $result = $skill->execute($task, $message);
                            $taskManager->markCompleted($task, $result);
                        } catch (\Throwable $e) {
                            $taskManager->markFailed($task, $e->getMessage());
                        }
                    } else {
                        $taskManager->markFailed($task, 'Skill not found: '.$skillId);
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $task->fresh(['messages', 'artifacts', 'pushNotificationConfig'])->toProtocolArray(),
                    ]);
                case 'tasks/get':
                    $task = $taskManager->findTask($params['id'] ?? '');
                    if (! $task) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32000,
                                'message' => 'Task not found',
                            ],
                        ], 404);
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $task->toProtocolArray(),
                    ]);
                case 'tasks/cancel':
                    $task = $taskManager->cancelTask($params['id'] ?? '');
                    if (! $task) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32000,
                                'message' => 'Task not found',
                            ],
                        ], 404);
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $task->toProtocolArray(),
                    ]);
                case 'tasks/pushNotification/set':
                    $task = $taskManager->findTask($params['id'] ?? '');
                    if (! $task) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32000,
                                'message' => 'Task not found',
                            ],
                        ], 404);
                    }
                    if (empty($params['pushConfig']['target_url'])) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32602,
                                'message' => 'Missing required pushConfig.target_url',
                            ],
                        ], 400);
                    }
                    $config = $taskManager->setPushNotificationConfig($task, $params['pushConfig']);

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $taskManager->toTaskPushNotificationConfig($task, $config),
                    ]);
                case 'tasks/pushNotification/get':
                    $task = $taskManager->findTask($params['id'] ?? '');
                    if (! $task) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32000,
                                'message' => 'Task not found',
                            ],
                        ], 404);
                    }
                    $config = $taskManager->getPushNotificationConfig($task);
                    if (! $config) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => -32001,
                                'message' => 'No push notification config set',
                            ],
                        ], 404);
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $taskManager->toTaskPushNotificationConfig($task, $config),
                    ]);
                default:
                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => [
                            'code' => -32601,
                            'message' => 'Method not found',
                        ],
                    ], 400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32603,
                    'message' => 'Internal error',
                    'data' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
