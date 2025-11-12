<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Repository\Scope\ScopeContainer;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Subscriber\RepositoryEventSubscribers;
use Nette\Database\Explorer;
use Nette\DI\Container;

final readonly class RepositoryDependencies
{
    private RepositoryEventSubscribers $events;

    public function __construct(private Explorer $explorer, Container $container, private RepositoryManager $repositoryManager, private ScopeContainer $scopeContainer)
    {
        $subscribers = [];
        foreach ($container->findByType(EventSubscriber::class) as $eventSubscriberName) {
            $eventSubscriber = $container->getService($eventSubscriberName);
            if ($eventSubscriber instanceof EventSubscriber) {
                $subscribers[] = $eventSubscriber;
            }
        }
        $this->events = new RepositoryEventSubscribers(...$subscribers);
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEvents(): RepositoryEventSubscribers
    {
        return $this->events;
    }

    public function getManager(): RepositoryManager
    {
        return $this->repositoryManager;
    }

    public function getScopeContainer(): ScopeContainer
    {
        return $this->scopeContainer;
    }
}
