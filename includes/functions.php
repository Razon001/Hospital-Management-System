<?php
/**
 * Core helper functions: session/auth, flash messages, CSRF, formatting.
 * Included by every protected page, right after config/database.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------------------------------------------------------
 * Sanitization / small helpers
 * --------------------------------------------------------------- */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: {$url}");
    exit;
}

function padCode($prefix, $id, $digits = 4) {
    return $prefix . str_pad((string)$id, $digits, '0', STR_PAD_LEFT);
}

/* ---------------------------------------------------------------
 * Auth
 * --------------------------------------------------------------- */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(loginRedirectPath());
    }
}

/**
 * Works out the correct relative path back to login.php based on
 * how deep the current script is nested (root vs one module folder down).
 */
function loginRedirectPath() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // If the script lives inside a module subfolder, go up one level.
    return (substr_count(trim(dirname($script), '/'), '/') >= 1 && basename(dirname($script)) !== '')
        ? '../login.php'
        : 'login.php';
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
    ];
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $roles = (array)$roles;
    return in_array($_SESSION['role'], $roles, true);
}

function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        setFlash('danger', "You don't have permission to access that page.");
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $home = (basename(dirname($script)) !== '' && dirname($script) !== '/') ? '../index.php' : 'index.php';
        redirect($home);
    }
}

/** Returns the doctors.id row linked to the currently logged in doctor user, or null. */
function currentDoctorId($pdo) {
    if (!hasRole('doctor')) return null;
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

/* ---------------------------------------------------------------
 * Flash messages (one-time notices shown after redirects)
 * --------------------------------------------------------------- */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function renderFlash() {
    if (empty($_SESSION['flash'])) return;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info');
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . sanitize($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        . '</div>';
}

/* ---------------------------------------------------------------
 * CSRF protection
 * --------------------------------------------------------------- */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        setFlash('danger', 'Your session expired or the form was resubmitted. Please try again.');
        return false;
    }
    return true;
}

/* ---------------------------------------------------------------
 * Formatting
 * --------------------------------------------------------------- */
function formatDate($date, $format = 'd M Y') {
    if (empty($date) || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function formatDateTime($datetime, $format = 'd M Y, h:i A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '—';
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

function formatMoney($amount) {
    return number_format((float)$amount, 2);
}

function statusBadge($status) {
    $map = [
        'active'     => 'success',
        'inactive'   => 'secondary',
        'scheduled'  => 'primary',
        'completed'  => 'success',
        'cancelled'  => 'danger',
        'available'  => 'success',
        'occupied'   => 'danger',
        'maintenance'=> 'warning',
        'admitted'   => 'primary',
        'discharged' => 'success',
        'paid'       => 'success',
        'partial'    => 'warning',
        'unpaid'     => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge badge-status bg-' . $class . '">' . ucfirst(sanitize($status)) . '</span>';
}

/** Simple age calculator from a date of birth. */
function calcAge($dob) {
    if (empty($dob)) return '—';
    try {
        $d = new DateTime($dob);
        $now = new DateTime();
        return $d->diff($now)->y . ' yrs';
    } catch (Exception $e) {
        return '—';
    }
}
