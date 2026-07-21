<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'billing';
$errors = [];

$patient_id = (int)($_GET['patient_id'] ?? 0);
$descriptions = ['']; $quantities = [1]; $unitPrices = [''];
$discount = 0; $tax = 0; $paymentMethod = 'Cash'; $status = 'unpaid'; $paidAmount = 0; $billType = 'OPD';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { redirect('add.php'); }

    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $billType = $_POST['bill_type'] ?? 'OPD';
    $descriptions = $_POST['description'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unitPrices = $_POST['unit_price'] ?? [];
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';
    $paidAmount = (float)($_POST['paid_amount'] ?? 0);

    if (!$patient_id) $errors[] = 'Please select a patient.';

    // Build clean item list, dropping empty rows
    $items = [];
    $totalAmount = 0;
    foreach ($descriptions as $i => $desc) {
        $desc = trim($desc);
        $qty = (int)($quantities[$i] ?? 0);
        $price = (float)($unitPrices[$i] ?? 0);
        if ($desc === '' || $qty <= 0) continue;
        $amount = round($qty * $price, 2);
        $items[] = ['description' => $desc, 'quantity' => $qty, 'unit_price' => $price, 'amount' => $amount];
        $totalAmount += $amount;
    }
    if (!$items) $errors[] = 'Please add at least one billable item.';

    $netAmount = round($totalAmount - $discount + $tax, 2);
    if ($netAmount < 0) $errors[] = 'Discount cannot be greater than the item total plus tax.';

    $status = $paidAmount <= 0 ? 'unpaid' : ($paidAmount >= $netAmount ? 'paid' : 'partial');

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO bills (bill_no, patient_id, bill_type, total_amount, discount, tax, net_amount, paid_amount, payment_method, status, bill_date) VALUES ('', ?,?,?,?,?,?,?,?,?, NOW())")
                ->execute([$patient_id, $billType, $totalAmount, $discount, $tax, $netAmount, $paidAmount, $paymentMethod, $status]);
            $billId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE bills SET bill_no = ? WHERE id = ?")->execute([padCode('BILL', $billId), $billId]);

            $itemStmt = $pdo->prepare("INSERT INTO bill_items (bill_id, description, quantity, unit_price, amount) VALUES (?,?,?,?,?)");
            foreach ($items as $it) {
                $itemStmt->execute([$billId, $it['description'], $it['quantity'], $it['unit_price'], $it['amount']]);
            }
            $pdo->commit();
            setFlash('success', 'Bill created successfully.');
            redirect("view.php?id=$billId");
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving the bill. Please try again.';
        }
    }
}

$patients = $pdo->query("SELECT id, name, patient_code FROM patients ORDER BY name")->fetchAll();

$pageTitle = 'Create Bill';
$pageEyebrow = 'Finance';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-lg-9">
<div class="card">
    <div class="card-header">New Bill</div>
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?></div>
        <?php endif; ?>
        <form method="POST" id="billForm">
            <?= csrfField() ?>
            <div class="row g-3 mb-2">
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
                    <label class="form-label">Bill Type</label>
                    <select name="bill_type" class="form-select">
                        <?php foreach (['OPD','IPD','Pharmacy','Lab','Other'] as $t): ?>
                            <option value="<?= $t ?>" <?= $billType === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section-title">Line Items</div>
            <table class="table" id="itemsTable">
                <thead><tr><th style="width:50%;">Description</th><th style="width:12%;">Qty</th><th style="width:18%;">Unit Price ($)</th><th style="width:15%;">Amount</th><th></th></tr></thead>
                <tbody id="itemsBody">
                <?php foreach ($descriptions as $i => $d): ?>
                    <tr class="item-row">
                        <td><input type="text" name="description[]" class="form-control" value="<?= sanitize($d) ?>" placeholder="e.g. Consultation fee"></td>
                        <td><input type="number" name="quantity[]" class="form-control qty-input" min="1" value="<?= sanitize((string)($quantities[$i] ?? 1)) ?>"></td>
                        <td><input type="number" step="0.01" name="unit_price[]" class="form-control price-input" min="0" value="<?= sanitize((string)($unitPrices[$i] ?? '')) ?>"></td>
                        <td><span class="row-amount mono">0.00</span></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" id="addRowBtn" class="btn btn-sm btn-outline-primary mb-3"><i class="bi bi-plus-lg me-1"></i>Add Item</button>

            <div class="row g-3 justify-content-end">
                <div class="col-md-5">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Discount ($)</label><input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control" value="<?= sanitize((string)$discount) ?>"></div>
                        <div class="col-6"><label class="form-label">Tax ($)</label><input type="number" step="0.01" min="0" name="tax" id="taxInput" class="form-control" value="<?= sanitize((string)$tax) ?>"></div>
                        <div class="col-6"><label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <?php foreach (['Cash','Card','Insurance','Online'] as $m): ?>
                                    <option value="<?= $m ?>" <?= $paymentMethod === $m ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Amount Paid Now ($)</label><input type="number" step="0.01" min="0" name="paid_amount" id="paidInput" class="form-control" value="<?= sanitize((string)$paidAmount) ?>"></div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card" style="background:var(--paper);border-style:dashed;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between" style="font-size:14px;"><span class="text-muted">Subtotal</span><span class="mono" id="subtotalOut">$0.00</span></div>
                            <div class="d-flex justify-content-between" style="font-size:14px;"><span class="text-muted">Discount</span><span class="mono" id="discountOut">-$0.00</span></div>
                            <div class="d-flex justify-content-between" style="font-size:14px;"><span class="text-muted">Tax</span><span class="mono" id="taxOut">+$0.00</span></div>
                            <hr class="hairline-divider" style="margin:8px 0;">
                            <div class="d-flex justify-content-between" style="font-size:17px;font-weight:600;"><span>Net Total</span><span class="mono" id="netOut">$0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hairline-divider">
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Create Bill</button>
                <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>

<script>
function rowTemplate() {
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td><input type="text" name="description[]" class="form-control" placeholder="e.g. Consultation fee"></td>
        <td><input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1"></td>
        <td><input type="number" step="0.01" name="unit_price[]" class="form-control price-input" min="0"></td>
        <td><span class="row-amount mono">0.00</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button></td>`;
    return tr;
}

function recalc() {
    let subtotal = 0;
    document.querySelectorAll('#itemsBody .item-row').forEach(function (row) {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const amount = qty * price;
        row.querySelector('.row-amount').textContent = amount.toFixed(2);
        subtotal += amount;
    });
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const tax = parseFloat(document.getElementById('taxInput').value) || 0;
    const net = Math.max(0, subtotal - discount + tax);
    document.getElementById('subtotalOut').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('discountOut').textContent = '-$' + discount.toFixed(2);
    document.getElementById('taxOut').textContent = '+$' + tax.toFixed(2);
    document.getElementById('netOut').textContent = '$' + net.toFixed(2);
}

document.getElementById('itemsBody').addEventListener('input', recalc);
document.getElementById('discountInput').addEventListener('input', recalc);
document.getElementById('taxInput').addEventListener('input', recalc);

document.getElementById('addRowBtn').addEventListener('click', function () {
    document.getElementById('itemsBody').appendChild(rowTemplate());
});

document.getElementById('itemsBody').addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-row');
    if (!btn) return;
    const rows = document.querySelectorAll('#itemsBody .item-row');
    if (rows.length > 1) { btn.closest('tr').remove(); recalc(); }
});

recalc();
</script>

<?php require_once '../includes/footer.php'; ?>
