<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title ?? 'Game Tracker') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #121212; color: #e0e0e0; }
        .navbar { background-color: #1e1e2e !important; }
        .card { background-color: #1e1e2e; border: 1px solid #2e2e3e; color: #e0e0e0; }
        .card-header { background-color: #2a2a3e; border-bottom: 1px solid #2e2e3e; }
        .table { color: #e0e0e0; }
        .table-hover tbody tr:hover { background-color: #2a2a3e; color: #fff; }
        .table thead th { border-bottom-color: #2e2e3e; color: #aaa; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
        .form-control, .form-select { background-color: #2a2a3e; border-color: #3e3e5e; color: #e0e0e0; }
        .form-control:focus, .form-select:focus { background-color: #2a2a3e; border-color: #6c63ff; color: #fff; box-shadow: 0 0 0 .2rem rgba(108,99,255,.25); }
        .form-control::placeholder { color: #888; }
        .btn-primary { background-color: #6c63ff; border-color: #6c63ff; }
        .btn-primary:hover { background-color: #574fd6; border-color: #574fd6; }
        .btn-outline-secondary { color: #aaa; border-color: #555; }
        .btn-outline-secondary:hover { background-color: #2a2a3e; color: #fff; }
        .game-cover { width: 60px; height: 80px; object-fit: cover; border-radius: 4px; }
        .game-cover-placeholder { width: 60px; height: 80px; background: #2e2e3e; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #555; font-size: 1.5rem; }
        .search-cover { width: 80px; height: 107px; object-fit: cover; border-radius: 4px; }
        .search-cover-placeholder { width: 80px; height: 107px; background: #2e2e3e; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #555; font-size: 2rem; }
        .badge-status-backlog         { background-color: #555; }
        .badge-status-playing         { background-color: #198754; }
        .badge-status-completed       { background-color: #6c63ff; }
        .badge-status-dropped         { background-color: #dc3545; }
        .badge-status-wishlist        { background-color: #fd7e14; }
        .badge-status-never-finished  { background-color: #6c757d; }
        .completion-bar { height: 6px; border-radius: 3px; background: #2e2e3e; }
        .completion-bar .fill { height: 100%; border-radius: 3px; background: #6c63ff; }
        a { color: #a89fff; }
        a:hover { color: #fff; }
        .text-muted { color: #888 !important; }
        hr { border-color: #2e2e3e; }
        .modal-content { background-color: #1e1e2e; color: #e0e0e0; border: 1px solid #2e2e3e; }
        .modal-header, .modal-footer { border-color: #2e2e3e; }
        .input-group-text { background-color: #2a2a3e; border-color: #3e3e5e; color: #aaa; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-controller me-2"></i>Game Tracker
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php"><i class="bi bi-list-ul me-1"></i>My Games</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/search.php"><i class="bi bi-search me-1"></i>Search &amp; Add</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/add.php"><i class="bi bi-plus-circle me-1"></i>Manual Add</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-down-up me-1"></i>Import / Export
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" style="background:#1e1e2e; border-color:#2e2e3e;">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/import.php"><i class="bi bi-upload me-2"></i>Import CSV</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/export.php"><i class="bi bi-download me-2"></i>Export CSV</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container pb-5">
