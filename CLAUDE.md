# CLAUDE.md

Vedi **`AGENTS.md`** per le regole operative complete (branching, Definition of Done, loop locale + gate GitHub, guardrail, README didattici, design-lock).

All'avvio di ogni sessione, in quest'ordine:
1. Leggi `docs/LESSON.md` (knowledge accumulato — vale per te e per ogni subagent).
2. Leggi `docs/PROGRESS.md` (dove eravamo rimasti).
3. Leggi `docs/IMPLEMENTATION-PLAN.md` (piano completo) e `AGENTS.md` (regole).

Promemoria chiave:
- **`copilot` solo con `-p`** (altrimenti si blocca).
- **Una PR per macro-task**; sotto-task = commit locali con loop locale (test + Playwright se UI + review Copilot locale).
- **README didattici e prolissi** con molti esempi: l'accessibilità per junior è un requisito.
- Aggiorna `PROGRESS.md` ad ogni sotto-task e `LESSON.md` quando impari qualcosa.

---

# AI working guide for `padosoft/laravel-rebel-sessions`

> Working on this package with an AI agent (Claude Code, Cursor, Copilot, Codex)? Read this.
> It's the "batteries" that make vibe-coding here land on the first try. Plain Markdown — every
> tool can read it.

## What this package is
Device/session registry for Laravel Rebel: session/device tracking, logout-everywhere,
refresh-token rotation with reuse detection, and device trust.

Part of the **Laravel Rebel** suite — an enterprise authentication control plane over Laravel
Fortify. The shared language (value objects, contracts, the audit trail) lives in
`padosoft/laravel-rebel-core`; this package builds on it.

## Non-negotiable conventions
- `declare(strict_types=1);` in every PHP file; `final` classes; constructor property promotion.
- **PHPStan level max** must stay green. Do NOT add `@phpstan-ignore`, baseline entries, or
  `assert()`/inline `@var` to silence errors — fix the root cause. Common recipes:
  - narrow `mixed` before casting: `is_scalar($x) ? (string) $x : null`;
  - `json_decode($s, true)` is `array<array-key, mixed>`;
  - the container's `make('request')` is already typed `Illuminate\Http\Request`;
  - use `cursor()` for large scans, `withoutGlobalScopes()` for cross-tenant admin reads;
  - nested Eloquent `where(fn ($q) => …)` closures receive `Illuminate\Database\Eloquent\Builder`.
- **Tests:** Pest, Testbench. Cover happy path, auth/fail-closed, tenant-scoping, empty state.
- **Style:** Pint (`composer pint`). **Docs/comments in English.**
- Package wiring uses `spatie/laravel-package-tools` (`configurePackage`).

## Security & telemetry rules (suite-wide)
- Never store PII in cleartext: identifiers, IPs and User-Agents are **keyed HMACs** (core
  `KeyedHasher`). Never log OTPs/secrets (the `Redactor` sanitizes audit metadata).
- **Telemetry completeness:** if this package is a channel/driver/bridge/provider, it MUST capture
  everything that fills the admin panel (sends, **delivery receipts**, cost, country, devices,
  anomalies…). Record through the core `AuditLogger` contract — it persists to `rebel_auth_events`
  (never session) and supports **configurable sync|queue** dispatch (Horizon-ready). Skip a field
  only when the driver genuinely can't supply it, and surface an honest empty state — never fake data.

## How to extend it
- Implement the core `SessionRegistry` / `DeviceTrust` contracts (from `laravel-rebel-core`); the
  shipped implementations are `src/DatabaseSessionRegistry.php` and `src/DatabaseDeviceTrust.php`.
- Drive session lifecycle through `src/SessionManager.php` (`start`, refresh rotation, **reuse
  detection** that burns every token of the user, logout-everywhere). Add new flows here.
- Models `src/Models/RebelSession.php` and `src/Models/RebelDevice.php`; states live in
  `src/Enums/SessionStatus.php` and `src/Enums/SessionType.php` — extend these for new
  session/device states rather than adding magic strings.

## Definition of Done (per change)
1. Red→green with Pest; `composer phpstan` (max) + `composer pint -- --test` clean.
2. One feature branch, one PR to `main`. CI matrix **PHP 8.3/8.4/8.5 × Laravel 12/13** must be green.
3. Update `README.md` + `CHANGELOG.md`. Squash-merge.
4. **Release:** `git tag vX.Y.Z && git push origin vX.Y.Z` + `gh release create`. Stay in `0.1.x`
   (Composer `^0.1` excludes `0.2.0` and would break dependents).

## Skills
This repo ships invocable skills under `.claude/skills/` — at least `rebel-package-dev` (the dev
loop + PHPStan-max recipes). Invoke it before non-trivial work.
