<?php

declare(strict_types=1);

require __DIR__.'/webhook.php';
require __DIR__.'/auth.php';
require __DIR__.'/app.php';

Route::get('/seed-admin', function () {
    if (\App\Models\User::where('email', 'giannismoll7@hotmail.com')->exists()) {
        return 'User already exists!';
    }
    \App\Actions\User\CreateUser::execute([
        'name' => 'Giannis',
        'email' => 'giannismoll7@hotmail.com',
        'password' => 'VfV120@@VfV120@@',
        'email_verified_at' => now(),
    ]);
    return 'Admin user created successfully!';
});
