<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$activeMenu = 'patients';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();
if (!$patient) { setFlash('danger', 'Patient not found.'); redirect('list.php'); }

$appts = $pdo->prepare("SELECT a.*, d.name AS doctor_name FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$appts->execute([$id]);
$appointments = $appts->fetchAll();

$adms = $pdo->prepare("SELECT ad.*, w.ward_name, w.bed_no, d.name AS doctor_name FROM admissions ad
                        JOIN wards w ON ad.ward_id = w.id JOIN doctors d ON ad.doctor_id = d.id
                        WHERE ad.patient_id = ? ORDER BY ad.admission_date DESC");
$adms->execute([$id]);
$admissions = $adms->fetchAll();

$canSeeFinance = hasRole(['admin','receptionist']);
$canSeeRecords = hasRole(['admin','doctor']);

if ($canSeeFinance) {
    $billStmt = $pdo->prepare("SELECT * FROM bills WHERE patient_id = ? ORDER BY bill_date DESC");
    $billStmt->execute([$id]);
    $bills = $billStmt->fetchAll();
}

if ($canSeeRecords) {
    $recStmt = $pdo->prepare("SELECT mr.*, d.name AS doctor_name FROM medical_records mr JOIN doctors d ON mr.doctor_id = d.id WHERE mr.patient_id = ? ORDER BY mr.record_date DESC");
    $recStmt->execute([$id]);
    $records = $recStmt->fetchAll();
}

$pageTitle = 'Patient Record';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="mb-3"><a href="list.php" class="text-muted" style="font-size:14px;"><i class="bi bi-arrow-left"></i> Back to Patients</a></div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="patient-code-chip mb-2 d-inline-block"><?= sanitize($patient['patient_code']) ?></span>
                        <h4 class="mb-0"><?= sanitize($patient['name']) ?></h4>
                        <p class="text-muted mb-0"><?= sanitize($patient['gender']) ?> · <?= calcAge($patient['dob']) ?> · <?= sanitize($patient['blood_group'] ?: 'Blood group unknown') ?></p>
                    </div>
                    <?= statusBadge($patient['status']) ?>
                </div>
                <hr class="hairline-divider">
                <table class="table table-borderless mb-0" style="font-size:14px;">
                    <tr><td class="text-muted" style="width:40%;">Date of Birth</td><td><?= formatDate($patient['dob']) ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= sanitize($patient['phone'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= sanitize($patient['email'] ?: '—') ?></td></tr>
                    <tr><td class="text-muted">Address</td><td><?= nl2br(sanitize($patient['address'] ?: '—')) ?></td></tr>
                    <tr><td class="text-muted">Emergency Contact</td><td><?= sanitize($patient['emergency_contact_name'] ?: '—') ?><?= $patient['emergency_contact_phone'] ? ' ('.sanitize($patient['emergency_contact_phone']).')' : '' ?></td></tr>
                </table>
                <?php if ($patient['medical_history']): ?>
                    <hr class="hairline-divider">
                    <div class="eyebrow-label mb-1">Medical History</div>
                    <p style="font-size:14px;" class="mb-0"><?= nl2br(sanitize($patient['medical_history'])) ?></p>
                <?php endif; ?>
                <?php if (hasRole(['admin','receptionist'])): ?>
                <hr class="hairline-divider">
                <a href="form.php?id=<?= $patient['id'] ?>" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-pencil me-1"></i>Edit Record</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <a href="../appointments/form.php?patient_id=<?= $patient['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-plus me-1"></i>Book Appointment</a>
            <?php if (hasRole(['admin','receptionist'])): ?>
                <a href="../admissions/add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-hospital me-1"></i>Admit to Ward</a>
                <a href="../billing/add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt-cutoff me-1"></i>Create Bill</a>
            <?php endif; ?>
            <?php if ($canSeeRecords): ?>
                <a href="../medical_records/add.php?patient_id=<?= $patient['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-medical me-1"></i>Add Medical Record</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Appointment History</div>
            <div class="table-wrap" style="border:none;">
                <table class="data-table table table-hover mb-0">
                    <thead><tr><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$appointments): ?>
                        <tr><td colspan="5"><div class="empty-state py-3"><i class="bi bi-calendar2-check"></i>No appointments yet.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= sanitize($a['doctor_name']) ?></td>
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

        <div class="card mb-3">
            <div class="card-header">Admission History (IPD)</div>
            <div class="table-wrap" style="border:none;">
                <table class="data-table table table-hover mb-0">
                    <thead><tr><th>Ward / Bed</th><th>Doctor</th><th>Admitted</th><th>Discharged</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if (!$admissions): ?>
                        <tr><td colspan="5"><div class="empty-state py-3"><i class="bi bi-hospital"></i>No admissions on record.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($admissions as $a): ?>
                        <tr>
                            <td><?= sanitize($a['ward_name']) ?> / <?= sanitize($a['bed_no']) ?></td>
                            <td><?= sanitize($a['doctor_name']) ?></td>
                            <td><?= formatDateTime($a['admission_date']) ?></td>
                            <td><?= $a['discharge_date'] ? formatDateTime($a['discharge_date']) : '—' ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($canSeeRecords): ?>
        <div class="card mb-3">
            <div class="card-header">Medical Records</div>
            <div class="card-body">
            <?php if (!$records): ?>
                <div class="empty-state py-3"><i class="bi bi-file-medical"></i>No medical records yet.</div>
            <?php endif; ?>
            <?php foreach ($records as $r): ?>
                <div class="mb-3 pb-3" style="border-bottom:1px solid var(--line);">
                    <div class="d-flex justify-content-between">
                        <strong><?= sanitize($r['diagnosis'] ?: 'Consultation note') ?></strong>
                        <span class="text-muted" style="font-size:12.5px;"><?= formatDateTime($r['record_date']) ?></span>
                    </div>
                    <div class="text-muted" style="font-size:13px;">by <?= sanitize($r['doctor_name']) ?></div>
                    <?php if ($r['prescription']): ?><p class="mb-1 mt-2" style="font-size:14px;"><strong>Prescription:</strong> <?= nl2br(sanitize($r['prescription'])) ?></p><?php endif; ?>
                    <?php if ($r['notes']): ?><p class="mb-0" style="font-size:14px;"><strong>Notes:</strong> <?= nl2br(sanitize($r['notes'])) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canSeeFinance): ?>
        <div class="card">
            <div class="card-header">Billing History</div>
            <div class="table-wrap" style="border:none;">
                <table class="data-table table table-hover mb-0">
                    <thead><tr><th>Bill No.</th><th>Type</th><th>Date</th><th>Net Amount</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$bills): ?>
                        <tr><td colspan="6"><div class="empty-state py-3"><i class="bi bi-receipt"></i>No bills yet.</div></td></tr>
                    <?php endif; ?>
                    <?php foreach ($bills as $b): ?>
                        <tr>
                            <td><span class="bill-no-chip"><?= sanitize($b['bill_no']) ?></span></td>
                            <td><?= sanitize($b['bill_type']) ?></td>
                            <td><?= formatDate($b['bill_date']) ?></td>
                            <td class="mono">$<?= formatMoney($b['net_amount']) ?></td>
                            <td><?= statusBadge($b['status']) ?></td>
                            <td><a href="../billing/view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
