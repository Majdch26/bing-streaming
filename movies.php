<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Get all movies with duration */
$stmt = $conn->prepare("
    SELECT v.*, f.duree AS duree 
    FROM video v
    JOIN film f ON v.id = f.id_video
    ORDER BY v.date_publication DESC
");
$stmt->execute();
$movies = $stmt->fetchAll();

/* Get unique genres */
$genres_stmt = $conn->query("SELECT DISTINCT categorie FROM video");
$genres = $genres_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/movies.css">
</head>
<body>
    <div class="loading-screen">
        <div class="loading-logo"></div>
    </div>
    
    <?php include('navbar.php'); ?>

    <div class="filter-section">
        <h3>Filter Movies:</h3>
        <select class="genre-filter" id="genreFilter">
            <option value="all">All Genres</option>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= htmlspecialchars($genre) ?>">
                    <?= htmlspecialchars($genre) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="content-section">
        <div class="movies-grid">
            <?php foreach ($movies as $movie): 
                $duration = $movie['duree'] ?? 0;
                $hours = floor($duration / 60);
                $minutes = $duration % 60;
            ?>
            <div class="movie-card" data-genre="<?= htmlspecialchars($movie['categorie']) ?>">
                <div class="movie-poster-container">
                    <img src="<?= htmlspecialchars($movie['image']) ?>" class="movie-poster" alt="<?= htmlspecialchars($movie['titre']) ?>">
                    <div class="movie-badge">
                        <?= rand(75, 98) ?>% Match
                    </div>
                </div>
                <div class="movie-info">
                    <h3 class="movie-title"><?= htmlspecialchars($movie['titre']) ?></h3>
                    <div class="movie-meta">
                        <span><?= date('Y', strtotime($movie['date_publication'])) ?></span>
                        <?php if($duration > 0): ?>
                        <span class="movie-duration">
                            <?= $hours > 0 ? "{$hours}h " : "" ?><?= $minutes ?>m
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="movie-description">
                        <?= htmlspecialchars($movie['description'] ?? 'No description available') ?>
                    </p>
                    <a href="watch.php?movie=<?= $movie['id'] ?>" class="watch-button">Watch Now</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="/js/movies_series.js"></script>
</body>
</html>