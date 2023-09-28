<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class SoftDeleteQueryEvent extends UpdateQueryEvent
{
    /**
     * @var (SoftDeleteSubscriber&EventSubscriber)[]
     */
    protected array $subscribers = [];

    public function __construct(QueryInterface $query)
    {
        parent::__construct($query);
        foreach ($query->getEventSubscribers()->toArray() as $subscriber) {
            if ($subscriber instanceof SoftDeleteSubscriber) {
                $this->subscribers[] = $subscriber;
            }
        }
    }

    public function handle(array &$data): int
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof SoftDeleteSubscriber) {
            return $subscriber->onSoftDelete($this, $data);
        }
        return $this->query->scopeRaw()->update($data);
    }
}
