<?php

require_once __DIR__ . '/includes/db.php';

$db    = get_db();
$games = $db->query('SELECT * FROM games ORDER BY title ASC')->fetchAll();

$filename = 'game-tracker-export-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fwrite($out, "\xEF\xBB\xBF");

$columns = [
    'id', 'igdb_id', 'title', 'cover_url', 'genres', 'platforms',
    'release_year', 'developer', 'summary', 'my_rating', 'playtime_hours',
    'completion_percent', 'status', 'notes', 'added_at', 'updated_at',
];

fputcsv($out, $columns);

foreach ($games as $game) {
    fputcsv($out, array_map(fn($col) => $game[$col] ?? '', $columns));
}

fclose($out);
exit;
