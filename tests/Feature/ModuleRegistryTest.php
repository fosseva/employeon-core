<?php

declare(strict_types=1);

use Employeon\Support\Module;
use Employeon\Support\ModuleRegistry;
use Inertia\Testing\AssertableInertia;

it('allows packages to register PHP backed modules', function (): void {
    $this->withoutVite();

    app(ModuleRegistry::class)->register(
        Module::make('documents')
            ->label('Documents')
            ->group('Admin')
            ->icon('clipboard')
            ->title('Documents')
            ->subtitle('Policy documents and employee files')
            ->stats([
                ['label' => 'Drafts', 'value' => '5'],
            ])
            ->rows([
                ['name' => 'Handbook', 'status' => 'Ready', 'due' => 'Today'],
            ])
    );

    $this->withSession([
        'employeon.user' => [
            'name' => 'Aniket Magadum',
            'email' => 'aniket@example.com',
            'role' => 'People Operations Admin',
        ],
    ]);

    $this->get('/documents')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ModuleTemplate')
            ->where('template.title', 'Documents')
            ->where('template.stats.0.label', 'Drafts')
            ->where('template.rows.0.name', 'Handbook')
        );

    expect(app(ModuleRegistry::class)->navigation())
        ->toContain([
            'label' => 'Documents',
            'href' => '/documents',
            'icon' => 'clipboard',
            'group' => 'Admin',
        ]);
});
