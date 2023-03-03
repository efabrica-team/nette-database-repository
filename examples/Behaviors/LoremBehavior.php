<?php

namespace Examples\Behaviors;

use Nette\Database\Table\Selection;

trait LoremBehavior
{
    final public function defaultConditionsApplyFirstLorem(Selection $selection): void
    {
    }

    final public function defaultConditionsApplySecondLorem(Selection $selection): void
    {
    }

    final public function beforeSelectApplyThirdLorem(Selection $selection): void
    {
    }
}
