<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Cast;

use Nette\Utils\Json;

class JsonCastBehavior extends CastBehavior
{
    public function getCastType(): string
    {
        return 'array';
    }

    public function decodeFromDB($encoded)
    {
        if (is_string($encoded)) {
            $decoded = Json::decode($encoded, Json::FORCE_ARRAY);
            return is_array($decoded) ? $decoded : $encoded;
        }
        return $encoded;
    }

    public function encodeForDB($decoded): string
    {
        return is_array($decoded) ? Json::encode($decoded) : $decoded;
    }
}
