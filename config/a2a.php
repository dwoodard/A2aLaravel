<?php

return [
    'name' => env('A2A_AGENT_NAME', 'Laravel A2A Agent'),
    'description' => env('A2A_AGENT_DESCRIPTION', 'A2A-compliant agent built with Laravel'),
    'version' => env('A2A_AGENT_VERSION', '0.1.0'),
    'provider' => env('A2A_AGENT_PROVIDER', 'Your Company'),
    'documentation_url' => env('A2A_AGENT_DOCS_URL', null),
    'service_url' => env('A2A_AGENT_SERVICE_URL', null),
    'capabilities' => [
        'streaming' => env('A2A_AGENT_STREAMING', false),
        'pushNotifications' => env('A2A_AGENT_PUSH', false),
    ],
    'authentication' => [
        'schemes' => [], // e.g., ['ApiKey', 'OAuth2']
    ],
    'skills' => [
        'echo' => Dwoodard\A2aLaravel\Skills\EchoSkill::class,
        // Example closure skill:
        // 'reverse' => [
        //     'name' => 'Reverse',
        //     'description' => 'Reverses the user message.',
        //     'handler' => function($task, $message) { return strrev($message->content); }
        // ],
    ],
];
