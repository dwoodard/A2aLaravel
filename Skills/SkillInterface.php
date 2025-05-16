<?php

namespace Dwoodard\A2aLaravel\Skills;

use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Models\Task;

interface SkillInterface
{
    public function id(): string;

    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function outputSchema(): array;

    public function execute(Task $task, Message $inputMessage);
}
