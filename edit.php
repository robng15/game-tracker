<?php

require_once __DIR__ . '/includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = get_db();

$game = $db->prepare('SELECT * FROM games WHERE id = ?');
$game->execute([$id]);
$game = $game->fetch();

if (!$game) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Edit — ' . $game['title'];
$error      = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title              = trim($_POST['title'] ?? '');
    $cover_url          = trim($_POST['cover_url'] ?? '') ?: null;
    $genres             = trim($_POST['genres'] ?? '') ?: null;
    $platforms          = trim($_POST['platforms'] ?? '') ?: null;
    $release_year       = (int)($_POST['release_year'] ?? 0) ?: null;
    $developer          = trim($_POST['developer'] ?? '') ?: null;
    $summary            = trim($_POST['summary'] ?? '') ?: null;
    $my_rating          = $_POST['my_rating'] !== '' ? (float)$_POST['my_rating'] : null;
    $playtime_hours     = $_POST['playtime_hours'] !== '' ? (float)$_POST['playtime_hours'] : null;
    $completion_percent = max(0, min(100, (int)($_POST['completion_percent'] ?? 0)));
    $status             = $_POST['status'] ?? 'backlog';
    $platform_played    = trim($_POST['platform_played'] ?? '') ?: null;
    $format             = trim($_POST['format'] ?? '') ?: null;
    $notes              = trim($_POST['notes'] ?? '') ?: null;

    if ($title === '') {
        $error = 'Title is required.';
    } else {
        $db->prepare("
            UPDATE games SET
                title = ?, cover_url = ?, genres = ?, platforms = ?,
                release_year = ?, developer = ?, summary = ?,
                my_rating = ?, playtime_hours = ?, completion_percent = ?,
                status = ?, platform_played = ?, format = ?,
                notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ")->execute([
            $title, $cover_url, $genres, $platforms, $release_year,
            $developer, $summary, $my_rating, $playtime_hours,
            $completion_percent, $status, $platform_played, $format, $notes, $id
        ]);

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Use POST values on validation failure, otherwise DB values
$v = ($error && $_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $game;

$statuses = ['backlog' => 'Backlog', 'playing' => 'Playing', 'completed' => 'Completed', 'dropped' => 'Dropped', 'wishlist' => 'Wishlist', 'never-finished' => 'Never Finished'];

$platforms_list = [
    'Sinclair Spectrum', 'BBC Master', 'Acorn Archimedes', 'Commodore Amiga',
    'Amstrad CPC 464', 'Atari ST', 'Sega Game Gear', 'Sega Master System',
    'Sega Mega Drive', 'Sega Saturn', 'NES', 'SNES', 'Game Boy', 'Nintendo 64',
    'Nintendo DS', 'Game Boy Advance', 'Wii', 'Sony Playstation', 'Xbox',
    'Xbox 360', 'PC', 'Steam Deck',
];

$formats_list = ['Owned', 'Borrowed', 'Co-played'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0">Edit Game</h4>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
    <div class="card mb-4">
        <div class="card-header">
            Game Details
            <?php if ($game['igdb_id']): ?>
            <span class="badge bg-secondary ms-2">IGDB #<?= $game['igdb_id'] ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">

            <?php if ($game['cover_url']): ?>
            <div class="mb-3 d-flex gap-4 align-items-start">
                <img src="<?= htmlspecialchars($game['cover_url']) ?>" style="width:80px;border-radius:6px;" alt="">
                <div class="flex-grow-1">
                    <div class="mb-2">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= htmlspecialchars($v['title'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label small text-muted">Cover URL</label>
                        <input type="url" name="cover_url" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($v['cover_url'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= htmlspecialchars($v['title'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cover URL</label>
                    <input type="url" name="cover_url" class="form-control"
                           value="<?= htmlspecialchars($v['cover_url'] ?? '') ?>" placeholder="https://...">
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Developer</label>
                    <input type="text" name="developer" class="form-control"
                           value="<?= htmlspecialchars($v['developer'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Release Year</label>
                    <input type="number" name="release_year" class="form-control" min="1970" max="2099"
                           value="<?= htmlspecialchars((string)($v['release_year'] ?? '')) ?>">
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label">Genres</label>
                    <input type="text" name="genres" class="form-control"
                           value="<?= htmlspecialchars($v['genres'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Platforms</label>
                    <input type="text" name="platforms" class="form-control"
                           value="<?= htmlspecialchars($v['platforms'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Summary</label>
                <textarea name="summary" class="form-control" rows="3"><?= htmlspecialchars($v['summary'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">My Tracking</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($v['status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">My Rating</label>
                    <div class="input-group">
                        <input type="number" name="my_rating" class="form-control"
                               min="1" max="10" step="0.5" placeholder="—"
                               value="<?= htmlspecialchars((string)($v['my_rating'] ?? '')) ?>">
                        <span class="input-group-text">/ 10</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Playtime</label>
                    <div class="input-group">
                        <input type="number" name="playtime_hours" class="form-control"
                               min="0" step="0.5" placeholder="0"
                               value="<?= htmlspecialchars((string)($v['playtime_hours'] ?? '')) ?>">
                        <span class="input-group-text">hrs</span>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label d-flex justify-content-between">
                    <span>Completion</span>
                    <span id="completion-val"><?= (int)($v['completion_percent'] ?? 0) ?>%</span>
                </label>
                <input type="range" name="completion_percent" class="form-range" min="0" max="100" step="5"
                       value="<?= (int)($v['completion_percent'] ?? 0) ?>"
                       oninput="document.getElementById('completion-val').textContent = this.value + '%'">
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label">Platform Played</label>
                    <select name="platform_played" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($platforms_list as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= ($v['platform_played'] ?? '') === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($formats_list as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= ($v['format'] ?? '') === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($v['notes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check2 me-1"></i>Save Changes
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        <a href="delete.php?id=<?= $id ?>" class="btn btn-outline-danger ms-auto">
            <i class="bi bi-trash me-1"></i>Delete
        </a>
    </div>
</form>

</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
