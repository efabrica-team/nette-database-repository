# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/efabrica-team/nette-database-repository/compare/0.3.0...main
[0.3.0]: https://github.com/efabrica-team/nette-database-repository/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/efabrica-team/nette-database-repository/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/efabrica-team/nette-database-repository/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/efabrica-team/nette-database-repository/compare/...0.1.0
