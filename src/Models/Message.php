<?php

namespace Dwoodard\A2aLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'a2a_messages';

    protected $fillable = [
        'task_id', 'role', 'parts', 'metadata', 'index',
    ];

    protected $casts = [
        'parts' => 'array',
        'metadata' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Convert the message to a protocol-compliant array.
     */
    public function toProtocolArray(): array
    {
        return [
            'role' => $this->role,
            'parts' => $this->parts,
            'metadata' => $this->metadata,
        ];
    }
}
