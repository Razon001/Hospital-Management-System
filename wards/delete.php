<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole(['admin','receptionist']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $chk = $pdo->prepare("SELECT COUNT(*) c FROM admissions WHERE ward_id = ?");
        $chk->execute([$id]);
        if ((int)$chk->fetch()['c'] > 0) {
            setFlash('danger', 'This ward has admission history and cannot be deleted. Set its status to Maintenance instead.');
        } else {
            $pdo->prepare("DELETE FROM wards WHERE id = ?")->execute([$id]);
            setFlash('success', 'Ward deleted.');
        }
    }
}
redirect('list.php');
