<?php

namespace Efabrica\NetteRepository\Subscriber;

use Efabrica\NetteRepository\Repository\Repository;
use Generator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<EventSubscriber>
 */
final class RepositoryEvents implements IteratorAggregate
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
        foreach ($events->subscribers as $key => $subscriber) {
            if (!$subscriber->supportsRepository($repository)) {
                unset($events->subscribers[$key]);
            }
        }
        return $events;
    }

    /**
     * @param class-string<EventSubscriber> $eventClass
     * @return $this
     */
    public function removeEvent(string $eventClass): self
    {
        foreach ($this->subscribers as $key => $subscriber) {
            if ($subscriber instanceof $eventClass) {
                unset($this->subscribers[$key]);
            }
        }
        return $this;
    }

    /**
     * @return Generator<EventSubscriber>
     */
    public function getIterator(): Generator
    {
        yield from $this->subscribers;
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
