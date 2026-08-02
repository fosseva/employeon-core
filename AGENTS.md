# Employeon Agent Guide

## Persistence

- Prefer Eloquent models for first-party domain tables once the table has behavior beyond simple installation or lookup plumbing.
- Avoid using the `DB` facade directly for core domain persistence. Keep raw query builder usage for framework integration boundaries, external host-app tables, schema checks, tests that assert raw database state, and genuinely ad hoc aggregate queries.
- Controllers should depend on managers/services, not facades or models directly, unless the route is intentionally a thin CRUD surface.

## Laravel Boost

- Keep Laravel Boost guidance available for this package through `resources/boost/guidelines/core.blade.php`.
- When working in an application with Laravel Boost installed, prefer Boost's version-aware Laravel documentation and inspection tools before relying on memory.
- Keep package guidance concise, actionable, and focused on Employeon conventions so consuming applications receive useful AI context without noise.
