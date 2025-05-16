<?php

namespace Dwoodard\A2aLaravel\Enums;

/**
 * Enum representing the possible lifecycle states of a Task (A2A protocol 6.3).
 */
enum TaskState: string
{
    case SUBMITTED = 'submitted'; // Task received by the server and acknowledged, but processing has not yet actively started.
    case WORKING = 'working'; // Task is actively being processed by the agent.
    case INPUT_REQUIRED = 'input-required'; // Agent requires additional input from the client/user to proceed.
    case COMPLETED = 'completed'; // Task finished successfully.
    case CANCELED = 'canceled'; // Task was canceled.
    case FAILED = 'failed'; // Task terminated due to an error during processing.
    case UNKNOWN = 'unknown'; // The state of the task cannot be determined (invalid/expired ID).

    /**
     * Returns true if this state is terminal (no further updates expected).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELED,
            self::FAILED,
            self::UNKNOWN,
        ], true);
    }
}
