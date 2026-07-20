<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

it('renders the login page', function (): void {
    $this->withoutVite();

    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/Login')
        );
});
