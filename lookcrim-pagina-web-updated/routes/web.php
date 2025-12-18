<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicationsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('landing');
});

// Banned notice page
Route::get('/banned', function () {
    return view('auth.banned');
})->name('banned');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
        // Publications admin routes (protected by controller middleware)
        Route::get('/registers/create', [PublicationsController::class, 'create'])->name('create-publication');
        // Legacy template alias: publications-create (same action, legacy name)
        Route::get('/registers/create', [PublicationsController::class, 'create'])->name('publications-create');
        Route::post('/registers', [PublicationsController::class, 'store'])->name('publications-store');
            Route::get('/registers/create', [PublicationsController::class, 'create'])->name('create-publication');
            Route::get('/registers/create', [PublicationsController::class, 'create'])->name('publications-create');
            Route::post('/registers', [PublicationsController::class, 'store'])->name('publications-store');
            Route::get('/registers/{id}/edit', [PublicationsController::class, 'edit'])->name('edit-publication')->where('id','[0-9]+');
            Route::get('/registers/{id}/edit', [PublicationsController::class, 'edit'])->name('publications-edit')->where('id','[0-9]+');
            Route::put('/registers/{id}', [PublicationsController::class, 'update'])->name('update-publication')->where('id','[0-9]+');
            Route::get('/registers/{id}/delete', [PublicationsController::class, 'confirmDelete'])->name('delete-publication')->where('id','[0-9]+');
            Route::get('/registers/{id}/delete', [PublicationsController::class, 'confirmDelete'])->name('publications-delete')->where('id','[0-9]+');
            Route::post('/registers/{id}/delete', [PublicationsController::class, 'delete'])->name('publications-delete-post')->where('id','[0-9]+');

        // Public publications routes
        // Public-facing registers routes (replaces old publications URLs)
        Route::get('/registers', ['as' => 'publications', 'uses' => 'PublicationsController@index']);
        Route::get('/registers/{id}', ['as' => 'publication', 'uses' => 'PublicationsController@show'])->where('id','[0-9]+');

        // Map view showing registers with coordinates
        Route::get('/map', [App\Http\Controllers\PublicationsController::class, 'map'])->name('publications-map');

        // Backwards-compatible admin aliases for publications (legacy templates expect these names)
        Route::get('/registers/create', [PublicationsController::class, 'create'])->name('publications-create');
        Route::post('/registers', [PublicationsController::class, 'store'])->name('publications-store');
        Route::get('/registers/{id}/edit', [PublicationsController::class, 'edit'])->name('publications-edit')->where('id','[0-9]+');
        Route::put('/registers/{id}', [PublicationsController::class, 'update'])->name('publications-update')->where('id','[0-9]+');
        Route::get('/registers/{id}/delete', [PublicationsController::class, 'confirmDelete'])->name('publications-delete')->where('id','[0-9]+');
        Route::post('/registers/{id}/delete', [PublicationsController::class, 'delete'])->name('publications-delete-post')->where('id','[0-9]+');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Legacy public routes restored
|--------------------------------------------------------------------------
| These routes were copied from the legacy app to keep public endpoints
| such as publications working after installing
| the Breeze auth scaffold.
*/

Route::namespace('App\\Http\\Controllers')->group(function () {
    Route::get('/homepage', ['as' => 'homepage', 'uses' => 'HomepageController@show']);
    Route::get('/lang/{lang}', 'LanguageController@setLanguage');

    Route::get('/registers', ['as' => 'publications', 'uses' => 'PublicationsController@index']);
    Route::get('/registers/{id}', ['as' => 'publication', 'uses' => 'PublicationsController@show']);

    // Legacy user management route expected by older templates (protected)
    Route::get('/user/management', [\App\Http\Controllers\UserController::class, 'index'])
        ->name('users-list')
        ->middleware(['auth', 'can:admin']);
    Route::post('/user/password/{id}', [\App\Http\Controllers\UserController::class, 'password_replacement'])->name('users.password.create')->middleware(['auth','can:admin']);
    Route::get('/users/create', [\App\Http\Controllers\UserController::class, 'create'])->name('users.create')->middleware(['auth','can:admin']);
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store')->middleware(['auth','can:admin']);
    Route::get('/user/{id}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('users.edit')->middleware(['auth','can:admin']);
    Route::put('/user/{id}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update')->middleware(['auth','can:admin']);
    Route::post('/user/ban/{id}', [\App\Http\Controllers\UserController::class, 'ban'])->name('users.ban')->middleware(['auth','can:admin']);

    // Page Settings - Roles management
    Route::get('/settings/roles', [\App\Http\Controllers\Settings\RolesController::class, 'index'])->name('settings.roles.index')->middleware(['auth','can:admin']);
    Route::get('/settings/roles/{slug}/edit', [\App\Http\Controllers\Settings\RolesController::class, 'edit'])->name('settings.roles.edit')->middleware(['auth','can:admin']);
    Route::put('/settings/roles/{slug}', [\App\Http\Controllers\Settings\RolesController::class, 'update'])->name('settings.roles.update')->middleware(['auth','can:admin']);
    Route::get('/settings/roles/create', [\App\Http\Controllers\Settings\RolesController::class, 'create'])->name('settings.roles.create')->middleware(['auth','can:admin']);
    Route::post('/settings/roles', [\App\Http\Controllers\Settings\RolesController::class, 'store'])->name('settings.roles.store')->middleware(['auth','can:admin']);
    Route::delete('/settings/roles/{slug}', [\App\Http\Controllers\Settings\RolesController::class, 'destroy'])->name('settings.roles.destroy')->middleware(['auth','can:admin']);
});
