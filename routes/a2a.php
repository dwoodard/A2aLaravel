<?php

use Dwoodard\A2aLaravel\Http\Controllers\PushWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/.well-known/agent.json', [\Dwoodard\A2aLaravel\Http\Controllers\AgentDiscoveryController::class, 'show']);
Route::post('/a2a', [\Dwoodard\A2aLaravel\Http\Controllers\A2aJsonRpcController::class, 'handle']);
Route::post('/a2a/subscribe', [\Dwoodard\A2aLaravel\Http\Controllers\A2aSseController::class, 'sendSubscribe']);
Route::post('/a2a/resubscribe', [\Dwoodard\A2aLaravel\Http\Controllers\A2aSseController::class, 'resubscribe']);
Route::post('/a2a/push/{taskId?}', PushWebhookController::class);
