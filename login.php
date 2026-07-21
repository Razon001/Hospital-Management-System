<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Your session expired. Please try logging in again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = 'Please enter both username and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Incorrect username or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'This account has been deactivated. Please contact the administrator.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                redirect('index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — MediCore HMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-mark">H+</div>
            <div class="brand-text">MediCore HMS</div>
        </div>
        <p class="text-muted" style="margin-top:-10px;margin-bottom:20px;font-size:14px;">Sign in to manage patients, appointments, and hospital operations.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger py-2" style="font-size:14px;">
                <?php foreach ($errors as $e) echo sanitize($e) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="e.g. admin" required autofocus value="<?= sanitize($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Sign in</button>
        </form>

        <div class="login-demo-box">
            <strong>Demo logins</strong> (change these after first login)<br>
            Admin&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;→ <code>admin</code> / <code>admin123</code><br>
            Doctor&nbsp;&nbsp;&nbsp;&nbsp; → <code>dr.ayesha</code> / <code>doctor123</code><br>
            Reception → <code>reception</code> / <code>reception123</code>
        </div>
    </div>
</div>
</body>
</html>
