<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$pageTitle = 'Pharmacy';
$pageEyebrow = 'Finance & Pharmacy';
$activeMenu = 'pharmacy';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM medicines WHERE 1=1";
$params = [];
if ($search !== '') { $sql .= " AND (name LIKE ? OR category LIKE ?)"; $params = ["%$search%", "%$search%"]; }
$sql .= " ORDER BY name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$lowStockCount = 0; $expiringCount = 0; $expiredCount = 0;
$today = new DateTime();
foreach ($medicines as $m) {
    if ($m['quantity'] <= $m['reorder_level']) $lowStockCount++;
    if ($m['expiry_date']) {
        $exp = new DateTime($m['expiry_date']);
        $diffDays = (int)$today->diff($exp)->format('%r%a');
        if ($diffDays < 0) $expiredCount++;
        elseif ($diffDays <= 30) $expiringCount++;
    }
}

require_once '../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="stat-tile <?= $lowStockCount ? 'stat-warn' : '' ?>"><span class="eyebrow">Low Stock Items</span><div class="stat-number"><?= $lowStockCount ?></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-tile <?= $expiringCount ? 'stat-warn' : '' ?>"><span class="eyebrow">Expiring in 30 Days</span><div class="stat-number"><?= $expiringCount ?></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-tile <?= $expiredCount ? 'stat-warn' : '' ?>"><span class="eyebrow">Expired</span><div class="stat-number"><?= $expiredCount ?></div></div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" class="form-control" placeholder="Search medicine or category..." value="<?= sanitize($search) ?>" style="min-width:260px;">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <?php if (hasRole(['admin','receptionist'])): ?>
        <a href="form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Medicine</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead><tr><th>Name</th><th>Category</th><th>Manufacturer</th><th>Quantity</th><th>Unit Price</th><th>Expiry</th><th>Flags</th><?php if (hasRole(['admin','receptionist'])): ?><th style="width:100px;">Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php if (!$medicines): ?>
            <tr><td colspan="8"><div class="empty-state"><i class="bi bi-capsule"></i>No medicines in inventory yet.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($medicines as $m):
            $isLow = $m['quantity'] <= $m['reorder_level'];
            $isExpired = false; $isExpiringSoon = false;
            if ($m['expiry_date']) {
                $exp = new DateTime($m['expiry_date']);
                $diffDays = (int)$today->diff($exp)->format('%r%a');
                $isExpired = $diffDays < 0;
                $isExpiringSoon = !$isExpired && $diffDays <= 30;
            }
        ?>
            <tr class="<?= ($isLow || $isExpired) ? 'low-stock-row' : '' ?>">
                <td><strong><?= sanitize($m['name']) ?></strong></td>
                <td><?= sanitize($m['category'] ?: '—') ?></td>
                <td><?= sanitize($m['manufacturer'] ?: '—') ?></td>
                <td class="mono"><?= (int)$m['quantity'] ?></td>
                <td class="mono">$<?= formatMoney($m['unit_price']) ?></td>
                <td><?= formatDate($m['expiry_date']) ?></td>
                <td>
                    <?php if ($isExpired): ?><span class="badge-status bg-danger badge">Expired</span><?php endif; ?>
                    <?php if ($isExpiringSoon): ?><span class="badge-status bg-warning badge">Expiring Soon</span><?php endif; ?>
                    <?php if ($isLow): ?><span class="badge-status bg-danger badge">Low Stock</span><?php endif; ?>
                </td>
                <?php if (hasRole(['admin','receptionist'])): ?>
                <td>
                    <a href="form.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this medicine from inventory?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
