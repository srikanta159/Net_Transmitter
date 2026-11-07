# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-11-07

### Added
- Full compatibility and testing for PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4.
- GitHub Actions CI workflow with multi-PHP matrix testing (`.github/workflows/ci.yml`).
- Root `phpunit.xml.dist` configured for modern PHPUnit 9 / 10 / 11.
- Modern TLS encryption constants (`CRYPTO_TLS1_0`, `CRYPTO_TLS1_1`, `CRYPTO_TLS1_2`, `CRYPTO_TLS1_3`) in `NetworkStream`.
- Comprehensive documentation in `README.md` with detailed code examples and architecture overview.
- Added official `LICENSE` (LGPL-2.1) file and `.editorconfig`.
- Fallback standalone PSR-4 autoloader in test bootstrap.
- Community health files: `CONTRIBUTING.md`, `SECURITY.md`, issue and pull request templates.

### Changed
- Migrated autoloading in `composer.json` from deprecated PSR-0 to PSR-4 (`PEAR2\Net\Transmitter\` mapped to `src/PEAR2/Net/Transmitter/`).
- Modernized package name to `srikanta159/net_transmitter` with replacement alias for `pear2/net_transmitter`.
- Refactored `setUp()` in test suite to be compatible with PHPUnit 8+ (`setUp(): void` with no parameters and separate `createClient()` factory).
- Added `: void` return types across all test fixture methods.
- Exception constructors and error handlers now support `\Throwable` in addition to `\Exception`.

### Fixed
- **PHP 8.0+ `stream_select()` fatal ValueError**: Fixed `isDataAwaiting()` and `isAcceptingData()` in `Stream` so that `$tv_usec` is passed as `null` when `$sTimeout === null`, preventing fatal errors on every blocking send/receive.
- **PHP 8.1+ ReturnTypeWillChange**: Added `#[\ReturnTypeWillChange]` attributes on `FilterCollection` methods implementing `SeekableIterator` and `Countable` to suppress deprecation notices.
- **PHP 8.0+ Undefined Constant in `setCrypto`**: Added `defined()` checks before accessing `constant()` for crypto methods.
- **Safe Socket Peer Resolution**: Added defensive checks and string boundaries in `TcpServerConnection` for client peer IP and port resolution.

### Removed
- Removed defunct `.travis.yml` and `.scrutinizer.yml`.
- Removed legacy alpha/beta release text files.
- Removed redundant plain `README` file in favor of `README.md`.

---

## [1.0.0b2] - 2017-05-13
- Packaging fix and regeneration of package.xml.

## [1.0.0b1] - 2015-02-14
- Beta release with persistent connection enhancements and stream filter collection.
