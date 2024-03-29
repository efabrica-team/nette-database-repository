<?php

namespace Efabrica\NetteRepository\Event;

use Nette\Database\Table\ActiveRow;

final class InsertEventResponse extends RepositoryEventResponse
{
    /**
     * @var bool|int|ActiveRow
     */
    private $return;

    /**
     * @param RepositoryEvent    $event
     * @param bool|int|ActiveRow $return
     */
    public function __construct(RepositoryEvent $event, $return)
    {
        parent::__construct($event);
        $this->return = $return;
    }

    /**
     * @return bool|int|ActiveRow
     */
    public function getReturn()
    {
        return $this->return;
    }
}
