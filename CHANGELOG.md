# Release Notes

## [Unreleased](https://github.com/laravel/envoy/compare/v2.8.3...2.x)

## [v2.8.3](https://github.com/laravel/envoy/compare/v2.8.2...v2.8.3) - 2022-06-07

### Fixed

- Fix hostname returned when multiple host aliases are defined by @h3xx in https://github.com/laravel/envoy/pull/250

## [v2.8.2](https://github.com/laravel/envoy/compare/v2.8.1...v2.8.2) - 2022-02-08

### Changed

- Add `@before` hook ([#242](https://github.com/laravel/envoy/pull/242)

## [v2.8.1 (2022-01-21)](https://github.com/laravel/envoy/compare/v2.8.0...v2.8.1)

### Fixed

- Fix passing `null` to `file_exists` ([50597d8](https://github.com/laravel/envoy/commit/50597d81a6be4601c62bd35417e70bc982d6d32a))

## [v2.8.0 (2022-01-12)](https://github.com/laravel/envoy/compare/v2.7.2...v2.8.0)

### Changed

- Laravel 9 Support ([#239](https://github.com/laravel/envoy/pull/239))

### Fixed

- Fix PHP 8.1 warnings ([#238](https://github.com/laravel/envoy/pull/238))

## [v2.7.2 (2021-11-09)](https://github.com/laravel/envoy/compare/v2.7.1...v2.7.2)

### Fixed

- Fix trim on windows for listed story tasks ([#236](https://github.com/laravel/envoy/pull/236))

## [v2.7.1 (2021-11-02)](https://github.com/laravel/envoy/compare/v2.7.0...v2.7.1)

### Changed

- Get value of snake case variables ([#235](https://github.com/laravel/envoy/pull/235))

## [v2.7.0 (2021-03-30)](https://github.com/laravel/envoy/compare/v2.6.0...v2.7.0)

### Added

- Microsoft Teams notifications ([#224](https://github.com/laravel/envoy/pull/224))

## [v2.6.0 (2021-03-02)](https://github.com/laravel/envoy/compare/v2.5.0...v2.6.0)

### Added

- Add success callbacks ([#219](https://github.com/laravel/envoy/pull/219))

### Changed

- Pass exit code to finished callbacks ([#220](https://github.com/laravel/envoy/pull/220))

## [v2.5.0 (2021-01-05)](https://github.com/laravel/envoy/compare/v2.4.0...v2.5.0)

### Added

- Allow hyphenated options ([#212](https://github.com/laravel/envoy/pull/212))

## [v2.4.0 (2020-11-03)](https://github.com/laravel/envoy/compare/v2.3.0...v2.4.0)

### Added

- PHP 8 Support ([#204](https://github.com/laravel/envoy/pull/204))

## [v2.3.1 (2020-08-25)](https://github.com/laravel/envoy/compare/v2.3.0...v2.3.1)

### Fixed

- Fix version ([d2cbc18](https://github.com/laravel/envoy/commit/d2cbc18f14c543b35db7805a346529ab9def1802))

## [v2.3.0 (2020-08-25)](https://github.com/laravel/envoy/compare/v2.2.0...v2.3.0)

### Added

- Laravel 8 support ([#201](https://github.com/laravel/envoy/pull/201))

## [v2.2.0 (2020-06-30)](https://github.com/laravel/envoy/compare/v2.1.0...v2.2.0)

### Added

- Guzzle 7 support ([6fd5e60](https://github.com/laravel/envoy/commit/6fd5e6013d22d99c7e0bced820ae85819564bc06))

## [v2.1.0 (2020-05-26)](https://github.com/laravel/envoy/compare/v2.0.1...v2.1.0)

### Added

- Add Telegram notifications support ([#192](https://github.com/laravel/envoy/pull/192))

## [v2.0.1 (2020-03-03)](https://github.com/laravel/envoy/compare/v2.0.0...v2.0.1)

### Fixed

- Fix a bug with story overriding arguments ([65d779c](https://github.com/laravel/envoy/commit/65d779cc1742082fecca5a1c51627f61022c3547))

## [v2.0.0 (2020-03-03)](https://github.com/laravel/envoy/compare/v1.6.5...v2.0.0)

### Changed

- Drop support for PHP 7.1 ([ccbc9e0](https://github.com/laravel/envoy/commit/ccbc9e0387dcc9eb9e24538cab4de634abab1f57))
- Drop support for Laravel 4.x and 5.x ([b80e909](https://github.com/laravel/envoy/commit/b80e909e2848255c7d55a53d0ee9d176212ae5de))
- Allow task arguments to override story ones ([#157](https://github.com/laravel/envoy/pull/157))
- Cycle through different colors for each host ([#164](https://github.com/laravel/envoy/pull/164), [7582ba3](https://github.com/laravel/envoy/commit/7582ba342f252303a0197b2808d226d6aed4423d))

### Fixed

- Fix line splitting when reading story code ([#168](https://github.com/laravel/envoy/pull/168))

## [v1.6.5 (2020-02-18)](https://github.com/laravel/envoy/compare/v1.6.4...v1.6.5)

### Fixed

- Fixed an issue with argument parsing ([#182](https://github.com/laravel/envoy/pull/182))

## [v1.6.4 (2019-11-28)](https://github.com/laravel/envoy/compare/v1.6.3...v1.6.4)

### Fixed

- Use `fromShellCommandline` for process input ([f25b2aa](https://github.com/laravel/envoy/commit/f25b2aa59e4e6f0a67adefc8e108e7a0dac678b0))

## [v1.6.3 (2019-11-28)](https://github.com/laravel/envoy/compare/v1.6.2...v1.6.3)

### Fixed

- Convert command to array format ([#174](https://github.com/laravel/envoy/pull/174))

## [v1.6.2 (2019-11-26)](https://github.com/laravel/envoy/compare/v1.6.1...v1.6.2)

### Changed

- Allow Symfony 5 ([#172](https://github.com/laravel/envoy/pull/172))

## v1.0.0 (2014-02-10)

Initial commit.
