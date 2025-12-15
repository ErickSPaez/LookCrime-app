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
        Route::get('/publications/create', [PublicationsController::class, 'create'])->name('create-publication');
        // Legacy template alias: publications-create (same action, legacy name)
        Route::get('/publications/create', [PublicationsController::class, 'create'])->name('publications-create');
        Route::post('/publications', [PublicationsController::class, 'store'])->name('publications-store');
        Route::get('/publications/{id}/edit', [PublicationsController::class, 'edit'])->name('edit-publication')->where('id','[0-9]+');
        // Backwards-compatible route name used in legacy Blade templates
        Route::get('/publications/{id}/edit', [PublicationsController::class, 'edit'])->name('publications-edit')->where('id','[0-9]+');
        Route::put('/publications/{id}', [PublicationsController::class, 'update'])->name('update-publication')->where('id','[0-9]+');
        Route::get('/publications/{id}/delete', [PublicationsController::class, 'confirmDelete'])->name('delete-publication')->where('id','[0-9]+');
        // Legacy template name
        Route::get('/publications/{id}/delete', [PublicationsController::class, 'confirmDelete'])->name('publications-delete')->where('id','[0-9]+');
        Route::post('/publications/{id}/delete', [PublicationsController::class, 'delete'])->name('delete-publication')->where('id','[0-9]+');

        // Public publications routes
        Route::get('/publications', ['as' => 'publications', 'uses' => 'PublicationsController@index']);
        Route::get('/publications/{id}', ['as' => 'publication', 'uses' => 'PublicationsController@show'])->where('id','[0-9]+');

        // Map view showing publications with coordinates
        Route::get('/map', [App\Http\Controllers\PublicationsController::class, 'map'])->name('publications-map');

        // Backwards-compatible admin aliases for publications (legacy templates expect these names)
        Route::get('/publications/create', [PublicationsController::class, 'create'])->name('publications-create');
        Route::post('/publications', [PublicationsController::class, 'store'])->name('publications-store');
        Route::get('/publications/{id}/edit', [PublicationsController::class, 'edit'])->name('publications-edit')->where('id','[0-9]+');
        Route::put('/publications/{id}', [PublicationsController::class, 'update'])->name('publications-update')->where('id','[0-9]+');
        Route::get('/publications/{id}/delete', [PublicationsController::class, 'confirmDelete'])->name('publications-delete')->where('id','[0-9]+');
        Route::post('/publications/{id}/delete', [PublicationsController::class, 'delete'])->name('publications-delete-post')->where('id','[0-9]+');
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

    Route::get('/publications', ['as' => 'publications', 'uses' => 'PublicationsController@index']);
    Route::get('/publications/{id}', ['as' => 'publication', 'uses' => 'PublicationsController@show']);

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
});
