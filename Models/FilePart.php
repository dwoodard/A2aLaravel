<?php

namespace Dwoodard\A2aLaravel\Models;

class FileContent
{
    public ?string $name;

    public ?string $mimeType;

    public ?string $bytes;

    public ?string $uri;

    public function __construct(?string $name = null, ?string $mimeType = null, ?string $bytes = null, ?string $uri = null)
    {
        $this->name = $name;
        $this->mimeType = $mimeType;
        $this->bytes = $bytes;
        $this->uri = $uri;
    }

    public static function fromArray(array $arr): self
    {
        return new self(
            $arr['name'] ?? null,
            $arr['mimeType'] ?? null,
            $arr['bytes'] ?? null,
            $arr['uri'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'mimeType' => $this->mimeType,
            'bytes' => $this->bytes,
            'uri' => $this->uri,
        ];
    }

    public function isValid(): bool
    {
        // Constraint: exactly one of bytes or uri must be non-null if transmitting content
        $hasBytes = $this->bytes !== null;
        $hasUri = $this->uri !== null;

        return ($hasBytes xor $hasUri) || (! $hasBytes && ! $hasUri);
    }
}

class FilePart extends Part
{
    public ?array $metadata = null;

    public FileContent $file;

    public function __construct(FileContent $file, ?array $metadata = null)
    {
        parent::__construct('file', ['file' => $file->toArray()]);
        $this->file = $file;
        $this->metadata = $metadata;
    }

    public function getFile(): FileContent
    {
        return $this->file;
    }

    public function toProtocolArray(): array
    {
        return [
            'type' => 'file',
            'file' => $this->file->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
