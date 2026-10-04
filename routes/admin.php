<?php

use App\Http\Controllers\AdminController as A;
use App\Http\Controllers\OperatingController as O;
use App\Http\Middleware\AdminAccess;
use App\Http\Middleware\AdminLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(AdminLocale::class)->group(function () {
    Route::get('/login', [A::class, 'login'])->name('login');
    Route::post('/login', [A::class, 'authenticate'])->middleware('throttle:10,1');
    Route::prefix('admin')->middleware(AdminAccess::class)->group(function () {
        Route::get('/', [O::class, 'overview'])->name('admin');
        Route::get('/website', [A::class, 'dashboard']);
        Route::get('/operations/settings', [O::class, 'settings']);
        Route::post('/operations/settings', [O::class, 'saveSettings']);
        Route::post('/operations/seo-audit', [O::class, 'seoAudit']);
        Route::get('/operations/reports', [O::class, 'reports']);
        Route::get('/operations/permissions', [O::class, 'permissions']);
        Route::get('/operations/integrations', [O::class, 'integrations']);
        Route::post('/operations/integrations/jira/configure', [O::class, 'configureJira']);
        Route::post('/operations/integrations/jira/sync', [O::class, 'syncJira']);
        Route::post('/operations/integrations/jira/import', [O::class, 'importJira']);
        Route::post('/operations/integrations/{id}/retry', [O::class, 'retrySync'])->whereNumber('id');
        Route::get('/operations/attachments/{id}', [O::class, 'download'])->whereNumber('id');
        Route::get('/operations/record/{id}', [O::class, 'show'])->whereNumber('id');
        Route::post('/operations/record/{id}', [O::class, 'update'])->whereNumber('id');
        Route::post('/operations/record/{id}/transition', [O::class, 'transition'])->whereNumber('id');
        Route::post('/operations/record/{id}/comments', [O::class, 'comment'])->whereNumber('id');
        Route::post('/operations/record/{id}/attachments', [O::class, 'attach'])->whereNumber('id');
        Route::get('/operations/{type}/create', [O::class, 'create']);
        Route::get('/operations/{type}', [O::class, 'listing']);
        Route::post('/operations/{type}', [O::class, 'store']);
        Route::get('/api/v1/operations/record/{id}', [O::class, 'show'])->whereNumber('id');
        Route::post('/api/v1/operations/record/{id}', [O::class, 'update'])->whereNumber('id');
        Route::post('/api/v1/operations/record/{id}/transition', [O::class, 'transition'])->whereNumber('id');
        Route::get('/api/v1/operations/{type}', [O::class, 'listing']);
        Route::post('/api/v1/operations/{type}', [O::class, 'store']);
        Route::post('/logout', [A::class, 'logout']);
        Route::get('/contents', [A::class, 'contents']);
        Route::get('/content/create', [A::class, 'create']);
        Route::post('/content', [A::class, 'store']);
        Route::get('/content/{id}', [A::class, 'edit'])->whereNumber('id');
        Route::post('/content/{id}', [A::class, 'save'])->whereNumber('id');
        Route::get('/content/{id}/preview', [A::class, 'preview']);
        Route::get('/content/{id}/design', [A::class, 'design']);
        Route::post('/content/{id}/design', [A::class, 'saveDesign']);
        Route::get('/collections/{key}', [A::class, 'collection']);
        Route::post('/collections/{key}', [A::class, 'saveCollection']);
        Route::get('/media', [A::class, 'media']);
        Route::post('/media', [A::class, 'upload']);
        Route::post('/media/{id}', [A::class, 'saveMedia']);
        Route::post('/media/{id}/archive', [A::class, 'archiveMedia']);
        Route::get('/redirects', [A::class, 'redirects']);
        Route::post('/redirects', [A::class, 'saveRedirect']);
        Route::post('/redirects/{id}/delete', [A::class, 'deleteRedirect']);
        Route::get('/submissions', [A::class, 'submissions']);
        Route::post('/submissions/{id}', [A::class, 'saveSubmission']);
        Route::get('/users', [A::class, 'users']);
        Route::post('/users', [A::class, 'saveUser']);
        Route::get('/revisions', [A::class, 'revisions']);
        Route::post('/revisions/{id}/restore', [A::class, 'restore']);
        Route::get('/seo', [A::class, 'seo']);
        Route::get('/performance', [A::class, 'performance']);
        Route::post('/performance/clear', [A::class, 'clearCache']);
    });

});
