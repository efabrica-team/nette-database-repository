<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Repository\Scope\DefaultScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Subscriber\RepositoryEvents;
use Nette\Database\Explorer;
use Nette\DI\Container;

final class RepositoryDependencies
{
    private Explorer $explorer;

    private RepositoryEvents $events;

    private RepositoryManager $repositoryManager;

    private Scope $defaultScope;

    public function __construct(Explorer $explorer, Container $container, RepositoryManager $repositoryManager, DefaultScope $defaultScope)
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
        $this->defaultScope = $defaultScope;
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

    public function getDefaultScope(): Scope
    {
        return $this->defaultScope;
    }
}
