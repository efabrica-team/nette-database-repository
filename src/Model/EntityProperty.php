<?php

namespace Efabrica\NetteDatabaseRepository\Model;

class EntityProperty
{
    private string $type;

    private string $name;

    private string $annotations;

    public function __construct(array $matches)
    {
        $this->type = $matches[1];
        $this->name = $matches[2];
        $this->annotations = $matches[3];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAnnotations(): string
    {
        return $this->annotations;
    }

    public function hasAnnotation(string $annotation): bool
    {
        return str_contains($this->annotations, $annotation);
    }
}
