<?php

namespace Dwoodard\A2aLaravel\Models;

class DataPart extends Part
{
    public ?array $metadata = null;

    public function __construct(array $data, ?array $metadata = null)
    {
        parent::__construct('data', ['data' => $data]);
        $this->metadata = $metadata;
    }

    public function getData(): array
    {
        return $this->data['data'];
    }

    public function toProtocolArray(): array
    {
        return [
            'type' => 'data',
            'data' => $this->getData(),
            'metadata' => $this->metadata,
        ];
    }
}
