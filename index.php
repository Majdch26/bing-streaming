<?php
session_start();
include('db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['profile_id'])) {
    header("Location: login.php");
    exit();
}

// Get profile details
$stmt = $conn->prepare("SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?");
$stmt->execute([$_SESSION['profile_id'], $_SESSION['user_id']]);
$profile = $stmt->fetch();

if (!$profile) {
    header("Location: profiles.php");
    exit();
}

// Store profile avatar in session
$_SESSION['profile_avatar'] = $profile['avatar'];

// Get user's favorite videos
$stmt = $conn->prepare("
    SELECT v.*, 
           CASE WHEN f.id_video IS NOT NULL THEN 1 ELSE 0 END as is_movie
    FROM favoris f
    JOIN video v ON f.id_video = v.id
    LEFT JOIN film fl ON v.id = fl.id_video
    WHERE f.id_profil = ?
");
$stmt->execute([$_SESSION['profile_id']]);
$favorites = $stmt->fetchAll();

// Get popular videos
$stmt = $conn->query("
    SELECT v.*, 
           CASE WHEN f.id_video IS NOT NULL THEN 1 ELSE 0 END as is_movie
    FROM video v
    LEFT JOIN film f ON v.id = f.id_video
    ORDER BY date_publication DESC 
    LIMIT 10
");
$popular = $stmt->fetchAll();

// Get featured videos
$stmt = $conn->query("
    SELECT v.*, 
           CASE WHEN f.id_video IS NOT NULL THEN 1 ELSE 0 END as is_movie
    FROM video v
    LEFT JOIN film f ON v.id = f.id_video
    ORDER BY date_publication DESC 
    LIMIT 3
");
$featured = $stmt->fetchAll();

// Get Popular Series (Non-Movies)
$stmt = $conn->prepare("
    SELECT v.*, 
           CASE WHEN f.id_video IS NOT NULL THEN 1 ELSE 0 END as is_movie
    FROM video v
    LEFT JOIN film f ON v.id = f.id_video
    WHERE f.id_video IS NULL
    ORDER BY date_publication DESC 
    LIMIT 10
");
$stmt->execute();
$popularSeries = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BING - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/index.css">
</head>
<body>
    <div class="loading-screen">
        <div class="loading-logo"></div>
    </div>

    <?php include('navbar.php'); ?>

    <!-- Hero Carousel -->
    <!-- Hero Carousel -->
<div class="hero-carousel">
    <?php foreach ($featured as $index => $video): ?>
        <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
            <!-- Replace CSS background image with HTML img -->
            <img src="<?= htmlspecialchars($video['image']) ?>" class="hero-image" alt="<?= htmlspecialchars($video['titre']) ?>">
            <div class="gradient-overlay"></div>
            <div class="hero-content">
                <div class="content-wrapper">
                    <h1 class="hero-title"><?= htmlspecialchars($video['titre']) ?></h1>
                    <button class="watch-button" onclick="location.href='<?= $video['is_movie'] ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id'] ?>'">Watch Now</button>
                    <div class="age-rating">16+</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Keep arrows and dots -->
    <button class="carousel-arrow arrow-left" onclick="prevSlide()">‹</button>
    <button class="carousel-arrow arrow-right" onclick="nextSlide()">›</button>
    
    <div class="carousel-dots">
        <?php foreach ($featured as $index => $video): ?>
            <div class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></div>
        <?php endforeach; ?>
    </div>
</div>

    <!-- Content Sections -->
    <div class="content-section">
        <!-- Popular Section -->
        <div class="movie-section">
            <h2 class="section-title">Popular on BING</h2>
            <button class="section-arrow left" onclick="scrollSection(this.parentNode, -300)">‹</button>
            <div class="movies-container">
                <?php foreach ($popular as $video): ?>
                    <div class="movie-card" data-id="<?= $video['id'] ?>">
                        <div class="movie-poster" style="background-image: url('<?= htmlspecialchars($video['image']) ?>')"></div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?= htmlspecialchars($video['titre']) ?></h3>
                            <div class="movie-meta"><?= $video['categorie'] ?></div>
                        </div>
                        <div class="expanded-info">
                            <h3 class="expanded-title"><?= htmlspecialchars($video['titre']) ?></h3>
                            <div class="expanded-meta"><?= $video['categorie'] ?></div>
                            <div class="expanded-description">
                                <?= htmlspecialchars($video['description'] ?? 'No description available') ?>
                            </div>
                            <div class="expanded-actions">
                                <button class="expanded-watch-button" onclick="location.href='<?= $video['is_movie'] ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id'] ?>'">Watch Now</button>
                                <form method="post" action="toggle_favorite.php">
                                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                    <button type="submit" class="expanded-add-button <?= in_array($video['id'], array_column($favorites, 'id')) ? 'added' : '' ?>">
                                        <?= in_array($video['id'], array_column($favorites, 'id')) ? '✓' : '+' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="section-arrow right" onclick="scrollSection(this.parentNode, 300)">›</button>
        </div>


        <!-- Popular Series Section -->
<div class="movie-section">
    <h2 class="section-title">Popular Series</h2>
    <button class="section-arrow left" onclick="scrollSection(this.parentNode, -300)">‹</button>
    <div class="movies-container">
        <?php foreach ($popularSeries as $video): ?>
            <div class="movie-card" data-id="<?= $video['id'] ?>">
                <div class="movie-poster" style="background-image: url('<?= htmlspecialchars($video['image']) ?>')"></div>
                <div class="movie-info">
                    <h3 class="movie-title"><?= htmlspecialchars($video['titre']) ?></h3>
                    <div class="movie-meta"><?= $video['categorie'] ?></div>
                </div>
                <div class="expanded-info">
                    <h3 class="expanded-title"><?= htmlspecialchars($video['titre']) ?></h3>
                    <div class="expanded-meta"><?= $video['categorie'] ?></div>
                    <div class="expanded-description">
                        <?= htmlspecialchars($video['description'] ?? 'No description available') ?>
                    </div>
                    <div class="expanded-actions">
                        <button class="expanded-watch-button" onclick="location.href='<?= $video['is_movie'] ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id'] ?>'">
                            Watch Now
                        </button>
                        <form method="post" action="toggle_favorite.php">
                            <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                            <button type="submit" class="expanded-add-button <?= in_array($video['id'], array_column($favorites, 'id')) ? 'added' : '' ?>">
                                <?= in_array($video['id'], array_column($favorites, 'id')) ? '✓' : '+' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="section-arrow right" onclick="scrollSection(this.parentNode, 300)">›</button>
</div>

        <!-- Watchlist Popup -->
        <div class="watchlist-popup" id="watchlistPopup">
            <div class="watchlist-header">
                My Watchlist
                <button class="watchlist-close" onclick="toggleWatchlistPopup()">×</button>
            </div>
            <div class="watchlist-content">
                <?php if(empty($favorites)): ?>
                    <div class="watchlist-empty">
                        <div class="watchlist-empty-icon">🎬</div>
                        <p>Your watchlist is empty</p>
                    </div>
                <?php else: ?>
                    <?php foreach($favorites as $video): ?>
                        <div class="watchlist-item">
                            <div class="watchlist-item-poster" style="background-image: url('<?= htmlspecialchars($video['image']) ?>')"></div>
                            <div class="watchlist-item-details">
                                <div class="watchlist-item-title"><?= htmlspecialchars($video['titre']) ?></div>
                                <form method="post" action="remove_favorite.php">
                                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                    <button type="submit" class="watchlist-item-remove">×</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="watchlist-footer">
                <form method="post" action="clear_favorites.php">
                    <button type="submit" class="watchlist-clear">Clear All</button>
                </form>
            </div>
        </div>

        <!-- Watchlist Button -->
        <button class="watchlist-button" onclick="toggleWatchlistPopup()">
            <span class="watchlist-count"><?= count($favorites) ?></span>
            🎬
        </button>
    </div>

    <script src="/js/index.js"></script>
</body>
</html>