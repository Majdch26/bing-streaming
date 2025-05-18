<?php
/* Start session if not already started */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Database connection */
include('db.php');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="/css/navbar.css">
</head>
<body>
<div class="header">
  <div class="header-left">
    <button class="mobile-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span class="toggle-line"></span>
      <span class="toggle-line"></span>
      <span class="toggle-line"></span>
    </button>
    <a href="home.php" ><img class="logo" src="/images/logo8.png" alt="BING Logo"></a>
    <ul class="header-menu">
      <li id="menu" class="main-page" onclick="location.href='index.php'">HOME</li>
      <li id="menu" class="tv-series" onclick="location.href='series.php'">TV SERIES</li>
      <li id="menu" class="movies" onclick="location.href='movies.php'">MOVIES</li>
      <li id="menu" class="categories">CATEGORIES
        <div class="categories-popup">
          <div class="categories-left-panel">
            <p class="popup-title">Top Categories</p>
            <ul class="best-categories-content">
              <li onclick="location.href='category.php?cat=originals'">
                <div class="category-box" style="background-image: url('https://m.media-amazon.com/images/M/MV5BZjBlMjIxYzgtOGYyOC00NzY3LTg1ZWMtM2U0NzE0OTQ2YjRlXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg')"></div>
                <span>BING Originals</span>
              </li>
              <li class="child-category" onclick="location.href='category.php?cat=kids'">
                <div class="category-box" style="background-image: url('https://m.media-amazon.com/images/M/MV5BZjBlMjIxYzgtOGYyOC00NzY3LTg1ZWMtM2U0NzE0OTQ2YjRlXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg')"></div>
                <span>Kids</span>
              </li>
              <li onclick="location.href='category.php?cat=new-movies'">
                <div class="category-box" style="background-image: url('https://m.media-amazon.com/images/M/MV5BZjBlMjIxYzgtOGYyOC00NzY3LTg1ZWMtM2U0NzE0OTQ2YjRlXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg')"></div>
                <span>New Movies</span>
              </li>
              <li onclick="location.href='category.php?cat=new-tv'">
                <div class="category-box" style="background-image: url('https://m.media-amazon.com/images/M/MV5BZjBlMjIxYzgtOGYyOC00NzY3LTg1ZWMtM2U0NzE0OTQ2YjRlXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg')"></div>
                <span>New TV Shows</span>
              </li>
            </ul>
          </div>
          <div class="categories-right-panel">
            <div class="genres">
              <p class="popup-title">Genres</p>
              <ul class="genres-content">
                <li onclick="location.href='genre.php?genre=action'">Action & Adventure</li>
                <li onclick="location.href='genre.php?genre=comedy'">Comedy</li>
                <li onclick="location.href='genre.php?genre=drama'">Drama</li>
                <li onclick="location.href='genre.php?genre=documentary'">Documentary</li>
                <li onclick="location.href='genre.php?genre=family'">Kids & Family</li>
                <li onclick="location.href='genre.php?genre=fantasy'">Fantasy</li>
                <li onclick="location.href='genre.php?genre=horror'">Horror</li>
                <li onclick="location.href='genre.php?genre=romance'">Romance</li>
                <li onclick="location.href='genre.php?genre=sci-fi'">Sci-Fi</li>
                <li onclick="location.href='genre.php?genre=thriller'">Thriller</li>
                <li onclick="location.href='genre.php?genre=anime'">Anime</li>
                <li onclick="location.href='genre.php?genre=sports'">Sports</li>
                <li onclick="location.href='genre.php?genre=crime'">Crime</li>
              </ul>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>
  
  <div class="header-right">
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Search Container -->
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search movies, TV shows..." id="searchInput" autocomplete="off">
        <div class="search-results" id="searchResults"></div>
      </div>
      
      <!-- Mobile Search Button -->
      <button class="mobile-search-btn" id="mobileSearchBtn">
        <i class="fas fa-search"></i>
      </button>
      
      <div class="profile-container">
        <?php if (isset($_SESSION['profile_id'])): ?>
          <span class="profile-name"><?= htmlspecialchars($_SESSION['profile_name'] ?? 'Profile') ?></span>
          <img class="profile-picture" src="images/avatars/<?= htmlspecialchars($_SESSION['profile_avatar'] ?? 'default.png') ?>" alt="Profile">
        <?php else: ?>
          <i class="fas fa-user-circle profile-icon" style="font-size: 40px; color: #f4c430;"></i>
        <?php endif; ?>
        <ul class="profile-menu">
          <!--<li onclick="location.href='home.php'"><i class="fas fa-home"></i>  Home</li>-->
          <li onclick="location.href='account.php'"><i class="fas fa-user-cog"></i>  Account Settings</li>
          <!--<li onclick="location.href='watchlist.php'"><i class="fas fa-bookmark"></i>  My Watchlist</li>-->
          <li onclick="location.href='help.php'"><i class="fas fa-question-circle"></i>  Help</li>
          <li onclick="location.href='profiles.php'"><i class="fas fa-users"></i>  Switch Profile</li>
          <li onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i>  Sign Out</li>
        </ul>
      </div>
    <?php else: ?>
      <a href="login.php" class="login-button" style="background: #f4c430; color: #000; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: 600;">Sign In</a>
    <?php endif; ?>
  </div>
</div>

<!-- Mobile Search Overlay -->
<div class="mobile-search-overlay" id="mobileSearchOverlay">
  <div class="mobile-search-header">
    <h3 style="color: white;">Search</h3>
    <button class="mobile-search-close" id="mobileSearchClose">&times;</button>
  </div>
  <div style="position: relative;">
    <i class="fas fa-search mobile-search-icon"></i>
    <input type="text" class="mobile-search-input" placeholder="Search movies, TV shows..." id="mobileSearchInput" autocomplete="off">
  </div>
  <div class="mobile-search-results" id="mobileSearchResults"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="/js/navbar.js"></script>
</body>
</html>