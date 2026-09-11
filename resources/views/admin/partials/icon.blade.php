@php($iconName = $name ?? 'grid')

<svg
    class="admin-icon {{ $class ?? '' }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>
    @switch($iconName)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="2" />
            <rect x="14" y="3" width="7" height="7" rx="2" />
            <rect x="3" y="14" width="7" height="7" rx="2" />
            <rect x="14" y="14" width="7" height="7" rx="2" />
            @break

        @case('image')
        @case('slides')
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <circle cx="8.5" cy="9" r="1.5" />
            <path d="m4 17 4.5-4.5 3.5 3 2.5-2.5 5.5 5" />
            @break

        @case('calendar')
        @case('events')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            <path d="m8 15 2 2 5-5" />
            @break

        @case('clock')
        @case('sessions')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break

        @case('news')
            <path d="M5 4h14a2 2 0 0 1 2 2v13H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
            <path d="M7 8h8M7 12h10M7 16h6" />
            @break

        @case('programme')
        @case('programs')
        @case('resources')
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z" />
            <path d="M8 7h8M8 11h6" />
            @break

        @case('help')
        @case('faqs')
            <circle cx="12" cy="12" r="9" />
            <path d="M9.6 9a2.5 2.5 0 1 1 4.1 1.9c-1 .8-1.7 1.3-1.7 2.6" />
            <path d="M12 17h.01" />
            @break

        @case('pages')
            <path d="M6 2h8l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" />
            <path d="M14 2v5h5M8 12h8M8 16h6" />
            @break

        @case('home')
            <path d="m3 11 9-8 9 8" />
            <path d="M5 10v10h14V10M9 20v-6h6v6" />
            @break

        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z" />
            @break

        @case('external')
            <path d="M14 4h6v6M20 4l-9 9" />
            <path d="M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6" />
            @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break

        @case('edit')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z" />
            @break

        @case('trash')
            <path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6" />
            @break

        @case('logout')
            <path d="M10 17l5-5-5-5M15 12H3" />
            <path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" />
            @break

        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break

        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break

        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break

        @case('alert')
            <path d="M10.3 3.5 2.2 18a2 2 0 0 0 1.8 3h16a2 2 0 0 0 1.8-3L13.7 3.5a2 2 0 0 0-3.4 0Z" />
            <path d="M12 9v4M12 17h.01" />
            @break

        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break

        @case('arrow-left')
            <path d="M19 12H5M11 18l-6-6 6-6" />
            @break

        @case('upload')
            <path d="M12 16V4M7 9l5-5 5 5" />
            <path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5" />
            @break

        @case('drag')
            <circle cx="9" cy="6" r="1" fill="currentColor" stroke="none" />
            <circle cx="15" cy="6" r="1" fill="currentColor" stroke="none" />
            <circle cx="9" cy="12" r="1" fill="currentColor" stroke="none" />
            <circle cx="15" cy="12" r="1" fill="currentColor" stroke="none" />
            <circle cx="9" cy="18" r="1" fill="currentColor" stroke="none" />
            <circle cx="15" cy="18" r="1" fill="currentColor" stroke="none" />
            @break

        @default
            <rect x="3" y="3" width="18" height="18" rx="3" />
            <path d="M8 8h8v8H8z" />
    @endswitch
</svg>
