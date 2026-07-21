<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$pageTitle = 'Billing';
$pageEyebrow = 'Finance';
$activeMenu = 'billing';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT b.*, p.name AS patient_name, p.patient_code FROM bills b JOIN patients p ON b.patient_id = p.id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (b.bill_no LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($statusFilter !== '') { $sql .= " AND b.status = ?"; $params[] = $statusFilter; }
$sql .= " ORDER BY b.bill_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

$totals = $pdo->query("SELECT COALESCE(SUM(net_amount),0) net, COALESCE(SUM(paid_amount),0) paid FROM bills")->fetch();

require_once '../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="stat-tile"><span class="eyebrow">Total Billed</span><div class="stat-number">$<?= formatMoney($totals['net']) ?></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-tile"><span class="eyebrow">Total Collected</span><div class="stat-number" style="color:var(--success)">$<?= formatMoney($totals['paid']) ?></div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-tile"><span class="eyebrow">Outstanding</span><div class="stat-number" style="color:var(--danger)">$<?= formatMoney($totals['net'] - $totals['paid']) ?></div></div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" class="form-control" placeholder="Search bill no. or patient..." value="<?= sanitize($search) ?>" style="min-width:240px;">
        <select name="status" class="form-select" style="max-width:160px;">
            <option value="">All Status</option>
            <?php foreach (['paid','partial','unpaid'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <a href="add.php" class="btn btn-primary"><i class="bi bi-receipt-cutoff me-1"></i>Create Bill</a>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead><tr><th>Bill No.</th><th>Patient</th><th>Type</th><th>Date</th><th>Net Amount</th><th>Paid</th><th>Status</th><th style="width:110px;">Actions</th></tr></thead>
        <tbody>
        <?php if (!$bills): ?>
            <tr><td colspan="8"><div class="empty-state"><i class="bi bi-receipt"></i>No bills found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($bills as $b): ?>
            <tr>
                <td><span class="bill-no-chip"><?= sanitize($b['bill_no']) ?></span></td>
                <td><?= sanitize($b['patient_name']) ?> <span class="patient-code-chip"><?= sanitize($b['patient_code']) ?></span></td>
                <td><?= sanitize($b['bill_type']) ?></td>
                <td><?= formatDate($b['bill_date']) ?></td>
                <td class="mono">$<?= formatMoney($b['net_amount']) ?></td>
                <td class="mono">$<?= formatMoney($b['paid_amount']) ?></td>
                <td><?= statusBadge($b['status']) ?></td>
                <td>
                    <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this bill? This cannot be undone.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
