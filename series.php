<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Get all series with episode counts */
$stmt = $conn->prepare("
    SELECT v.*, 
           COUNT(DISTINCT e.numero_saison) AS seasons,
           COUNT(e.id) AS episodes
    FROM video v
    JOIN serie s ON v.id = s.id_video
    LEFT JOIN episode e ON v.id = e.id_serie
    GROUP BY v.id
    ORDER BY v.date_publication DESC
");
$stmt->execute();
$series = $stmt->fetchAll();

/* Get unique genres */
$genres_stmt = $conn->query("SELECT DISTINCT categorie FROM video");
$genres = $genres_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Series - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/series.css">
</head>
<body>

    <div class="loading-screen">
        <div class="loading-logo"></div>
    </div>
    <?php include('navbar.php'); ?>

    <div class="filter-section">
        <h3>Filter Series:</h3>
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
        <div class="series-grid">
            <?php foreach ($series as $serie): 
                $seasons = $serie['seasons'] ?? 0;
                $episodes = $serie['episodes'] ?? 0;
            ?>
            <div class="series-card" data-genre="<?= htmlspecialchars($serie['categorie']) ?>">
                <div class="series-poster-container">
                    <img src="<?= htmlspecialchars($serie['image']) ?>" class="series-poster" alt="<?= htmlspecialchars($serie['titre']) ?>">
                    <div class="series-badge">
                        <?= rand(75, 98) ?>% Match
                    </div>
                </div>
                <div class="series-info">
                    <h3 class="series-title"><?= htmlspecialchars($serie['titre']) ?></h3>
                    
                    <div class="series-stats">
                        <?php if($seasons > 0): ?>
                        <div class="stat-item">
                            <?= $seasons ?> Season<?= $seasons > 1 ? 's' : '' ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($episodes > 0): ?>
                        <div class="stat-item">
                            <?= $episodes ?> Episodes
                        </div>
                        <?php endif; ?>
                    </div>

                    <p class="series-description">
                        <?= htmlspecialchars($serie['description'] ?? 'Description coming soon') ?>
                    </p>
                    
                    <a href="serie.php?serie=<?= $serie['id'] ?>" class="watch-button">View Series</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="/js/movies_series.js"></script>
</body>
</html>