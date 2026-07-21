<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('admin');

$base_url = '../';
$activeMenu = 'doctors';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

$doctor = [
    'name' => '', 'department_id' => '', 'specialization' => '', 'qualification' => '',
    'gender' => 'Male', 'phone' => '', 'email' => '', 'consultation_fee' => '',
    'available_days' => '', 'available_time' => '09:00-17:00', 'status' => 'active', 'user_id' => null,
];
$linkedUsername = null;

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->execute([$id]);
    $doctor = $stmt->fetch();
    if (!$doctor) { setFlash('danger', 'Doctor not found.'); redirect('list.php'); }
    if ($doctor['user_id']) {
        $u = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $u->execute([$doctor['user_id']]);
        $linkedUsername = $u->fetchColumn();
    }
}

$selectedDays = $doctor['available_days'] ? explode(',', $doctor['available_days']) : [];
[$timeStart, $timeEnd] = array_pad(explode('-', $doctor['available_time'] ?: '09:00-17:00'), 2, '');

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$dayOptions = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect($isEdit ? "form.php?id=$id" : 'form.php'); }

    $name = trim($_POST['name'] ?? '');
    $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $gender = $_POST['gender'] ?? 'Other';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fee = (float)($_POST['consultation_fee'] ?? 0);
    $days = isset($_POST['days']) && is_array($_POST['days']) ? implode(',', $_POST['days']) : '';
    $timeStart = $_POST['time_start'] ?? '09:00';
    $timeEnd = $_POST['time_end'] ?? '17:00';
    $availableTime = $timeStart . '-' . $timeEnd;
    $status = $_POST['status'] ?? 'active';
    $createLogin = isset($_POST['create_login']);
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = $_POST['password'] ?? '';

    if ($name === '') $errors[] = 'Doctor name is required.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

    if ($createLogin && !$isEdit) {
        if ($newUsername === '' || strlen($newPassword) < 6) {
            $errors[] = 'To create a login, provide a username and a password of at least 6 characters.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$newUsername]);
            if ($chk->fetch()) $errors[] = 'That username is already taken.';
        }
    }

    // Optional password reset on an already-linked account during edit
    if ($isEdit && $linkedUsername && $newPassword !== '' && strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters (leave blank to keep current password).';
    }

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE doctors SET name=?, department_id=?, specialization=?, qualification=?, gender=?, phone=?, email=?, consultation_fee=?, available_days=?, available_time=?, status=? WHERE id=?");
            $stmt->execute([$name, $department_id, $specialization, $qualification, $gender, $phone, $email, $fee, $days, $availableTime, $status, $id]);

            if ($linkedUsername && $newPassword !== '') {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $doctor['user_id']]);
            }
            setFlash('success', 'Doctor profile updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO doctors (name, department_id, specialization, qualification, gender, phone, email, consultation_fee, available_days, available_time, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $department_id, $specialization, $qualification, $gender, $phone, $email, $fee, $days, $availableTime, $status]);
            $newDoctorId = $pdo->lastInsertId();

            if ($createLogin) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES (?,?,?,?,?,'doctor','active')")
                    ->execute([$newUsername, $hash, $name, $email, $phone]);
                $newUserId = $pdo->lastInsertId();
                $pdo->prepare("UPDATE doctors SET user_id = ? WHERE id = ?")->execute([$newUserId, $newDoctorId]);
            }
            setFlash('success', 'Doctor added successfully.');
        }
        redirect('list.php');
    }

    // repopulate on validation error
    $doctor = compact('name','department_id','specialization','qualification','gender','phone','email') + ['consultation_fee' => $fee, 'status' => $status];
    $selectedDays = $_POST['days'] ?? [];
}

$pageTitle = $isEdit ? 'Edit Doctor' : 'Add Doctor';
$pageEyebrow = 'Staff & Facility';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-9">
<div class="card">
    <div class="card-header"><?= $isEdit ? 'Edit Doctor Profile' : 'New Doctor' ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>

            <div class="form-section-title">Profile</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?= sanitize($doctor['name']) ?>" placeholder="e.g. Dr. John Smith">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= (int)$doctor['department_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?= sanitize($doctor['specialization']) ?>" placeholder="e.g. Interventional Cardiologist">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" class="form-control" value="<?= sanitize($doctor['qualification']) ?>" placeholder="e.g. MBBS, MD">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= $doctor['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= sanitize($doctor['phone']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= sanitize($doctor['email']) ?>">
                </div>
            </div>

            <div class="form-section-title">Schedule &amp; Fee</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Consultation Fee ($)</label>
                    <input type="number" step="0.01" min="0" name="consultation_fee" class="form-control" value="<?= sanitize((string)$doctor['consultation_fee']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Available From</label>
                    <input type="time" name="time_start" class="form-control" value="<?= sanitize($timeStart) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Available Until</label>
                    <input type="time" name="time_end" class="form-control" value="<?= sanitize($timeEnd) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Available Days</label><br>
                    <?php foreach ($dayOptions as $day): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="days[]" value="<?= $day ?>" id="day_<?= $day ?>" <?= in_array($day, $selectedDays) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="day_<?= $day ?>"><?= $day ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($isEdit): ?>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $doctor['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $doctor['status']==='inactive'?'selected':'' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-section-title">Login Access</div>
            <?php if ($isEdit && $linkedUsername): ?>
                <p class="text-muted" style="font-size:14px;">This doctor can already sign in as <span class="patient-code-chip">@<?= sanitize($linkedUsername) ?></span>.</p>
                <div class="mb-3">
                    <label class="form-label">Reset Password (optional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password" style="max-width:320px;">
                </div>
            <?php elseif ($isEdit && !$linkedUsername): ?>
                <p class="text-muted" style="font-size:14px;">No login account is linked to this doctor yet. Login accounts can only be created when adding a new doctor.</p>
            <?php else: ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="create_login" name="create_login" onchange="document.getElementById('loginFields').classList.toggle('d-none', !this.checked)">
                    <label class="form-check-label" for="create_login">Create a login account so this doctor can sign in</label>
                </div>
                <div id="loginFields" class="row g-3 d-none">
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. dr.smith">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters">
                    </div>
                </div>
            <?php endif; ?>

            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Doctor' ?></button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
