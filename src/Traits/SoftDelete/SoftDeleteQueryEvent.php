<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class SoftDeleteQueryEvent extends UpdateQueryEvent
{
    /**
     * @var (SoftDeleteSubscriber&EventSubscriber)[]
     */
    protected array $subscribers = [];

    public function __construct(Query $query)
    {
        parent::__construct($query);
        foreach ($query->getEvents()->toArray() as $subscriber) {
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
        return $this->query->getRepository()->query()->scopeRaw()->update($data);
    }
}
