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

    // Prevent self-deletion
    if ($_POST['id'] == $_SESSION['user_id']) {
        throw new Exception("You cannot delete your own account");
    }

    // Delete user (cascade will handle profiles)
    $stmt = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    header("Location: admin.php?success=User+deleted");
} catch (Exception $e) {
    header("Location: admin.php?error=" . urlencode($e->getMessage()));
}
?>