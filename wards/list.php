<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$pageTitle = 'Wards & Beds';
$pageEyebrow = 'Staff & Facility';
$activeMenu = 'wards';

$typeFilter = trim($_GET['type'] ?? '');
$sql = "SELECT * FROM wards WHERE 1=1";
$params = [];
if ($typeFilter !== '') { $sql .= " AND ward_type = ?"; $params[] = $typeFilter; }
$sql .= " ORDER BY ward_type, ward_name, room_no, bed_no";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$wards = $stmt->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) c FROM wards GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="stat-tile"><span class="eyebrow">Available</span><div class="stat-number" style="color:var(--success)"><?= (int)($counts['available'] ?? 0) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-tile"><span class="eyebrow">Occupied</span><div class="stat-number" style="color:var(--danger)"><?= (int)($counts['occupied'] ?? 0) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-tile"><span class="eyebrow">Maintenance</span><div class="stat-number" style="color:var(--warning)"><?= (int)($counts['maintenance'] ?? 0) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-tile"><span class="eyebrow">Total Beds</span><div class="stat-number"><?= array_sum($counts) ?></div></div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <select name="type" class="form-select" onchange="this.form.submit()" style="max-width:200px;">
            <option value="">All Ward Types</option>
            <?php foreach (['General','Semi-Private','Private','ICU'] as $t): ?>
                <option value="<?= $t ?>" <?= $typeFilter === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (hasRole(['admin','receptionist'])): ?>
        <a href="form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Ward / Bed</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead><tr><th>Ward</th><th>Type</th><th>Room</th><th>Bed</th><th>Charge / Day</th><th>Status</th><?php if (hasRole(['admin','receptionist'])): ?><th style="width:110px;">Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php if (!$wards): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-building"></i>No wards configured yet.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($wards as $w): ?>
            <tr>
                <td><strong><?= sanitize($w['ward_name']) ?></strong></td>
                <td><?= sanitize($w['ward_type']) ?></td>
                <td><?= sanitize($w['room_no'] ?: '—') ?></td>
                <td><?= sanitize($w['bed_no'] ?: '—') ?></td>
                <td class="mono">$<?= formatMoney($w['charge_per_day']) ?></td>
                <td><?= statusBadge($w['status']) ?></td>
                <?php if (hasRole(['admin','receptionist'])): ?>
                <td>
                    <a href="form.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this ward/bed entry?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $w['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
