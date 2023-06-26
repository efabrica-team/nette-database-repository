<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Cast;

use Nette\Utils\Json;

class JsonEventSubscriber extends CastEventSubscriber
{
    protected function getAnnotation(): string
    {
        return '@JSON';
    }

    protected function castTo($from)
    {
        return Json::decode($from, Json::FORCE_ARRAY);
    }

    protected function castFrom($to)
    {
        return Json::encode($to);
    }
}
