<?php

namespace Efabrica\NetteRepository\Traits\DefaultValue;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class DefaultValueBehavior extends RepositoryBehavior
{
    private string $field;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed  $value
     */
    public function __construct(string $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
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
