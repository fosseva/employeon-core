<?php

declare(strict_types=1);

use Employeon\Employees\Notifications\EmployeeInvitationNotification;
use Employeon\Support\DefaultDatabaseConnectionResolver;

return [
    'database_resolver' => DefaultDatabaseConnectionResolver::class,

    'access' => [
        'guard' => 'web',
        'user_model' => null,
        'user_name_column' => 'name',
        'user_email_column' => 'email',
    ],

    'employees' => [
        'table' => 'employees',
        'views_table' => 'employee_views',

        'invitations' => [
            'send_notification' => true,
            'notification' => EmployeeInvitationNotification::class,
            'url' => null,
        ],
    ],

    'attendance' => [
        'table' => 'attendance_entries',
    ],
];
