<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('admin');

$base_url = '../';
$pageTitle = 'Departments';
$pageEyebrow = 'Staff & Facility';
$activeMenu = 'departments';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT d.*, (SELECT COUNT(*) FROM doctors doc WHERE doc.department_id = d.id) AS doctor_count
        FROM departments d";
$params = [];
if ($search !== '') {
    $sql .= " WHERE d.name LIKE ?";
    $params[] = "%$search%";
}
$sql .= " ORDER BY d.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$departments = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" class="form-control" placeholder="Search departments..." value="<?= sanitize($search) ?>" style="max-width:280px;">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <a href="form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Department</a>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead>
            <tr><th>Name</th><th>Description</th><th>Doctors</th><th style="width:120px;">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$departments): ?>
            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-diagram-3"></i>No departments found.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($departments as $d): ?>
            <tr>
                <td><strong><?= sanitize($d['name']) ?></strong></td>
                <td class="text-muted"><?= sanitize($d['description'] ?: '—') ?></td>
                <td><?= (int)$d['doctor_count'] ?></td>
                <td>
                    <a href="form.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this department? This cannot be undone.');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>
