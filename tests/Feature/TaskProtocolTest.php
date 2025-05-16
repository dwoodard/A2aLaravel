<?php

declare(strict_types=1);

use Dwoodard\A2aLaravel\Enums\TaskState;
use Dwoodard\A2aLaravel\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serializes task with protocol fields', function () {
    $task = Task::create([
        'id' => 'task-abc',
        'session_id' => 'sess-1',
        'state' => TaskState::WORKING,
        'metadata' => ['foo' => 'bar'],
    ]);
    $protocol = $task->toProtocolArray();
    expect($protocol['id'])->toBe('task-abc');
    expect($protocol['session_id'])->toBe('sess-1');
    expect($protocol['state'])->toBe('working');
    expect($protocol['metadata'])->toBe(['foo' => 'bar']);
    expect($protocol)->toHaveKey('messages');
    expect($protocol)->toHaveKey('artifacts');
});

it('creates and serializes protocol compliant artifact', function () {
    // Create a Task first to satisfy the foreign key constraint
    \Dwoodard\A2aLaravel\Models\Task::create([
        'id' => 'task-123',
        'session_id' => 'sess-test',
        'state' => \Dwoodard\A2aLaravel\Enums\TaskState::WORKING,
    ]);

    $artifact = \Dwoodard\A2aLaravel\Models\Artifact::create([
        'task_id' => 'task-123',
        'name' => 'output.txt',
        'description' => 'Test artifact',
        'parts' => [['type' => 'text', 'text' => 'Hello']],
        'index' => 0,
        'append' => false,
        'lastChunk' => true,
        'metadata' => ['foo' => 'bar'],
    ]);

    $protocol = $artifact->toProtocolArray();
    expect($protocol['name'])->toBe('output.txt')
        ->and($protocol['description'])->toBe('Test artifact')
        ->and($protocol['parts'])->toEqual([['type' => 'text', 'text' => 'Hello']])
        ->and($protocol['index'])->toBe(0)
        ->and($protocol['append'])->toBeFalse()
        ->and($protocol['lastChunk'])->toBeTrue()
        ->and($protocol['metadata'])->toEqual(['foo' => 'bar']);
});
