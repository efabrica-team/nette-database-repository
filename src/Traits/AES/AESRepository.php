<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AES;

interface AESRepository
{
    public function encryptedFields(): array;

    /**
     * name of sql function which returns key, e.g. foo_bar()
     */
    public function keyFunction(): string;
}
