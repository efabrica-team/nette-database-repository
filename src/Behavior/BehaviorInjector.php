<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use Nette\DI\Container;

class BehaviorInjector
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function inject(Behavior $behavior): void
    {
        $this->container->callInjects($behavior);
    }
}