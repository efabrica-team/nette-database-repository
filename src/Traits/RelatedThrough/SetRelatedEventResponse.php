<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEventResponse;

class SetRelatedEventResponse extends RepositoryEventResponse
{
    public function __construct(RepositoryEvent $event, private readonly int $affectedRows)
    {
        parent::__construct($event);
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
