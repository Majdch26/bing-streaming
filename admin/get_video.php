<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Video ID not provided');
    }

    $videoId = $_GET['id'];
    
    // Get video info
    $stmt = $conn->prepare("SELECT v.*, 
        CASE WHEN f.id_video IS NOT NULL THEN 'film' ELSE 'serie' END as type,
        COALESCE(f.duree, s.duree) as duree,
        f.url_video as url_video
        FROM video v 
        LEFT JOIN film f ON v.id = f.id_video 
        LEFT JOIN serie s ON v.id = s.id_video 
        WHERE v.id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        throw new Exception('Video not found');
    }

    echo json_encode([
        'success' => true,
        'data' => $video
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>