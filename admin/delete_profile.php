<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // Verify admin status
    $stmt = $conn->prepare("SELECT is_admin FROM utilisateur WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_admin']) {
        header("Location: ../home.php");
        exit();
    }

    // Delete profile (cascade will handle favorites)
    $stmt = $conn->prepare("DELETE FROM profils WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    header("Location: admin.php?success=Profile+deleted");
} catch (PDOException $e) {
    header("Location: admin.php?error=" . urlencode($e->getMessage()));
}
?>