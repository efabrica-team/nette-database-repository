<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Cast;

/**
 * This behavior will automatically cast field from database to entity and back based on the callbacks you supply.
 * Useful for very specific one-off use cases.
 * @see CastEventSubscriber
 */
class CustomCastBehavior extends CastBehavior
{
    /**
     * @var callable
     */
    private $decode;

    /**
     * @var callable
     */
    private $encode;

    private string $type;

    public function __construct(array $fields, callable $decodeFromDB, callable $encodeForDB, string $type = 'mixed')
    {
        parent::__construct(...$fields);
        $this->type = $type;
        $this->decode = $decodeFromDB;
        $this->encode = $encodeForDB;
    }

    public function getCastType(): string
    {
        return $this->type;
    }

    public function decodeFromDB($encoded)
    {
        return ($this->decode)($encoded);
    }

    public function encodeForDB($decoded)
    {
        return ($this->encode)($decoded);
    }
}
