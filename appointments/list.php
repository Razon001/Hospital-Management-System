<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$pageTitle = 'Appointments';
$pageEyebrow = 'Clinical';
$activeMenu = 'appointments';

$dateFilter = trim($_GET['date'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$doctorFilter = (int)($_GET['doctor'] ?? 0);

$sql = "SELECT a.*, p.name AS patient_name, p.patient_code, d.name AS doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE 1=1";
$params = [];

if (hasRole('doctor')) {
    $myDoctorId = currentDoctorId($pdo);
    $sql .= " AND a.doctor_id = ?";
    $params[] = $myDoctorId ?: 0;
} elseif ($doctorFilter > 0) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctorFilter;
}

if ($dateFilter !== '') {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$doctors = hasRole('doctor') ? [] : $pdo->query("SELECT id, name FROM doctors ORDER BY name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="date" name="date" class="form-control" value="<?= sanitize($dateFilter) ?>" style="max-width:170px;">
        <select name="status" class="form-select" style="max-width:160px;">
            <option value="">All Status</option>
            <?php foreach (['scheduled','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!hasRole('doctor')): ?>
        <select name="doctor" class="form-select" style="max-width:200px;">
            <option value="0">All Doctors</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $doctorFilter === (int)$d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
        <a href="list.php" class="btn btn-outline-secondary">Reset</a>
    </form>
    <?php if (hasRole(['admin','receptionist'])): ?>
        <a href="form.php" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>New Appointment</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
    <table class="data-table table table-hover mb-0">
        <thead>
            <tr><th>Patient</th><?php if (!hasRole('doctor')): ?><th>Doctor</th><?php endif; ?><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th style="width:100px;">Actions</th></tr>
        </thead>
        <tbody>
        <?php if (!$appointments): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-calendar2-check"></i>No appointments found for these filters.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($appointments as $a): ?>
            <tr>
                <td><?= sanitize($a['patient_name']) ?> <span class="patient-code-chip"><?= sanitize($a['patient_code']) ?></span></td>
                <?php if (!hasRole('doctor')): ?><td><?= sanitize($a['doctor_name']) ?></td><?php endif; ?>
                <td><?= formatDate($a['appointment_date']) ?></td>
                <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                <td><?= sanitize($a['reason'] ?: '—') ?></td>
                <td><?= statusBadge($a['status']) ?></td>
                <td>
                    <?php if (hasRole(['admin','receptionist','doctor'])): ?>
                        <a href="form.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary" title="Open"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if (hasRole(['admin','receptionist'])): ?>
                    <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('Cancel and remove this appointment?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
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
