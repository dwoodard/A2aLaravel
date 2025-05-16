<?php

namespace Dwoodard\A2aLaravel\Enums;

/**
 * Enum for JSON-RPC and A2A error codes, with descriptive names.
 */
class JsonRpcErrors
{
    // JSON-RPC 2.0 standard errors
    public const PARSE_ERROR = -32700; // Parse error

    public const INVALID_REQUEST = -32600; // Invalid Request

    public const METHOD_NOT_FOUND = -32601; // Method not found

    public const INVALID_PARAMS = -32602; // Invalid params

    public const INTERNAL_ERROR = -32603; // Internal error

    // A2A / server-defined errors (-32000 to -32099)
    public const SERVER_ERROR = -32000; // Server error (generic)

    // --- Begin A2A-specific error codes ---
    public const TASK_NOT_FOUND = -32001; // Task not found

    public const TASK_NOT_CANCELABLE = -32002; // Task cannot be canceled

    public const PUSH_NOTIFICATION_NOT_SUPPORTED = -32003; // Push Notification is not supported

    public const OPERATION_NOT_SUPPORTED = -32004; // This operation is not supported

    public const CONTENT_TYPE_NOT_SUPPORTED = -32005; // Incompatible content types

    public const STREAMING_NOT_SUPPORTED = -32006; // Streaming is not supported

    public const AUTHENTICATION_REQUIRED = -32007; // Authentication required

    public const AUTHORIZATION_FAILED = -32008; // Authorization failed

    public const INVALID_TASK_STATE = -32009; // Invalid task state for operation

    public const RATE_LIMIT_EXCEEDED = -32010; // Rate limit exceeded

    public const RESOURCE_UNAVAILABLE = -32011; // A required resource is unavailable

    // --- End A2A-specific error codes ---
    public const PUSH_NOTIFICATION_NOT_SET = -32012; // No push notification config set
    // Add more as needed, with descriptive names
}
