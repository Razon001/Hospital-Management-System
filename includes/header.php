<?php
/**
 * Shared header: <head>, topbar, opens the layout wrapper divs.
 * Expects $base_url and $pageTitle to be set by the including page.
 * footer.php closes what this file opens.
 */
$base_url = $base_url ?? '';
$pageTitle = $pageTitle ?? 'Dashboard';
$pageEyebrow = $pageEyebrow ?? 'MediCore HMS';
$__u = currentUser();
$__initials = '';
if ($__u) {
    $parts = preg_split('/\s+/', trim($__u['full_name']));
    $__initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[count($parts) > 1 ? count($parts) - 1 : 0], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($pageTitle) ?> — MediCore HMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= $base_url ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<?php require_once __DIR__ . '/sidebar.php'; ?>
    <div class="main-col">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
                <div>
                    <div class="page-eyebrow"><?= sanitize($pageEyebrow) ?></div>
                    <h1 class="page-title"><?= sanitize($pageTitle) ?></h1>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn user-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border:1px solid var(--line);">
                    <span class="avatar"><?= sanitize($__initials) ?></span>
                    <span class="text-start d-none d-sm-block">
                        <span style="display:block;font-size:13px;font-weight:600;line-height:1.2;"><?= sanitize($__u['full_name']) ?></span>
                        <span class="role-pill"><?= sanitize($__u['role']) ?></span>
                    </span>
                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text text-muted" style="font-size:12.5px;">@<?= sanitize($__u['username']) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $base_url ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
        <div class="content-area">
            <?php renderFlash(); ?>
