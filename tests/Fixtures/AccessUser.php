<?php

declare(strict_types=1);

namespace Employeon\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

final class AccessUser extends Model
{
    use Notifiable;

    protected $table = 'access_users';

    protected $guarded = [];
}
