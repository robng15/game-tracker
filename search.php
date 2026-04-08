<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/igdb.php';

$page_title = 'Search Games — Game Tracker';

$query   = trim($_GET['q'] ?? '');
$results = [];
$error   = null;

if ($query !== '') {
    try {
        $results = igdb_search($query);
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

// Check which IGDB IDs are already tracked
$tracked_igdb_ids = [];
if (!empty($results)) {
    $db   = get_db();
    $ids  = array_filter(array_column($results, 'igdb_id'));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT igdb_id FROM games WHERE igdb_id IN ({$placeholders})");
        $stmt->execute($ids);
        $tracked_igdb_ids = array_column($stmt->fetchAll(), 'igdb_id');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mb-4">
    <div class="col-lg-8">
        <h4 class="mb-3"><i class="bi bi-search me-2"></i>Search &amp; Add Games</h4>
        <form method="get" class="d-flex gap-2">
            <input type="text" name="q" class="form-control"
                   placeholder="Search for a game..." value="<?= htmlspecialchars($query) ?>" autofocus>
            <button type="submit" class="btn btn-primary px-4">Search</button>
        </form>
        <div class="text-muted small mt-2">
            Can't find your game? <a href="add.php">Add it manually</a>.
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
    <div class="mt-1 small">Check your IGDB credentials in <code>config.php</code>.</div>
</div>
<?php endif; ?>

<?php if ($query !== '' && !$error): ?>
    <?php if (empty($results)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-emoji-frown fs-1 d-block mb-3"></i>
        No results found for <strong><?= htmlspecialchars($query) ?></strong>.
        <div class="mt-2"><a href="add.php">Add it manually instead</a>.</div>
    </div>
    <?php else: ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
        <?php foreach ($results as $game): ?>
        <?php $already_tracked = in_array($game['igdb_id'], $tracked_igdb_ids); ?>
        <div class="col">
            <div class="card h-100 position-relative <?= $already_tracked ? 'opacity-75' : '' ?>">
                <?php if ($already_tracked): ?>
                <div class="position-absolute top-0 end-0 m-1">
                    <span class="badge bg-success"><i class="bi bi-check2"></i> Added</span>
                </div>
                <?php endif; ?>
                <?php if ($game['cover_url']): ?>
                    <img src="<?= htmlspecialchars($game['cover_url']) ?>" class="card-img-top" style="height:180px;object-fit:cover;" alt="">
                <?php else: ?>
                    <div style="height:180px;background:#2e2e3e;display:flex;align-items:center;justify-content:center;color:#555;">
                        <i class="bi bi-controller fs-1"></i>
                    </div>
                <?php endif; ?>
                <div class="card-body p-2">
                    <div class="fw-semibold small lh-sm mb-1"><?= htmlspecialchars($game['title']) ?></div>
                    <?php if ($game['release_year']): ?>
                    <div class="text-muted" style="font-size:.75rem"><?= $game['release_year'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer p-2">
                    <?php if ($already_tracked): ?>
                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>Already Added</button>
                    <?php else: ?>
                    <a href="add.php?igdb_id=<?= $game['igdb_id'] ?>"
                       class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-plus me-1"></i>Add
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
