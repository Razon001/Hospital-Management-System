<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$pageTitle = 'Doctors';
$pageEyebrow = 'Staff & Facility';
$activeMenu = 'doctors';

$search = trim($_GET['q'] ?? '');
$deptFilter = (int)($_GET['department'] ?? 0);

$sql = "SELECT doc.*, dep.name AS department_name
        FROM doctors doc
        LEFT JOIN departments dep ON doc.department_id = dep.id
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (doc.name LIKE ? OR doc.specialization LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($deptFilter > 0) {
    $sql .= " AND doc.department_id = ?";
    $params[] = $deptFilter;
}
$sql .= " ORDER BY doc.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="text" name="q" class="form-control" placeholder="Search by name or specialization..." value="<?= sanitize($search) ?>" style="max-width:260px;">
        <select name="department" class="form-select" style="max-width:200px;" onchange="this.form.submit()">
            <option value="0">All Departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $deptFilter === (int)$d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <?php if (hasRole('admin')): ?>
        <a href="form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Doctor</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead>
            <tr><th>Name</th><th>Department</th><th>Specialization</th><th>Phone</th><th>Fee</th><th>Status</th><th style="width:140px;">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$doctors): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-person-badge"></i>No doctors found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($doctors as $d): ?>
            <tr>
                <td><strong><?= sanitize($d['name']) ?></strong><?= $d['user_id'] ? ' <i class="bi bi-box-arrow-in-right text-muted" title="Has login account"></i>' : '' ?></td>
                <td><?= sanitize($d['department_name'] ?: '—') ?></td>
                <td><?= sanitize($d['specialization'] ?: '—') ?></td>
                <td><?= sanitize($d['phone'] ?: '—') ?></td>
                <td class="mono">$<?= formatMoney($d['consultation_fee']) ?></td>
                <td><?= statusBadge($d['status']) ?></td>
                <td>
                    <a href="view.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                    <?php if (hasRole('admin')): ?>
                    <a href="form.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this doctor? This will also remove their appointment and medical record history. Consider setting status to Inactive instead. Continue?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
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
