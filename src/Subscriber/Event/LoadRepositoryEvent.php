<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class LoadRepositoryEvent extends RepositoryEvent
{
    private Entity $entity;

    public function __construct(Repository $repository, Entity $entity)
    {
        parent::__construct($repository);
        $this->entity = $entity;
    }

    public function handle(): LoadEntityResponse
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof EventSubscriber) {
            return $subscriber->onLoad($this);
        }
        return $this->stopPropagation();
    }

    public function stopPropagation(): LoadEntityResponse
    {
        $this->subscribers = [];
        return new LoadEntityResponse($this);
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }
}
