<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Arrays;
use Traversable;

class SelectionQuery extends Selection
{
    private Query $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
        $repository = $query->getRepository();
        parent::__construct($repository->getExplorer(), $repository->getExplorer()->getConventions(), $repository->getTableName());
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return bool|int|ActiveRow
     */
    public function insert(iterable $data)
    {
        if (!$this->query->doesEvents()) {
            return parent::insert($data);
        }
        $repository = $this->query->getRepository();
        if ($data instanceof Entity) {
            $data = [$data];
        } else {
            $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
            if (Arrays::isList($data)) {
                $data = array_map(fn ($item) => $item instanceof Entity ? $item : $repository->toEntity($item), $data);
            }
        }
        return (new InsertRepositoryEvent($repository, $data))->handle()->getReturn();
    }

    public function update(iterable $data): int
    {
        if (!$this->query->doesEvents()) {
            return parent::update($data);
        }
        $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
        return (new UpdateQueryEvent($this->getQuery()))->handle($data);
    }

    public function delete(): int
    {
        if (!$this->query->doesEvents()) {
            return parent::delete();
        }
        return (new DeleteQueryEvent($this->getQuery()))->handle();
    }

    protected function execute(): void
    {
        if ($this->rows === null && $this->query->doesEvents()) {
            (new SelectQueryEvent($this->query))->handle();
        }
        parent::execute();
    }
}
