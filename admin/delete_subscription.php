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

    // Check if any users still have this subscription
    $stmt = $conn->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_abonnement = ?");
    $stmt->execute([$_POST['id']]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        throw new Exception("Cannot delete subscription - $count users still have this plan");
    }

    // Delete subscription
    $stmt = $conn->prepare("DELETE FROM abonnement WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    header("Location: admin.php?success=Subscription+deleted");
} catch (Exception $e) {
    header("Location: admin.php?error=" . urlencode($e->getMessage()));
}
?>