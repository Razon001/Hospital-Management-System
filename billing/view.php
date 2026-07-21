<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$base_url = '../';
$activeMenu = 'billing';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    if (!verifyCsrf()) { redirect("view.php?id=$id"); }
    $addAmount = (float)($_POST['add_payment']);
    if ($addAmount > 0) {
        $b = $pdo->prepare("SELECT paid_amount, net_amount FROM bills WHERE id = ?");
        $b->execute([$id]);
        $row = $b->fetch();
        if ($row) {
            $newPaid = round((float)$row['paid_amount'] + $addAmount, 2);
            $newStatus = $newPaid <= 0 ? 'unpaid' : ($newPaid >= (float)$row['net_amount'] ? 'paid' : 'partial');
            $pdo->prepare("UPDATE bills SET paid_amount = ?, status = ? WHERE id = ?")->execute([$newPaid, $newStatus, $id]);
            setFlash('success', 'Payment recorded.');
        }
    }
    redirect("view.php?id=$id");
}

$stmt = $pdo->prepare("SELECT b.*, p.name AS patient_name, p.patient_code, p.phone, p.address FROM bills b JOIN patients p ON b.patient_id = p.id WHERE b.id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();
if (!$bill) { setFlash('danger', 'Bill not found.'); redirect('list.php'); }

$items = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

$pageTitle = 'Bill Details';
$pageEyebrow = 'Finance';
require_once '../includes/header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="list.php" class="text-muted" style="font-size:14px;"><i class="bi bi-arrow-left"></i> Back to Billing</a>
    <a href="print.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i>Print / PDF</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Bill <span class="bill-no-chip"><?= sanitize($bill['bill_no']) ?></span></span>
                <?= statusBadge($bill['status']) ?>
            </div>
            <div class="card-body">
                <div class="row mb-3" style="font-size:14px;">
                    <div class="col-6">
                        <div class="eyebrow-label">Patient</div>
                        <div><?= sanitize($bill['patient_name']) ?> (<?= sanitize($bill['patient_code']) ?>)</div>
                    </div>
                    <div class="col-6 text-md-end">
                        <div class="eyebrow-label">Date</div>
                        <div><?= formatDateTime($bill['bill_date']) ?></div>
                    </div>
                </div>
                <table class="table" style="font-size:14px;">
                    <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= sanitize($it['description']) ?></td>
                            <td><?= (int)$it['quantity'] ?></td>
                            <td class="mono">$<?= formatMoney($it['unit_price']) ?></td>
                            <td class="text-end mono">$<?= formatMoney($it['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="row justify-content-end">
                    <div class="col-md-5" style="font-size:14px;">
                        <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span class="mono">$<?= formatMoney($bill['total_amount']) ?></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Discount</span><span class="mono">-$<?= formatMoney($bill['discount']) ?></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Tax</span><span class="mono">+$<?= formatMoney($bill['tax']) ?></span></div>
                        <hr class="hairline-divider" style="margin:8px 0;">
                        <div class="d-flex justify-content-between" style="font-size:17px;font-weight:600;"><span>Net Total</span><span class="mono">$<?= formatMoney($bill['net_amount']) ?></span></div>
                        <div class="d-flex justify-content-between" style="color:var(--success);"><span>Paid</span><span class="mono">$<?= formatMoney($bill['paid_amount']) ?></span></div>
                        <div class="d-flex justify-content-between" style="color:var(--danger);"><span>Balance Due</span><span class="mono">$<?= formatMoney(max(0, $bill['net_amount'] - $bill['paid_amount'])) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Payment</div>
            <div class="card-body">
                <p style="font-size:14px;" class="text-muted">Method on file: <strong><?= sanitize($bill['payment_method']) ?></strong></p>
                <?php if ($bill['status'] !== 'paid'): ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <label class="form-label">Record a payment ($)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" max="<?= max(0, $bill['net_amount'] - $bill['paid_amount']) ?>" name="add_payment" class="form-control" required>
                            <button class="btn btn-primary">Add</button>
                        </div>
                        <div class="form-text">Balance due: $<?= formatMoney(max(0, $bill['net_amount'] - $bill['paid_amount'])) ?></div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success mb-0" style="font-size:14px;">This bill is fully paid.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
