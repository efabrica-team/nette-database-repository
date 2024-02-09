# eFabrica Nette Database Repository

This extension enhances the static analysis of your Nette Database by providing typehinted entities (ActiveRows), queries (Selections) and
repositories (services).

## Installation

You can install this extension using [Composer](http://getcomposer.org/), the dependency manager for PHP:

```sh
composer require efabrica/nette-repository
```

To enable the extension, you need to register it in config:

```neon
extensions:
    netteRepo: Efabrica\NetteRepository\Bridge\EfabricaNetteRepositoryExtension
```

Finally, you can run the repository code generation command to generate the necessary classes and files:

```sh
$ php vendor/bin/enc
```

## Usage

### Entity

The `Entity` class is a subclass of ActiveRow that serves as a superclass for your entities.
You can either use our code generation tool to create it automatically or write it manually.

#### Inserting an entity

```php
assert($repository instanceof PersonRepository);
assert($entity instanceof Person);
$entity = $repository->createRow();
$entity->name = 'John';
$entity->surname = 'Doe';
$entity->age = 42;
$entity->save();
```

Classic:

```php
$person = $repository->insert([
    Person::name => 'John',
    Person::surname => 'Doe',
    Person::age => 42,
]);
```

Multi-insert:

```php
$persons = [];
foreach (range(30, 40) as $age) {
    $person = $repository->createRow();
    $person->name = 'John';
    $person->surname = 'Doe';
    $person->age = $age;
    $persons[] = $person;
}
$repository->insert($persons);
```

#### Updating an entity

```php
$entity = $repository->find($id);
$entity->name = 'Jake';
$entity->save();
```

Classic:

```php
$repository->update($id, [
    Person::name => 'Jake',
]);
```

#### Deleting an entity

```php
$entity = $repository->find($id);
$entity->delete();
```

Or:

```php
$repository->delete($id);
```

### Scopes

Scope is a class that defines which existing behaviors are disabled for the Repository, Query and Entity.

The active Scope is passed down from Repository to Query and from Query down to Entity.

`->withScope(Scope $scope)` returns a clone of the object with the given scope applied.

`->scopeRaw()` returns a clone of the object with raw scope. Raw scope removes all behaviors.

`->scopeFull()` returns a clone of the object with full scope. Full scope keeps all behaviors. 
This is the default scope, unless you change the scope in repository's setup() method.

#### Example

```php
final class AdminScope implements \Efabrica\NetteRepository\Repository\Scope\Scope
{
    public function apply(RepositoryBehaviors $behaviors, Repository $repository): void
    {
        // Remove these behaviors because they are not needed for the Admin
        $behaviors
            ->remove(SoftDeleteBehavior::class)
            ->remove(PublishBehavior::class);
        
        // Do not add any new behaviors here, because this scope can be used by different repositories
        // and you might introduce unwanted side effects.
        
        // However, you can conditionally add behaviors based on the repository type and some parameter
        // For example, if you want to apply a special behavior for admin users in the user repository, you can do this:
        if ($repository instanceof UserRepository && $someContainerParameter) { // Scopes can be services and receive parameters.
            $behaviors->add(AdminBehavior::class); // This is a hypothetical behavior, just for illustration.
        }
    }
}
```

To use the Scope as a **container service**, which may not be necessary in your case, please follow these steps:

```php
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
abstract class RepositoryBase extends \Efabrica\NetteRepository\Repository\Repository
{
    /** @inject */
    public AdminScope $adminScope;
    
    public function scopeAdmin(): self
    {
        return $this->withScope($this->adminScope);
    }
    
    // Do this if you want to set the AdminScope as default:
    protected function setup(RepositoryBehaviors $behaviors) : void 
    {
        $behaviors->setScope($this->adminScope);
    }
}
```

And **optionally** implement shorthand methods for your queries:

```php
use YourBeautifulApplication\Admin\AdminScope;

abstract class QueryBase extends \Efabrica\NetteRepository\Repository\Query
{
    public function scopeAdmin(): self
    {
        // This method returns a copy of the query with your own Admin scope applied
        // The Admin scope removes some behaviors that are not relevant for the Admin
        return $this->withScope($this->repository->adminScope);
        // Alternatively, if the Admin scope does not depend on any parameters, you can create a new instance of it like this:
        return $this->withScope(new AdminScope());
    }
}
```

Usage:

```php
// all of these are equivalent:
$repository->findBy(['age > ?' => 18])->scopeAdmin()->fetchAll();
$repository->scopeAdmin()->findBy(['age > ?' => 18])->fetchAll();
$repository->query()->where('age > ?', 18)->scopeAdmin()->fetchAll();
$repository->scopeAdmin()->query()->where('age > ?', 18)->fetchAll();
$repository->query()->scopeAdmin()->where('age > ?', 18)->fetchAll();
```

## Code Generator

Code generation is fully optional, but it is recommended to use it.

>To run the code generation, use this command:
>```sh
>$ php bin/console efabrica:nette-repo:code-gen
>```

For every table in the database, it will generate these classes in the `/Generated/` namespace: (Example: `person` table)

- `Repository\Generated\Repository\PersonRepositoryBase` - Repository base class, holds typehints for `PersonQuery` and `Person` entity. (
  abstract)
- `Repository\Generated\Query\PersonQuery` - Query class, holds typehints for `Person` entity. (abstract)
- `Repository\Generated\Entity\Person` - Entity class, holds types for columns and public constants for column names. (final)

These classes are **always regenerated** when you run the code generator. They should not be modified manually.

For every table in the database, it will also generate these classes **outside** of the `/Generated/` namespace, but only if they don't
exist. If they exist, they will not be overwritten:

- `Repository\PersonRepository` - extends `PersonRepositoryBase`. Here you write your custom repository methods. (final)
- `Repository\Query\PersonQuery` - extends `PersonQuery`. Here you write your custom query methods. (final)
- `Repository\Entity\PersonBody` - trait that is inserted into `Person` entity. Here you write your custom entity methods. (trait)

These classes are **not regenerated** when you run the code generator. They are meant to be customized by you. If you want to regenerate
them, you have to delete them first.

#### Ignoring tables

It is also possible to ignore some tables. To do that, you can modify the `ignoredTables` parameter in the config file:

```neon=
netteRepo:
    ignoreTables:
        # These are the defaultly ignored tables:
        migrations: true
        migration_log: true
        phoenix_log: true
        phinxlog: true
```

#### Custom Inheritance

If you want to set different `extends` or `implements` for a generated class, you can do that by adding an entry into your config file:

```neon
netteRepo:
    inheritance:
        AuthorRepositoryBase:
            extends: 'App\Repository\PeopleRepositoryBase'
            implements: ['App\Repository\PersonRepositoryInterface']
        AuthorQuery:
            extends: 'App\Repository\PeopleQueryBase'
        Person:
            implements: ['App\Repository\PersonInterface']
```

- Every generated class can be used for this. 
- Key is the short class name (without namespace). 
<small>Generated classes are made such that there are no namespace collisions, so this shouldn't prove a problem.</small>
- `extends` must be string or null/not specified. 
- `implements` must be string array or empty array or null/not specified. 
- You cannot unimplement an interface.

This config schema is a bit verbose, but very intuitive once you see it and easy to read.

## Behaviors and Traits

#### DateBehavior

This behavior automatically sets the `created_at` and `updated_at` columns to the current date and time when inserting or updating a row.

#### FilterBehavior

This behavior applies a default where() condition to every select query.

#### KeepDefaultBehavior

This behavior ensures that there is always at least one row with a truthy value in the default column. This is useful for flag columns.

#### SoftDeleteBehavior

This behavior marks a row as deleted by setting the `deleted_at` column to the current date and time instead of removing it from the table.

#### LastManStandingBehavior

This behavior prevents deleting the last row in the table that matches a given query.

#### TreeTraverseBehavior

This behavior manages the `lft` and `rgt` columns that represent the hierarchical structure of the table. It updates them automatically when
inserting or updating a row.

#### SortingTrait

This trait adds methods to the repository for changing the order of rows, such as moveUp(), moveDown(), insertBefore(), and insertAfter().

#### CastBehavior

This behavior automatically casts values to the specified type when retrieving them from the database.

There are some predefined casts, but you can also define your own.

- `JsonCastBehavior`: casts values from JSON to PHP array and vice versa.
- `CarbonCastBehavior`: casts values from MySQL datetime to CarbonImmutable and vice versa.

#### Events

There are several events that you can listen to in your repository: Insert, Update, Delete, Select, Load

To implement your own event subscriber, create a new class that extends `Efabrica\NetteRepository\Subscriber\EventSubscriber` and register
it in the container. It will get automatically detected, since it extends the EventSubscriber.
