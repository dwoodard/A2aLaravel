<?php

namespace Dwoodard\A2aLaravel\Models;

use Dwoodard\A2aLaravel\Enums\TaskState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    protected $table = 'a2a_tasks';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'session_id', 'state', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'state' => TaskState::class,
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'task_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class, 'task_id');
    }

    public function pushNotificationConfig(): HasOne
    {
        return $this->hasOne(PushNotificationConfig::class, 'task_id');
    }

    /**
     * Convert the task to a protocol-compliant array.
     */
    public function toProtocolArray(): array
    {
        $statusObject = [
            'state' => $this->state?->value ?? 'unknown',
            'timestamp' => $this->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            // 'message' is optional in TaskStatus spec and not directly available on Task model
        ];

        $data = [
            'id' => $this->id,
            'sessionId' => $this->session_id,
            'status' => $statusObject,
            'metadata' => $this->metadata,
        ];

        if ($this->relationLoaded('artifacts')) {
            $data['artifacts'] = $this->artifacts->map(fn ($artifact) => method_exists($artifact, 'toProtocolArray') ? $artifact->toProtocolArray() : $artifact->toArray())->all();
        } else {
            $data['artifacts'] = null;
        }

        if ($this->relationLoaded('messages')) {
            $data['history'] = $this->messages->map(fn ($message) => method_exists($message, 'toProtocolArray') ? $message->toProtocolArray() : $message->toArray())->all();
        } else {
            $data['history'] = null;
        }

        return $data;
    }
}
