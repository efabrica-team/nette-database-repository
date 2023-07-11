<?php

namespace Efabrica\NetteDatabaseRepository\Event;

final class InsertEventResponse extends RepositoryEventResponse
{
    private $return;

    /**
     * @param RepositoryEvent $event
     * @param mixed           $return
     */
    public function __construct(RepositoryEvent $event, $return)
    {
        parent::__construct($event);
        $this->return = $return;
    }

    /**
     * @return mixed
     */
    public function getReturn()
    {
        return $this->return;
    }
}
