<?php

namespace Efabrica\NetteRepository\Traits\DefaultValue;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class DefaultValueBehavior extends RepositoryBehavior
{
    /**
     * @param mixed  $value
     */
    public function __construct(private readonly string $field, private $value)
    {
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
