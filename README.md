# Home – das persönliche Zuhause-Modul

Die **Ich-Sicht** der Platform und Spiegel von `organization` (Org-Sicht):
der Anlaufpunkt, an dem alles Persönliche eines Nutzers zusammenläuft.

- **Jetzt:** Dashboard „Mein Tag" (Check-in, offene Todos, Streak, Module).
- **Geplant:** Kalender (aggregiert zeitbezogene Dinge modulübergreifend),
  Agenda/Triage (Dinge weglegen/später), gespeist über Contribution-Registries,
  in die Module persönliche Beiträge (Widgets, Events, Agenda-Items) pushen.

**Scope:** aktuelles Team (Kind-Teams werden aufgelöst → kein Cross-Team).

## Struktur

```
home/
├── composer.json                       # martin3r/platform-home, Namespace Platform\Home
├── config/home.php                     # routing (/home), navigation, sidebar
├── routes/web.php                      # home.dashboard → /home
├── src/
│   ├── HomeServiceProvider.php         # Modul-Registrierung + Livewire/Views/Routes
│   └── Livewire/Dashboard.php          # „Mein Tag" (aus core übernommen)
└── resources/views/livewire/dashboard.blade.php
```

## Status

Skelett aus `module-template` erstellt, aktuelles Core-Dashboard übernommen.
Noch **nicht** in eine Instance eingebunden (kein Git-Remote / Registry-Eintrag).
Der Root-/Fallback-Einstieg zeigt weiter auf das Core-Dashboard — der Umzug
dorthin ist ein bewusster nächster Schritt.
