<?php

namespace Efabrica\NetteRepository\Event;

class DeleteEventResponse extends RepositoryEventResponse
{
    public function __construct(DeleteQueryEvent $event, private readonly int $affectedRows)
    {
        parent::__construct($event);
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
