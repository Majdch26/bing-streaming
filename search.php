<?php
/* Start session and include database connection */
session_start();
include('db.php');

/* Set headers for JSON response */
header('Content-Type: application/json');

/* Get search query from GET parameter */
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

/* Return empty array if query is too short*/
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    /* Search videos (both films and series) */
    $stmt = $conn->prepare("
        SELECT 
            v.id, 
            v.titre, 
            v.date_publication, 
            v.image,
            CASE 
                WHEN f.id_video IS NOT NULL THEN 'film'
                WHEN s.id_video IS NOT NULL THEN 'serie'
            END AS type
        FROM 
            video v
        LEFT JOIN 
            film f ON v.id = f.id_video
        LEFT JOIN 
            serie s ON v.id = s.id_video
        WHERE 
            v.titre LIKE :query
        ORDER BY 
            v.date_publication DESC
        LIMIT 10
    ");
    
    $searchQuery = '%' . $query . '%';
    $stmt->bindParam(':query', $searchQuery);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    /* Return JSON response */
    echo json_encode($results);
    
} catch (PDOException $e) {
    /* Log error and return empty array */
    error_log("Search error: " . $e->getMessage());
    echo json_encode([]);
}
?>