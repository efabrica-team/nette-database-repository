# Nette Database Repositories

This package extends Nettes ActiveRow and Selection to add support for database repositories. It allows you to to create
custom models, selections and define repository hooks before and after database actions.

All repository write actions expect to work with a single record. When performing mass actions with selections hook
methods will not be called because records are never actually fetched from database.

## Table of contents

* [Installation](#installation)
* [Usage](#usage)
    * [Repositories](#repositories)
        * [Hooks](#hooks)
    * [Selections](#selections)
        * [Factories](#factories)
    * [Models](#models)
        * [ActiveRow](#activerow)
        * [Custom models](#custom-models)
        * [Factories](#factories-1)
        * [Casts](#casts)
    * [Behaviors](#behaviors)
    * [Ingoring hooks](#ignoring-hooks)
* [Registration](#registration)

## Installation

Via composer

    composer require efabrica/nette-database-repository

## Usage

### Repositories

Repositories only need to implement the `getTableName()` method, which returns the name of the table to be queried
against.

```php
namespace Examples\Repositories;

use Efabrica\NetteDatabaseRepository\Repositories\Repository;

class UserRepository extends Repository
{
    public function getTableName(): string
    {
        return 'users';
    }
}
```

Repository insert, update, and delete actions are run in a transaction. In case of an unexpected error
throw [RepositoryException](src/Exceptions/RepositoryException.php) which will roll back database transactions.

#### Hooks

Repositories can define methods that will be called before and after executing database actions. To define
hook action you need to add a final public method to the repository that starts with the name of the hook (beforeInsert
etc.). Hooks for the same actions are called in the order of definition (from top to bottom).

Note that select hooks are also called on aggregations (sum of columns, etc.). So the results may not include all of
the data you may expect in after select hook. **ALWAYS CHECK THAT THE REQUIRED DATA IS SET!**

You can add additional parameters to hook methods and container will try to inject them.

```php
namespace Examples\Repositories;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositories\Repository;
use Nette\Database\Table\Selection;

class UserRepository extends Repository
{
    final public function defaultConditionsActionName(Selection $selection): void
    {
        //
    }
    
    final public function beforeSelectActionName(Selection $selection): void
    {
        //
    }
    
    final public function afterSelectActionName(Selection $selection): void
    {
        //
    }
    
    final public function beforeInsertActionName(array $data): array
    {
        //
    }
    
    final public function afterInsertActionName(ActiveRow $record, array $data): void
    {
        //
    }
    
    final public function beforeUpdateActionName(ActiveRow $record, array $data): array
    {
        //
    }
    
    final public function afterUpdateActionName(ActiveRow $oldRecord, ActiveRow $newRecord, array $data): void
    {
        //
    }
    
    final public function beforeDeleteActionName(ActiveRow $record): void
    {
        //
    }
    
    final public function afterDeleteActionName(ActiveRow $record): void
    {
        //
    }
}
```

**Default parameters must remain the same as in the definition. For example, by changing `$data` to `$values`
in `beforeInsert` hook, it would be impossible to determine which parameters should be injected and which are passed
from
the code.**

### Selections

You can define your own selections for repositories. These selections can contain custom scopes or annotations. Defining
custom selection is optional. If not defined, default selection is used.

```php
namespace Examples\Selections;

use Examples\Models\User;
use Efabrica\NetteDatabaseRepository\Selections\Selection;

/**
 * @template-extends Selection<User>
 * @template-implements Iterator<int, User>
 *
 * @method bool|int|User insert(iterable $data)
 * @method User|null get(mixed $key)
 * @method User|null fetch()
 * @method User[] fetchAll()
 */
class UserSelection extends Selection
{
    public function whereHasGroup(): self
    {
        return $this->where('group_id IS NOT NULL');
    }
}
```

#### Factories

To use your own seleciton, you need to create a SelectionFactory
extending [SelectionFactoryInterface](src/Selections/Factories/SelectionFactoryInterface.php). You can
use [GeneratedFactories](https://doc.nette.org/en/dependency-injection/factory#toc-parameterized-factory). And associate
it with repository.

```php
namespace Examples\Selections\Factories;

use Examples\Selections\UsersSelection;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;

interface UserSelectionFactory extends SelectionFactoryInterface
{
    public function create(string $tableName): UserSelection;
}
```

```neon
# config.neon
services:
    userRepository:
        create: Examples\Repositories\UserRepository
        arguments:
            selectionFactory: @userSelectionFactory
            
    userSelectionFactory:
        implement: Examples\Selections\Factories\UserSelectionFactory
```

### Models

#### ActiveRow

Retrieving results via repositories will return custom ActiveRow instances. All database actions invoked on ActiveRow
will be processed with the repository associated with the target table and all repository hook actions will be executed.

#### Custom models

You can define your own model extending ActiveRow. This is useful if you want to annotate model attributes or
assign [casts](#casts) to these attributes. Defining your own models is optional. If not defined, the default ActiveRow
is used.

When assigning casts, you can use class-string and the cast is created in the background. However, if you want to pass
some attributes, you need to create cast using a factory.

```php
namespace Examples\Models;

use Efabrica\NetteDatabaseRepository\Casts\JsonArrayCast;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property int $id
 * @property string $email
 * @property array $configuration
 * @property string $email
 * @property DateTime $created_at
 * @property DateTime $updated_at
 */
class User extends ActiveRow
{
    protected function getCasts(): array
    {
        return [
            'configuration' => [
                JsonArrayCast::class,
                CustomCast::class,
            ],
            'custom_attribute' => $this->castFactory->createFromType(CustomCastWithAttributes::class, ['param' => true]),
        ];
    }
}
```

#### Factories

When you create your own model, you must also create a factory for that model. Factory must
implement [ModelFactoryInterface](src/Models/Factories/ModelFactoryInterface.php) and be added to your
ModelFactoryManager. By default, [ManualModelFactoryManager](src/Models/Managers/ManualModelFactoryManager.php) is used.
Your custom model will be used whenever records from this table are retrieved.

```php
namespace Examples\Models\Factories;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Examples\Models\User;
use Nette\Database\Table\Selection;

interface UserModelFactory extends ModelFactoryInterface
{
    public function create(array $data, Selection $table): User;
}
```

```neon
# config.neon
services:
    modelFactoryManager:
        setup:
            - addFactory('users', @userFactory)
            
    userFactory:
        implement: Examples\Models\Factories\UserModelFactory
```

#### Casts

Casts can be used to mutate ActiveRow attributes on access or setup. Cast must
implement [CastInterface](src/Casts/CastInterface.php), which contains 2 methods. "get" method is used each time the
attribute is accessed, and the `set` method is used each time the attribute is set. Both methods have the same
properties that can provide more context for the cast.

| Property   | Description                                                                                                                                                                                                                                                                                                                              |
|------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| model      | The ActiveRow instance we're working with.                                                                                                                                                                                                                                                                                               |
| key        | The name of the accessed/set property.                                                                                                                                                                                                                                                                                                   |
| value      | A return/set value that we can modify.                                                                                                                                                                                                                                                                                                   |
| attributes | When accessing a property, the attributes then contain all other uncasted properties. When setting properties, attributes will contain "future" uncasted properties, i.e. if the original attributes are `['id' => 1, 'name' => 'lorem']` and we change `name` to `ipsum`, the attributes will contain `['id' => 1, 'name' => 'ipsum']`. |

```php
namespace Efabrica\NetteDatabaseRepository\Casts;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

final class JsonArrayCast implements CastInterface
{
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

    public function set(ActiveRow $model, string $key, $value, array $attributes): ?string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return $value;
    }
}
```

### Behaviors

Behaviors are traits containing hook methods that can be easily applied to repositories.

### Ignoring hooks

Before querying database, you can define which hooks should be ignored. These hook ignores can be applided to
repositories, selections and models.

```php
use Examples\Repositories\UserRepository;

/** @var UserRepository $userRepository */
$userRepository->query()->ignoreHooks()->fetchAll(); // Ignore all hooks

$userRepository->query()->ignoreHook('defaultConditionsHookName')->fetchAll(); // Ignore single hook by its name

$userRepository->query()->ignoreHookType('defaultConditions')->fetchAll(); // Ignore all default conditions hooks

$userRepository->query()->ignoreBehavior(SoftDeleteBehavior::class)->fetchAll(); // Ignore all hooks defined in trait
$userRepository->query()->ignoreBehavior(SoftDeleteBehavior::class, 'defaultConditions')->fetchAll(); // Ignore default condition hooks defined in trait

```

## Registration

When registering repositories, you must also add it to the repositoryManager. By the
default [ManualRepositoryManager](src/Repositores/Managers/ManualRepositoryManager.php) is used and takes the name of
the table as the first parameter and repository as the second parameter.

```neon
includes:
    - %vendorDir%/efabrica/nette-database-repository/config.neon

services:
    repositoryManager:
        setup:
            - addRepository('users', @userRepository)

    modelFactoryManager:
        setup:
            - addFactory('users', @userModelFactory)

    # Repositories
    userRepository:
        create: Examples\Repositories\UserRepository
        arguments:
            selectionFactory: @userSelectionFactory

    # Selections
    userSelectionFactory:
        implement: Examples\Selections\Factories\UserSelectionFactory

    # Models
    userModelFactory:
        implement: Examples\Models\Factories\UserModelFactory
```
