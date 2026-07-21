<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$base_url = '../';
$activeMenu = 'appointments';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

// Access rules: creating a new appointment is front-desk/admin only.
// Editing an existing one is allowed for admin/receptionist (full edit) or the
// assigned doctor (status + notes only).
if (!$isEdit) {
    requireRole(['admin','receptionist']);
}
$isDoctorRestricted = false;

$appointment = [
    'patient_id' => (int)($_GET['patient_id'] ?? 0),
    'doctor_id' => (int)($_GET['doctor_id'] ?? 0),
    'appointment_date' => date('Y-m-d'),
    'appointment_time' => '',
    'reason' => '', 'status' => 'scheduled', 'notes' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT a.*, p.name AS patient_name, p.patient_code, d.name AS doctor_name FROM appointments a
                            JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id WHERE a.id = ?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();
    if (!$appointment) { setFlash('danger', 'Appointment not found.'); redirect('list.php'); }

    if (hasRole('doctor')) {
        $myDoctorId = currentDoctorId($pdo);
        if (!$myDoctorId || (int)$appointment['doctor_id'] !== $myDoctorId) {
            setFlash('danger', "You don't have permission to open that appointment.");
            redirect('list.php');
        }
        $isDoctorRestricted = true;
    } else {
        requireRole(['admin','receptionist']);
    }
}

$patients = hasRole('doctor') ? [] : $pdo->query("SELECT id, name, patient_code FROM patients ORDER BY name")->fetchAll();
$doctors  = hasRole('doctor') ? [] : $pdo->query("SELECT id, name FROM doctors WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect($isEdit ? "form.php?id=$id" : 'form.php'); }

    if ($isDoctorRestricted) {
        // Doctors can only update status + notes on their own appointment.
        $status = $_POST['status'] ?? $appointment['status'];
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $pdo->prepare("UPDATE appointments SET status=?, notes=? WHERE id=?");
        $stmt->execute([$status, $notes, $id]);
        setFlash('success', 'Appointment updated.');
        redirect('list.php');
    }

    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $status = $_POST['status'] ?? 'scheduled';
    $notes = trim($_POST['notes'] ?? '');

    if (!$patient_id) $errors[] = 'Please select a patient.';
    if (!$doctor_id) $errors[] = 'Please select a doctor.';
    if (!$date) $errors[] = 'Please select a date.';
    if (!$time) $errors[] = 'Please select a time.';

    if (!$errors) {
        $conflictSql = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'scheduled'";
        $conflictParams = [$doctor_id, $date, $time];
        if ($isEdit) { $conflictSql .= " AND id != ?"; $conflictParams[] = $id; }
        $chk = $pdo->prepare($conflictSql);
        $chk->execute($conflictParams);
        if ($chk->fetch()) {
            $errors[] = 'This doctor already has a scheduled appointment at that date and time. Please choose another slot.';
        }
    }

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE appointments SET patient_id=?, doctor_id=?, appointment_date=?, appointment_time=?, reason=?, status=?, notes=? WHERE id=?");
            $stmt->execute([$patient_id, $doctor_id, $date, $time, $reason, $status, $notes, $id]);
            setFlash('success', 'Appointment updated.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, notes) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$patient_id, $doctor_id, $date, $time, $reason, $status, $notes]);
            setFlash('success', 'Appointment booked successfully.');
        }
        redirect('list.php');
    }
    $appointment = compact('patient_id','doctor_id','reason','status','notes') + ['appointment_date' => $date, 'appointment_time' => $time];
}

$pageTitle = $isEdit ? 'Appointment Details' : 'New Appointment';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><?= $isEdit ? 'Appointment Details' : 'Book Appointment' ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>

        <?php if ($isDoctorRestricted): ?>
            <table class="table table-borderless" style="font-size:14px;">
                <tr><td class="text-muted" style="width:35%;">Patient</td><td><strong><?= sanitize($appointment['patient_name']) ?></strong> <span class="patient-code-chip"><?= sanitize($appointment['patient_code']) ?></span></td></tr>
                <tr><td class="text-muted">Date &amp; Time</td><td><?= formatDate($appointment['appointment_date']) ?> at <?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td></tr>
                <tr><td class="text-muted">Reason</td><td><?= sanitize($appointment['reason'] ?: '—') ?></td></tr>
            </table>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['scheduled','completed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $appointment['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Consultation Notes</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Notes visible in the patient's record"><?= sanitize($appointment['notes']) ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Save</button>
                    <a href="list.php" class="btn btn-outline-secondary">Back</a>
                </div>
            </form>

        <?php else: ?>
            <form method="POST">
                <?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Patient *</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">— Select patient —</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (int)$appointment['patient_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= sanitize($p['name']) ?> (<?= sanitize($p['patient_code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Doctor *</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">— Select doctor —</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= (int)$appointment['doctor_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date *</label>
                        <input type="date" name="appointment_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= sanitize($appointment['appointment_date']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Time *</label>
                        <input type="time" name="appointment_time" class="form-control" required value="<?= sanitize($appointment['appointment_time']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason for Visit</label>
                        <input type="text" name="reason" class="form-control" value="<?= sanitize($appointment['reason']) ?>" placeholder="e.g. Routine check-up, follow-up, fever...">
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (['scheduled','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $appointment['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= sanitize($appointment['notes']) ?></textarea>
                    </div>
                    <?php endif; ?>
                </div>
                <hr class="hairline-divider">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Book Appointment' ?></button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
