<?php

declare(strict_types=1);

use Employeon\Contracts\DatabaseConnectionResolver;

it('uses the default Laravel connection by default', function (): void {
    expect(app(DatabaseConnectionResolver::class)->resolve())->toBeNull();
});

it('allows applications to provide their own database resolver', function (): void {
    app()->forgetInstance(DatabaseConnectionResolver::class);

    config()->set('employeon.database_resolver', AccessDatabaseResolver::class);

    expect(app(DatabaseConnectionResolver::class)->resolve())->toBe('acme_database');
});

final readonly class AccessDatabaseResolver implements DatabaseConnectionResolver
{
    public function resolve(): ?string
    {
        return 'acme_database';
    }
}
