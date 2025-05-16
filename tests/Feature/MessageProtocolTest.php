<?php

declare(strict_types=1);

namespace Dwoodard\A2aLaravel\Tests\Feature;

use Dwoodard\A2aLaravel\Models\Message;
use Dwoodard\A2aLaravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessageProtocolTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_serializes_message_with_protocol_fields()
    {
        $message = Message::create([
            'task_id' => 'task-xyz',
            'role' => 'user',
            'parts' => [['type' => 'text', 'text' => 'Hello']],
            'metadata' => ['foo' => 'bar'],
            'index' => 0,
        ]);
        $protocol = $message->toProtocolArray();
        $this->assertEquals('user', $protocol['role']);
        $this->assertEquals([['type' => 'text', 'text' => 'Hello']], $protocol['parts']);
        $this->assertEquals(['foo' => 'bar'], $protocol['metadata']);
    }
}
