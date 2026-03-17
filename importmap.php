<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    'marked' => [
        'version' => '17.0.4',
    ],
    'canvas-confetti' => [
        'version' => '1.9.4',
    ],
    'alpinejs' => [
        'version' => '3.14.8',
    ],
    '@fullcalendar/core' => [
        'version' => '6.1.20',
    ],
    '@fullcalendar/daygrid' => [
        'version' => '6.1.20',
    ],
    '@fullcalendar/interaction' => [
        'version' => '6.1.20',
    ],
    'preact' => [
        'version' => '10.12.1',
    ],
    'preact/compat' => [
        'version' => '10.12.1',
    ],
    '@fullcalendar/core/index.js' => [
        'version' => '6.1.20',
    ],
    '@fullcalendar/core/internal.js' => [
        'version' => '6.1.20',
    ],
    '@fullcalendar/core/preact.js' => [
        'version' => '6.1.20',
    ],
    'preact/hooks' => [
        'version' => '10.12.1',
    ],
];
