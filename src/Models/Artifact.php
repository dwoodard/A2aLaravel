<?php

namespace Dwoodard\A2aLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class Artifact extends Model
{
    protected $table = 'a2a_artifacts';

    protected $fillable = [
        'task_id', 'name', 'description', 'parts', 'index', 'append', 'lastChunk', 'metadata',
    ];

    protected $casts = [
        'parts' => 'array',
        'metadata' => 'array',
        'append' => 'boolean',
        'lastChunk' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Convert the artifact to a protocol-compliant array.
     */
    public function toProtocolArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parts' => $this->parts,
            'index' => $this->index ?? 0,
            'append' => $this->append ?? false,
            'lastChunk' => $this->lastChunk ?? false,
            'metadata' => $this->metadata,
        ];
    }
}
