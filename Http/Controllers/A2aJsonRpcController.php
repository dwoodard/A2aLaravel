<?php

namespace Dwoodard\A2aLaravel\Http\Controllers;

use Dwoodard\A2aLaravel\Enums\JsonRpcErrors;
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
                    'code' => JsonRpcErrors::INVALID_REQUEST,
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
                            'code' => JsonRpcErrors::INVALID_PARAMS,
                            'message' => 'Missing required params: id and message are required',
                        ],
                    ], 400);
                }
            }

            switch ($method) {
                case 'tasks/send':
                    // Accept TaskSendParams fields
                    $taskId = $params['id'];
                    $sessionId = $params['sessionId'] ?? null;
                    $message = $params['message'];
                    $pushConfig = $params['pushNotification'] ?? null;
                    $historyLength = $params['historyLength'] ?? null;
                    $metadata = $params['metadata'] ?? null;

                    // If task exists, treat as continuation; else, create new
                    $task = $taskManager->findTask($taskId);
                    if ($task) {
                        $msg = $taskManager->addMessage($task, $message);
                        $taskManager->markWorking($task);
                        // Update push notification config if provided
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
                        // Set push notification config if provided
                        if ($pushConfig) {
                            $taskManager->setPushNotificationConfig($task, $pushConfig);
                        }
                    }
                    // Skill dispatch (unchanged)
                    $skillId = $params['skill_id'] ?? ($task->metadata['skill_id'] ?? 'echo');
                    $skill = $skillRegistry->getSkillById($skillId);
                    if ($isAsync) {
                        // Dispatch to queue
                        ExecuteSkillJob::dispatch($task->id, $msg->id, $skillId);
                        // Prepare protocol array with history limit if requested
                        $taskArr = $task->fresh(['messages', 'artifacts', 'pushNotificationConfig'])->toProtocolArray();
                        if (is_int($historyLength) && $historyLength > 0) {
                            $taskArr['messages'] = collect($taskArr['messages'])->sortBy('created_at')->take(-$historyLength)->values();
                        }

                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'result' => $taskArr,
                        ]);
                    } elseif ($skill) {
                        try {
                            $result = $skill->execute($task, $msg);
                            $taskManager->markCompleted($task, $result);
                        } catch (\Throwable $e) {
                            $taskManager->markFailed($task, $e->getMessage());
                        }
                    } else {
                        $taskManager->markFailed($task, 'Skill not found: '.$skillId);
                    }
                    // Prepare protocol array with history limit if requested
                    $taskArr = $task->fresh(['messages', 'artifacts', 'pushNotificationConfig'])->toProtocolArray();
                    if (is_int($historyLength) && $historyLength > 0) {
                        $taskArr['messages'] = collect($taskArr['messages'])->sortBy('created_at')->take(-$historyLength)->values();
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $taskArr,
                    ]);
                case 'tasks/get':
                    $taskId = $params['id'] ?? '';
                    $historyLength = $params['historyLength'] ?? null;
                    $task = $taskManager->findTask($taskId);
                    if (! $task) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => JsonRpcErrors::TASK_NOT_FOUND,
                                'message' => 'Task not found',
                            ],
                        ], 404);
                    }
                    $taskArr = $task->toProtocolArray();
                    if (is_int($historyLength) && $historyLength > 0) {
                        $taskArr['messages'] = collect($taskArr['messages'])->sortBy('created_at')->take(-$historyLength)->values();
                    }

                    return response()->json([
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => $taskArr,
                    ]);
                case 'tasks/cancel':
                    $task = $taskManager->cancelTask($params['id'] ?? '');
                    if (! $task) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => JsonRpcErrors::TASK_NOT_FOUND,
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
                                'code' => JsonRpcErrors::TASK_NOT_FOUND,
                                'message' => 'Task not found',
                            ],
                        ], 404);
                    }
                    if (empty($params['pushConfig']['target_url'])) {
                        return response()->json([
                            'jsonrpc' => '2.0',
                            'id' => $id,
                            'error' => [
                                'code' => JsonRpcErrors::INVALID_PARAMS,
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
                                'code' => JsonRpcErrors::TASK_NOT_FOUND,
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
                                'code' => JsonRpcErrors::PUSH_NOTIFICATION_NOT_SET,
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
                            'code' => JsonRpcErrors::METHOD_NOT_FOUND,
                            'message' => 'Method not found',
                        ],
                    ], 400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => JsonRpcErrors::INTERNAL_ERROR,
                    'message' => 'Internal error',
                    'data' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
