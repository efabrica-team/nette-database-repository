<?php

namespace Examples\Behaviors;

use Nette\Database\Table\Selection;

trait IpsumBehavior
{
    final public function defaultConditionsApplyFirstIpsum(Selection $selection): void
    {
    }

    final public function defaultConditionsApplySecondIpsum(Selection $selection): void
    {
    }

    final public function beforeSelectApplyThirdIpsum(Selection $selection): void
    {
    }
}
