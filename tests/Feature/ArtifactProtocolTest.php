<?php

use Dwoodard\A2aLaravel\Models\Artifact;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
 
uses(TestCase::class);
uses(RefreshDatabase::class);


it('creates and serializes protocol compliant artifact', function () {
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
    expect($protocol['name'])->toBe('output.txt')
        ->and($protocol['description'])->toBe('Test artifact')
        ->and($protocol['parts'])->toEqual([['type' => 'text', 'text' => 'Hello']])
        ->and($protocol['index'])->toBe(0)
        ->and($protocol['append'])->toBeFalse()
        ->and($protocol['lastChunk'])->toBeTrue()
        ->and($protocol['metadata'])->toEqual(['foo' => 'bar']);
});
