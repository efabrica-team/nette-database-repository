<?php

namespace Efabrica\NetteRepository\Traits\Publish;

interface PublishInterface
{
    public function publish($entity, bool $published = true): int;

    public function hide($entity): int;
}
