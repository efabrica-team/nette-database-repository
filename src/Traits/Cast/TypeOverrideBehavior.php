<?php

namespace Efabrica\NetteRepository\Traits\Cast;

interface TypeOverrideBehavior
{
    /**
     * @return string[]
     */
    public function getFields(): array;

    public function getTypeOverride(): ?string;
}
