<?php

/**
 * Locale metadata for installed languages.
 *
 * Adding a language pack: drop a lang/<code>.json file and add an entry here.
 * No code changes are needed beyond this file and the JSON.
 *
 * Keys: ISO 639-1 code
 * - native:  the language's own name (shown in the picker)
 * - name:    English name (for admin display)
 * - dir:     'ltr' or 'rtl'
 * - font:    primary font stack for this locale (CSS font-family value)
 * - flag:    emoji flag (optional, for display)
 */
return [
    'en' => [
        'native' => 'English',
        'name' => 'English',
        'dir' => 'ltr',
        'font' => "'Inter', ui-sans-serif, system-ui, sans-serif",
        'flag' => '🇬🇧',
    ],

    'fr' => [
        'native' => 'Français',
        'name' => 'French',
        'dir' => 'ltr',
        'font' => "'Inter', ui-sans-serif, system-ui, sans-serif",
        'flag' => '🇫🇷',
    ],

    'pt' => [
        'native' => 'Português',
        'name' => 'Portuguese',
        'dir' => 'ltr',
        'font' => "'Inter', ui-sans-serif, system-ui, sans-serif",
        'flag' => '🇵🇹',
    ],

    'ar' => [
        'native' => 'العربية',
        'name' => 'Arabic',
        'dir' => 'rtl',
        'font' => "'Noto Sans Arabic', ui-sans-serif, system-ui, sans-serif",
        'flag' => '🇦🇪',
    ],

    // Language packs installed on request (not in v1 core)
    'am' => [
        'native' => 'አማርኛ',
        'name' => 'Amharic',
        'dir' => 'ltr',
        'font' => "'Noto Sans Ethiopic', serif",
        'flag' => '🇪🇹',
    ],
];
