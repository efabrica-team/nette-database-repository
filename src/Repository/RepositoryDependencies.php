<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Subscriber\RepositoryEvents;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Nette\Database\Explorer;
use Nette\DI\Container;

final class RepositoryDependencies
{
    private Explorer $explorer;

    private RepositoryEvents $events;

    private RepositoryManager $repositoryManager;

    public function __construct(Explorer $explorer, Container $container, RepositoryManager $repositoryManager)
    {
        $this->explorer = $explorer;

        $subscribers = [];
        foreach ($container->findByType(EventSubscriber::class) as $eventSubscriberName) {
            $eventSubscriber = $container->getService($eventSubscriberName);
            if ($eventSubscriber instanceof EventSubscriber) {
                $subscribers[] = $eventSubscriber;
            }
        }
        $this->events = new RepositoryEvents(...$subscribers);
        $this->repositoryManager = $repositoryManager;
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEvents(): RepositoryEvents
    {
        return $this->events;
    }

    public function getManager(): RepositoryManager
    {
        return $this->repositoryManager;
    }
}
