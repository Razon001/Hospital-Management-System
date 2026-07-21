<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'admissions';
$errors = [];

$patient_id = (int)($_GET['patient_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect('add.php'); }

    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    $admission_date = $_POST['admission_date'] ?? date('Y-m-d\TH:i');
    $diagnosis = trim($_POST['diagnosis'] ?? '');

    if (!$patient_id) $errors[] = 'Please select a patient.';
    if (!$doctor_id) $errors[] = 'Please select an attending doctor.';
    if (!$ward_id) $errors[] = 'Please select an available ward/bed.';

    if (!$errors) {
        // Re-check the bed is still available (race-safety for concurrent admits)
        $chk = $pdo->prepare("SELECT status FROM wards WHERE id = ?");
        $chk->execute([$ward_id]);
        $wardStatus = $chk->fetchColumn();
        if ($wardStatus !== 'available') {
            $errors[] = 'That bed is no longer available. Please choose another.';
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO admissions (patient_id, doctor_id, ward_id, admission_date, diagnosis, status) VALUES (?,?,?,?,?, 'admitted')");
            $stmt->execute([$patient_id, $doctor_id, $ward_id, str_replace('T', ' ', $admission_date), $diagnosis]);
            $pdo->prepare("UPDATE wards SET status = 'occupied' WHERE id = ?")->execute([$ward_id]);
            $pdo->commit();
            setFlash('success', 'Patient admitted successfully.');
            redirect('list.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while admitting the patient. Please try again.';
        }
    }
}

$patients = $pdo->query("SELECT id, name, patient_code FROM patients WHERE status='active' ORDER BY name")->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM doctors WHERE status='active' ORDER BY name")->fetchAll();
$availableWards = $pdo->query("SELECT * FROM wards WHERE status='available' ORDER BY ward_type, ward_name")->fetchAll();

$pageTitle = 'Admit Patient';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header">Admit Patient to Ward</div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <?php if (!$availableWards): ?>
            <div class="alert alert-warning">There are no available beds right now. <a href="../wards/list.php">Check ward status</a> or add a new bed.</div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">— Select patient —</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $patient_id === (int)$p['id'] ? 'selected' : '' ?>><?= sanitize($p['name']) ?> (<?= sanitize($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Attending Doctor *</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">— Select doctor —</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ward / Bed *</label>
                    <select name="ward_id" class="form-select" required <?= !$availableWards ? 'disabled' : '' ?>>
                        <option value="">— Select available bed —</option>
                        <?php foreach ($availableWards as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= sanitize($w['ward_name']) ?> — <?= sanitize($w['room_no']) ?>/<?= sanitize($w['bed_no']) ?> (<?= $w['ward_type'] ?>, $<?= formatMoney($w['charge_per_day']) ?>/day)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Admission Date &amp; Time</label>
                    <input type="datetime-local" name="admission_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Diagnosis / Reason for Admission</label>
                    <textarea name="diagnosis" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary" <?= !$availableWards ? 'disabled' : '' ?>>Admit Patient</button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
