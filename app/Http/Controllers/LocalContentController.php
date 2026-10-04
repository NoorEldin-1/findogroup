<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalContentController extends Controller
{
    public function posts(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:250', 'category' => 'nullable|integer', 'after' => 'nullable|date', 'slug' => 'nullable|string|max:1800', 'perPage' => 'nullable|integer|min:1|max:30', 'page' => 'nullable|integer|min:1']);
        $q = DB::table('cms_posts as p')->join('contents as c', 'c.id', '=', 'p.content_id')->join('content_routes as r', 'r.content_id', '=', 'c.id')->where('c.status', 'published')->where(fn ($q) => $q->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()));
        if ($request->filled('category')) {
            $q->whereJsonContains('p.data->categories', (int) $request->category);
        }
        if ($request->filled('search')) {
            $q->where(fn ($q) => $q->where('p.data->title->rendered', 'like', '%'.$request->search.'%')->orWhere('p.data->excerpt->rendered', 'like', '%'.$request->search.'%'));
        }
        if ($request->filled('after')) {
            $q->where('c.published_at', '>=', $request->after);
        }
        if ($request->filled('slug')) {
            $q->where('r.path', '/blogs/'.rawurldecode($request->slug));
        }
        $total = $q->count();
        $perPage = (int) $request->input('perPage', 9);
        $page = (int) $request->input('page', 1);
        $posts = $q->orderByDesc('c.published_at')->offset(($page - 1) * $perPage)->limit($perPage)->pluck('p.data')->map(fn ($d) => json_decode($d, true))->all();
        $categories = app(Cms::class)->collection('categories');
        foreach ($posts as &$post) {
            $post['_embedded']['wp:term'] = [array_values(array_filter($categories, fn ($c) => in_array($c['id'], $post['categories'] ?? [])))];
        }unset($post);
        $json = json_encode(['posts' => $posts, 'post' => $posts[0] ?? null, 'total' => $total, 'totalPages' => (int) ceil($total / $perPage)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response(str_replace('https://findogroup.com/wp-content/', '/wp-content/', $json))->header('Content-Type', 'application/json')->header('X-Robots-Tag', 'noindex');
    }

    public function comments(Request $request)
    {
        $comments = app(Cms::class)->collection('comments');

        return response()->json(['comments' => array_values(array_filter($comments, fn ($c) => (int) $c['post'] === (int) $request->input('post')))])->header('X-Robots-Tag', 'noindex');
    }

    public function categories()
    {
        return response()->json(['categories' => app(Cms::class)->collection('categories')])->header('X-Robots-Tag','noindex');
    }
}
