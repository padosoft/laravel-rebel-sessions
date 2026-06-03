# Changelog

All notable changes to `padosoft/laravel-rebel-sessions` are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-06-03

### Added
- **`SessionManager`**: session + refresh-token tracking with **rotation and reuse
  detection**. Each refresh belongs to a chain (`root_id`); a rotation consumes the old
  token and issues a child. Presenting a non-active, expired, or someone-else's refresh is
  rejected — and a reuse (theft) signal **burns every live token of the subject**.
  Rotations lock the chain root to serialize concurrent requests.
- **`revokeAll` / logout-everywhere** and **`isRefreshReused`** — the default
  implementation of the core `SessionRegistry` contract.
- **`DatabaseDeviceTrust`** (core `DeviceTrust`): remembered devices by fingerprint hash,
  expiring after N days, atomic upsert, tenant-scoped.
- Migrations (`rebel_sessions`, `rebel_devices`, both UUID), models, enums, config.
- CI matrix (PHP 8.3/8.4/8.5 × Laravel 12/13), Pest suite, PHPStan level max, Pint.

[Unreleased]: https://github.com/padosoft/laravel-rebel-sessions/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/padosoft/laravel-rebel-sessions/releases/tag/v0.1.0
