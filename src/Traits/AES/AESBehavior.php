<?php

namespace Efabrica\NetteRepository\Traits\AES;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class AESBehavior extends RepositoryBehavior
{
    public string $keyFunction;

    public array $encryptedFields;

    public function __construct(string $keyFunction, array $encryptedFields)
    {
        $this->keyFunction = $keyFunction;
        $this->encryptedFields = $encryptedFields;
    }

    public function keyFunction(): string
    {
        return $this->keyFunction;
    }

    public function encryptedFields(): array
    {
        return $this->encryptedFields;
    }
}
