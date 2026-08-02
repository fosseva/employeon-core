<?php

declare(strict_types=1);

namespace Employeon\Attendance\Http\Controllers;

use Employeon\Attendance\AttendanceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AttendanceController
{
    public function index(Request $request, AttendanceManager $attendanceManager): Response
    {
        return Inertia::render('Attendance/Index', array_replace_recursive(
            [
                'user' => $this->userFromSession($request),
                'template' => [
                    'title' => 'Attendance',
                    'subtitle' => 'Daily presence and time tracking',
                ],
            ],
            $attendanceManager->pageData(),
        ));
    }

    public function store(Request $request, AttendanceManager $attendanceManager): RedirectResponse
    {
        $request->validate($this->rules());

        $attendanceManager->create($this->payload($request));

        return back()->with('status', 'Attendance entry created.');
    }

    public function update(Request $request, AttendanceManager $attendanceManager, int $entry): RedirectResponse
    {
        $request->validate($this->rules());

        $attendanceManager->update($entry, $this->payload($request));

        return back()->with('status', 'Attendance entry updated.');
    }

    public function destroy(AttendanceManager $attendanceManager, int $entry): RedirectResponse
    {
        $attendanceManager->delete($entry);

        return back()->with('status', 'Attendance entry deleted.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:present,absent,on_leave'],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function payload(Request $request): array
    {
        return [
            'employee_id' => $request->string('employee_id')->toString(),
            'attendance_date' => $request->string('attendance_date')->toString(),
            'status' => $request->string('status')->toString(),
            'check_in_at' => $request->string('check_in_at')->toString(),
            'check_out_at' => $request->string('check_out_at')->toString(),
            'notes' => $request->string('notes')->toString(),
        ];
    }

    /**
     * @return array{name: string, email: string, role: string}
     */
    private function userFromSession(Request $request): array
    {
        $user = $request->session()->get('employeon.user');

        if (is_array($user)) {
            return [
                'name' => $this->stringValue($user['name'] ?? null, 'Employeon Admin'),
                'email' => $this->stringValue($user['email'] ?? null, 'admin@example.com'),
                'role' => $this->stringValue($user['role'] ?? null, 'Admin'),
            ];
        }

        return [
            'name' => 'Employeon Admin',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ];
    }

    private function stringValue(mixed $value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return $default;
    }
}
