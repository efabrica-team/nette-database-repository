<?php

namespace Efabrica\NetteRepository\Traits\ManyToMany;

interface ManyToManyEventSubscriber
{
    public function onManyToMany(ManyToManyRepositoryEvent $event): int;
}
