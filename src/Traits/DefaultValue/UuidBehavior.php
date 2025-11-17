<?php

namespace Efabrica\NetteRepository\Traits\DefaultValue;

use Ramsey\Uuid\Uuid;

class UuidBehavior extends DefaultValueBehavior
{
    public function __construct(string $field)
    {
        parent::__construct($field, null);
    }

    public function getValue()
    {
        return Uuid::uuid4()->toString();
    }
}
