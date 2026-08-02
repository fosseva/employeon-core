<?php

declare(strict_types=1);

namespace Employeon\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class AccessUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'access_users';

    protected $guarded = [];
}
