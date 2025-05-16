<?php

namespace Dwoodard\A2aLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationConfig extends Model
{
    protected $table = 'a2a_push_notification_configs';

    protected $fillable = [
        'task_id', 'target_url', 'token', 'auth',
    ];

    protected $casts = [
        'auth' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Convert the config to a protocol-compliant array (omit secrets).
     */
    public function toProtocolArray(): array
    {
        return [
            'target_url' => $this->target_url,
            'auth' => $this->auth,
            // 'token' intentionally omitted for security
        ];
    }
}
