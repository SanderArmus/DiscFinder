<?php

use Illuminate\Support\Facades\Artisan;

test('maintenance mode shows custom 503 page', function () {
    Artisan::call('up');

    Artisan::call('down', [
        '--render' => 'errors.503',
    ]);

    try {
        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Maintenance');
    } finally {
        Artisan::call('up');
    }
});
