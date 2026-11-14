<?php

use Illuminate\Support\Facades\Route;

// The home page and the public school website come from classivo/cms. Without
// that package the home page sends visitors to the admin sign-in.
if (! class_exists(\Classivo\Cms\CmsPlugin::class)) {
    Route::redirect('/', '/admin');
}

// Generic sign-in entry point (used by package routes behind the `auth`
// middleware, e.g. PDF downloads and the LMS storefront). Each portal has
// its own sign-in page; staff go to the admin panel by default.
Route::get('/login', function () {
    $intended = (string) session('url.intended', '');
    $path = parse_url($intended, PHP_URL_PATH) ?: '';

    foreach (['student', 'parent'] as $portal) {
        if (str_starts_with($path, '/'.$portal)) {
            return redirect('/'.$portal.'/login');
        }
    }

    return redirect('/admin/login');
})->name('login');
