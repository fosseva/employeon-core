<?php

declare(strict_types=1);

namespace Employeon\Employees\Database\Factories;

use Employeon\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Employee>
 */
final class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $displayName = "{$firstName} {$lastName}";
        $employeeNumber = 'EMP-'.fake()->unique()->numerify('######');
        $accessStatus = fake()->randomElement(['not_invited', 'invited', 'active', 'disabled']);

        return [
            'user_id' => in_array($accessStatus, ['active', 'disabled'], true) ? fake()->numberBetween(1, 5000) : null,
            'access_status' => $accessStatus,
            'invite_token' => $accessStatus === 'invited' ? Str::random(48) : null,
            'invited_at' => in_array($accessStatus, ['invited', 'active'], true) ? fake()->dateTimeBetween('-90 days', 'now') : null,
            'invite_accepted_at' => $accessStatus === 'active' ? fake()->dateTimeBetween('-60 days', 'now') : null,
            'access_disabled_at' => $accessStatus === 'disabled' ? fake()->dateTimeBetween('-45 days', 'now') : null,
            'employee_number' => $employeeNumber,
            'first_name' => $firstName,
            'middle_name' => fake()->optional(0.18)->firstName(),
            'last_name' => $lastName,
            'display_name' => $displayName,
            'work_email' => strtolower($firstName.'.'.$lastName.'.'.$employeeNumber.'@company.test'),
            'personal_email' => fake()->boolean(72) ? fake()->unique()->safeEmail() : null,
            'employment_status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'on_leave']),
            'joined_on' => fake()->boolean(90) ? fake()->dateTimeBetween('-8 years', 'now')->format('Y-m-d') : null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => ['employment_status' => 'active']);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['employment_status' => 'inactive']);
    }

    public function onLeave(): self
    {
        return $this->state(fn (): array => ['employment_status' => 'on_leave']);
    }

    public function notInvited(): self
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'access_status' => 'not_invited',
            'invite_token' => null,
            'invited_at' => null,
            'invite_accepted_at' => null,
            'access_disabled_at' => null,
        ]);
    }

    public function invited(): self
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'access_status' => 'invited',
            'invite_token' => Str::random(48),
            'invited_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'invite_accepted_at' => null,
            'access_disabled_at' => null,
        ]);
    }

    public function accessActive(): self
    {
        return $this->state(fn (): array => [
            'user_id' => fake()->numberBetween(1, 5000),
            'access_status' => 'active',
            'invite_token' => null,
            'invited_at' => now()->subDays(fake()->numberBetween(10, 90)),
            'invite_accepted_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'access_disabled_at' => null,
        ]);
    }

    public function accessDisabled(): self
    {
        return $this->state(fn (): array => [
            'user_id' => fake()->numberBetween(1, 5000),
            'access_status' => 'disabled',
            'invite_token' => null,
            'access_disabled_at' => now()->subDays(fake()->numberBetween(1, 45)),
        ]);
    }
}
