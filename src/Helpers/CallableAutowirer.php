<?php

namespace Efabrica\NetteDatabaseRepository\Helpers;

use Nette\DI\Container;

final class CallableAutowirer
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return mixed
     */
    public function callMethod(callable $callable, array $args = [])
    {
        return $this->container->callMethod($callable, $args);
    }
}
