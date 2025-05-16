<?php

namespace Dwoodard\A2aLaravel\Http\Controllers;

use Dwoodard\A2aLaravel\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PushWebhookController extends Controller
{
    public function __invoke(Request $request, AgentService $agentService)
    {
        $payload = $request->all();
        // Optionally: validate token here
        $agentService->handlePushWebhook($payload);

        return response()->json(['ok' => true]);
    }
}
