<?php

namespace Efabrica\NetteDatabaseRepository\Casts;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use JsonException;

final class JsonArrayCast implements CastInterface
{
    /**
     * @param mixed $value
     *
     * @throws JsonException
     */
    public function get(ActiveRow $model, string $key, $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return (array)json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        if (is_object($value)) {
            return (array)json_decode((string)json_encode($value), true);
        }
        return (array)$value;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function set(ActiveRow $model, string $key, $value, array $attributes)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value) ?: null;
        }

        return $value;
    }
}
