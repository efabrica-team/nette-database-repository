<?php

namespace Efabrica\NetteDatabaseRepository\Selections\Factories;

use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Nette\DI\Container;
use RuntimeException;

final class ClassSelectionFactory implements SelectionFactoryInterface
{
    private Container $container;

    /**
     * @var class-string<Selection>
     */
    private string $selectionClass;

    public function __construct(Container $container, string $selectionClass)
    {
        if (!is_a($selectionClass, Selection::class, true)) {
            throw new RuntimeException('Selection class must be class string of "' . Selection::class . '" but "' . $selectionClass . '" given');
        }

        $this->container = $container;
        $this->selectionClass = $selectionClass;
    }

    public function create(string $tableName): Selection
    {
        /** @var Selection $selection */
        $selection = $this->container->createInstance($this->selectionClass, ['tableName' => $tableName]);
        return $selection;
    }
}
