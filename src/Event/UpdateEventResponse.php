<?php

namespace Efabrica\NetteRepository\Event;

class UpdateEventResponse extends RepositoryEventResponse
{
    public function __construct(UpdateQueryEvent $event, private readonly int $affectedRows)
    {
        parent::__construct($event);
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
