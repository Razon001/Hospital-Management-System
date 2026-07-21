<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'pharmacy';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];
$medicine = ['name' => '', 'category' => '', 'manufacturer' => '', 'quantity' => 0, 'unit_price' => '', 'expiry_date' => '', 'reorder_level' => 10];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $medicine = $stmt->fetch();
    if (!$medicine) { setFlash('danger', 'Medicine not found.'); redirect('list.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect($isEdit ? "form.php?id=$id" : 'form.php'); }

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'unit_price' => (float)($_POST['unit_price'] ?? 0),
        'expiry_date' => $_POST['expiry_date'] ?: null,
        'reorder_level' => (int)($_POST['reorder_level'] ?? 10),
    ];
    if ($data['name'] === '') $errors[] = 'Medicine name is required.';
    if ($data['quantity'] < 0) $errors[] = 'Quantity cannot be negative.';

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare("UPDATE medicines SET name=?, category=?, manufacturer=?, quantity=?, unit_price=?, expiry_date=?, reorder_level=? WHERE id=?");
            $stmt->execute(array_merge(array_values($data), [$id]));
            setFlash('success', 'Medicine updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO medicines (name, category, manufacturer, quantity, unit_price, expiry_date, reorder_level) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute(array_values($data));
            setFlash('success', 'Medicine added to inventory.');
        }
        redirect('list.php');
    }
    $medicine = $data;
}

$pageTitle = $isEdit ? 'Edit Medicine' : 'Add Medicine';
$pageEyebrow = 'Finance & Pharmacy';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="card">
    <div class="card-header"><?= $isEdit ? 'Edit Medicine' : 'New Medicine' ?></div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Medicine Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?= sanitize($medicine['name']) ?>" placeholder="e.g. Paracetamol 500mg">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="<?= sanitize($medicine['category']) ?>" placeholder="e.g. Analgesic">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control" value="<?= sanitize($medicine['manufacturer']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= sanitize($medicine['expiry_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantity in Stock</label>
                    <input type="number" min="0" name="quantity" class="form-control" value="<?= sanitize((string)$medicine['quantity']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unit Price ($)</label>
                    <input type="number" step="0.01" min="0" name="unit_price" class="form-control" value="<?= sanitize((string)$medicine['unit_price']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" min="0" name="reorder_level" class="form-control" value="<?= sanitize((string)$medicine['reorder_level']) ?>">
                    <div class="form-text">You'll be alerted when stock falls to or below this.</div>
                </div>
            </div>
            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Medicine' ?></button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<?php require_once '../includes/footer.php'; ?>
