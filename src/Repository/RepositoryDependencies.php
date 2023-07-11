<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Nette\Database\Explorer;
use Nette\DI\Container;

final class RepositoryDependencies
{
    private Explorer $explorer;

    private Events $events;

    public function __construct(Explorer $explorer, Container $container)
    {
        $this->explorer = $explorer;

        $subscribers = [];
        foreach ($container->findByType(EventSubscriber::class) as $eventSubscriberName) {
            $eventSubscriber = $container->getService($eventSubscriberName);
            if ($eventSubscriber instanceof EventSubscriber) {
                $subscribers[] = $eventSubscriber;
            }
        }
        $this->events = new Events(...$subscribers);
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }
}
