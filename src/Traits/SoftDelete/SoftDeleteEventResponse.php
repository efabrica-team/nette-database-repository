<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\UpdateEventResponse;

class SoftDeleteEventResponse extends UpdateEventResponse
{
    public function __construct(SoftDeleteQueryEvent $event, int $affectedRows)
    {
        parent::__construct($event, $affectedRows);
    }

    public static function fromUpdate(SoftDeleteQueryEvent $event, UpdateEventResponse $response): SoftDeleteEventResponse
    {
        return new self($event, $response->getAffectedRows());
    }
}
