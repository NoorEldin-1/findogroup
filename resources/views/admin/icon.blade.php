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
    'share' => 'M18 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M18 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M8.6 13.5l6.8 4 M15.4 6.5l-6.8 4',
    'eye' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
    'activity' => 'M22 12h-4l-3 9L9 3l-3 9H2',
    'check' => 'M22 11.1V12a10 10 0 1 1-5.9-9.1 M22 4L12 14l-3-3',
    'money' => 'M12 2v20 M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
    'trend' => 'M22 7l-8.5 8.5-5-5L2 17 M16 7h6v6',
    'alert' => 'M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z M12 9v4 M12 17h.01',
    'phone' => 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z',
    'handshake' => 'M11 17l2 2a1 1 0 1 0 3-3 M14 14l2.5 2.5a1 1 0 1 0 3-3l-3.9-3.9a3 3 0 0 0-4.2 0l-.9.9a1 1 0 1 1-3-3l2.8-2.8a5.8 5.8 0 0 1 7.1-.9l.5.3a2 2 0 0 0 1.5.2L21 4 M21 3l1 11h-2 M3 3L2 14l6.5 6.5a1 1 0 1 0 3-3 M3 4h8',
    'calendar' => 'M3 5h18v16H3z M3 10h18 M8 3v4 M16 3v4',
    'tasks' => 'M3 6h11 M3 12h11 M3 18h11 M18 5l1.5 1.5L22 4 M18 11l1.5 1.5L22 10 M18 17l1.5 1.5L22 16',
    'arrow-out' => 'M7 17L17 7 M8 7h9v9',
    'help' => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20 M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3 M12 17h.01',
];
@endphp
<svg class="{{ $class ?? 'nav-icon' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['pages'] }}"/></svg>
