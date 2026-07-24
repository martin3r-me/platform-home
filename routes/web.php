<?php

/**
 * Home – Web-Routes.
 *
 * Routes werden automatisch mit dem Modul-Prefix (aus config/home.php) und
 * der Auth-Middleware versehen (siehe ModuleRouter).
 */

use Platform\Home\Livewire\Dashboard;

/**
 * Dashboard („Mein Tag") – Landing-View des Zuhause-Moduls.
 */
Route::get('/', Dashboard::class)->name('home.dashboard');

/**
 * Geplante weitere Views:
 * Route::get('/kalender', Calendar::class)->name('home.calendar');
 * Route::get('/agenda',   Agenda::class)->name('home.agenda');
 */
