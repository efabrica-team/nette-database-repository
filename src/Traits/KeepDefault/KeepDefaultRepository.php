<?php

namespace Efabrica\NetteDatabaseRepository\Traits\KeepDefault;

use Efabrica\NetteDatabaseRepository\Repository\Query;

interface KeepDefaultRepository
{
    public const ANNOTATION = '@KeepDefault';

    public function keepDefaultQuery(): Query;
}
