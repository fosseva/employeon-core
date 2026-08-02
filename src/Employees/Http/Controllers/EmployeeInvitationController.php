<?php

declare(strict_types=1);

namespace Employeon\Employees\Http\Controllers;

use Employeon\Employees\EmployeeManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class EmployeeInvitationController
{
    public function accept(Request $request, EmployeeManager $employeeManager, string $token): RedirectResponse
    {
        $acceptedInvitation = $employeeManager->acceptInvitation($token);

        if ($acceptedInvitation === null) {
            return redirect()->route('login')->withErrors([
                'invite_token' => 'This invitation link is invalid or has already been used.',
            ]);
        }

        $guard = config('employeon.access.guard', 'web');

        if (is_string($guard) && $this->guardUsesAccessUserModel($guard)) {
            $authGuard = Auth::guard($guard);

            if ($authGuard instanceof StatefulGuard) {
                $authGuard->loginUsingId($acceptedInvitation['user_id']);
            }
        }

        $request->session()->put('employeon.user', [
            'user_id' => $acceptedInvitation['user_id'],
            'name' => $acceptedInvitation['name'],
            'email' => $acceptedInvitation['email'],
            'role' => $acceptedInvitation['role'],
        ]);

        return redirect()->route('employeon.profile');
    }

    private function guardUsesAccessUserModel(string $guard): bool
    {
        $provider = config('auth.guards.'.$guard.'.provider');
        $guardModel = is_string($provider) ? config('auth.providers.'.$provider.'.model') : null;
        $accessUserModel = config('employeon.access.user_model');

        return is_string($guardModel)
            && is_string($accessUserModel)
            && $guardModel === $accessUserModel;
    }
}
