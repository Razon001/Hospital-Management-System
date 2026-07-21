<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'wards';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];
$ward = ['ward_name' => '', 'ward_type' => 'General', 'room_no' => '', 'bed_no' => '', 'charge_per_day' => '', 'status' => 'available'];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM wards WHERE id = ?");
    $stmt->execute([$id]);
    $ward = $stmt->fetch();
    if (!$ward) { setFlash('danger', 'Ward not found.'); redirect('list.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect($isEdit ? "form.php?id=$id" : 'form.php'); }

    $data = [
        'ward_name' => trim($_POST['ward_name'] ?? ''),
        'ward_type' => $_POST['ward_type'] ?? 'General',
        'room_no' => trim($_POST['room_no'] ?? ''),
        'bed_no' => trim($_POST['bed_no'] ?? ''),
        'charge_per_day' => (float)($_POST['charge_per_day'] ?? 0),
        'status' => $_POST['status'] ?? 'available',
    ];
    if ($data['ward_name'] === '') $errors[] = 'Ward name is required.';

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE wards SET ward_name=?, ward_type=?, room_no=?, bed_no=?, charge_per_day=?, status=? WHERE id=?");
            $stmt->execute(array_merge(array_values($data), [$id]));
            setFlash('success', 'Ward updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO wards (ward_name, ward_type, room_no, bed_no, charge_per_day, status) VALUES (?,?,?,?,?,?)");
            $stmt->execute(array_values($data));
            setFlash('success', 'Ward / bed added successfully.');
        }
        redirect('list.php');
    }
    $ward = $data;
}

$pageTitle = $isEdit ? 'Edit Ward' : 'Add Ward / Bed';
$pageEyebrow = 'Staff & Facility';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><?= $isEdit ? 'Edit Ward / Bed' : 'New Ward / Bed' ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ward Name *</label>
                    <input type="text" name="ward_name" class="form-control" required value="<?= sanitize($ward['ward_name']) ?>" placeholder="e.g. General Ward A">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ward Type</label>
                    <select name="ward_type" class="form-select">
                        <?php foreach (['General','Semi-Private','Private','ICU'] as $t): ?>
                            <option value="<?= $t ?>" <?= $ward['ward_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Room No.</label>
                    <input type="text" name="room_no" class="form-control" value="<?= sanitize($ward['room_no']) ?>" placeholder="e.g. R-101">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bed No.</label>
                    <input type="text" name="bed_no" class="form-control" value="<?= sanitize($ward['bed_no']) ?>" placeholder="e.g. B-01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Charge / Day ($)</label>
                    <input type="number" step="0.01" min="0" name="charge_per_day" class="form-control" value="<?= sanitize((string)$ward['charge_per_day']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['available','occupied','maintenance'] as $s): ?>
                            <option value="<?= $s ?>" <?= $ward['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Beds are set to Occupied/Available automatically on admit/discharge — change this manually only for corrections or maintenance.</div>
                </div>
            </div>
            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Ward / Bed' ?></button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
