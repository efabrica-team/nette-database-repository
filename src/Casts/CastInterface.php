<?php

namespace Efabrica\NetteDatabaseRepository\Casts;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

interface CastInterface
{
    /**
     * @param ActiveRow $model Instance of ActiveRow that we are working with.
     * @param string $key Name of accessed/set property.
     * @param mixed $value Value that will be returned that we can modify.
     * @param array $attributes All other attributes of ActiveRow.
     *
     * @return mixed Value that will be returned.
     */
    public function get(ActiveRow $model, string $key, $value, array $attributes);

    /**
     * @param ActiveRow $model Instance of ActiveRow that we are working with.
     * @param string $key Name of accessed/set property.
     * @param mixed $value Value that will be set that we can modify.
     * @param array $attributes It contains all other attributes that will be set.
     *
     * @return mixed Value that will be set.
     */
    public function set(ActiveRow $model, string $key, $value, array $attributes);
}
