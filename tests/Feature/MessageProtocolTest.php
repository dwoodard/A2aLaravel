<?php

declare(strict_types=1);

use Dwoodard\A2aLaravel\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serializes message with protocol fields', function () {
    $message = Message::create([
        'task_id' => 'task-xyz',
        'role' => 'user',
        'parts' => [['type' => 'text', 'text' => 'Hello']],
        'metadata' => ['foo' => 'bar'],
        'index' => 0,
    ]);
    $protocol = $message->toProtocolArray();
    expect($protocol['role'])->toBe('user');
    expect($protocol['parts'])->toBe([['type' => 'text', 'text' => 'Hello']]);
    expect($protocol['metadata'])->toBe(['foo' => 'bar']);
});

it('serializes message with empty metadata', function () {
    $message = Message::create([
        'task_id' => 'task-abc',
        'role' => 'assistant',
        'parts' => [['type' => 'text', 'text' => 'Hi']],
        'metadata' => [],
        'index' => 1,
    ]);
    $protocol = $message->toProtocolArray();
    expect($protocol['role'])->toBe('assistant');
    expect($protocol['parts'])->toBe([['type' => 'text', 'text' => 'Hi']]);
    expect($protocol['metadata'])->toBe([]);
});

it('serializes message with multiple parts', function () {
    $message = Message::create([
        'task_id' => 'task-multi',
        'role' => 'system',
        'parts' => [
            ['type' => 'text', 'text' => 'Welcome'],
            ['type' => 'image', 'url' => 'https://example.com/image.png'],
        ],
        'metadata' => ['baz' => 'qux'],
        'index' => 2,
    ]);
    $protocol = $message->toProtocolArray();
    expect($protocol['role'])->toBe('system');
    expect($protocol['parts'])->toBe([
        ['type' => 'text', 'text' => 'Welcome'],
        ['type' => 'image', 'url' => 'https://example.com/image.png'],
    ]);
    expect($protocol['metadata'])->toBe(['baz' => 'qux']);
});

it('serializes message with null metadata', function () {
    $message = Message::create([
        'task_id' => 'task-null',
        'role' => 'user',
        'parts' => [['type' => 'text', 'text' => 'Null meta']],
        'metadata' => null,
        'index' => 3,
    ]);
    $protocol = $message->toProtocolArray();
    expect($protocol['role'])->toBe('user');
    expect($protocol['parts'])->toBe([['type' => 'text', 'text' => 'Null meta']]);
    expect($protocol['metadata'])->toBeNull();
});
