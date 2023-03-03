<?php

namespace Tests;

trait HasEvents
{
    private array $events = [];

    /**
     * @return static
     */
    public function fireEvent(string $event): self
    {
        $this->events[] = $event;
        return $this;
    }

    public function wasEventFired(string $event): bool
    {
        return in_array($event, $this->events, true);
    }
}
