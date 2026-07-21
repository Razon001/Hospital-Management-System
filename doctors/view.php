<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$activeMenu = 'doctors';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT doc.*, dep.name AS department_name FROM doctors doc LEFT JOIN departments dep ON doc.department_id = dep.id WHERE doc.id = ?");
$stmt->execute([$id]);
$doctor = $stmt->fetch();
if (!$doctor) { setFlash('danger', 'Doctor not found.'); redirect('list.php'); }

$appts = $pdo->prepare("SELECT a.*, p.name AS patient_name, p.patient_code FROM appointments a
                         JOIN patients p ON a.patient_id = p.id
                         WHERE a.doctor_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 15");
$appts->execute([$id]);
$appointments = $appts->fetchAll();

$pageTitle = 'Doctor Profile';
$pageEyebrow = 'Staff & Facility';
require_once '../includes/header.php';
?>

<div class="mb-3"><a href="list.php" class="text-muted" style="font-size:14px;"><i class="bi bi-arrow-left"></i> Back to Doctors</a></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar mx-auto mb-2" style="width:64px;height:64px;font-size:22px;"><?= strtoupper(substr($doctor['name'],0,1)) ?></div>
                <h4 class="mb-0"><?= sanitize($doctor['name']) ?></h4>
                <p class="text-muted mb-2"><?= sanitize($doctor['specialization'] ?: 'General') ?></p>
                <?= statusBadge($doctor['status']) ?>
                <hr class="hairline-divider">
                <table class="table table-borderless mb-0" style="font-size:14px;text-align:left;">
                    <tr><td class="text-muted">Department</td><td><?= sanitize($doctor['department_name'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Qualification</td><td><?= sanitize($doctor['qualification'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Gender</td><td><?= sanitize($doctor['gender']) ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= sanitize($doctor['phone'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= sanitize($doctor['email'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Fee</td><td>$<?= formatMoney($doctor['consultation_fee']) ?></td></tr>
                    <tr><td class="text-muted">Days</td><td><?= sanitize($doctor['available_days'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Hours</td><td><?= sanitize($doctor['available_time'] ?: '—') ?></td></tr>
                </table>
                <?php if (hasRole('admin')): ?>
                <a href="form.php?id=<?= $doctor['id'] ?>" class="btn btn-outline-primary btn-sm mt-2 w-100"><i class="bi bi-pencil me-1"></i>Edit Profile</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Recent Appointments</div>
            <div class="table-wrap" style="border:none;">
                <table class="data-table table table-hover mb-0">
                    <thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$appointments): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-check"></i>No appointments recorded yet.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= sanitize($a['patient_name']) ?> <span class="patient-code-chip"><?= sanitize($a['patient_code']) ?></span></td>
                            <td><?= formatDate($a['appointment_date']) ?></td>
                            <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                            <td><?= sanitize($a['reason'] ?: '—') ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
