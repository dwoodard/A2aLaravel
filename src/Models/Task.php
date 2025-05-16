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
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'state' => $this->state?->value ?? 'unknown',
            'metadata' => $this->metadata,
            'messages' => $this->messages->map(fn ($m) => method_exists($m, 'toProtocolArray') ? $m->toProtocolArray() : $m->toArray()),
            'artifacts' => $this->artifacts->map(fn ($a) => method_exists($a, 'toProtocolArray') ? $a->toProtocolArray() : $a->toArray()),
            'pushNotificationConfig' => $this->pushNotificationConfig,
        ];
    }
}
