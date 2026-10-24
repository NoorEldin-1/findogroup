<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PublishedPosts
{
    public function paginate(array $filters = []): array
    {
        $query = DB::table('cms_posts as p')->join('contents as c', 'c.id', '=', 'p.content_id')
            ->join('content_routes as r', 'r.content_id', '=', 'c.id')
            ->where('c.status', 'published')
            ->where(fn ($query) => $query->whereNull('c.published_at')->orWhere('c.published_at', '<=', now()));
        if (isset($filters['category']) && $filters['category'] !== '') {
            $query->whereJsonContains('p.data->categories', (int) $filters['category']);
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->where(fn ($query) => $query->where('p.data->title->rendered', 'like', '%'.$filters['search'].'%')
                ->orWhere('p.data->excerpt->rendered', 'like', '%'.$filters['search'].'%'));
        }
        if (! empty($filters['after'])) {
            $query->where('c.published_at', '>=', $filters['after']);
        }
        if (! empty($filters['slug'])) {
            $query->where('r.path', '/blogs/'.rawurldecode($filters['slug']));
        }
        $total = $query->count();
        $perPage = (int) ($filters['perPage'] ?? 9);
        $page = (int) ($filters['page'] ?? 1);
        $posts = $query->orderByDesc('c.published_at')->orderByDesc('c.id')
            ->offset(($page - 1) * $perPage)->limit($perPage)->pluck('p.data')
            ->map(fn ($data) => json_decode($data, true))->all();
        $categories = app(Cms::class)->collection('categories');
        foreach ($posts as &$post) {
            $post['_embedded']['wp:term'] = [array_values(array_filter($categories, fn ($category) => in_array($category['id'], $post['categories'] ?? [])))];
        }

        return ['posts' => $posts, 'post' => $posts[0] ?? null, 'total' => $total, 'totalPages' => (int) ceil($total / $perPage)];
    }
}
