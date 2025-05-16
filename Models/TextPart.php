<?php

namespace Dwoodard\A2aLaravel\Models;

class TextPart extends Part
{
    public ?array $metadata = null;

    public function __construct(string $text, ?array $metadata = null)
    {
        parent::__construct('text', ['text' => $text]);
        $this->metadata = $metadata;
    }

    public function getText(): string
    {
        return $this->data['text'];
    }

    public function toProtocolArray(): array
    {
        return [
            'type' => 'text',
            'text' => $this->getText(),
            'metadata' => $this->metadata,
        ];
    }
}
