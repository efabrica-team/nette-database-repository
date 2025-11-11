<?php

namespace Efabrica\NetteRepository\Event;

use Nette\Database\Table\ActiveRow;

final class InsertEventResponse extends RepositoryEventResponse
{
    /**
     * @param RepositoryEvent    $event
     * @param bool|int|ActiveRow $return
     */
    public function __construct(RepositoryEvent $event, private $return)
    {
        parent::__construct($event);
    }

    /**
     * @return bool|int|ActiveRow
     */
    public function getReturn()
    {
        return $this->return;
    }
}
