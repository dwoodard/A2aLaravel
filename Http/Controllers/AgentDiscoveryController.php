<?php

namespace Dwoodard\A2aLaravel\Http\Controllers;

use Dwoodard\A2aLaravel\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AgentDiscoveryController extends Controller
{
    public function show(): JsonResponse
    {
        $card = app(AgentService::class)->getAgentCard();

        return response()->json($card);
    }
}
