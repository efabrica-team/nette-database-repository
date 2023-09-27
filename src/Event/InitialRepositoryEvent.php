<?php

namespace Efabrica\NetteRepository\Event;

/**
 * This event is used to filter out event subscribers for performance reasons.
 * @see RepositoryEventSubscribers::forRepository()
 */
final class InitialRepositoryEvent extends RepositoryEvent
{
    public function stopPropagation(): void
    {
    }
}
