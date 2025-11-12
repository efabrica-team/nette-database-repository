<?php

namespace Efabrica\NetteRepository\CodeGen;

class EntityProperty
{
    /**
     * @var string contains everything after the property name and native type
     */
    protected string $annotations;

    public function __construct(protected string $type, protected string $name, string $annotations, private readonly string $nativeType)
    {
        $this->annotations = trim($annotations);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toString(): string
    {
        return "@property {$this->type} \${$this->name} ({$this->nativeType}) {$this->annotations}";
    }
}
