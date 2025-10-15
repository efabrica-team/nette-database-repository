# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]



## [0.5.2] - 2025-10-15
### Fixed
- fixed delete() operation to work with entities in non-event mode

## [0.5.1] - 2025-07-23
### Fixed
- wrong diff behavior when updating entities (unsavedDiff())

## [0.5.0] - 2025-03-18
### Added
- nette/database 3.2 support
- PHP8.3 required
- sorting to top/bottom
### Fixed
- Fix repository connection ensure (if callback throw)

## [0.4.4] - 2024-07-22
### Fixed
- nikic/php-parser 5 support

## [0.4.3] - 2024-04-25
### Fixed
- symfony/console 7 support

## [0.4.2] - 2024-04-10
### Fixed
- count() now defaults to '*' only if no limit or offset is set
- Repository->fetchChunked() is now typehinted too
- InsertRepositoryEvent now correctly sets Query on the reused entity.
- PHPStan lvl8

## [0.4.1] - 2024-03-19
### Fixed
- Repository->create() did not work with PHP7.4

## [0.4.0] - 2024-03-19
### Added
- Repository code gen updated
- added event responses to all events, not just some
- renamed GetRelatedThrough to GetRelated, etc.
- fixed typehints in Repository, Query and Entity
- insertOne
- createRow() is internal now, switch to create()
- Entity save() accepts $data now (optional)

## [0.3.0] - 2024-02-27
### Added
- Entity->getPrimary() and Entity->getSignature() was reimplemented and has a new optional $original parameter now

### Fixed
- updating internal data of entities (sometimes they were not updated)
- fixed event subscribers getting old entities in onUpdate when using update directly on findBy query

### Changed
- Query->whereRows() now has different use case and because of that was renamed to whereEntities()

## [0.2.1] - 2024-02-02

## [0.2.0] - 2024-02-02
### Removed
- efabrica internal extensions
### Fixed
- unsigned types in entity code generator

## [0.1.0] - 2023-12-18
### Added
- Initial version

[Unreleased]: https://github.com/efabrica-team/nette-database-repository/compare/0.5.2...main
[0.5.2]: https://github.com/efabrica-team/nette-database-repository/compare/0.5.1...0.5.2
[0.5.1]: https://github.com/efabrica-team/nette-database-repository/compare/0.5.0...0.5.1
[0.5.0]: https://github.com/efabrica-team/nette-database-repository/compare/0.4.4...0.5.0
[0.4.4]: https://github.com/efabrica-team/nette-database-repository/compare/0.4.3...0.4.4
[0.4.3]: https://github.com/efabrica-team/nette-database-repository/compare/0.4.2...0.4.3
[0.4.2]: https://github.com/efabrica-team/nette-database-repository/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/efabrica-team/nette-database-repository/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/efabrica-team/nette-database-repository/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/efabrica-team/nette-database-repository/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/efabrica-team/nette-database-repository/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/efabrica-team/nette-database-repository/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/efabrica-team/nette-database-repository/compare/...0.1.0
