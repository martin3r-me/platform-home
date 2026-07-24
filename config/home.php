<?php

/**
 * Home – Konfiguration des persönlichen Zuhause-Moduls.
 *
 * Spiegel von `organization` (Org-Sicht): das ist die Ich-Sicht — der
 * Anlaufpunkt, an dem alles Persönliche des Nutzers zusammenläuft.
 * Aktuell: Dashboard („Mein Tag"). Geplant: Kalender, Agenda/Triage.
 *
 * Scope: aktuelles Team (Kind-Teams werden aufgelöst → kein Cross-Team).
 */

return [
    /**
     * Routing: /home/...
     */
    'routing' => [
        'mode'   => env('HOME_MODE', 'path'),
        'prefix' => 'home',
    ],

    /**
     * Guard für Authentication.
     */
    'guard' => 'web',

    /**
     * Navigation. order = 0 → das Zuhause steht ganz oben / zuerst.
     */
    'navigation' => [
        'route' => 'home.dashboard',
        'icon'  => 'heroicon-o-home',
        'order' => 0,
    ],

    /**
     * Sidebar-Struktur des Moduls.
     * (Das Dashboard rendert aktuell seine eigene Schnellzugriff-Sidebar inline;
     *  hier stehen die Modul-Views, sobald Kalender/Agenda dazukommen.)
     */
    'sidebar' => [
        [
            'group' => 'Zuhause',
            'items' => [
                [
                    'label' => 'Mein Tag',
                    'route' => 'home.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
            ],
        ],
    ],
];
