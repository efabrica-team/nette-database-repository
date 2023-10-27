<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

use Efabrica\NetteRepository\Repository\Query;

interface RelatedThroughEventSubscriber
{
    public function onSetRelated(SetRelatedThroughRepositoryEvent $event): int;

    public function onGetRelated(GetRelatedThroughQueryEvent $event): Query;
}
