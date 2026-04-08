<?php

use App\Models\User;

test('non-admin users cannot access admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('admin users can view users list', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users')
            ->where('users.data.0.id', $admin->id)
            ->where('users.data.1.id', $user->id));
});

test('admin can update user role to trusted', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => 'admin'])->save();

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update', ['user' => $user->id]), [
            'role' => 'trusted',
        ])
        ->assertRedirect();

    expect($user->fresh()->role)->toBe('trusted');
});
