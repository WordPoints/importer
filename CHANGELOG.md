# Change Log for Importer

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased]

Nothing documented yet.

## [1.3.0] - 2017-10-04

### Requires

- WordPoints: 2.4+

### Added

- This change log.

### Changed

- Minified CSS is now used.
- Classes are now autoloaded.
- `wordpoints_prevent_interruptions()` is now used instead of duplicating its code.

### Removed

- Backward-compatibility code for WordPoints 2.1.

### Fixed

- Deprecated notices from `Channel`, `Module Name`, and `Module URI` extension headers.

## [1.2.1] - 2016-12-02

### Fixed

- Using the old term "hook" instead of "reaction". #12
- Prevent interruptions from causing partial imports. #14

## [1.2.0] - 2016-08-08

### Requires

- WordPoints: 2.1+

### Added

- Module channel and ID headers (to support updates through the WordPoints.org updater module.) #7

### Changed

- We now import to the new hooks API instead of the old points hooks. #8

## [1.1.0] - 2015-04-01

### Added

- Importing points settings from CubePoints to points hooks. #2
- Importing ranks from CubePoints. #1

## [1.0.0] - 2015-02-28

### Added

- Importer for points logs, user points, and excluded users from CubePoints.

[unreleased]: https://github.com/WordPoints/importer/compare/master...HEAD
[1.3.0]: https://github.com/WordPoints/importer/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/WordPoints/importer/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/WordPoints/importer/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/WordPoints/importer/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/WordPoints/importer/compare/...1.0.0
