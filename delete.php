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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $db->prepare('DELETE FROM games WHERE id = ?')->execute([$id]);
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Delete — ' . $game['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-5">
    <div class="card border-danger">
        <div class="card-header text-danger fw-semibold">
            <i class="bi bi-exclamation-triangle me-2"></i>Delete Game
        </div>
        <div class="card-body">
            <div class="d-flex gap-3 align-items-start mb-3">
                <?php if ($game['cover_url']): ?>
                <img src="<?= htmlspecialchars($game['cover_url']) ?>" style="width:60px;border-radius:4px;" alt="">
                <?php endif; ?>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($game['title']) ?></div>
                    <?php if ($game['developer']): ?>
                    <div class="text-muted small"><?= htmlspecialchars($game['developer']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-muted">Are you sure you want to delete this game? This cannot be undone.</p>
            <form method="post" class="d-flex gap-2">
                <button type="submit" name="confirm" value="1" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>Yes, Delete
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
