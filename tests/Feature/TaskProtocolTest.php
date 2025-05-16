<?php

declare(strict_types=1);

namespace Dwoodard\A2aLaravel\Tests\Feature;

use Dwoodard\A2aLaravel\Enums\TaskState;
use Dwoodard\A2aLaravel\Models\Task;
use Dwoodard\A2aLaravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskProtocolTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_serializes_task_with_protocol_fields()
    {
        $task = Task::create([
            'id' => 'task-abc',
            'session_id' => 'sess-1',
            'state' => TaskState::WORKING,
            'metadata' => ['foo' => 'bar'],
        ]);
        $protocol = $task->toProtocolArray();
        $this->assertEquals('task-abc', $protocol['id']);
        $this->assertEquals('sess-1', $protocol['session_id']);
        $this->assertEquals('working', $protocol['state']);
        $this->assertEquals(['foo' => 'bar'], $protocol['metadata']);
        $this->assertArrayHasKey('messages', $protocol);
        $this->assertArrayHasKey('artifacts', $protocol);
    }
}
