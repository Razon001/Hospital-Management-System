<?php
/**
 * Sidebar navigation.
 * Expects $base_url (browser-relative prefix) and optionally $activeMenu
 * to be set by the including page before header.php pulls this in.
 */
$activeMenu = $activeMenu ?? '';
$__u = currentUser();
function navActive($key, $activeMenu) { return $activeMenu === $key ? 'active' : ''; }
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="appSidebar">
    <div class="brand">
        <div class="brand-mark">H+</div>
        <div class="brand-text">MediCore HMS<small>Hospital Management</small></div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Overview</div>
        <a href="<?= $base_url ?>index.php" class="<?= navActive('dashboard', $activeMenu) ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <div class="sidebar-section-label">Clinical</div>
        <a href="<?= $base_url ?>patients/list.php" class="<?= navActive('patients', $activeMenu) ?>">
            <i class="bi bi-person-lines-fill"></i> Patients
        </a>
        <a href="<?= $base_url ?>appointments/list.php" class="<?= navActive('appointments', $activeMenu) ?>">
            <i class="bi bi-calendar2-check"></i> Appointments
        </a>
        <a href="<?= $base_url ?>admissions/list.php" class="<?= navActive('admissions', $activeMenu) ?>">
            <i class="bi bi-hospital"></i> Admissions (IPD)
        </a>

        <div class="sidebar-section-label">Staff &amp; Facility</div>
        <a href="<?= $base_url ?>doctors/list.php" class="<?= navActive('doctors', $activeMenu) ?>">
            <i class="bi bi-person-badge"></i> Doctors
        </a>
        <?php if (hasRole('admin')): ?>
        <a href="<?= $base_url ?>departments/list.php" class="<?= navActive('departments', $activeMenu) ?>">
            <i class="bi bi-diagram-3"></i> Departments
        </a>
        <?php endif; ?>
        <a href="<?= $base_url ?>wards/list.php" class="<?= navActive('wards', $activeMenu) ?>">
            <i class="bi bi-building"></i> Wards &amp; Beds
        </a>

        <?php if (hasRole(['admin','receptionist'])): ?>
        <div class="sidebar-section-label">Finance &amp; Pharmacy</div>
        <a href="<?= $base_url ?>billing/list.php" class="<?= navActive('billing', $activeMenu) ?>">
            <i class="bi bi-receipt"></i> Billing
        </a>
        <?php endif; ?>
        <?php if (hasRole(['admin','receptionist','doctor'])): ?>
        <a href="<?= $base_url ?>pharmacy/list.php" class="<?= navActive('pharmacy', $activeMenu) ?>">
            <i class="bi bi-capsule"></i> Pharmacy
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-foot">
        Signed in as <strong style="color:#fff"><?= sanitize($__u['full_name']) ?></strong><br>
        MediCore HMS &middot; v1.0
    </div>
</aside>
