<?php

require_once __DIR__ . '/includes/db.php';

$page_title = 'My Games — Game Tracker';

$db = get_db();

// Filters
$status_filter = $_GET['status'] ?? '';
$sort          = $_GET['sort'] ?? 'added_at';
$order         = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$platform_filter = $_GET['platform'] ?? '';

$allowed_sorts = ['title', 'release_year', 'my_rating', 'playtime_hours', 'completion_percent', 'added_at', 'status', 'platform_played'];
if (!in_array($sort, $allowed_sorts)) $sort = 'added_at';

$conditions = [];
$params     = [];
if ($status_filter !== '') {
    $conditions[] = 'status = ?';
    $params[]     = $status_filter;
}
if ($platform_filter !== '') {
    $conditions[] = 'platform_played LIKE ?';
    $params[]     = '%"' . $platform_filter . '"%';
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$games = $db->prepare("SELECT * FROM games {$where} ORDER BY {$sort} {$order}");
$games->execute($params);
$games = $games->fetchAll();

// Stats
$stats = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status='playing'   THEN 1 ELSE 0 END) AS playing,
        SUM(CASE WHEN status='backlog'   THEN 1 ELSE 0 END) AS backlog,
        ROUND(SUM(playtime_hours), 1) AS total_hours,
        ROUND(AVG(CASE WHEN my_rating IS NOT NULL THEN my_rating END), 1) AS avg_rating
    FROM games
")->fetch();

$statuses = ['backlog' => 'Backlog', 'playing' => 'Playing', 'completed' => 'Completed', 'dropped' => 'Dropped', 'wishlist' => 'Wishlist', 'never-finished' => 'Never Finished'];

$platforms_list = [
    'Sinclair Spectrum', 'BBC Master', 'Acorn Archimedes', 'Commodore Amiga',
    'Amstrad CPC 464', 'Atari ST', 'Sega Game Gear', 'Sega Master System',
    'Sega Mega Drive', 'Sega Saturn', 'NES', 'SNES', 'Game Boy', 'Nintendo 64',
    'Nintendo DS', 'Game Boy Advance', 'Wii', 'Sony Playstation', 'Xbox',
    'Xbox 360', 'PC', 'Steam Deck',
];

function sort_url(string $col, string $current_sort, string $current_order): string {
    $new_order = ($col === $current_sort && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $params = array_merge($_GET, ['sort' => $col, 'order' => $new_order]);
    return '?' . http_build_query($params);
}

function sort_icon(string $col, string $current_sort, string $current_order): string {
    if ($col !== $current_sort) return '<i class="bi bi-arrow-down-up text-muted ms-1" style="font-size:.75rem"></i>';
    return $current_order === 'ASC'
        ? '<i class="bi bi-arrow-up ms-1" style="font-size:.75rem"></i>'
        : '<i class="bi bi-arrow-down ms-1" style="font-size:.75rem"></i>';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Total Games',   'value' => $stats['total'],       'icon' => 'collection',     'color' => '#6c63ff'],
        ['label' => 'Completed',     'value' => $stats['completed'],    'icon' => 'check-circle',   'color' => '#6c63ff'],
        ['label' => 'Playing',       'value' => $stats['playing'],      'icon' => 'play-circle',    'color' => '#198754'],
        ['label' => 'Backlog',       'value' => $stats['backlog'],      'icon' => 'clock-history',  'color' => '#fd7e14'],
        ['label' => 'Total Hours',   'value' => ($stats['total_hours'] ?? 0) . 'h', 'icon' => 'stopwatch', 'color' => '#0dcaf0'],
        ['label' => 'Avg Rating',    'value' => $stats['avg_rating'] ?? '—', 'icon' => 'star',      'color' => '#ffc107'],
    ] as $s): ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card h-100 text-center py-3">
            <i class="bi bi-<?= $s['icon'] ?> fs-3 mb-1" style="color:<?= $s['color'] ?>"></i>
            <div class="fs-4 fw-bold"><?= htmlspecialchars((string)$s['value']) ?></div>
            <div class="text-muted small"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters & Controls -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
            <div class="col-auto">
                <label class="col-form-label text-muted small">Status:</label>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($statuses as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $status_filter === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="col-form-label text-muted small">Platform:</label>
            </div>
            <div class="col-auto">
                <select name="platform" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <?php foreach ($platforms_list as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $platform_filter === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($status_filter || $platform_filter): ?>
            <div class="col-auto">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Games Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= count($games) ?> game<?= count($games) !== 1 ? 's' : '' ?></span>
        <a href="search.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Add Game</a>
    </div>

    <?php if (empty($games)): ?>
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-controller fs-1 d-block mb-3"></i>
        No games yet. <a href="search.php">Search &amp; add one</a> or <a href="add.php">add manually</a>.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:110px"></th>
                    <th><a href="<?= sort_url('title', $sort, $order) ?>" class="text-decoration-none text-reset">Title<?= sort_icon('title', $sort, $order) ?></a></th>
                    <th><a href="<?= sort_url('release_year', $sort, $order) ?>" class="text-decoration-none text-reset">Year<?= sort_icon('release_year', $sort, $order) ?></a></th>
                    <th><a href="<?= sort_url('status', $sort, $order) ?>" class="text-decoration-none text-reset">Status<?= sort_icon('status', $sort, $order) ?></a></th>
                    <th><a href="<?= sort_url('platform_played', $sort, $order) ?>" class="text-decoration-none text-reset">Platform Played<?= sort_icon('platform_played', $sort, $order) ?></a></th>
                    <th>Format</th>
                    <th><a href="<?= sort_url('my_rating', $sort, $order) ?>" class="text-decoration-none text-reset">Rating<?= sort_icon('my_rating', $sort, $order) ?></a></th>
                    <th><a href="<?= sort_url('playtime_hours', $sort, $order) ?>" class="text-decoration-none text-reset">Hours<?= sort_icon('playtime_hours', $sort, $order) ?></a></th>
                    <th><a href="<?= sort_url('completion_percent', $sort, $order) ?>" class="text-decoration-none text-reset">Complete<?= sort_icon('completion_percent', $sort, $order) ?></a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($games as $g): ?>
            <tr>
                <td>
                    <?php if ($g['cover_url']): ?>
                        <img src="<?= htmlspecialchars($g['cover_url']) ?>" class="game-cover" alt="">
                    <?php else: ?>
                        <div class="game-cover-placeholder"><i class="bi bi-controller"></i></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($g['title']) ?></div>
                    <?php if ($g['developer']): ?>
                    <div class="text-muted small"><?= htmlspecialchars($g['developer']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-muted"><?= htmlspecialchars((string)($g['release_year'] ?? '—')) ?></td>
                <td>
                    <span class="badge badge-status-<?= htmlspecialchars($g['status']) ?>">
                        <?= htmlspecialchars($statuses[$g['status']] ?? ucfirst($g['status'])) ?>
                    </span>
                </td>
                <td class="text-muted small"><?php
                    $pp = !empty($g['platform_played']) ? json_decode($g['platform_played'], true) : [];
                    echo $pp ? htmlspecialchars(implode(', ', $pp)) : '—';
                ?></td>
                <td class="text-muted small"><?= htmlspecialchars($g['format'] ?? '—') ?></td>
                <td>
                    <?php if ($g['my_rating'] !== null): ?>
                    <span class="fw-semibold"><?= htmlspecialchars((string)$g['my_rating']) ?></span>
                    <span class="text-muted small">/10</span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($g['playtime_hours'] !== null): ?>
                    <?= htmlspecialchars((string)$g['playtime_hours']) ?>h
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td style="min-width:100px">
                    <div class="d-flex align-items-center gap-2">
                        <div class="completion-bar flex-grow-1">
                            <div class="fill" style="width:<?= (int)$g['completion_percent'] ?>%"></div>
                        </div>
                        <span class="text-muted small" style="min-width:32px"><?= (int)$g['completion_percent'] ?>%</span>
                    </div>
                </td>
                <td class="text-end">
                    <a href="edit.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="delete.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
