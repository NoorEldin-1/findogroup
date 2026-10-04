@php
$paths = [
    'overview' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
    'articles' => 'M4 3h12l4 4v14H4z M14 3v6h6 M8 13h8 M8 17h6',
    'pages' => 'M3 4h18v16H3z M3 9h18 M9 9v11',
    'media' => 'M3 3h18v18H3z M3 17l6-6 4 4 3-3 5 5 M8 7h.01',
    'seo' => 'M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14 M15 15l6 6 M7 11l2-3 3 2',
    'redirects' => 'M4 7h15 M15 3l4 4-4 4 M20 17H5 M9 13l-4 4 4 4',
    'requests' => 'M3 5h18v14H3z M3 5l9 7 9-7',
    'performance' => 'M13 2L4 14h7l-1 8 10-13h-8z',
    'revisions' => 'M3 11a9 9 0 1 1 2 7 M3 4v7h7 M12 7v5l3 2',
    'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M17 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87',
    'video' => 'M4 3h16v18H4z M9 8l7 4-7 4z',
];
@endphp
<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['pages'] }}"/></svg>
