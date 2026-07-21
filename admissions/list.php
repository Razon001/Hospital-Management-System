<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$pageTitle = 'Admissions (IPD)';
$pageEyebrow = 'Clinical';
$activeMenu = 'admissions';

$statusFilter = trim($_GET['status'] ?? 'admitted');
$sql = "SELECT ad.*, p.name AS patient_name, p.patient_code, d.name AS doctor_name, w.ward_name, w.bed_no
        FROM admissions ad
        JOIN patients p ON ad.patient_id = p.id
        JOIN doctors d ON ad.doctor_id = d.id
        JOIN wards w ON ad.ward_id = w.id
        WHERE 1=1";
$params = [];
if ($statusFilter !== '') { $sql .= " AND ad.status = ?"; $params[] = $statusFilter; }
$sql .= " ORDER BY ad.admission_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$admissions = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <select name="status" class="form-select" onchange="this.form.submit()" style="max-width:200px;">
            <option value="">All</option>
            <option value="admitted" <?= $statusFilter==='admitted'?'selected':'' ?>>Currently Admitted</option>
            <option value="discharged" <?= $statusFilter==='discharged'?'selected':'' ?>>Discharged</option>
        </select>
    </form>
    <?php if (hasRole(['admin','receptionist'])): ?>
        <a href="add.php" class="btn btn-primary"><i class="bi bi-hospital me-1"></i>Admit Patient</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead><tr><th>Patient</th><th>Doctor</th><th>Ward / Bed</th><th>Admitted</th><th>Discharged</th><th>Status</th><th style="width:90px;">Actions</th></tr></thead>
        <tbody>
        <?php if (!$admissions): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-hospital"></i>No admissions found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($admissions as $a): ?>
            <tr>
                <td><?= sanitize($a['patient_name']) ?> <span class="patient-code-chip"><?= sanitize($a['patient_code']) ?></span></td>
                <td><?= sanitize($a['doctor_name']) ?></td>
                <td><?= sanitize($a['ward_name']) ?> / <?= sanitize($a['bed_no']) ?></td>
                <td><?= formatDateTime($a['admission_date']) ?></td>
                <td><?= $a['discharge_date'] ? formatDateTime($a['discharge_date']) : '—' ?></td>
                <td><?= statusBadge($a['status']) ?></td>
                <td><a href="view.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
