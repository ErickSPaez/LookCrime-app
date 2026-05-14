<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegistersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Link público para confirmar cambio de email.
// Va fuera de auth porque el usuario puede abrirlo desde el correo sin sesión iniciada.
Route::get('/profile/email-change/verify/{token}', [ProfileController::class, 'verifyEmailChange'])
    ->name('profile.email-change.verify');

// Gate all registers and map routes behind auth
Route::middleware('auth')->group(function () {
    Route::get('/no-access', function () {
        return view('no-access');
    })->name('no-access');

    Route::get('/registers', [RegistersController::class, 'index'])->name('registers.index');
    Route::get('/registers/{id}', [RegistersController::class, 'show'])->name('registers.show')->where('id', '[0-9]+');

    Route::get('/registers/create', [RegistersController::class, 'create'])->name('registers.create');
    Route::post('/registers', [RegistersController::class, 'store'])->name('registers.store');
    Route::get('/registers/{id}/edit', [RegistersController::class, 'edit'])->name('registers.edit')->where('id', '[0-9]+');
    Route::put('/registers/{id}', [RegistersController::class, 'update'])->name('registers.update')->where('id', '[0-9]+');

    Route::get('/registers/{id}/delete', function () {
        abort(404);
    })->where('id', '[0-9]+');

    Route::post('/registers/{id}/delete', [RegistersController::class, 'delete'])->name('registers.delete')->where('id', '[0-9]+');

    Route::get('/map', [RegistersController::class, 'map'])->name('registers.map');

    Route::post('/api/registers/search-radius', [\App\Http\Controllers\Api\RegistersSearchController::class, 'search']);
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/email-change', [ProfileController::class, 'requestEmailChange'])->name('profile.email-change');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Legacy public routes restored
|--------------------------------------------------------------------------
*/

Route::namespace('App\\Http\\Controllers')->group(function () {
    Route::middleware('auth')->get('/lang/{lang}', 'LanguageController@setLanguage');

    Route::get('/user/management', [\App\Http\Controllers\UserController::class, 'index'])
        ->name('users-list')
        ->middleware(['auth', 'permission:view_page_management']);

    Route::post('/users/mail/test', [\App\Http\Controllers\UserController::class, 'sendTestEmail'])
        ->name('users.mail.test')
        ->middleware(['auth', 'can:admin']);

    Route::get('/users/create', [\App\Http\Controllers\UserController::class, 'create'])
        ->name('users.create')
        ->middleware(['auth', 'permission:create_user', 'can:admin']);

    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])
        ->name('users.store')
        ->middleware(['auth', 'permission:create_user', 'can:admin']);

    Route::post('/user/password/{id}', [\App\Http\Controllers\UserController::class, 'resendPasswordSetupEmail'])
        ->name('users.password.resend')
        ->where('id', '[0-9]+')
        ->middleware(['auth', 'permission:create_user', 'can:admin']);

    Route::get('/user/{id}/edit', [\App\Http\Controllers\UserController::class, 'edit'])
        ->name('users.edit')
        ->middleware(['auth', 'permission:edit_user']);

    Route::put('/user/{id}', [\App\Http\Controllers\UserController::class, 'update'])
        ->name('users.update')
        ->middleware(['auth', 'permission:edit_user']);

    Route::post('/user/ban/{id}', [\App\Http\Controllers\UserController::class, 'ban'])
        ->name('users.ban')
        ->middleware(['auth', 'permission:ban_user']);

    Route::get('/settings/roles', [\App\Http\Controllers\Settings\RolesController::class, 'index'])
        ->name('settings.roles.index')
        ->middleware(['auth']);

    Route::get('/settings/roles/{slug}/edit', [\App\Http\Controllers\Settings\RolesController::class, 'edit'])
        ->name('settings.roles.edit')
        ->middleware(['auth']);

    Route::put('/settings/roles/{slug}', [\App\Http\Controllers\Settings\RolesController::class, 'update'])
        ->name('settings.roles.update')
        ->middleware(['auth']);

    Route::get('/settings/roles/create', [\App\Http\Controllers\Settings\RolesController::class, 'create'])
        ->name('settings.roles.create')
        ->middleware(['auth']);

    Route::post('/settings/roles', [\App\Http\Controllers\Settings\RolesController::class, 'store'])
        ->name('settings.roles.store')
        ->middleware(['auth']);

    Route::delete('/settings/roles/{slug}', [\App\Http\Controllers\Settings\RolesController::class, 'destroy'])
        ->name('settings.roles.destroy')
        ->middleware(['auth']);

    Route::get('/settings/city', [\App\Http\Controllers\Settings\CitiesController::class, 'index'])
        ->name('settings.city.index')
        ->middleware(['auth']);

    Route::get('/settings/statistics', [\App\Http\Controllers\Settings\StatisticsController::class, 'index'])
        ->name('settings.statistics.index')
        ->middleware(['auth']);

    Route::get('/settings/city/create', [\App\Http\Controllers\Settings\CitiesController::class, 'create'])
        ->name('settings.city.create')
        ->middleware(['auth']);

    Route::post('/settings/city', [\App\Http\Controllers\Settings\CitiesController::class, 'store'])
        ->name('settings.city.store')
        ->middleware(['auth']);

    Route::get('/settings/city/{slug}/edit', [\App\Http\Controllers\Settings\CitiesController::class, 'edit'])
        ->name('settings.city.edit')
        ->middleware(['auth']);

    Route::put('/settings/city/{slug}', [\App\Http\Controllers\Settings\CitiesController::class, 'update'])
        ->name('settings.city.update')
        ->middleware(['auth']);

    Route::delete('/settings/city/{slug}', [\App\Http\Controllers\Settings\CitiesController::class, 'destroy'])
        ->name('settings.city.destroy')
        ->middleware(['auth']);
});