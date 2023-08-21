<?php

namespace Efabrica\NetteRepository\Traits\Cast;

use Nette\Utils\Json;
use Nette\Utils\JsonException;

final class JsonCastBehavior extends CastBehavior
{
    public function getCastType(): string
    {
        return 'array';
    }

    /**
     * @param mixed $encoded
     * @return mixed|string
     * @throws JsonException
     */
    public function decodeFromDB($encoded)
    {
        if (is_string($encoded)) {
            $decoded = Json::decode($encoded, Json::FORCE_ARRAY);
            return is_array($decoded) ? $decoded : $encoded;
        }
        return $encoded;
    }

    /**
     * @param mixed $decoded
     * @return string|mixed
     * @throws JsonException
     */
    public function encodeForDB($decoded)
    {
        return is_array($decoded) ? Json::encode($decoded) : $decoded;
    }
}
