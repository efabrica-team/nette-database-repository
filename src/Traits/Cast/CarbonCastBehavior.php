<?php

namespace Efabrica\NetteRepository\Traits\Cast;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

final class CarbonCastBehavior extends CastBehavior
{
    public function getCastType(): string
    {
        return CarbonImmutable::class;
    }

    public function decodeFromDB($encoded)
    {
        if ($encoded === null) {
            return null;
        }
        if (is_int($encoded)) {
            return CarbonImmutable::createFromTimestamp($encoded);
        }
        try {
            if (!is_string($encoded) && !$encoded instanceof DateTimeInterface) {
                return null;
            }
            return CarbonImmutable::parse($encoded);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function encodeForDB($decoded)
    {
        return $decoded;
    }
}
