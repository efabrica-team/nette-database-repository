<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEventResponse;

class SetRelatedEventResponse extends RepositoryEventResponse
{
    private int $affectedRows;

    public function __construct(RepositoryEvent $event, int $affectedRows)
    {
        parent::__construct($event);
        $this->affectedRows = $affectedRows;
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
