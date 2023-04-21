<?php

namespace Efabrica\NetteDatabaseRepository\Models\Factories;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use RuntimeException;

final class ClassModelFactory implements ModelFactoryInterface
{
    private Container $container;

    /**
     * @var class-string<ActiveRow>
     */
    private string $modelClass;

    public function __construct(Container $container, string $modelClass)
    {
        if (!is_a($modelClass, ActiveRow::class, true)) {
            throw new RuntimeException('Model class must be class string of "' . ActiveRow::class . '" but "' . $modelClass . '" given');
        }

        $this->container = $container;
        $this->modelClass = $modelClass;
    }

    public function create(array $data, Selection $table): ActiveRow
    {
        /** @var ActiveRow $activeRow */
        $activeRow = $this->container->createInstance($this->modelClass, ['data' => $data, 'table' => $table]);
        return $activeRow;
    }
}
