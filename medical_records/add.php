<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','doctor']);

$base_url = '../';
$activeMenu = 'patients';
$errors = [];

$patient_id = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
if (!$patient) { setFlash('danger', 'Patient not found.'); redirect('../patients/list.php'); }

$myDoctorId = hasRole('doctor') ? currentDoctorId($pdo) : null;
if (hasRole('doctor') && !$myDoctorId) {
    setFlash('danger', "Your login isn't linked to a doctor profile yet. Please ask the administrator to link it.");
    redirect("../patients/view.php?id=$patient_id");
}

$doctors = hasRole('admin') ? $pdo->query("SELECT id, name FROM doctors WHERE status='active' ORDER BY name")->fetchAll() : [];

$recentAppts = $pdo->prepare("SELECT id, appointment_date, appointment_time FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC LIMIT 10");
$recentAppts->execute([$patient_id]);
$recentAppts = $recentAppts->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect("add.php?patient_id=$patient_id"); }

    $doctor_id = hasRole('doctor') ? $myDoctorId : (int)($_POST['doctor_id'] ?? 0);
    $appointment_id = (int)($_POST['appointment_id'] ?? 0) ?: null;
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$doctor_id) $errors[] = 'Please select the attending doctor.';
    if ($diagnosis === '' && $prescription === '' && $notes === '') $errors[] = 'Please fill in at least a diagnosis, prescription, or note.';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, prescription, notes) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$patient_id, $doctor_id, $appointment_id, $diagnosis, $prescription, $notes]);
        setFlash('success', 'Medical record added.');
        redirect("../patients/view.php?id=$patient_id");
    }
}

$pageTitle = 'Add Medical Record';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header">New Medical Record — <?= sanitize($patient['name']) ?> <span class="patient-code-chip"><?= sanitize($patient['patient_code']) ?></span></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="patient_id" value="<?= $patient_id ?>">

            <?php if (hasRole('admin')): ?>
            <div class="mb-3">
                <label class="form-label">Attending Doctor *</label>
                <select name="doctor_id" class="form-select" required>
                    <option value="">— Select doctor —</option>
                    <?php foreach ($doctors as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($recentAppts): ?>
            <div class="mb-3">
                <label class="form-label">Link to Appointment (optional)</label>
                <select name="appointment_id" class="form-select">
                    <option value="">— Not linked —</option>
                    <?php foreach ($recentAppts as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= formatDate($a['appointment_date']) ?> at <?= date('h:i A', strtotime($a['appointment_time'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Diagnosis</label>
                <input type="text" name="diagnosis" class="form-control" placeholder="e.g. Acute bronchitis">
            </div>
            <div class="mb-3">
                <label class="form-label">Prescription</label>
                <textarea name="prescription" class="form-control" rows="3" placeholder="Medicines, dosage, and duration"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Additional Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Observations, advice, follow-up plan"></textarea>
            </div>

            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save Record</button>
                <a href="../patients/view.php?id=<?= $patient_id ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
