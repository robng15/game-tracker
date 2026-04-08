# Game Tracker — Project Notes

## Overview
PHP app for tracking video games played. Single user, SQLite database, Bootstrap 5 frontend.

## Features
- Search IGDB API to pull game details
- Manual add if game not found on IGDB
- Personal rating (1–10), playtime (hours), completion (%), status, notes
- CSV import/export

## Stack
- PHP
- SQLite
- Bootstrap 5
- IGDB API (via Twitch OAuth2)

## File Structure
```
game-tracker/
├── config.php              # IGDB credentials + DB path
├── index.php               # Dashboard — list all tracked games
├── search.php              # Search IGDB, click to add
├── add.php                 # Add game (pre-filled from IGDB or manual)
├── edit.php                # Edit tracking data
├── delete.php              # Delete a game
├── import.php              # CSV import
├── export.php              # CSV export
├── includes/
│   ├── db.php              # SQLite init + schema
│   ├── igdb.php            # IGDB API wrapper (auth, search, fetch)
│   ├── header.php          # Bootstrap 5 nav/header
│   └── footer.php          # Bootstrap footer
└── db/                     # SQLite database lives here
```

## Database Schema
```sql
games (
    id, igdb_id, title, cover_url, genres, platforms,
    release_year, developer, summary,
    my_rating (1–10), playtime_hours, completion_percent (0–100),
    status (backlog/playing/completed/dropped/wishlist),
    notes, added_at, updated_at
)
settings (key, value)  -- used for IGDB token caching
```

## Game Status Options
- Backlog
- Playing
- Completed
- Dropped
- Wishlist

## IGDB API
- Auth: Twitch OAuth2 — POST https://id.twitch.tv/oauth2/token
- Base URL: https://api.igdb.com/v4/
- Token cached in SQLite settings table
- Cover images: https://images.igdb.com/igdb/image/upload/t_cover_big/{image_id}.jpg

## CSV Format
Columns: id, igdb_id, title, cover_url, genres, platforms, release_year,
         developer, summary, my_rating, playtime_hours, completion_percent,
         status, notes, added_at, updated_at
