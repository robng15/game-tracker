<?php

require_once __DIR__ . '/includes/db.php';

$page_title = 'Import CSV — Game Tracker';

$result = null;
$error  = null;

$expected_columns = [
    'title', 'igdb_id', 'cover_url', 'genres', 'platforms',
    'release_year', 'developer', 'summary', 'my_rating', 'playtime_hours',
    'completion_percent', 'status', 'notes',
];

$valid_statuses = ['backlog', 'playing', 'completed', 'dropped', 'wishlist', 'never-finished'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed.';
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $error = 'Please upload a .csv file.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            $error = 'Could not read CSV headers.';
        } else {
            $headers = array_map('strtolower', array_map('trim', $headers));

            if (!in_array('title', $headers)) {
                $error = 'CSV must contain at least a "title" column.';
            } else {
                $db         = get_db();
                $imported   = 0;
                $skipped    = 0;
                $row_num    = 1;
                $mode       = $_POST['mode'] ?? 'skip'; // skip or overwrite duplicates

                while (($row = fgetcsv($handle)) !== false) {
                    $row_num++;
                    if (count($row) !== count($headers)) continue;

                    $data = array_combine($headers, $row);

                    $title = trim($data['title'] ?? '');
                    if ($title === '') {
                        $skipped++;
                        continue;
                    }

                    // Check for existing by igdb_id or title
                    $igdb_id = !empty($data['igdb_id']) ? (int)$data['igdb_id'] : null;

                    $existing = null;
                    if ($igdb_id) {
                        $stmt = $db->prepare('SELECT id FROM games WHERE igdb_id = ?');
                        $stmt->execute([$igdb_id]);
                        $existing = $stmt->fetch();
                    }
                    if (!$existing) {
                        $stmt = $db->prepare('SELECT id FROM games WHERE title = ?');
                        $stmt->execute([$title]);
                        $existing = $stmt->fetch();
                    }

                    $cover_url          = trim($data['cover_url'] ?? '') ?: null;
                    $genres             = trim($data['genres'] ?? '') ?: null;
                    $platforms          = trim($data['platforms'] ?? '') ?: null;
                    $release_year       = !empty($data['release_year']) ? (int)$data['release_year'] : null;
                    $developer          = trim($data['developer'] ?? '') ?: null;
                    $summary            = trim($data['summary'] ?? '') ?: null;
                    $my_rating          = ($data['my_rating'] ?? '') !== '' ? (float)$data['my_rating'] : null;
                    $playtime_hours     = ($data['playtime_hours'] ?? '') !== '' ? (float)$data['playtime_hours'] : null;
                    $completion_percent = max(0, min(100, (int)($data['completion_percent'] ?? 0)));
                    $status             = in_array($data['status'] ?? '', $valid_statuses) ? $data['status'] : 'backlog';
                    $platform_played    = trim($data['platform_played'] ?? '') ?: null;
                    $format             = trim($data['format'] ?? '') ?: null;
                    $notes              = trim($data['notes'] ?? '') ?: null;

                    if ($existing && $mode === 'skip') {
                        $skipped++;
                        continue;
                    }

                    if ($existing && $mode === 'overwrite') {
                        $db->prepare("
                            UPDATE games SET
                                igdb_id = ?, title = ?, cover_url = ?, genres = ?, platforms = ?,
                                release_year = ?, developer = ?, summary = ?,
                                my_rating = ?, playtime_hours = ?, completion_percent = ?,
                                status = ?, platform_played = ?, format = ?,
                                notes = ?, updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ")->execute([
                            $igdb_id, $title, $cover_url, $genres, $platforms, $release_year,
                            $developer, $summary, $my_rating, $playtime_hours,
                            $completion_percent, $status, $platform_played, $format, $notes, $existing['id']
                        ]);
                        $imported++;
                    } else {
                        $db->prepare("
                            INSERT INTO games
                                (igdb_id, title, cover_url, genres, platforms, release_year, developer, summary,
                                 my_rating, playtime_hours, completion_percent, status, platform_played, format, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ")->execute([
                            $igdb_id, $title, $cover_url, $genres, $platforms, $release_year,
                            $developer, $summary, $my_rating, $playtime_hours,
                            $completion_percent, $status, $platform_played, $format, $notes
                        ]);
                        $imported++;
                    }
                }

                fclose($handle);
                $result = compact('imported', 'skipped');
            }
        }
        if ($handle) fclose($handle);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-6">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0"><i class="bi bi-upload me-2"></i>Import CSV</h4>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($result !== null): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2"></i>
    Import complete — <strong><?= $result['imported'] ?></strong> game<?= $result['imported'] !== 1 ? 's' : '' ?> imported,
    <strong><?= $result['skipped'] ?></strong> skipped.
    <a href="index.php" class="alert-link ms-2">View My Games</a>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">Upload CSV File</div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <div class="form-text text-muted">
                    Must include a <code>title</code> column. Use <a href="export.php">Export CSV</a> to get the correct format.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">If a duplicate is found (same title or IGDB ID):</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="mode" id="mode-skip" value="skip" checked>
                    <label class="form-check-label" for="mode-skip">Skip — keep existing entry</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="mode" id="mode-overwrite" value="overwrite">
                    <label class="form-check-label" for="mode-overwrite">Overwrite — update existing entry</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i>Import
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Expected CSV Columns</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.85rem">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Required</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ([
                    ['title',              true,  'Game title'],
                    ['igdb_id',            false, 'IGDB numeric ID (optional)'],
                    ['cover_url',          false, 'Full URL to cover image'],
                    ['genres',             false, 'Comma-separated'],
                    ['platforms',          false, 'Comma-separated'],
                    ['release_year',       false, '4-digit year'],
                    ['developer',          false, 'Studio / developer name'],
                    ['summary',            false, 'Game description'],
                    ['my_rating',          false, '1–10, decimals allowed'],
                    ['playtime_hours',     false, 'Hours played'],
                    ['completion_percent', false, '0–100'],
                    ['status',             false, 'backlog / playing / completed / dropped / wishlist / never-finished'],
                    ['platform_played',    false, 'e.g. PC, SNES, Sega Mega Drive'],
                    ['format',             false, 'Owned / Borrowed / Co-played'],
                    ['notes',              false, 'Personal notes'],
                ] as [$col, $req, $note]): ?>
                <tr>
                    <td><code><?= $col ?></code></td>
                    <td><?= $req ? '<span class="text-danger">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    <td class="text-muted"><?= $note ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
