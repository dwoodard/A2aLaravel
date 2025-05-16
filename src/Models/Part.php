<?php

namespace Dwoodard\A2aLaravel\Models;

/**
 * Represents a single Part of a Message or Artifact (e.g., text, file, data).
 * This is a value object, not an Eloquent model.
 */
class Part
{
    public string $type;

    public array $data;

    public function __construct(string $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public static function fromArray(array $arr): self
    {
        return match ($arr['type'] ?? null) {
            'text' => new TextPart($arr['text'] ?? '', $arr['metadata'] ?? null),
            'file' => new FilePart($arr['file'] ?? [], $arr['metadata'] ?? null),
            'data' => new DataPart($arr['data'] ?? [], $arr['metadata'] ?? null),
            default => new self($arr['type'] ?? 'unknown', $arr),
        };
    }

    public function toArray(): array
    {
        return array_merge(['type' => $this->type], $this->data);
    }

    public function toProtocolArray(): array
    {
        // Default fallback, subclasses override for protocol compliance
        return array_merge(['type' => $this->type], $this->data, ['metadata' => $this->data['metadata'] ?? null]);
    }
}
