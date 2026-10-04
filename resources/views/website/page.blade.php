<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page['seo']['title'] ?? $page['title'] }}</title>
    <meta name="description" content="{{ $page['seo']['description'] ?? '' }}">
    <meta name="robots" content="{{ $noindex ? 'noindex, follow' : ($page['seo']['robots'] ?? 'index, follow, max-image-preview:large') }}">
    @if($status === 200)
    <link rel="canonical" href="{{ ($page['seo']['canonical'] ?? rtrim(config('website.seo_base_url'), '/') . $page['path']) }}">
    @endif
    @php($fallbackImage = app(\App\Services\Cms::class)->collection('settings')['default_image'] ?? config('website.seo_base_url').'/og-image.jpg')
    <meta property="og:site_name" content="{{ app(\App\Services\Cms::class)->collection('settings')['site_name'] ?? 'Findo Group' }}">
    <meta property="og:locale" content="{{ $page['seo']['og:locale'] ?? ($locale === 'ar' ? 'ar_AR' : 'en_US') }}">
    <meta property="og:type" content="{{ ($page['kind'] ?? '') === 'post' ? 'article' : 'website' }}">
    <meta property="og:title" content="{{ $page['seo']['og:title'] ?? $page['seo']['title'] ?? $page['title'] }}">
    <meta property="og:description" content="{{ $page['seo']['og:description'] ?? $page['seo']['description'] ?? '' }}">
    <meta property="og:url" content="{{ ($page['seo']['canonical'] ?? rtrim(config('website.seo_base_url'), '/') . $page['path']) }}">
    <meta property="og:image" content="{{ ($page['seo']['image'] ?? '') ?: $fallbackImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $page['seo']['twitter:title'] ?? $page['seo']['title'] ?? $page['title'] }}">
    <meta name="twitter:description" content="{{ $page['seo']['twitter:description'] ?? $page['seo']['description'] ?? '' }}">
    <meta name="twitter:image" content="{{ ($page['seo']['twitter:image'] ?? $page['seo']['image'] ?? '') ?: $fallbackImage }}">
    @foreach($page['seo']['schemas'] ?? [] as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endforeach
    <script>try{if(localStorage.getItem('findo-theme')==='dark')document.documentElement.classList.add('dark')}catch(e){}</script>
    @if($page['path'] === '/')
    <link rel="preload" as="image" href="/wp-content/themes/findo-wordpress-theme/react-build/assets/hero-ai-light-optimized-1280.webp" imagesrcset="/wp-content/themes/findo-wordpress-theme/react-build/assets/hero-ai-light-optimized-640.webp 640w, /wp-content/themes/findo-wordpress-theme/react-build/assets/hero-ai-light-optimized-1280.webp 1280w, /wp-content/themes/findo-wordpress-theme/react-build/assets/hero-ai-light-optimized-1920.webp 1920w" imagesizes="100vw" fetchpriority="high">
    @endif
    @if($page['path'] === '/')
    <style data-findo-design>{!! file_get_contents(public_path('wp-content/themes/findo-wordpress-theme/react-build/assets/css/findo-app.css')) !!}</style>
    @else
    <link rel="stylesheet" href="/wp-content/themes/findo-wordpress-theme/react-build/assets/css/findo-app.css">
    @endif
    <script id="findo-cms-data" type="application/json">{!! json_encode(['collections'=>app(\App\Services\Cms::class)->publicData(),'design'=>$page['design'] ?? [],'globalDesign'=>app(\App\Services\Cms::class)->siteDesign(),'mediaAlts'=>app(\App\Services\Cms::class)->mediaAlts()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @if(app(\Illuminate\Foundation\Vite::class)->isRunningHot())
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>{!! app(\Illuminate\Foundation\Vite::class)->content('resources/css/app.css') !!}</style>
        @vite('resources/js/app.js')
    @endif
</head>
<body data-page-kind="{{ $page['kind'] ?? 'error' }}" data-local-clone="true">
    {!! $page['body_html'] !!}
    <div id="findo-toast" role="status" aria-live="polite" hidden></div>
</body>
</html>
