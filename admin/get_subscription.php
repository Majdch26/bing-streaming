<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Subscription ID not provided');
    }

    $stmt = $conn->prepare("SELECT * FROM abonnement WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        throw new Exception('Subscription not found');
    }

    echo json_encode([
        'success' => true,
        'data' => $subscription
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>