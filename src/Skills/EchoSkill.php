<?php

namespace Dwoodard\A2aLaravel\Skills;

use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Models\Task;

class EchoSkill implements SkillInterface
{
    public function id(): string
    {
        return 'echo';
    }

    public function name(): string
    {
        return 'Echo';
    }

    public function description(): string
    {
        return 'Echoes back the user message.';
    }

    public function inputSchema(): array
    {
        return [];
    }

    public function outputSchema(): array
    {
        return [];
    }

    public function execute(Task $task, Message $inputMessage)
    {
        return $inputMessage->content;
    }
}
