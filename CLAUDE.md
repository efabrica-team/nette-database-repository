# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`efabrica/nette-repository` is a PHP 8.4 library that layers a typed Repository pattern on top of Nette Database. It exists to give static analysis full type information for entities (typed `ActiveRow` subclasses), queries (typed `Selection`s) and repositories (DI services), plus a behavior/event system that hooks the database lifecycle (insert/update/delete/select/load). It is a library, not an application — there is no app to run; consumers register the DI extension and use the generated classes.

## Commands

The code generator binary is `bin/enc` (alias for the `efabrica:nette-repo:code-gen` console command). It boots the **consumer's** Nette container (`<root>/app/bootstrap.php`), so it cannot be run from within this library repo — it only runs inside a host application.

## Architecture

The three-layer core lives in `src/Repository/`:

- **`Repository<E, Q>`** (abstract) — the service. Holds an `Explorer`, table name, entity/query class-strings, a `RepositoryBehaviors` set, and `RepositoryEventSubscribers`. Subclasses implement `setup(RepositoryBehaviors $behaviors)` to register behaviors. CRUD entry points (`find`, `insert`, `update`, `delete`, `query`, …) live here.
- **`Query<E>`** (extends Nette `Selection`) — the query builder; carries the active Scope down to fetched entities.
- **`Entity`** (extends `ActiveRow`) — typed row; `save()`/`update()`/`delete()` delegate back through the repository so behaviors/events fire.

`RepositoryManager` resolves repositories by class (`byClass`) or table name (`byTableName`, via `ModuleWriter::toRepoServiceName`) and caches them. `RepositoryDependencies` is the bag of shared services (Explorer, events, scope container, manager) injected into every repository constructor.

### Behaviors + Events (the extension mechanism)

This is the heart of the library. Two cooperating pieces:

- **Behavior** (`src/Traits/*/...Behavior.php`, all extend `Efabrica\NetteRepository\Traits\RepositoryBehavior`) — a plain config-holder object registered per-repository in `setup()`. It carries *which columns / settings* a feature uses (e.g. `DateBehavior` holds the `created_at`/`updated_at` field names). It contains no logic.
- **EventSubscriber** (`src/Subscriber/EventSubscriber.php` and `src/Traits/*/...EventSubscriber.php`) — the logic. Registered as DI services (see the `loadConfiguration()` list in the Bridge extension), auto-discovered because they extend `EventSubscriber`. Each implements `supportsEvent()` to opt in (typically by checking the repository has the matching behavior) and overrides `onInsert`/`onUpdate`/`onDelete`/`onSelect`/`onLoad`.

Events (`src/Event/*`) flow as a chain: each subscriber receives the event and calls `$event->handle()` to pass to the next subscriber, or `$event->stopPropagation()` to short-circuit. Handlers return an `*EventResponse`. This is how SoftDelete rewrites a DELETE into an UPDATE, Date stamps timestamps, Filter injects WHERE clauses, Cast transforms values, etc.

**To add a feature**: create a `Trait/<Feature>/` directory with a `<Feature>Behavior` (config) + `<Feature>EventSubscriber` (logic), register the subscriber in `EfabricaNetteRepositoryExtension::loadConfiguration()`, and have repos opt in via `$behaviors->add(...)` in `setup()`. Follow an existing pair like `Traits/Date/` or `Traits/SoftDelete/` as the template.

### Scopes (`src/Repository/Scope/`)

A `Scope` selectively disables (or conditionally adds) behaviors. It propagates Repository → Query → Entity. `FullScope` (default) keeps all behaviors; `RawScope` removes all; custom scopes implement `Scope::apply(RepositoryBehaviors $behaviors, Repository $repository)`. Applied via `->withScope()`, `->scopeRaw()`, `->scopeFull()` (each returns a clone). `ScopeContainer` is the DI-registered holder.

### Code generation (`src/CodeGen/`)

For each DB table, `RepositoryCodeGenerationCommand` (using `EntityStructureFactory` + the `*Writer` classes built on `nette/php-generator` and `nikic/php-parser`) emits:

- **Always regenerated** (in `Generated/` namespace, never edit): `…Repository\Generated\Repository\<Name>RepositoryBase`, `…\Generated\Query\<Name>Query`, `…\Generated\Entity\<Name>`.
- **Generated once, then yours to edit** (outside `Generated/`, never overwritten): `<Name>Repository` (final), `Query\<Name>Query` (final), `Entity\<Name>Body` (trait mixed into the entity).

Config (the Bridge extension schema): `ignoreTables`, `tableAlias`, `inheritance` (override `extends`/`implements` of any generated class by short name), `configNeonPath`.

### DI wiring

`src/Bridge/EfabricaNetteRepositoryExtension.php` registers the manager, deps, scope container, the code-gen command + Symfony console `Application`, and every built-in event subscriber. Consumers register it as `netteRepo:` in their Nette config.

## Conventions

- PHP 8.4, strict typing, heavy use of generics in PHPDoc (`@template E of Entity`, `@template Q of Query<E>`) — keep these accurate; PHPStan level 3 depends on them.
- Entities expose column names as class constants (e.g. `Person::NAME`); prefer them over string literals in queries.
- Behaviors hold no logic; subscribers hold no per-repo config. Don't merge the two.
