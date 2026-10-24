<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\PublishedPosts;
use Illuminate\Http\Request;

class LocalContentController extends Controller
{
    public function posts(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:250', 'category' => 'nullable|integer', 'after' => 'nullable|date', 'slug' => 'nullable|string|max:1800', 'perPage' => 'nullable|integer|min:1|max:30', 'page' => 'nullable|integer|min:1']);
        $json = json_encode(app(PublishedPosts::class)->paginate($request->only(['category', 'search', 'after', 'slug', 'perPage', 'page'])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response(str_replace('https://findogroup.com/wp-content/', '/wp-content/', $json))->header('Content-Type', 'application/json')->header('X-Robots-Tag', 'noindex');
    }

    public function comments(Request $request)
    {
        $comments = app(Cms::class)->collection('comments');

        return response()->json(['comments' => array_values(array_filter($comments, fn ($c) => (int) $c['post'] === (int) $request->input('post')))])->header('X-Robots-Tag', 'noindex');
    }

    public function categories()
    {
        return response()->json(['categories' => app(Cms::class)->collection('categories')])->header('X-Robots-Tag', 'noindex');
    }
}
