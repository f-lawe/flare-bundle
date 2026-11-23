# Changelog
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Flare services (`Tracer`, `Sampler`, `Sender`, recorders, etc.) are now private services of the Symfony container: they are listed by `debug:container` and can be decorated the Symfony way
- Symfony Messenger support: messages handled by `messenger:consume` are traced as jobs, in subtasks, continuing the trace of the code sending them, and their errors are reported
- `collect.jobs` config option, with `ignored_messages`

### Changed
- Symfony now owns the instances of Flare services: Flare's container only holds the service builders registered by `FlareProvider::register()`

### Removed
- **Breaking:** the `flare.service` tag, use Symfony service decoration instead
- **Breaking:** autowiring Flare subservices (`Tracer`, `Logger`, `Reporter`, etc.), inject `Flare` and use its properties instead (`$flare->tracer`, `$flare->log()`, etc.)

## [3.3.0] - 2026-11-17

### Added
- [Sampling](https://flareapp.io/docs/php/performance/sampling) configuration
- [Sender](https://flareapp.io/docs/php/general/integrating-into-a-framework#senders) configuration
- Monolog integration

### Changed
- Code now complies with Symfony 8+ guidelines

## [3.2.0] - 2026-11-08

### Added
- Symfony 8 support

### Changed
- Use Symfony's container to load Flare's subservices
- Better config management

### Fixed
- Flare is now disabled when the API key is not provided

## [3.1.1] - 2026-05-26

### Added
- Allow contrib recipe

## [3.1.0] - 2026-05-26

### Added
- Support for `log`, `report`, `trace_limits` and `application` options

## [3.0.0] - 2026-05-22

### Added
- `use_defaults` config option
- Config options for `collect` and `censor` items
- Now targets Flare v3

### Fixed
- Loading the Flare API key from `.env` now works properly

## [1.0.0] - 2026-04-23

### Added
- Support trace and censor config
- Wire Flare for dependency injection

[Unreleased]: https://github.com/f-lawe/flare-bundle/compare/v3.3.0...HEAD
[3.3.0]: https://github.com/f-lawe/flare-bundle/compare/v3.2.0...v3.3.0
[3.2.0]: https://github.com/f-lawe/flare-bundle/compare/v3.1.1...v3.2.0
[3.1.1]: https://github.com/f-lawe/flare-bundle/compare/v3.1.0...v3.1.1
[3.1.0]: https://github.com/f-lawe/flare-bundle/compare/v3.0.0...v3.1.0
[3.0.0]: https://github.com/f-lawe/flare-bundle/compare/v1.0.0...v3.0.0
[1.0.0]: https://github.com/f-lawe/flare-bundle/releases/tag/v1.0.0
