<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicationsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TeamController;
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
    // Team admin routes
    Route::get('/team/create', [TeamController::class, 'create'])->name('team-create');
    Route::post('/team', [TeamController::class, 'store'])->name('team-store');
    Route::get('/team/edit', [TeamController::class, 'edit'])->name('team-edit');
    Route::put('/team', [TeamController::class, 'update'])->name('team-update');
        Route::get('/project/create', [ProjectController::class, 'create'])->name('create-project');
        Route::post('/project', [ProjectController::class, 'store'])->name('project-store');
        Route::get('/project/edit', [ProjectController::class, 'edit'])->name('edit-project');
        Route::put('/project', [ProjectController::class, 'update'])->name('update-project');
        // News admin routes (create/edit/delete)
        Route::get('/newsevents/create', [\App\Http\Controllers\NewsController::class, 'create'])->name('news-create');
        Route::post('/newsevents', [\App\Http\Controllers\NewsController::class, 'store'])->name('news-store');
        Route::get('/newsevents/{id}/edit', [\App\Http\Controllers\NewsController::class, 'edit'])->name('news-edit')->where('id','[0-9]+');
        Route::put('/newsevents/{id}', [\App\Http\Controllers\NewsController::class, 'update'])->name('news-update')->where('id','[0-9]+');
        Route::get('/newsevents/{id}/delete', [\App\Http\Controllers\NewsController::class, 'confirmDelete'])->name('news-delete')->where('id','[0-9]+');
        Route::post('/newsevents/{id}/delete', [\App\Http\Controllers\NewsController::class, 'delete'])->name('news-delete-post')->where('id','[0-9]+');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Legacy public routes restored
|--------------------------------------------------------------------------
| These routes were copied from the legacy app to keep public endpoints
| such as news, publications and newsletter working after installing
| the Breeze auth scaffold.
*/

Route::namespace('App\\Http\\Controllers')->group(function () {
    Route::get('/homepage', ['as' => 'homepage', 'uses' => 'HomepageController@show']);
    Route::get('/lang/{lang}', 'LanguageController@setLanguage');

    // Public read routes
    Route::get('/project', ['as' => 'project', 'uses' => 'ProjectController@show']);
    Route::get('/team', ['as' => 'team', 'uses' => 'TeamController@show']);

    Route::get('/research', ['as' => 'research', 'uses' => 'ResearchController@index']);
    Route::get('/research/{id}', ['as' => 'single-research', 'uses' => 'ResearchController@show']);

    Route::get('/publications', ['as' => 'publications', 'uses' => 'PublicationsController@index']);
    Route::get('/publications/{id}', ['as' => 'publication', 'uses' => 'PublicationsController@show']);

    Route::get('/newsevents', ['as' => 'news', 'uses' => 'NewsController@index']);
    Route::get('/newsevents/{id}', ['as' => 'new', 'uses' => 'NewsController@show']);

    // Backwards-compatible admin aliases for news
    Route::get('/newsevents/create', [App\Http\Controllers\NewsController::class, 'create'])->name('news-create');
    Route::post('/newsevents', [App\Http\Controllers\NewsController::class, 'store'])->name('news-store');
    Route::get('/newsevents/{id}/edit', [App\Http\Controllers\NewsController::class, 'edit'])->name('news-edit')->where('id','[0-9]+');
    Route::put('/newsevents/{id}', [App\Http\Controllers\NewsController::class, 'update'])->name('news-update')->where('id','[0-9]+');
    Route::get('/newsevents/{id}/delete', [App\Http\Controllers\NewsController::class, 'confirmDelete'])->name('news-delete')->where('id','[0-9]+');
    Route::post('/newsevents/{id}/delete', [App\Http\Controllers\NewsController::class, 'delete'])->name('news-delete-post')->where('id','[0-9]+');

    Route::get('/contact', ['as' => 'contact', 'uses' => 'ContactController@show']);

    // Newsletter public & admin endpoints (controllers may enforce auth/admin themselves)
    Route::get('/newsletter/create', ['as' => 'create-newsletter', 'uses' => 'NewsletterController@create']);
    Route::post('/newsletter', ['as' => 'newsletter-store', 'uses' => 'NewsletterController@store']);

    Route::get('/newsletter/{id}/edit', ['as' => 'edit-newsletter', 'uses' => 'NewsletterController@edit'])->where('id','[0-9]+');
    Route::put('/newsletter/{id}', ['as' => 'update-newsletter', 'uses' => 'NewsletterController@update'])->where('id','[0-9]+');

    Route::get('/newsletter/{id}/delete', ['as' => 'delete-newsletter', 'uses' => 'NewsletterController@confirmDelete'])->where('id','[0-9]+');
    Route::post('/newsletter/{id}/delete', ['as' => 'delete-newsletter', 'uses' => 'NewsletterController@delete'])->where('id','[0-9]+');

    Route::get('/newsletter', ['as' => 'newsletter', 'uses' => 'NewsletterController@index']);
    // Show newsletter by id (legacy /newsletter/{id})
    Route::get('/newsletter/{id}', ['as' => 'newsletter-show', 'uses' => 'NewsletterController@show'])->where('id','[0-9]+');
    Route::get('/newsletter/{id}/preview', ['as' => 'preview-newsletter', 'uses' => 'NewsletterController@preview'])->where('id','[0-9]+');

    Route::get('/newsletter/{id}/send', ['as' => 'send-newsletter', 'uses' => 'NewsletterController@confirmSend'])->where('id','[0-9]+');
    Route::post('/newsletter/{id}/send', ['as' => 'send-newsletter', 'uses' => 'NewsletterController@send'])->where('id','[0-9]+');

    Route::get('/newsletter/{id}/test', ['as' => 'test-newsletter', 'uses' => 'NewsletterController@confirmTest'])->where('id','[0-9]+');
    Route::post('/newsletter/{id}/test', ['as' => 'test-newsletter', 'uses' => 'NewsletterController@sendToMe'])->where('id','[0-9]+');

    Route::get('/newsletter/{id}/create-section', ['as' => 'create-newsletter-section', 'uses' => 'NewsletterController@createSection'])->where('id','[0-9]+');
    Route::delete('/newsletter/{newsletterID}/{sectionID}', ['as' => 'delete-newsletter-section', 'uses' => 'NewsletterController@deleteSection'])
        ->where('newsletterID','[0-9]+')->where('sectionID','[0-9]+');
    // Support GET on section URL to avoid MethodNotAllowed when visited directly — redirect to edit page
    Route::get('/newsletter/{newsletterID}/{sectionID}', function($newsletterID, $sectionID){
        return redirect()->route('edit-newsletter', ['id' => $newsletterID]);
    })->where('newsletterID','[0-9]+')->where('sectionID','[0-9]+');

    // Subscription endpoints
    Route::get('/newsletter/subscribe', ['as' => 'newsletter-subscribe-form', 'uses' => 'NewsletterController@showSubscribeForm']);
    Route::post('/newsletter/subscribe', ['as' => 'newsletter-subscribe', 'uses' => 'NewsletterController@storeSubscriber']);
    Route::get('/newsletter/subscribe/{token}', ['as' => 'newsletter-confirm-subscribe', 'uses' => 'NewsletterController@registSubscriber']);
    Route::get('/newsletter/unsubscribe/{token}', ['as' => 'newsletter-delete-subscriber', 'uses' => 'NewsletterController@deleteSubscriber']);

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
