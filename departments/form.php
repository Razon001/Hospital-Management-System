<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('admin');

$base_url = '../';
$activeMenu = 'departments';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$department = ['name' => '', 'description' => ''];
$errors = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $department = $stmt->fetch();
    if (!$department) {
        setFlash('danger', 'Department not found.');
        redirect('list.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        redirect($isEdit ? "form.php?id=$id" : 'form.php');
    }
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') $errors[] = 'Department name is required.';

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE departments SET name=?, description=? WHERE id=?");
            $stmt->execute([$name, $description, $id]);
            setFlash('success', 'Department updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?,?)");
            $stmt->execute([$name, $description]);
            setFlash('success', 'Department added successfully.');
        }
        redirect('list.php');
    }
    $department = ['name' => $name, 'description' => $description];
}

$pageTitle = $isEdit ? 'Edit Department' : 'Add Department';
$pageEyebrow = 'Staff & Facility';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><?= $isEdit ? 'Edit Department' : 'New Department' ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Department Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= sanitize($department['name']) ?>" placeholder="e.g. Cardiology">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this department"><?= sanitize($department['description']) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Department' ?></button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
