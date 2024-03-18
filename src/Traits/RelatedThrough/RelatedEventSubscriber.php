<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

interface RelatedEventSubscriber
{
    public function onSetRelated(SetRelatedRepositoryEvent $event): SetRelatedEventResponse;

    public function onGetRelated(GetRelatedQueryEvent $event): GetRelatedEventResponse;
}
