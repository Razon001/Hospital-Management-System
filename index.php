<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
requireLogin();

$base_url = '';
$pageTitle = 'Dashboard';
$pageEyebrow = 'Overview';
$activeMenu = 'dashboard';
$role = $_SESSION['role'];

if ($role === 'doctor') {
    /* ---------------- Doctor-scoped dashboard ---------------- */
    $doctorId = currentDoctorId($pdo);

    $myToday = 0; $myUpcoming = 0; $myPatients = 0; $myCompletedMonth = 0; $upcomingList = [];
    if ($doctorId) {
        $myToday = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id=? AND appointment_date=CURDATE()");
        $myToday->execute([$doctorId]); $myToday = $myToday->fetch()['c'];

        $myUpcoming = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id=? AND appointment_date >= CURDATE() AND status='scheduled'");
        $myUpcoming->execute([$doctorId]); $myUpcoming = $myUpcoming->fetch()['c'];

        $myPatients = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id=?");
        $myPatients->execute([$doctorId]); $myPatients = $myPatients->fetch()['c'];

        $myCompletedMonth = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id=? AND status='completed' AND MONTH(appointment_date)=MONTH(CURDATE()) AND YEAR(appointment_date)=YEAR(CURDATE())");
        $myCompletedMonth->execute([$doctorId]); $myCompletedMonth = $myCompletedMonth->fetch()['c'];

        $stmt = $pdo->prepare("SELECT a.*, p.name AS patient_name, p.patient_code FROM appointments a
                                JOIN patients p ON a.patient_id = p.id
                                WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE() AND a.status = 'scheduled'
                                ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 8");
        $stmt->execute([$doctorId]);
        $upcomingList = $stmt->fetchAll();
    }
} else {
    /* ---------------- Admin / Receptionist dashboard ---------------- */
    $totalPatients   = $pdo->query("SELECT COUNT(*) c FROM patients")->fetch()['c'];
    $totalDoctors    = $pdo->query("SELECT COUNT(*) c FROM doctors WHERE status='active'")->fetch()['c'];
    $todayAppts      = $pdo->query("SELECT COUNT(*) c FROM appointments WHERE appointment_date = CURDATE()")->fetch()['c'];
    $availableBeds   = $pdo->query("SELECT COUNT(*) c FROM wards WHERE status='available'")->fetch()['c'];
    $totalBeds       = $pdo->query("SELECT COUNT(*) c FROM wards")->fetch()['c'];
    $admittedNow     = $pdo->query("SELECT COUNT(*) c FROM admissions WHERE status='admitted'")->fetch()['c'];
    $pendingBills    = $pdo->query("SELECT COUNT(*) c FROM bills WHERE status != 'paid'")->fetch()['c'];
    $lowStock        = $pdo->query("SELECT COUNT(*) c FROM medicines WHERE quantity <= reorder_level")->fetch()['c'];
    $totalRevenue    = $pdo->query("SELECT COALESCE(SUM(paid_amount),0) c FROM bills")->fetch()['c'];

    $recent = $pdo->query("SELECT a.*, p.name AS patient_name, p.patient_code, d.name AS doctor_name
                            FROM appointments a
                            JOIN patients p ON a.patient_id = p.id
                            JOIN doctors d ON a.doctor_id = d.id
                            ORDER BY a.created_at DESC LIMIT 8")->fetchAll();

    // Revenue for the last 6 months (filled with zero for empty months)
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("-$i months"));
        $months[$key] = ['label' => date('M', strtotime("-$i months")), 'total' => 0.0];
    }
    $revRows = $pdo->query("SELECT DATE_FORMAT(bill_date, '%Y-%m') ym, SUM(paid_amount) total
                             FROM bills WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                             GROUP BY ym")->fetchAll();
    foreach ($revRows as $r) {
        if (isset($months[$r['ym']])) $months[$r['ym']]['total'] = (float)$r['total'];
    }
    $chartLabels = array_column($months, 'label');
    $chartValues = array_column($months, 'total');
}

require_once 'includes/header.php';
?>

<div class="vitals-strip">
    <span class="vitals-label"><i class="bi bi-activity"></i> Live Overview</span>
    <svg viewBox="0 0 800 40" preserveAspectRatio="none" style="width:100%;">
        <path d="M0,20 L20,20 L28,6 L36,34 L44,14 L52,20 L120,20 L128,6 L136,34 L144,14 L152,20 L220,20 L228,6 L236,34 L244,14 L252,20 L320,20 L328,6 L336,34 L344,14 L352,20 L420,20 L428,6 L436,34 L444,14 L452,20 L520,20 L528,6 L536,34 L544,14 L552,20 L620,20 L628,6 L636,34 L644,14 L652,20 L720,20 L728,6 L736,34 L744,14 L752,20 L800,20"
              fill="none" stroke="#0E6B64" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" opacity="0.55"/>
    </svg>
</div>

<?php if ($role === 'doctor'): ?>

    <?php if (!$doctorId): ?>
        <div class="alert alert-warning">Your login isn't linked to a doctor profile yet. Please ask the administrator to link your account from the Doctors section.</div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Today's Appointments</span><div class="stat-number"><?= (int)$myToday ?></div><div class="stat-sub">Scheduled for today</div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Upcoming</span><div class="stat-number stat-accent"><?= (int)$myUpcoming ?></div><div class="stat-sub">Scheduled ahead</div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">My Patients</span><div class="stat-number"><?= (int)$myPatients ?></div><div class="stat-sub">Distinct patients seen</div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Completed (this month)</span><div class="stat-number"><?= (int)$myCompletedMonth ?></div><div class="stat-sub">Consultations closed</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">My Upcoming Appointments</div>
        <div class="table-wrap" style="border:none;">
            <table class="data-table table table-hover mb-0">
                <thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$upcomingList): ?>
                    <tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-check"></i>No upcoming appointments.</div></td></tr>
                <?php endif; ?>
                <?php foreach ($upcomingList as $row): ?>
                    <tr>
                        <td><?= sanitize($row['patient_name']) ?> <span class="patient-code-chip"><?= sanitize($row['patient_code']) ?></span></td>
                        <td><?= formatDate($row['appointment_date']) ?></td>
                        <td><?= date('h:i A', strtotime($row['appointment_time'])) ?></td>
                        <td><?= sanitize($row['reason'] ?: '—') ?></td>
                        <td><?= statusBadge($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Total Patients</span><div class="stat-number"><?= (int)$totalPatients ?></div><div class="stat-sub"><a href="patients/list.php">View all &rarr;</a></div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Active Doctors</span><div class="stat-number"><?= (int)$totalDoctors ?></div><div class="stat-sub"><a href="doctors/list.php">View all &rarr;</a></div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Today's Appointments</span><div class="stat-number stat-accent"><?= (int)$todayAppts ?></div><div class="stat-sub"><a href="appointments/list.php">View schedule &rarr;</a></div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile"><span class="eyebrow">Beds Available</span><div class="stat-number"><?= (int)$availableBeds ?><span style="font-size:16px;color:var(--ink-soft);"> / <?= (int)$totalBeds ?></span></div><div class="stat-sub"><?= (int)$admittedNow ?> currently admitted</div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-4">
            <div class="stat-tile"><span class="eyebrow">Total Revenue Collected</span><div class="stat-number">$<?= formatMoney($totalRevenue) ?></div><div class="stat-sub"><a href="billing/list.php">View billing &rarr;</a></div></div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="stat-tile <?= $pendingBills > 0 ? 'stat-warn' : '' ?>"><span class="eyebrow">Pending Bills</span><div class="stat-number"><?= (int)$pendingBills ?></div><div class="stat-sub">Unpaid or partially paid</div></div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="stat-tile <?= $lowStock > 0 ? 'stat-warn' : '' ?>"><span class="eyebrow">Low Stock Medicines</span><div class="stat-number"><?= (int)$lowStock ?></div><div class="stat-sub"><a href="pharmacy/list.php">Check pharmacy &rarr;</a></div></div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="patients/form.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>New Patient</a>
        <a href="appointments/form.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-plus me-1"></i>New Appointment</a>
        <a href="admissions/add.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-hospital me-1"></i>Admit Patient</a>
        <a href="billing/add.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt-cutoff me-1"></i>Create Bill</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Revenue — Last 6 Months</div>
                <div class="card-body">
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">Recent Appointments</div>
                <div class="table-wrap" style="border:none;">
                    <table class="data-table table table-hover mb-0">
                        <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (!$recent): ?>
                            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-calendar2-check"></i>No appointments yet.</div></td></tr>
                        <?php endif; ?>
                        <?php foreach ($recent as $row): ?>
                            <tr>
                                <td><?= sanitize($row['patient_name']) ?></td>
                                <td><?= sanitize($row['doctor_name']) ?></td>
                                <td><?= formatDate($row['appointment_date']) ?></td>
                                <td><?= statusBadge($row['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode(array_map(fn($v) => round($v, 2), $chartValues)) ?>,
                backgroundColor: '#0E6B64',
                borderRadius: 4,
                maxBarThickness: 42
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#E1E7E4' } },
                x: { grid: { display: false } }
            }
        }
    });
    </script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
