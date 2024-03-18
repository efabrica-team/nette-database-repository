<?php

namespace Efabrica\NetteRepository\Event;

class UpdateEventResponse extends RepositoryEventResponse
{
    private int $affectedRows;

    public function __construct(UpdateQueryEvent $event, int $affectedRows)
    {
        parent::__construct($event);
        $this->affectedRows = $affectedRows;
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }
}
