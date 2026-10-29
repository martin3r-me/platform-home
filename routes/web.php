<?php

/**
 * Home – Web-Routes.
 *
 * Routes werden automatisch mit dem Modul-Prefix (aus config/home.php) und
 * der Auth-Middleware versehen (siehe ModuleRouter).
 * Views sind aktuell Platzhalter – Struktur zuerst.
 */

use Platform\Home\Livewire\Inbox;
use Platform\Home\Livewire\MeinTag;
use Platform\Home\Livewire\Agenda;
use Platform\Home\Livewire\AgendaBoard;
use Platform\Home\Livewire\Checkin;
use Platform\Home\Livewire\Kalender;
use Platform\Home\Livewire\Zeiten;
use Platform\Home\Livewire\Aufgaben;
use Platform\Home\Livewire\Ziele;
use Platform\Home\Livewire\Kurse;
use Platform\Home\Livewire\Notizen;
use Platform\Home\Livewire\Benachrichtigungen;
use Platform\Home\Livewire\Profil;

// Cockpit: Eingang + „Heute" zusammengeführt. `/` und `/eingang` rendern dieselbe
// Fläche (Strom + Kontext-Schiene); `/eingang/{item}` ist die eigene Item-View.
// Name `home.dashboard` bleibt für Alt-Links/Redirects erhalten.
Route::get('/',        Inbox::class)->name('home.dashboard');
Route::get('/eingang/{item?}', Inbox::class)->whereNumber('item')->name('home.inbox');
Route::get('/mein-tag', MeinTag::class)->name('home.mein-tag');
Route::get('/agenda',   Agenda::class)->name('home.agenda');
Route::get('/agenda/{agenda}', AgendaBoard::class)->whereNumber('agenda')->name('home.agenda.show');
Route::get('/checkin',  Checkin::class)->name('home.checkin');

// Meins
Route::get('/kalender', Kalender::class)->name('home.kalender');
Route::get('/zeiten',   Zeiten::class)->name('home.zeiten');
Route::get('/aufgaben', Aufgaben::class)->name('home.aufgaben');
Route::get('/ziele',    Ziele::class)->name('home.ziele');
Route::get('/kurse',    Kurse::class)->name('home.kurse');
Route::get('/notizen',  Notizen::class)->name('home.notizen');

// Konto
Route::get('/benachrichtigungen', Benachrichtigungen::class)->name('home.benachrichtigungen');
Route::get('/profil',             Profil::class)->name('home.profil');
