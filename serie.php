<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Get series ID from URL */
$series_id = isset($_GET['serie']) ? (int)$_GET['serie'] : 0;

if ($series_id <= 0) {
    header("Location: index.php");
    exit();
}

/* Get series details - fixed query to match your table structure */
$stmt = $conn->prepare("
    SELECT v.*, s.duree 
    FROM video v 
    JOIN serie s ON v.id = s.id_video 
    WHERE v.id = ?
");
$stmt->execute([$series_id]);
$series = $stmt->fetch();

if (!$series) {
    header("Location: index.php");
    exit();
}

/* Get all episodes grouped by season - fixed to use your episode table structure */
$episodes_stmt = $conn->prepare("
    SELECT * FROM episode 
    WHERE id_serie = ? 
    ORDER BY numero_saison ASC, numero_episode ASC
");
$episodes_stmt->execute([$series_id]);
$episodes = $episodes_stmt->fetchAll();

/* Group episodes by season */
$seasons = [];
foreach ($episodes as $episode) {
    $season_num = $episode['numero_saison'];
    if (!isset($seasons[$season_num])) {
        $seasons[$season_num] = [];
    }
    $seasons[$season_num][] = $episode;
}

/* Get similar series (same category) - fixed to match your table structure */
$similar_stmt = $conn->prepare("
    SELECT v.* FROM video v 
    JOIN serie s ON v.id = s.id_video 
    WHERE v.categorie = ? AND v.id != ?
    ORDER BY RANDOM() 
    LIMIT 6
");
$similar_stmt->execute([$series['categorie'], $series_id]);
$similar_series = $similar_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($series['titre']); ?> - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/serie.css">
</head>
<body>
    <div class="loading-screen">
        <div class="loading-logo"></div>
    </div>
    <?php include('navbar.php'); ?>

    <section class="hero-section">
    <div class="hero-backdrop">
        <img src="<?php echo htmlspecialchars($series['image']); ?>" class="hero-bg-image" alt="<?php echo htmlspecialchars($series['titre']); ?>">
        <div class="hero-gradient"></div>
    </div>
    <div class="hero-content">
        <h1 class="hero-title"><?php echo htmlspecialchars($series['titre']); ?></h1>
        <div class="hero-meta">
            <!--<span class="match">98% Match</span>-->
            <span class="year"><?php echo date('Y', strtotime($series['date_publication'])); ?></span>
            <span class="duration"><?php echo floor($series['duree'] / 60); ?>h <?php echo $series['duree'] % 60; ?>m</span>
            <span class="quality">HD</span>
        </div>
        <p class="hero-description"><?php echo htmlspecialchars($series['description']); ?></p>
        <div class="hero-actions">
            <?php if (!empty($seasons[1][0])): ?>
                <a href="watch.php?serie=<?php echo $series_id; ?>&season=1&episode=<?php echo $seasons[1][0]['id']; ?>" class="action-button play-button">
                    <i class="fas fa-play"></i> Play S1 E1
                </a>
            <?php endif; ?>
            <!--<button class="action-button secondary-button">
                <i class="fas fa-plus"></i> My List
            </button>
            <button class="action-button secondary-button">
                <i class="fas fa-share-alt"></i> Share
            </button>-->
        </div>
    </div>
</section>
    
    <div class="series-container">
        <div class="seasons-container">
            <h2 class="section-title">Episodes</h2>
            
            <div class="season-selector">
                <?php foreach ($seasons as $season_num => $season_episodes): ?>
                    <div class="season-tab <?php echo $season_num == 1 ? 'active' : ''; ?>" 
                         onclick="changeSeason(<?php echo $season_num; ?>)">
                        Season <?php echo $season_num; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php foreach ($seasons as $season_num => $season_episodes): ?>
                <div class="season-content" id="season-<?php echo $season_num; ?>" 
                     style="<?php echo $season_num != 1 ? 'display: none;' : ''; ?>">
                    <div class="episodes-grid">
                        <?php foreach ($season_episodes as $episode): ?>
                            <a href="watch.php?serie=<?php echo $series_id; ?>&season=<?php echo $season_num; ?>&episode=<?php echo $episode['id']; ?>" class="episode-card">
                                <img src="<?php echo htmlspecialchars($episode['image_episode']); ?>" alt="<?php echo htmlspecialchars($episode['titre']); ?>" class="episode-image">
                                <div class="episode-info">
                                    <div class="episode-number">S<?php echo $episode['numero_saison']; ?> E<?php echo $episode['numero_episode']; ?></div>
                                    <h3 class="episode-title"><?php echo htmlspecialchars($episode['titre']); ?></h3>
                                    <p class="episode-description"><?php echo htmlspecialchars($episode['description']); ?></p>
                                    <div class="episode-meta">
                                        <span><?php echo floor($episode['duree'] / 60); ?>m <?php echo $episode['duree'] % 60; ?>s</span>
                                        <span><i class="fas fa-closed-captioning"></i> Subtitles</span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($similar_series)): ?>
            <div class="similar-section">
                <h2 class="section-title">More Like This</h2>
                <div class="similar-grid">
                    <?php foreach ($similar_series as $similar): ?>
                        <a href="serie.php?serie=<?php echo $similar['id']; ?>" class="similar-card">
                            <img src="<?php echo htmlspecialchars($similar['image']); ?>" alt="<?php echo htmlspecialchars($similar['titre']); ?>" class="similar-image">
                            <div class="similar-title"><?php echo htmlspecialchars($similar['titre']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="/js/serie.js"></script>
</body>
</html>










