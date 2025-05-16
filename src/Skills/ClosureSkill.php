<?php

namespace Dwoodard\A2aLaravel\Skills;

use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Models\Task;

class ClosureSkill implements SkillInterface
{
    protected string $id;

    protected string $name;

    protected string $description;

    protected $handler;

    protected array $inputSchema;

    protected array $outputSchema;

    public function __construct(string $id, array $config)
    {
        $this->id = $id;
        $this->name = $config['name'] ?? $id;
        $this->description = $config['description'] ?? '';
        $this->handler = $config['handler'];
        $this->inputSchema = $config['inputSchema'] ?? [];
        $this->outputSchema = $config['outputSchema'] ?? [];
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function inputSchema(): array
    {
        return $this->inputSchema;
    }

    public function outputSchema(): array
    {
        return $this->outputSchema;
    }

    public function execute(Task $task, Message $inputMessage)
    {
        return call_user_func($this->handler, $task, $inputMessage);
    }
}
