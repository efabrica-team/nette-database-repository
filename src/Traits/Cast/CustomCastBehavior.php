<?php

namespace Efabrica\NetteRepository\Traits\Cast;

/**
 * This behavior will automatically cast field from database to entity and back based on the callbacks you supply.
 * Useful for very specific one-off use cases.
 * @see CastEventSubscriber
 */
final class CustomCastBehavior extends CastBehavior
{
    /**
     * @var callable
     */
    private $decode;

    /**
     * @var callable
     */
    private $encode;

    public function __construct(array $fields, callable $decodeFromDB, callable $encodeForDB, private readonly string $type = 'mixed')
    {
        parent::__construct(...$fields);
        $this->decode = $decodeFromDB;
        $this->encode = $encodeForDB;
    }

    public function getTypeOverride(): string
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
