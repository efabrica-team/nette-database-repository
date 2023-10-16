<?php

namespace Efabrica\NetteRepository\CodeGen;

class EntityProperty
{
    protected string $type;

    protected string $name;

    /**
     * @var string contains everything after the property name and native type
     */
    protected string $annotations;

    private string $nativeType;

    public function __construct(string $type, string $name, string $annotations, string $nativeType)
    {
        $this->type = $type;
        $this->name = $name;
        $this->annotations = trim($annotations);
        $this->nativeType = $nativeType;
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
