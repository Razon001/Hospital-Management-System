<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, p.name AS patient_name, p.patient_code, p.phone, p.address FROM bills b JOIN patients p ON b.patient_id = p.id WHERE b.id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();
if (!$bill) { die('Bill not found.'); }

$items = $pdo->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= sanitize($bill['bill_no']) ?> — MediCore HMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body style="background:#fff;">
<div class="printable-invoice p-4">
    <div class="no-print text-end mb-3">
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
    </div>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="brand-mark" style="width:38px;height:38px;border-radius:8px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk',sans-serif;font-weight:700;">H+</div>
                <div class="brand-font" style="font-size:20px;">MediCore HMS</div>
            </div>
            <p class="text-muted mb-0 mt-2" style="font-size:13px;">123 Wellness Road, Dhaka, Bangladesh<br>Phone: +880 1XXX-XXXXXX</p>
        </div>
        <div class="text-end">
            <h4 class="mb-0">INVOICE</h4>
            <div class="bill-no-chip"><?= sanitize($bill['bill_no']) ?></div>
            <p class="text-muted mb-0 mt-1" style="font-size:13px;"><?= formatDateTime($bill['bill_date']) ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <div class="eyebrow-label">Billed To</div>
            <div style="font-size:14px;">
                <strong><?= sanitize($bill['patient_name']) ?></strong> (<?= sanitize($bill['patient_code']) ?>)<br>
                <?= sanitize($bill['phone'] ?: '') ?><br>
                <?= nl2br(sanitize($bill['address'] ?: '')) ?>
            </div>
        </div>
        <div class="col-6 text-end">
            <div class="eyebrow-label">Bill Type</div>
            <div style="font-size:14px;"><?= sanitize($bill['bill_type']) ?></div>
            <div class="eyebrow-label mt-2">Status</div>
            <div><?= statusBadge($bill['status']) ?></div>
        </div>
    </div>

    <table class="table" style="font-size:14px;">
        <thead style="background:var(--paper);"><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th class="text-end">Amount</th></tr></thead>
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
        <div class="col-5" style="font-size:14px;">
            <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span class="mono">$<?= formatMoney($bill['total_amount']) ?></span></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Discount</span><span class="mono">-$<?= formatMoney($bill['discount']) ?></span></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Tax</span><span class="mono">+$<?= formatMoney($bill['tax']) ?></span></div>
            <hr class="hairline-divider" style="margin:8px 0;">
            <div class="d-flex justify-content-between" style="font-size:17px;font-weight:600;"><span>Net Total</span><span class="mono">$<?= formatMoney($bill['net_amount']) ?></span></div>
            <div class="d-flex justify-content-between" style="color:var(--success);"><span>Paid</span><span class="mono">$<?= formatMoney($bill['paid_amount']) ?></span></div>
            <div class="d-flex justify-content-between" style="color:var(--danger);"><span>Balance Due</span><span class="mono">$<?= formatMoney(max(0, $bill['net_amount'] - $bill['paid_amount'])) ?></span></div>
        </div>
    </div>

    <p class="text-muted text-center mt-4" style="font-size:12.5px;">Thank you. This is a computer-generated invoice from MediCore HMS.</p>
</div>
</body>
</html>
