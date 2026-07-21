<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
        $stmt->execute([$id]);
        $userId = $stmt->fetchColumn();

        $pdo->prepare("DELETE FROM doctors WHERE id = ?")->execute([$id]);

        if ($userId) {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        }
        setFlash('success', 'Doctor deleted.');
    }
}
redirect('list.php');
