<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Model\EntityRelations;
use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Nette\Database\Explorer;
use Nette\DI\Container;

final class RepositoryDependencies
{
    private Explorer $explorer;

    private Events $events;

    private EntityRelations $entityRelations;

    public function __construct(Explorer $explorer, Container $container, EntityRelations $entityRelations)
    {
        $this->explorer = $explorer;
        $eventSubscribers = $container->getByType(EventSubscriber::class);
        $subscribers = [];
        foreach ($eventSubscribers as $eventSubscriberName) {
            $eventSubscriber = $container->getService($eventSubscriberName);
            if ($eventSubscriber instanceof EventSubscriber) {
                $subscribers[] = $eventSubscriber;
            }
        }
        $this->events = new Events(...$subscribers);
        $this->entityRelations = $entityRelations;
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }

    public function getEntityRelations(): EntityRelations
    {
        return $this->entityRelations;
    }
}
