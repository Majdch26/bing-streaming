<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['profile_id'])) {
    header("Location: login.php");
    exit();
}

/* Check if we're watching a movie or episode */
if (isset($_GET['movie'])) {
    $video_id = (int)$_GET['movie'];
    $video_type = 'movie';
} elseif (isset($_GET['episode'])) {
    $video_id = (int)$_GET['episode'];
    $video_type = 'episode';
    $series_id = isset($_GET['serie']) ? (int)$_GET['serie'] : 0;
    $season_num = isset($_GET['season']) ? (int)$_GET['season'] : 1;
} else {
    header("Location: home.php");
    exit();
}

/* Get video details based on type */
if ($video_type === 'movie') {
    $stmt = $conn->prepare("
        SELECT v.*, f.url_video, f.duree as video_duree, 0 as is_favorite
        FROM video v 
        JOIN film f ON v.id = f.id_video
        WHERE v.id = ?
    ");
    $stmt->execute([$video_id]);
} else {
    $stmt = $conn->prepare("
        SELECT e.*, v.titre as serie_title, v.image as serie_image, 
               v.categorie as serie_categorie, v.date_publication as serie_date,
               0 as is_favorite
        FROM episode e
        JOIN video v ON e.id_serie = v.id
        WHERE e.id = ? AND e.numero_saison = ?
    ");
    $stmt->execute([$video_id, $season_num]);
}

$video = $stmt->fetch();

if (!$video) {
    header("Location: home.php");
    exit();
}

/* Set default values for missing fields */
$video['categorie'] = $video['categorie'] ?? ($video['serie_categorie'] ?? 'Unknown');
$video['date_publication'] = $video['date_publication'] ?? ($video['serie_date'] ?? null);
$video['duree'] = $video['duree'] ?? ($video['video_duree'] ?? 0);

/* Get similar videos */
if ($video_type === 'movie') {
    $similar_stmt = $conn->prepare("
        SELECT v.* FROM video v 
        JOIN film f ON v.id = f.id_video
        WHERE v.categorie = ? AND v.id != ?
        ORDER BY RANDOM() 
        LIMIT 5
    ");
    $similar_stmt->execute([$video['categorie'], $video_id]);
} else {
    $similar_stmt = $conn->prepare("
        SELECT v.* FROM video v 
        JOIN serie s ON v.id = s.id_video
        WHERE v.categorie = ? AND v.id != ?
        ORDER BY RANDOM() 
        LIMIT 5
    ");
    $similar_stmt->execute([$video['categorie'], $series_id]);
}

$similar = $similar_stmt->fetchAll();

/* Get next episode if available */
$next_episode = null;
if ($video_type === 'episode') {
    $next_stmt = $conn->prepare("
        SELECT id FROM episode 
        WHERE id_serie = ? AND numero_saison = ? AND numero_episode = ?
        LIMIT 1
    ");
    $next_stmt->execute([$series_id, $season_num, $video['numero_episode'] + 1]);
    $next_episode = $next_stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video_type === 'movie' ? $video['titre'] : $video['titre'] . ' | ' . $video['serie_title']) ?> | BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    <link rel="stylesheet" href="/css/watch.css">
</head>
<body>
    <div class="loading-screen">
        <div class="loading-logo"></div>
    </div>
    <?php include('navbar.php'); ?>
    
    <div class="video-container">
        <video id="player" playsinline controls data-poster="<?= htmlspecialchars($video_type === 'movie' ? $video['image'] : ($video['serie_image'] ?? '')) ?>">
            <source src="<?= htmlspecialchars($video_type === 'movie' ? $video['url_video'] : ($video['url_video'] ?? '')) ?>" type="video/mp4">
        </video>
    </div>
    
    <div class="video-info">
        <h1 class="video-title"><?= htmlspecialchars($video_type === 'movie' ? $video['titre'] : ($video['titre'] ?? 'Untitled')) ?></h1>
        <?php if ($video_type === 'episode'): ?>
            <div class="series-title">
                <i class="fas fa-tv"></i>
                <?= htmlspecialchars($video['serie_title'] ?? 'Unknown Series') ?> • S<?= $season_num ?> E<?= $video['numero_episode'] ?? 1 ?>
            </div>
        <?php endif; ?>
        
        <div class="video-meta">
            <!--<span class="meta-item match-percentage">
                <i class="fas fa-thumbs-up"></i> 95% Match
            </span>-->
            <span class="meta-item">
                <i class="far fa-calendar"></i> <?= isset($video['date_publication']) ? date('Y', strtotime($video['date_publication'])) : 'Unknown' ?>
            </span>
            <span class="meta-item">
                <i class="far fa-clock"></i>
                <?php if ($video_type === 'movie'): ?>
                    <?= floor(($video['duree'] ?? 0) / 60) ?>h <?= ($video['duree'] ?? 0) % 60 ?>m
                <?php else: ?>
                    <?= floor(($video['duree'] ?? 0) / 60) ?>m
                <?php endif; ?>
            </span>
            <span class="meta-item quality-badge">
                <i class="fas fa-high-definition"></i> HD
            </span>
            <span class="meta-item">
                <i class="fas fa-closed-captioning"></i> Subtitles
            </span>
        </div>
        
        <p class="video-description">
            <?= htmlspecialchars($video_type === 'movie' ? ($video['description'] ?? 'No description available') : ($video['description'] ?? 'No episode description')) ?>
        </p>
        
        <div class="action-buttons">
            <!--<button class="action-button">
                <i class="fas fa-plus"></i>
                <span>My List</span>
            </button>
            <button class="action-button">
                <i class="fas fa-share-alt"></i>
                <span>Share</span>
            </button>
            <button class="action-button">
                <i class="fas fa-heart"></i>
                <span>Rate</span>
            </button>-->
            <?php if ($video_type === 'episode'): ?>
                <a href="serie.php?serie=<?= $series_id ?>" class="action-button">
                    <i class="fas fa-tv"></i>
                    <span>View Series</span>
                </a>
                <?php if ($next_episode): ?>
                    <a href="watch.php?serie=<?= $series_id ?>&season=<?= $season_num ?>&episode=<?= $next_episode['id'] ?>" class="action-button play-button">
                        <i class="fas fa-play"></i>
                        <span>Next Episode</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($similar)): ?>
        <div class="similar-section">
            <h2 class="section-title">More Like This</h2>
            <div class="similar-row">
                <?php foreach ($similar as $similar_video): ?>
                    <div class="similar-card" onclick="location.href='<?= $video_type === 'movie' ? 'watch.php?movie=' . $similar_video['id'] : 'serie.php?serie=' . $similar_video['id'] ?>'">
                        <img src="<?= htmlspecialchars($similar_video['image'] ?? '') ?>" alt="<?= htmlspecialchars($similar_video['titre'] ?? '') ?>" class="similar-thumbnail">
                        <div class="similar-info">
                            <div class="similar-title"><?= htmlspecialchars($similar_video['titre'] ?? 'Untitled') ?></div>
                            <div class="similar-meta">
                                <span><?= isset($similar_video['date_publication']) ? date('Y', strtotime($similar_video['date_publication'])) : 'Unknown' ?></span>
                                <span class="quality-badge">HD</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
    <script>
        const player = new Plyr('#player', {
            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'],
            settings: ['captions', 'quality', 'speed'],
            keyboard: { focused: true, global: true },
            tooltips: { controls: true, seek: true },
            ratio: '16:9'
        });
        
        /* Customize player colors to match theme */
        document.documentElement.style.setProperty('--plyr-color-main', 'var(--secondary)');
        document.documentElement.style.setProperty('--plyr-video-control-color', 'var(--light)');
        document.documentElement.style.setProperty('--plyr-video-control-color-hover', 'var(--light)');
        document.documentElement.style.setProperty('--plyr-video-progress-buffered-background', 'rgba(255, 255, 255, 0.2)');
        document.documentElement.style.setProperty('--plyr-menu-background', 'var(--dark)');
        document.documentElement.style.setProperty('--plyr-menu-color', 'var(--light)');
        document.documentElement.style.setProperty('--plyr-menu-item-arrow-color', 'var(--gray)');
        document.documentElement.style.setProperty('--plyr-menu-shadow', '0 5px 20px rgba(0, 0, 0, 0.5)');
        
        /* Add hover effect to similar cards */
        document.querySelectorAll('.similar-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.querySelector('.similar-thumbnail').style.transform = 'scale(1.05)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.querySelector('.similar-thumbnail').style.transform = 'scale(1)';
            });
        });

        /* Loading screen */
window.addEventListener('load', function() {
    setTimeout(function() {
        document.querySelector('.loading-screen').style.opacity = '0';
        setTimeout(function() {
            document.querySelector('.loading-screen').style.display = 'none';
        }, 500);
    }, 1000);
});
    </script>
</body>
</html>











