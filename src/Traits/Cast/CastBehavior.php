<?php

namespace Efabrica\NetteRepository\Traits\Cast;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will automatically cast field from database to entity and back.
 * There can be multiple CastBehaviors with same class in one repository.
 */
abstract class CastBehavior extends RepositoryBehavior implements TypeOverrideBehavior
{
    private array $fields;

    public function __construct(string ...$fields)
    {
        $this->fields = $fields;
    }

    /**
     * @return string[] fields to cast
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return string used in generated entity PHPDoc
     * @example "array" or DateTimeInterface::class
     */
    abstract public function getTypeOverride(): string;

    /**
     * @param mixed $encoded value from database
     * @return mixed value for entity
     * @example JSON decode
     */
    abstract public function decodeFromDB($encoded);

    /**
     * @param mixed $decoded value in entity
     * @return mixed value for database
     * @example JSON encode
     */
    abstract public function encodeForDB($decoded);
}
