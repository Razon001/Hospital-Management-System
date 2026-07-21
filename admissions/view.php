<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$activeMenu = 'admissions';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT ad.*, p.name AS patient_name, p.patient_code, p.id AS patient_id, d.name AS doctor_name, w.ward_name, w.bed_no, w.charge_per_day
                        FROM admissions ad
                        JOIN patients p ON ad.patient_id = p.id
                        JOIN doctors d ON ad.doctor_id = d.id
                        JOIN wards w ON ad.ward_id = w.id
                        WHERE ad.id = ?");
$stmt->execute([$id]);
$admission = $stmt->fetch();
if (!$admission) { setFlash('danger', 'Admission not found.'); redirect('list.php'); }

$linkedBill = $pdo->prepare("SELECT * FROM bills WHERE bill_type='IPD' AND reference_id = ? LIMIT 1");
$linkedBill->execute([$id]);
$bill = $linkedBill->fetch();

$pageTitle = 'Admission Details';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="mb-3"><a href="list.php" class="text-muted" style="font-size:14px;"><i class="bi bi-arrow-left"></i> Back to Admissions</a></div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Admission Record</span> <?= statusBadge($admission['status']) ?>
    </div>
    <div class="card-body">
        <table class="table table-borderless" style="font-size:14px;">
            <tr><td class="text-muted" style="width:40%;">Patient</td><td><a href="../patients/view.php?id=<?= $admission['patient_id'] ?>"><?= sanitize($admission['patient_name']) ?></a> <span class="patient-code-chip"><?= sanitize($admission['patient_code']) ?></span></td></tr>
            <tr><td class="text-muted">Attending Doctor</td><td><?= sanitize($admission['doctor_name']) ?></td></tr>
            <tr><td class="text-muted">Ward / Bed</td><td><?= sanitize($admission['ward_name']) ?> / <?= sanitize($admission['bed_no']) ?> ($<?= formatMoney($admission['charge_per_day']) ?>/day)</td></tr>
            <tr><td class="text-muted">Admitted On</td><td><?= formatDateTime($admission['admission_date']) ?></td></tr>
            <tr><td class="text-muted">Discharged On</td><td><?= $admission['discharge_date'] ? formatDateTime($admission['discharge_date']) : '—' ?></td></tr>
            <tr><td class="text-muted">Diagnosis</td><td><?= nl2br(sanitize($admission['diagnosis'] ?: '—')) ?></td></tr>
        </table>

        <?php if ($bill): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center" style="font-size:14px;">
                <span>Ward bill <span class="bill-no-chip"><?= sanitize($bill['bill_no']) ?></span> — <?= statusBadge($bill['status']) ?></span>
                <a href="../billing/view.php?id=<?= $bill['id'] ?>" class="btn btn-sm btn-outline-primary">View Bill</a>
            </div>
        <?php endif; ?>

        <?php if ($admission['status'] === 'admitted' && hasRole(['admin','receptionist'])): ?>
            <hr class="hairline-divider">
            <a href="discharge.php?id=<?= $admission['id'] ?>" class="btn btn-primary"><i class="bi bi-box-arrow-right me-1"></i>Discharge Patient</a>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
