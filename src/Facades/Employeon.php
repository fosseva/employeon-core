<?php

namespace Employeon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Employeon\Employeon module(\Employeon\Support\Module $module)
 * @method static array modules()
 * @method static \Employeon\Employeon resource(string $handle, string $model)
 * @method static array resources()
 * @method static \Employeon\Employeon route(string $group, string $path)
 * @method static array routes()
 *
 * @see \Employeon\Employeon
 */
class Employeon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Employeon\Employeon::class;
    }
}
