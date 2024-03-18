<?php

namespace Efabrica\NetteRepository\Event;

class DeleteEventResponse extends RepositoryEventResponse
{
    private int $affectedRows;

    public function __construct(DeleteQueryEvent $event, int $affectedRows)
    {
        parent::__construct($event);
        $this->affectedRows = $affectedRows;
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
