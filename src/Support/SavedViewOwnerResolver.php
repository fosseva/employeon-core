<?php

declare(strict_types=1);

namespace Employeon\Support;

use Employeon\Contracts\DatabaseConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class SavedViewOwnerResolver
{
    public function __construct(
        private DatabaseConnectionResolver $databaseConnectionResolver,
    ) {}

    public function resolve(?Request $request = null): ?SavedViewOwner
    {
        $userModel = $this->userModelClass();

        if ($userModel === null) {
            return null;
        }

        $ownerId = $this->authenticatedUserId();
        $ownerId ??= $request instanceof Request ? $this->sessionUserId($request) : null;

        if ($ownerId === null || ! $this->userExists($userModel, $ownerId)) {
            return null;
        }

        return new SavedViewOwner($userModel, $ownerId);
    }

    private function authenticatedUserId(): ?int
    {
        $guardName = $this->configString('employeon.access.guard', 'web');
        $id = Auth::guard($guardName)->id();

        return is_numeric($id) ? (int) $id : null;
    }

    private function sessionUserId(Request $request): ?int
    {
        $user = $request->session()->get('employeon.user');

        if (! is_array($user) || ! is_numeric($user['user_id'] ?? null)) {
            return null;
        }

        return (int) $user['user_id'];
    }

    private function userExists(string $userModel, int $userId): bool
    {
        /** @var Model $model */
        $model = new $userModel;
        $connection = $this->connectionName();

        if ($connection !== null) {
            $model->setConnection($connection);
        }

        if (! $this->tableExists($model->getTable())) {
            return false;
        }

        return DB::connection($connection)
            ->table($model->getTable())
            ->where($model->getKeyName(), $userId)
            ->exists();
    }

    private function userModelClass(): ?string
    {
        $configuredModel = config('employeon.access.user_model');

        if (is_string($configuredModel) && class_exists($configuredModel)) {
            return $configuredModel;
        }

        $provider = config('auth.guards.'.$this->configString('employeon.access.guard', 'web').'.provider');
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

    private function configString(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
