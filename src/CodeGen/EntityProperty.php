<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

class EntityProperty
{
    protected string $type;

    protected string $name;

    /**
     * @var string contains everything after the property name and native type
     */
    protected string $annotations;

    private ?string $dbType;

    public function __construct(string $_, string $type, string $name, ?string $dbType, string $annotations)
    {
        $this->type = $type;
        $this->name = $name;
        $this->annotations = trim($annotations);
        $this->dbType = $dbType;
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

    public function getDbType(): ?string
    {
        return $this->dbType;
    }

    public function toString(string $originalAnnotations = ''): string
    {
        foreach (explode(' ', $originalAnnotations) as $annotation) {
            if (!str_contains($this->annotations, $annotation)) {
                $this->annotations .= ' ' . $annotation;
            }
        }
        return "@property {$this->type} \${$this->name} ({$this->dbType}) {$this->annotations}";
    }

    public function setType($getName): void
    {
        $this->type = $getName;
    }
}
