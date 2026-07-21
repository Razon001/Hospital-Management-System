<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'patients';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];
$patient = [
    'name' => '', 'dob' => '', 'gender' => 'Male', 'blood_group' => '', 'phone' => '', 'email' => '',
    'address' => '', 'emergency_contact_name' => '', 'emergency_contact_phone' => '', 'medical_history' => '', 'status' => 'active',
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
    if (!$patient) { setFlash('danger', 'Patient not found.'); redirect('list.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect($isEdit ? "form.php?id=$id" : 'form.php'); }

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'dob' => $_POST['dob'] ?: null,
        'gender' => $_POST['gender'] ?? 'Other',
        'blood_group' => trim($_POST['blood_group'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
        'medical_history' => trim($_POST['medical_history'] ?? ''),
        'status' => $_POST['status'] ?? 'active',
    ];

    if ($data['name'] === '') $errors[] = 'Patient name is required.';
    if ($data['phone'] === '') $errors[] = 'Phone number is required.';
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE patients SET name=?, dob=?, gender=?, blood_group=?, phone=?, email=?, address=?, emergency_contact_name=?, emergency_contact_phone=?, medical_history=?, status=? WHERE id=?");
            $stmt->execute(array_merge(array_values($data), [$id]));
            setFlash('success', 'Patient record updated.');
            redirect("view.php?id=$id");
        } else {
            $stmt = $pdo->prepare("INSERT INTO patients (name, dob, gender, blood_group, phone, email, address, emergency_contact_name, emergency_contact_phone, medical_history, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute(array_values($data));
            $newId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE patients SET patient_code = ? WHERE id = ?")->execute([padCode('PAT', $newId), $newId]);
            setFlash('success', 'Patient registered successfully.');
            redirect("view.php?id=$newId");
        }
    }
    $patient = $data;
}

$pageTitle = $isEdit ? 'Edit Patient' : 'Register Patient';
$pageEyebrow = 'Clinical';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-9">
<div class="card">
    <div class="card-header"><?= $isEdit ? 'Edit Patient Record' : 'New Patient Registration' ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>

            <div class="form-section-title">Personal Details</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?= sanitize($patient['name']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= sanitize($patient['dob']) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $patient['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">— Unknown —</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= $patient['blood_group'] === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone *</label>
                    <input type="text" name="phone" class="form-control" required value="<?= sanitize($patient['phone']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= sanitize($patient['email']) ?>">
                </div>
                <?php if ($isEdit): ?>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $patient['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $patient['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= sanitize($patient['address']) ?></textarea>
                </div>
            </div>

            <div class="form-section-title">Emergency Contact</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control" value="<?= sanitize($patient['emergency_contact_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="<?= sanitize($patient['emergency_contact_phone']) ?>">
                </div>
            </div>

            <div class="form-section-title">Medical History</div>
            <div class="mb-1">
                <textarea name="medical_history" class="form-control" rows="3" placeholder="Known conditions, allergies, ongoing medication, etc."><?= sanitize($patient['medical_history']) ?></textarea>
            </div>

            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Register Patient' ?></button>
                <a href="<?= $isEdit ? "view.php?id=$id" : 'list.php' ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
