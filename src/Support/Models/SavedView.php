<?php

declare(strict_types=1);

namespace Employeon\Support\Models;

use Illuminate\Database\Eloquent\Model;

final class SavedView extends Model
{
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'owner_id' => 'integer',
        'columns' => 'array',
        'sorts' => 'array',
    ];
}
