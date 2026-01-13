<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistersController;
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
    return redirect('/login');
});

// Removed legacy /register alias to avoid shadowing Breeze's registration route

// Banned notice page (still requires login)
Route::get('/banned', function () {
    return view('auth.banned');
})->name('banned')->middleware('auth');

// Gate all registers and map routes behind auth
Route::middleware('auth')->group(function () {
    Route::get('/registers', [RegistersController::class, 'index'])->name('registers.index');
    Route::get('/registers/{id}', [RegistersController::class, 'show'])->name('registers.show')->where('id','[0-9]+');

    Route::get('/registers/create', [RegistersController::class, 'create'])->name('registers.create');
    Route::post('/registers', [RegistersController::class, 'store'])->name('registers.store');
    Route::get('/registers/{id}/edit', [RegistersController::class, 'edit'])->name('registers.edit')->where('id','[0-9]+');
    Route::put('/registers/{id}', [RegistersController::class, 'update'])->name('registers.update')->where('id','[0-9]+');
    Route::get('/registers/{id}/delete', [RegistersController::class, 'confirmDelete'])->name('registers.delete.confirm')->where('id','[0-9]+');
    Route::post('/registers/{id}/delete', [RegistersController::class, 'delete'])->name('registers.delete')->where('id','[0-9]+');

    Route::get('/map', [RegistersController::class, 'map'])->name('registers.map');
});

        // Removed duplicated public registers routes; all gated above.
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
| such as registers working after installing
| the Breeze auth scaffold.
*/

Route::namespace('App\\Http\\Controllers')->group(function () {
    // Gate language switching as well
    Route::middleware('auth')->get('/lang/{lang}', 'LanguageController@setLanguage');

    // Legacy user management route expected by older templates (protected)
    Route::get('/user/management', [\App\Http\Controllers\UserController::class, 'index'])
        ->name('users-list')
        ->middleware(['auth', 'permission:view_page_management']);

    Route::post('/user/password/{id}', [\App\Http\Controllers\UserController::class, 'password_replacement'])
        ->name('users.password.create')
        ->middleware(['auth','permission:send_password_reset','can:admin']);

    Route::post('/users/mail/test', [\App\Http\Controllers\UserController::class, 'sendTestEmail'])
        ->name('users.mail.test')
        ->middleware(['auth','can:admin']);

    Route::get('/users/create', [\App\Http\Controllers\UserController::class, 'create'])
        ->name('users.create')
        ->middleware(['auth','permission:create_user','can:admin']);

    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])
        ->name('users.store')
        ->middleware(['auth','permission:create_user','can:admin']);

    Route::get('/user/{id}/edit', [\App\Http\Controllers\UserController::class, 'edit'])
        ->name('users.edit')
        ->middleware(['auth','permission:edit_user']);

    Route::put('/user/{id}', [\App\Http\Controllers\UserController::class, 'update'])
        ->name('users.update')
        ->middleware(['auth','permission:edit_user']);

    Route::post('/user/ban/{id}', [\App\Http\Controllers\UserController::class, 'ban'])
        ->name('users.ban')
        ->middleware(['auth','permission:ban_user']);

    // Page Settings - Roles management
    Route::get('/settings/roles', [\App\Http\Controllers\Settings\RolesController::class, 'index'])
        ->name('settings.roles.index')
        ->middleware(['auth','permission:view_page_settings_roles']);

    Route::get('/settings/roles/{slug}/edit', [\App\Http\Controllers\Settings\RolesController::class, 'edit'])
        ->name('settings.roles.edit')
        ->middleware(['auth','permission:edit_role']);

    Route::put('/settings/roles/{slug}', [\App\Http\Controllers\Settings\RolesController::class, 'update'])
        ->name('settings.roles.update')
        ->middleware(['auth','permission:edit_role']);

    Route::get('/settings/roles/create', [\App\Http\Controllers\Settings\RolesController::class, 'create'])
        ->name('settings.roles.create')
        ->middleware(['auth','permission:create_role']);

    Route::post('/settings/roles', [\App\Http\Controllers\Settings\RolesController::class, 'store'])
        ->name('settings.roles.store')
        ->middleware(['auth','permission:create_role']);

    Route::delete('/settings/roles/{slug}', [\App\Http\Controllers\Settings\RolesController::class, 'destroy'])
        ->name('settings.roles.destroy')
        ->middleware(['auth','permission:delete_role']);
});
