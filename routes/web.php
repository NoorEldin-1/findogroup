<?php

use App\Http\Controllers\LocalContentController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsiteSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', fn () => response(config('website.prevent_indexing') ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nDisallow: /admin\nDisallow: /portal\nSitemap: ".config('website.seo_base_url')."/sitemap_index.xml\n")->header('Content-Type', 'text/plain'));
Route::get('/sitemap_index.xml', [SitemapController::class, 'index']);
Route::get('/sitemap.xml', fn () => redirect('/sitemap_index.xml', 301));
Route::get('/page-sitemap.xml', fn (SitemapController $controller) => $controller->urls('page'));
Route::get('/app-sitemap.xml', fn () => redirect('/page-sitemap.xml', 301));
Route::get('/post-sitemap.xml', fn (SitemapController $controller) => $controller->urls('post'));
Route::get('/post-sitemap{part}.xml', fn () => redirect('/post-sitemap.xml', 301))->whereNumber('part');
Route::get('/local-api/posts', [LocalContentController::class, 'posts']);
Route::get('/local-api/comments', [LocalContentController::class, 'comments']);
Route::get('/local-api/categories', [LocalContentController::class, 'categories']);
Route::post('/forms/submit', WebsiteSubmissionController::class)->middleware('throttle:10,1');
require __DIR__.'/admin.php';
Route::get('/{path?}', WebsiteController::class)->where('path', '.*');
