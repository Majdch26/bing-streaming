<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('User ID not provided');
    }

    $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>