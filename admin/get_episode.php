<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Episode ID not provided');
    }

    $stmt = $conn->prepare("SELECT * FROM episode WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $episode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$episode) {
        throw new Exception('Episode not found');
    }

    echo json_encode([
        'success' => true,
        'data' => $episode
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>