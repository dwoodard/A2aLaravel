<?php

declare(strict_types=1);

namespace Dwoodard\A2aLaravel\Tests\Feature;

use Dwoodard\A2aLaravel\Models\Artifact;
use Dwoodard\A2aLaravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArtifactProtocolTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_and_serializes_protocol_compliant_artifact()
    {
        $artifact = Artifact::create([
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
        $this->assertEquals('output.txt', $protocol['name']);
        $this->assertEquals('Test artifact', $protocol['description']);
        $this->assertEquals([['type' => 'text', 'text' => 'Hello']], $protocol['parts']);
        $this->assertSame(0, $protocol['index']);
        $this->assertFalse($protocol['append']);
        $this->assertTrue($protocol['lastChunk']);
        $this->assertEquals(['foo' => 'bar'], $protocol['metadata']);
    }
}
