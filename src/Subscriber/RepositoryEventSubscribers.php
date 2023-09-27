<?php

namespace Efabrica\NetteRepository\Subscriber;

use ArrayIterator;
use Efabrica\NetteRepository\Event\InitialRepositoryEvent;
use Efabrica\NetteRepository\Repository\Repository;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<EventSubscriber>
 * @immutable
 */
final class RepositoryEventSubscribers implements IteratorAggregate
{
    /**
     * @var array<class-string<EventSubscriber>, EventSubscriber>
     */
    private array $subscribers = [];

    public function __construct(EventSubscriber ...$subscribers)
    {
        foreach ($subscribers as $subscriber) {
            $this->subscribers[get_class($subscriber)] = $subscriber;
        }
    }

    public function forRepository(Repository $repository): self
    {
        $events = clone $this;
        $repository = $repository->scopeFull();
        $event = new InitialRepositoryEvent($repository);
        foreach ($events->subscribers as $key => $subscriber) {
            if (!$subscriber->supportsEvent($event)) {
                unset($events->subscribers[$key]);
            }
        }
        return $events;
    }

    /**
     * @return Traversable<EventSubscriber>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->subscribers);
    }

    /**
     * @return EventSubscriber[]
     */
    public function toArray(): array
    {
        return $this->subscribers;
    }

    /**
     * @template E of EventSubscriber
     * @param class-string<E> $eventClass
     * @return E|null
     */
    public function get(string $eventClass): ?EventSubscriber
    {
        $subscriber = $this->subscribers[$eventClass] ?? null;
        assert($subscriber instanceof $eventClass || $subscriber === null);
        return $subscriber;
    }
}
