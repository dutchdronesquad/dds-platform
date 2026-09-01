<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('authorized editors can render a safe markdown preview', function () {
    $editor = User::factory()->create();
    $editor->assignRole(Role::Editor->value);

    $this->actingAs($editor)
        ->postJson(route('admin.markdown-preview'), [
            'markdown' => "## Programma\n\n- Briefing\n- Vliegen\n\n<script>alert('onveilig')</script>\n\n[Onveilig](javascript:alert(1))",
        ])
        ->assertOk()
        ->assertJsonPath('html', function (string $html): bool {
            expect($html)
                ->toContain('<h2>Programma</h2>')
                ->toContain('<li>Briefing</li>')
                ->not->toContain('<script>')
                ->not->toContain('href="javascript:');

            return true;
        });
});

test('markdown previews require event or season management access', function () {
    $this->postJson(route('admin.markdown-preview'), ['markdown' => '# Preview'])
        ->assertUnauthorized();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('admin.markdown-preview'), ['markdown' => '# Preview'])
        ->assertForbidden();
});
