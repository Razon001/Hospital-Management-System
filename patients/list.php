<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$pageTitle = 'Patients';
$pageEyebrow = 'Clinical';
$activeMenu = 'patients';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM patients WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (name LIKE ? OR phone LIKE ? OR patient_code LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" class="form-control" placeholder="Search by name, phone, or patient ID..." value="<?= sanitize($search) ?>" style="min-width:300px;">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <?php if (hasRole(['admin','receptionist'])): ?>
        <a href="form.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add Patient</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead>
            <tr><th>Patient ID</th><th>Name</th><th>Gender / Age</th><th>Blood Group</th><th>Phone</th><th>Status</th><th style="width:140px;">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$patients): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-person-lines-fill"></i>No patients found.<?= $search ? ' Try a different search.' : '' ?></div></td></tr>
        <?php endif; ?>
        <?php foreach ($patients as $p): ?>
            <tr>
                <td><span class="patient-code-chip"><?= sanitize($p['patient_code']) ?></span></td>
                <td><strong><?= sanitize($p['name']) ?></strong></td>
                <td><?= sanitize($p['gender']) ?> · <?= calcAge($p['dob']) ?></td>
                <td><?= sanitize($p['blood_group'] ?: '—') ?></td>
                <td><?= sanitize($p['phone'] ?: '—') ?></td>
                <td><?= statusBadge($p['status']) ?></td>
                <td>
                    <a href="view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if (hasRole(['admin','receptionist'])): ?>
                    <a href="form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this patient? This will also remove their appointments, admissions, bills, and medical records. Continue?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
