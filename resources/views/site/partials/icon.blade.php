@php
    $paths = [
        'arrow' => 'M4 12h16m-6-6 6 6-6 6',
        'external' => 'M7 17 17 7M7 7h10v10',
        'calendar' => 'M8 3v4m8-4v4M3 10h18M5 5h14a2 2 0 0 1 2 2v13H3V7a2 2 0 0 1 2-2Z',
        'location' => 'M12 22s8-7 8-13a8 8 0 0 0-16 0c0 6 8 13 8 13Zm0-10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
        'download' => 'M12 3v12m-5-5 5 5 5-5M4 15v6h16v-6',
        'document' => 'M14 2H4v20h16V8l-6-6Zm0 0v6h6M8 13h8m-8 4h8',
        'image' => 'M4 4h16v16H4V4Zm0 12 5-5 4 4 2-2 5 5M15 9h.01',
        'play' => 'm9 5 12 7-12 7V5Z',
        'globe' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM3 12h18M12 3c5 4 5 14 0 18-5-4-5-14 0-18Z',
        'chevron' => 'm7 10 5 5 5-5',
        'clock' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 7v5l4 2',
        'people' => 'M16 21v-3a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v3m20 0v-3a4 4 0 0 0-3-4M13 6a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm4-4a4 4 0 0 1 0 8',
        'search' => 'm21 21-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z',
        'check' => 'm5 12 4 4L20 5',
    ];
@endphp
<svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="{{ $paths[$name] ?? $paths['arrow'] }}"/></svg>
