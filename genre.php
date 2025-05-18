<?php
session_start();
include('db.php');

/* Check if genre parameter exists */
if(!isset($_GET['genre'])) {
    $_SESSION['error'] = "No genre specified";
    header("Location: index.php");
    exit();
}

$genre = strtolower($_GET['genre']);

/* Get valid genres from database */
$validGenres = $conn->query("SELECT DISTINCT LOWER(categorie) AS genre FROM video")->fetchAll(PDO::FETCH_COLUMN);

// Validate genre exists
if(!in_array($genre, $validGenres)) {
    $_SESSION['error'] = "Invalid genre: " . htmlspecialchars($genre);
    header("Location: index.php");
    exit();
}

/* Get content for this genre */
try {
    $stmt = $conn->prepare("
        SELECT v.*, 
               COALESCE(f.duree, SUM(e.duree)) AS duration,
               COUNT(e.id) AS episodes,
               CASE 
                   WHEN f.id_video IS NOT NULL THEN 'movie'
                   WHEN s.id_video IS NOT NULL THEN 'series'
               END AS type
        FROM video v
        LEFT JOIN film f ON v.id = f.id_video
        LEFT JOIN serie s ON v.id = s.id_video
        LEFT JOIN episode e ON s.id_video = e.id_serie
        WHERE LOWER(v.categorie) = ?
        GROUP BY v.id
        ORDER BY v.date_publication DESC
    ");
    $stmt->execute([$genre]);
    $content = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucwords(str_replace('-', ' ', $genre)) ?> - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/genre.css">
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="genre-header">
        <h1 class="genre-title"><?= ucwords(str_replace('-', ' ', $genre)) ?></h1>
        <?php if(empty($content)): ?>
            <p class="error-message">No content found in this category</p>
        <?php endif; ?>
    </div>

    <?php if(!empty($content)): ?>
    <div class="content-grid">
        <?php foreach($content as $item): 
            $duration = $item['duration'] ?? 0;
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
        ?>
        <div class="content-card">
            <img src="<?= htmlspecialchars($item['image']) ?>" class="content-poster" alt="<?= htmlspecialchars($item['titre']) ?>">
            <div class="content-info">
                <h3 class="content-title"><?= htmlspecialchars($item['titre']) ?></h3>
                <div class="content-meta">
                    <?php if($item['type'] == 'movie'): ?>
                        <?= $hours ?>h <?= $minutes ?>m
                    <?php else: ?>
                        <?= $item['episodes'] ?> Episodes
                    <?php endif; ?>
                </div>
                <a href="<?= $item['type'] == 'movie' ? 'watch.php?movie=' : 'serie.php?serie=' ?><?= $item['id'] ?>" 
                   class="watch-button">
                    <?= $item['type'] == 'movie' ? 'Watch Movie' : 'View Series' ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>