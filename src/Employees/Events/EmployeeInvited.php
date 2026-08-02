<?php

declare(strict_types=1);

namespace Employeon\Employees\Events;

final readonly class EmployeeInvited
{
    public function __construct(
        public int $employeeId,
        public ?int $userId,
        public string $email,
        public string $token,
        public string $inviteUrl,
    ) {}
}
