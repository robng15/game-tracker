# Game Tracker — Project Notes

## Overview
PHP app for tracking video games played. Single user, SQLite database, Bootstrap 5 frontend.

## Stack
- PHP
- SQLite
- Bootstrap 5
- IGDB API (via Twitch OAuth2)

## Deployment
- GitHub repo: https://github.com/robng15/game-tracker (public)
- Live URL: https://gametracker.red-kite-dev.co.uk
- Hosted on Plesk — auto-deploys on `git push` via webhook
- Server path: `/var/www/vhosts/gametracker.red-kite-dev.co.uk/httpdocs`
- `config.php` is gitignored — must be created manually on server from `config.example.php`
- SQLite DB at `db/games.db` — `db/` folder needs `chmod 775` on server

## File Structure
```
game-tracker/
├── config.php              # IGDB credentials + DB path (gitignored)
├── config.example.php      # Template for config.php
├── index.php               # Dashboard — list all tracked games
├── search.php              # Search IGDB, click to add
├── add.php                 # Add game (pre-filled from IGDB or manual)
├── edit.php                # Edit tracking data
├── delete.php              # Delete a game
├── import.php              # CSV import
├── export.php              # CSV export
├── includes/
│   ├── db.php              # SQLite init + schema + migrations
│   ├── igdb.php            # IGDB API wrapper (auth, search, fetch)
│   ├── header.php          # Bootstrap 5 nav/header + dark theme CSS
│   └── footer.php          # Bootstrap footer
└── db/                     # SQLite database lives here
```

## Database Schema
```sql
games (
    id, igdb_id, title, cover_url, genres, platforms,
    release_year, developer, summary,
    my_rating (1–10), playtime_hours, completion_percent (0–100),
    status (backlog/playing/completed/dropped/wishlist/never-finished),
    platform_played (JSON array), format (Owned/Borrowed/Co-played),
    notes, added_at, updated_at
)
settings (key, value)  -- used for IGDB token caching
```

New columns are added via ALTER TABLE in `init_schema()` — safe to run on existing databases.

## Game Status Options
- Backlog
- Playing
- Completed
- Dropped
- Wishlist
- Never Finished

## Platform Played Options (multi-select, stored as JSON array)
Sinclair Spectrum, BBC Master, Acorn Archimedes, Commodore Amiga, Amstrad CPC 464,
Atari ST, Sega Game Gear, Sega Master System, Sega Mega Drive, Sega Saturn,
NES, SNES, Game Boy, Nintendo 64, Nintendo DS, Game Boy Advance, Wii,
Sony Playstation, Xbox, Xbox 360, PC, Steam Deck, Apple

## Format Options
- Owned
- Borrowed
- Co-played

## IGDB API
- Auth: Twitch OAuth2 — POST https://id.twitch.tv/oauth2/token
- Base URL: https://api.igdb.com/v4/
- Token cached in SQLite settings table (auto-refreshes ~60 days)
- Cover images: https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg
- Twitch app must have Client Type set to Confidential to generate a Client Secret

## CSV Format
Export columns: id, igdb_id, title, cover_url, genres, platforms, release_year,
developer, summary, my_rating, playtime_hours, completion_percent, status,
platform_played (comma-separated), format, notes, added_at, updated_at

Import accepts platform_played as either comma-separated string or JSON array.

---

## Change History

### 2026-04-08 — Initial build
- PHP/SQLite/Bootstrap 5 app scaffolded
- IGDB API integration with Twitch OAuth2 token caching
- Pages: index (dashboard), search, add, edit, delete, import, export
- Dark theme UI with stats row, sortable/filterable game table
- CSV export (Excel-compatible BOM), CSV import with skip/overwrite modes
- Git repo initialised, pushed to GitHub (robng15/game-tracker)

### 2026-04-08 — Deployment to Plesk
- Subdomain created: gametracker.red-kite-dev.co.uk
- Deployed via Plesk Git integration pulling from public GitHub repo
- Removed Plesk default index.html to allow index.php to serve
- BASE_URL set to empty string (app is at domain root)

### 2026-04-08 — New columns: Platform Played, Format, Never Finished status
- Added Platform Played field (single select dropdown, 23 options)
- Added Format field: Owned, Borrowed, Co-played
- Added Never Finished to status options
- Added Platform Played filter to dashboard
- SQLite migration runs automatically on existing DB

### 2026-04-08 — Platform Played made multi-select
- Changed from single select to scrollable checkbox grid
- Stored as JSON array in DB
- Display in table as comma-separated string
- Filter uses JSON LIKE matching for exact platform name
- Export decodes to comma-separated; import accepts both formats

### 2026-04-08 — Cover thumbnail increased to 100px wide
- game-cover CSS updated to 100×133px (maintains cover art aspect ratio)

### 2026-04-08 — Added Apple to Platform Played options
