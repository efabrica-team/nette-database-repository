<?php

namespace Efabrica\NetteDatabaseRepository\Models;

use Efabrica\NetteDatabaseRepository\Casts\CastInterface;
use Efabrica\NetteDatabaseRepository\Casts\Factories\CastFactory;
use Efabrica\NetteDatabaseRepository\Helpers\HasHookIgnores;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\RepositoryManagerInterface;
use Nette\Database\Table\ActiveRow as BaseActiveRow;
use Nette\Database\Table\Selection;
use ReflectionClass;

class ActiveRow extends BaseActiveRow
{
    protected RepositoryManagerInterface $repositoryManager;

    protected CastFactory $castFactory;

    protected Selection $table;

    protected array $attributes = [];

    protected array $casts = [];

    public function __construct(RepositoryManagerInterface $repositoryManager, CastFactory $castFactory, array $data, Selection $table)
    {
        $this->repositoryManager = $repositoryManager;
        $this->castFactory = $castFactory;
        $this->table = $table;

        $this->initCasts();

        $data = $this->castDataToSet($data);
        $this->attributes = $data;

        parent::__construct($data, $table);
    }

    public function &__get(string $key)
    {
        $value = array_key_exists($key, $this->attributes) ? $this->attributes[$key] : parent::__get($key);
        return $this->castDataToGet([$key => $value])[$key];
    }

    /**
     * @param string $column
     * @param mixed $value
     *
     * @return void
     */
    public function __set($column, $value): void
    {
        $value = $this->castDataToSet([$column => $value])[$column];
        $this->attributes[$column] = $value;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key): bool
    {
        return parent::__isset($key) || isset($this->attributes[$key]);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function __unset($key): void
    {
        unset($this->attributes[$key]);
    }

    public function getData(): array
    {
        return parent::toArray();
    }

    public function toArray(): array
    {
        parent::toArray();
        return $this->castDataToGet($this->attributes);
    }

    public function update(iterable $data): bool
    {
        $repository = $this->repositoryManager->createForTable($this->table->getName());
        if ($repository === null) {
            return parent::update($data);
        }

        $repository->update($this, $data);

        return (bool)$data;
    }

    /**
     * @internal
     */
    public function originalUpdate(iterable $data): bool
    {
        return parent::update($data);
    }

    public function delete(): int
    {
        $repository = $this->repositoryManager->createForTable($this->table->getName());
        if ($repository === null) {
            return parent::delete();
        }

        return (int)$repository->delete($this);
    }

    /**
     * @internal
     */
    public function originalDelete(): int
    {
        return parent::delete();
    }

    public function castDataToGet(array $data): array
    {
        foreach ($data as $key => $value) {
            foreach ($this->getCastsByAttribute($key) as $cast) {
                $value = $cast->get($this, $key, $value, array_merge($this->attributes, $data));
            }
            $data[$key] = $value;
        }
        return $data;
    }

    public function castDataToSet(array $data): array
    {
        foreach ($data as $key => $value) {
            foreach ($this->getCastsByAttribute($key) as $cast) {
                $value = $cast->set($this, $key, $value, array_merge($this->attributes, $data));
            }
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * @return array<string, CastInterface|CastInterface[]|class-string<CastInterface>|class-string<CastInterface>[]>
     */
    protected function getCasts(): array
    {
        return [];
    }

    private function initCasts(): void
    {
        foreach ($this->getCasts() as $attribute => $casts) {
            if (!is_array($casts)) {
                $casts = [$casts];
            }

            foreach ($casts as $cast) {
                if ($cast instanceof CastInterface) {
                    $this->casts[$attribute][] = $cast;
                    continue;
                }
                $this->casts[$attribute][] = $this->castFactory->createFromType($cast);
            }
        }
    }

    /**
     * @return CastInterface[]
     */
    private function getCastsByAttribute(string $name): array
    {
        return $this->casts[$name] ?? [];
    }

    public function __debugInfo(): array
    {
        $rc = new ReflectionClass($this);

        $props = [];
        foreach ($rc->getProperties() as $prop) {
            $prop->setAccessible(true);
            $props[$prop->getName()] = $prop->getValue($this);
        }

        $props['castedAttributes'] = [];
        foreach ($this->attributes as $key => $value) {
            $props['castedAttributes'][$key] = $this->$key;
        }

        return $props;
    }
}
