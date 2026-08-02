## Employeon Core

Employeon Core is a Laravel package for employee operations modules, including employee records, access invitations, attendance, access management, and reusable saved views.

### Package Structure

- First-party package code lives under the `Employeon\` namespace in `src/`.
- HTTP controllers should stay thin and delegate behavior to managers or services.
- Inertia React pages live in `resources/js/Pages`, with shared application layout in `resources/js/Layouts`.
- Migrations live in `database/migrations` and should remain clean while the package is still under active development.

### Persistence

- Prefer Eloquent models for first-party domain tables once the table has behavior beyond simple installation or lookup plumbing.
- Avoid direct `DB` facade usage for core domain persistence.
- Raw query builder usage is acceptable for framework integration boundaries, host-application tables, schema checks, tests that assert raw database state, and genuinely ad hoc aggregate queries.
- Employee persistence should go through `Employeon\Employees\Models\Employee`.
- Saved-view persistence should go through `Employeon\Support\Models\SavedView`.

### Saved Views

- Use the generic `saved_views` table for reusable view persistence.
- Scope saved views by `owner_type`, `owner_id`, and `viewable_type`.
- Employees use `viewable_type = employees`.
- Use real configured user ownership for persisted views.
- If no owner can be resolved, show default views and skip writes.
- Keep default views outside the generic saved-view manager.

### Frontend

- Keep employee saved-view UI compact and usable on mobile, tablet, and desktop.
- Saved-view tabs should scroll horizontally when they overflow.
- Keep page-level actions such as Add Employee, Import, Export, and bulk actions separate from the saved-view tab row.
- Use dialogs/popups for view settings instead of collapsible filter panels.
- Prefer clear labels over ambiguous controls, especially for filters, sort controls, and column customization.

### Testing

- Use Pest for package tests.
- For employee module work, run `./vendor/bin/pest tests/Feature/EmployeeModuleTest.php`.
- For broader changes, run `./vendor/bin/pest`, `composer analyse`, and `composer format:test`.
