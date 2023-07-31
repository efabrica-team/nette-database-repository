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

    public function __construct(string $type, string $name, string $annotations)
    {
        $this->type = $type;
        $this->name = $name;
        $this->annotations = trim($annotations);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType($getName): void
    {
        $this->type = $getName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toString(): string
    {
        return "@property {$this->type} \${$this->name} ({$this->dbType}) {$this->annotations}";
    }
}
