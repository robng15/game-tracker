<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/igdb.php';

$page_title = 'Add Game — Game Tracker';

$igdb_id = isset($_GET['igdb_id']) ? (int)$_GET['igdb_id'] : null;
$game    = [];
$error   = null;

// Pre-fill from IGDB if igdb_id supplied
if ($igdb_id) {
    try {
        $game = igdb_get_game($igdb_id) ?? [];
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title              = trim($_POST['title'] ?? '');
    $igdb_id_post       = (int)($_POST['igdb_id'] ?? 0) ?: null;
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
    $pp_raw          = $_POST['platform_played'] ?? [];
    $platform_played = !empty($pp_raw) ? json_encode(array_values($pp_raw)) : null;
    $format             = trim($_POST['format'] ?? '') ?: null;
    $notes              = trim($_POST['notes'] ?? '') ?: null;

    if ($title === '') {
        $error = 'Title is required.';
    } else {
        $db = get_db();
        $db->prepare("
            INSERT INTO games
                (igdb_id, title, cover_url, genres, platforms, release_year, developer, summary,
                 my_rating, playtime_hours, completion_percent, status, platform_played, format, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $igdb_id_post, $title, $cover_url, $genres, $platforms, $release_year,
            $developer, $summary, $my_rating, $playtime_hours, $completion_percent,
            $status, $platform_played, $format, $notes
        ]);

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

$statuses = ['backlog' => 'Backlog', 'playing' => 'Playing', 'completed' => 'Completed', 'dropped' => 'Dropped', 'wishlist' => 'Wishlist', 'never-finished' => 'Never Finished'];

$platforms_list = [
    'Sinclair Spectrum', 'BBC Master', 'Acorn Archimedes', 'Commodore Amiga',
    'Amstrad CPC 464', 'Atari ST', 'Sega Game Gear', 'Sega Master System',
    'Sega Mega Drive', 'Sega Saturn', 'NES', 'SNES', 'Game Boy', 'Nintendo 64',
    'Nintendo DS', 'Game Boy Advance', 'Wii', 'Sony Playstation', 'Xbox',
    'Xbox 360', 'PC', 'Steam Deck',
];

$formats_list = ['Owned', 'Borrowed', 'Co-played'];
$is_igdb  = !empty($game);

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-8">

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= $igdb_id ? 'search.php' : BASE_URL . '/index.php' ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0"><?= $is_igdb ? 'Add Game from IGDB' : 'Add Game Manually' ?></h4>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="igdb_id" value="<?= htmlspecialchars((string)($game['igdb_id'] ?? '')) ?>">
    <input type="hidden" name="cover_url" value="<?= htmlspecialchars($game['cover_url'] ?? '') ?>">

    <div class="card mb-4">
        <div class="card-header">Game Details <?= $is_igdb ? '<span class="badge bg-secondary ms-2">From IGDB</span>' : '' ?></div>
        <div class="card-body">

            <?php if ($is_igdb && $game['cover_url']): ?>
            <div class="mb-3 text-center text-md-start d-flex gap-4 align-items-start">
                <img src="<?= htmlspecialchars($game['cover_url']) ?>" style="width:100px;border-radius:6px;" alt="">
                <div>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($game['title']) ?></div>
                    <?php if ($game['developer']): ?>
                    <div class="text-muted"><?= htmlspecialchars($game['developer']) ?></div>
                    <?php endif; ?>
                    <?php if ($game['release_year']): ?>
                    <div class="text-muted small"><?= $game['release_year'] ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       value="<?= htmlspecialchars($_POST['title'] ?? $game['title'] ?? '') ?>"
                       <?= $is_igdb ? 'readonly' : '' ?>>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Developer</label>
                    <input type="text" name="developer" class="form-control"
                           value="<?= htmlspecialchars($_POST['developer'] ?? $game['developer'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Release Year</label>
                    <input type="number" name="release_year" class="form-control" min="1970" max="2099"
                           value="<?= htmlspecialchars((string)($_POST['release_year'] ?? $game['release_year'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <?php if (!$is_igdb): ?>
                    <label class="form-label">Cover URL</label>
                    <input type="url" name="cover_url" class="form-control"
                           value="<?= htmlspecialchars($_POST['cover_url'] ?? '') ?>" placeholder="https://...">
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label">Genres</label>
                    <input type="text" name="genres" class="form-control"
                           value="<?= htmlspecialchars($_POST['genres'] ?? $game['genres'] ?? '') ?>"
                           placeholder="e.g. RPG, Action">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Platforms</label>
                    <input type="text" name="platforms" class="form-control"
                           value="<?= htmlspecialchars($_POST['platforms'] ?? $game['platforms'] ?? '') ?>"
                           placeholder="e.g. PC, PlayStation 5">
                </div>
            </div>

            <?php if (!empty($game['summary']) || isset($_POST['summary'])): ?>
            <div class="mt-3">
                <label class="form-label">Summary</label>
                <textarea name="summary" class="form-control" rows="3"><?= htmlspecialchars($_POST['summary'] ?? $game['summary'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
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
                        <option value="<?= $val ?>" <?= ($_POST['status'] ?? 'backlog') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">My Rating</label>
                    <div class="input-group">
                        <input type="number" name="my_rating" class="form-control"
                               min="1" max="10" step="0.5" placeholder="—"
                               value="<?= htmlspecialchars((string)($_POST['my_rating'] ?? '')) ?>">
                        <span class="input-group-text">/ 10</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Playtime</label>
                    <div class="input-group">
                        <input type="number" name="playtime_hours" class="form-control"
                               min="0" step="0.5" placeholder="0"
                               value="<?= htmlspecialchars((string)($_POST['playtime_hours'] ?? '')) ?>">
                        <span class="input-group-text">hrs</span>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label d-flex justify-content-between">
                    <span>Completion</span>
                    <span id="completion-val"><?= (int)($_POST['completion_percent'] ?? 0) ?>%</span>
                </label>
                <input type="range" name="completion_percent" class="form-range" min="0" max="100" step="5"
                       value="<?= (int)($_POST['completion_percent'] ?? 0) ?>"
                       oninput="document.getElementById('completion-val').textContent = this.value + '%'">
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-8">
                    <label class="form-label">Platform Played</label>
                    <?php $checked_platforms = $_POST['platform_played'] ?? []; ?>
                    <div style="max-height:160px;overflow-y:auto;background:#2a2a3e;border:1px solid #3e3e5e;border-radius:.375rem;padding:.5rem .75rem;">
                        <div class="row g-1">
                        <?php foreach ($platforms_list as $p): ?>
                        <div class="col-6">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox"
                                       name="platform_played[]" value="<?= htmlspecialchars($p) ?>"
                                       id="pp_add_<?= md5($p) ?>"
                                       <?= in_array($p, $checked_platforms) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="pp_add_<?= md5($p) ?>">
                                    <?= htmlspecialchars($p) ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($formats_list as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>" <?= ($_POST['format'] ?? '') === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"
                          placeholder="Personal notes, thoughts, etc."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-plus-circle me-1"></i>Add Game
        </button>
        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
