<?php

declare(strict_types=1);

namespace Employeon\Support;

use Employeon\Contracts\DatabaseConnectionResolver;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final readonly class SessionUserManager
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    /**
     * @return array{name: string, email: string, role: string}
     */
    public function login(Request $request, string $email): array
    {
        $name = $this->nameFromEmail($email);
        $userId = $this->findOrCreateUser($email, $name);

        if ($userId !== null) {
            $this->loginGuard($userId);
        }

        $user = array_filter([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'User',
        ], fn (mixed $value): bool => $value !== null);

        $request->session()->put('employeon.user', $user);

        return $user;
    }

    public function logout(Request $request): void
    {
        $guard = Auth::guard($this->guardName());

        if ($guard instanceof StatefulGuard) {
            $guard->logout();
        }

        $request->session()->forget('employeon.user');
        $request->session()->regenerateToken();
    }

    private function findOrCreateUser(string $email, string $name): ?int
    {
        $userModel = $this->userModelClass();

        if ($email === '' || $userModel === null) {
            return null;
        }

        /** @var Model $model */
        $model = new $userModel;
        $connection = $this->connectionName();

        if ($connection !== null) {
            $model->setConnection($connection);
        }

        $table = $model->getTable();

        if (! $this->tableExists($table) || ! Schema::connection($connection)->hasColumn($table, $this->userEmailColumn())) {
            return null;
        }

        $existingId = DB::connection($connection)
            ->table($table)
            ->where($this->userEmailColumn(), $email)
            ->value($model->getKeyName());

        if (is_numeric($existingId)) {
            return (int) $existingId;
        }

        $payload = [
            $this->userEmailColumn() => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::connection($connection)->hasColumn($table, $this->userNameColumn())) {
            $payload[$this->userNameColumn()] = $name;
        }

        if (Schema::connection($connection)->hasColumn($table, 'password')) {
            $payload['password'] = Hash::make(Str::random(32));
        }

        return (int) DB::connection($connection)
            ->table($table)
            ->insertGetId($payload);
    }

    private function loginGuard(int $userId): void
    {
        if (! $this->guardUsesUserModel()) {
            return;
        }

        $guard = Auth::guard($this->guardName());

        if ($guard instanceof StatefulGuard) {
            $guard->loginUsingId($userId);
        }
    }

    private function guardUsesUserModel(): bool
    {
        $provider = config('auth.guards.'.$this->guardName().'.provider');
        $guardModel = is_string($provider) ? config('auth.providers.'.$provider.'.model') : null;
        $userModel = $this->userModelClass();

        return is_string($guardModel)
            && $userModel !== null
            && $guardModel === $userModel;
    }

    private function userModelClass(): ?string
    {
        $configuredModel = config('employeon.access.user_model');

        if (is_string($configuredModel) && class_exists($configuredModel)) {
            return $configuredModel;
        }

        $provider = config('auth.guards.'.$this->guardName().'.provider');
        $guardModel = is_string($provider) ? config('auth.providers.'.$provider.'.model') : null;

        if (is_string($guardModel) && class_exists($guardModel)) {
            return $guardModel;
        }

        return null;
    }

    private function tableExists(string $table): bool
    {
        $connection = $this->connectionName();

        if ($connection !== null && ! is_array(config('database.connections.'.$connection))) {
            return false;
        }

        return Schema::connection($connection)->hasTable($table);
    }

    private function connectionName(): ?string
    {
        return $this->databaseConnectionResolver->resolve();
    }

    private function guardName(): string
    {
        return $this->configString('employeon.access.guard', 'web');
    }

    private function userNameColumn(): string
    {
        return $this->configString('employeon.access.user_name_column', 'name');
    }

    private function userEmailColumn(): string
    {
        return $this->configString('employeon.access.user_email_column', 'email');
    }

    private function nameFromEmail(string $email): string
    {
        $name = trim(str_replace(['.', '_', '-'], ' ', strstr($email, '@', true) ?: $email));

        return $name !== '' ? ucwords($name) : 'Employeon User';
    }

    private function configString(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
