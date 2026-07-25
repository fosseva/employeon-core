# employeon-core

Employeon Core provides the application shell, shared Inertia UI, module
registration, and small extension points used by Employeon feature packages.

## Registering Modules

Feature packages can register PHP-backed modules from their service provider.
The core package supplies navigation, routing, search, layout, and the shared
module template.

```php
use Employeon\Support\Module;
use Employeon\Support\ModuleRegistry;

app(ModuleRegistry::class)->register(
    Module::make('documents')
        ->label('Documents')
        ->group('Admin')
        ->icon('clipboard')
        ->title('Documents')
        ->subtitle('Policy documents and employee files')
        ->stats([
            ['label' => 'Drafts', 'value' => '5'],
        ])
        ->rows([
            ['name' => 'Handbook', 'status' => 'Ready', 'due' => 'Today'],
        ])
);
```

This creates `/documents`, adds the sidebar item, and renders the shared React
template without the feature package writing JavaScript or CSS.

## Database Resolver

Self-hosted installs use Laravel's default connection. Managed applications can
replace the resolver:

```php
'database_resolver' => App\Support\EmployeonDatabaseResolver::class,
```

The resolver returns a connection name or `null` for Laravel's default
connection.

## Access Control

Employeon Core includes a working Roles & Permissions screen backed by
`spatie/laravel-permission`.

Run the installer and migrations in the Laravel application:

```bash
php artisan employeon:install
php artisan migrate
```

The screen at `/roles-permissions` can:

- create, update, and delete roles
- create, update, and delete permissions
- map permissions to roles
- assign roles to users

By default, users are resolved from the configured Laravel `web` guard. If the
host application uses another authenticatable model, publish the config and set:

```php
'access' => [
    'guard' => 'web',
    'user_model' => App\Models\User::class,
    'user_name_column' => 'name',
    'user_email_column' => 'email',
],
```

The configured user model should use Spatie's `HasRoles` trait so Laravel
authorization checks can read the assignments made from the Employeon screen.

For managed/cloud installs, the same access-control queries run against the
connection returned by the database resolver. Self-hosted installs can leave the
resolver as the default and use the normal Laravel database connection.

## First-Party Modules

Employees and Attendance currently live in this repository for fast iteration,
but they are structured as separable modules:

- `Employeon\Employees`
- `Employeon\Attendance`

Each module has its own manager, controller, routes, Inertia page, migrations,
and tests. The core service provider registers them for now, similar to how a
framework repository can contain components that may later become independent
packages.

The initial Employees module supports:

- employee number
- first, middle, last, and display names
- work email and personal email
- employment status
- joined date
- login access status
- invite, reset invite, disable access, unlink access, and link existing user

The initial Attendance module supports:

- daily attendance entries
- present, absent, and on-leave statuses
- check-in and check-out timestamps
- notes

The default tables are `employees` and `attendance_entries`. They can be changed
from the published `employeon.php` config.

## Employee Login Access

Employees do not authenticate directly. An employee can log in only when the
employee record is linked to a Laravel user account through `employees.user_id`.

The Employees screen can:

- invite an employee using `work_email`
- create a user account when no matching user exists
- link an employee to an existing user by email
- reset the invite token
- disable access
- unlink access

The package stores access lifecycle fields on the employee record:

- `access_status`
- `invite_token`
- `invited_at`
- `invite_accepted_at`
- `access_disabled_at`

When an employee is invited, the package now does both:

- dispatches `Employeon\Employees\Events\EmployeeInvited`
- sends `Employeon\Employees\Notifications\EmployeeInvitationNotification` when enabled

The default notification uses Laravel mail notifications, so the host
application must have mail configured. The configured user model must also be
notifiable, usually by using Laravel's `Notifiable` trait.

Invitation behavior can be configured from `employeon.php`:

```php
'employees' => [
    'invitations' => [
        'send_notification' => true,
        'notification' => Employeon\Employees\Notifications\EmployeeInvitationNotification::class,
        'url' => 'https://app.example.com/accept-invite/{token}?email={email}',
    ],
],
```

If `url` is not set, the notification uses `/employee-invitations/{token}` under
the app URL. Opening that link accepts the invite, clears the token, marks the
employee access as active, and starts the Employeon session. Host applications
can listen for the `EmployeeInvited` event when they need custom email branding,
queue behavior, password setup, or onboarding logic.
