<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'admissions';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT ad.*, p.name AS patient_name, p.patient_code, d.name AS doctor_name, w.ward_name, w.bed_no, w.charge_per_day
                        FROM admissions ad
                        JOIN patients p ON ad.patient_id = p.id
                        JOIN doctors d ON ad.doctor_id = d.id
                        JOIN wards w ON ad.ward_id = w.id
                        WHERE ad.id = ?");
$stmt->execute([$id]);
$admission = $stmt->fetch();
if (!$admission) { setFlash('danger', 'Admission not found.'); redirect('list.php'); }
if ($admission['status'] === 'discharged') { setFlash('info', 'This patient has already been discharged.'); redirect("view.php?id=$id"); }

$days = max(1, (int)ceil((strtotime('now') - strtotime($admission['admission_date'])) / 86400));
$estimatedCharge = $days * (float)$admission['charge_per_day'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect("discharge.php?id=$id"); }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE admissions SET status='discharged', discharge_date = NOW() WHERE id = ?")->execute([$id]);
        $pdo->prepare("UPDATE wards SET status = 'available' WHERE id = ?")->execute([$admission['ward_id']]);

        // Auto-generate an IPD bill for the ward stay
        $pdo->prepare("INSERT INTO bills (bill_no, patient_id, bill_type, reference_id, total_amount, net_amount, status, bill_date) VALUES ('', ?, 'IPD', ?, ?, ?, 'unpaid', NOW())")
            ->execute([$admission['patient_id'], $id, $estimatedCharge, $estimatedCharge]);
        $billId = $pdo->lastInsertId();
        $pdo->prepare("UPDATE bills SET bill_no = ? WHERE id = ?")->execute([padCode('BILL', $billId), $billId]);

        $desc = "Ward stay — {$admission['ward_name']} ({$admission['bed_no']}), {$days} day(s)";
        $pdo->prepare("INSERT INTO bill_items (bill_id, description, quantity, unit_price, amount) VALUES (?,?,?,?,?)")
            ->execute([$billId, $desc, $days, $admission['charge_per_day'], $estimatedCharge]);

        $pdo->commit();
        setFlash('success', "Patient discharged. A draft bill ({$days} day(s) ward stay) has been created — review it before finalizing.");
        redirect("../billing/view.php?id=$billId");
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'Something went wrong while processing the discharge. Please try again.');
        redirect("view.php?id=$id");
    }
}

$pageTitle = 'Discharge Patient';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card">
    <div class="card-header">Confirm Discharge</div>
    <div class="card-body">
        <table class="table table-borderless" style="font-size:14px;">
            <tr><td class="text-muted" style="width:40%;">Patient</td><td><strong><?= sanitize($admission['patient_name']) ?></strong> <span class="patient-code-chip"><?= sanitize($admission['patient_code']) ?></span></td></tr>
            <tr><td class="text-muted">Ward / Bed</td><td><?= sanitize($admission['ward_name']) ?> / <?= sanitize($admission['bed_no']) ?></td></tr>
            <tr><td class="text-muted">Admitted On</td><td><?= formatDateTime($admission['admission_date']) ?></td></tr>
            <tr><td class="text-muted">Length of Stay</td><td><?= $days ?> day(s)</td></tr>
            <tr><td class="text-muted">Ward Charges</td><td class="mono">$<?= formatMoney($estimatedCharge) ?> (<?= $days ?> × $<?= formatMoney($admission['charge_per_day']) ?>)</td></tr>
        </table>
        <div class="alert alert-info" style="font-size:13.5px;">Discharging will free up the bed and automatically create a draft bill for the ward charges above. You'll be able to add consultation, medicine, or lab charges before finalizing payment.</div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Confirm Discharge &amp; Generate Bill</button>
                <a href="view.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
