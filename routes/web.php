<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Serves the single-page frontend for all non-API routes.
*/

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
