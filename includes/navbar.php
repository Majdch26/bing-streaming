<?php
/* Barre de navigation. Variables attendues : $profil (profil en cours) et $page (page active) */

$page = $page ?? '';
$genres = requete('SELECT c.nom, c.slug FROM categorie c
                   WHERE EXISTS (SELECT 1 FROM video_categorie vc WHERE vc.id_categorie = c.id)
                   ORDER BY c.nom')->fetchAll();
$raccourcis = [
    ['Movies', 'movies.php', 'images/posters/inception.jpg'],
    ['TV Series', 'series.php', 'images/posters/strangerthings.jpg'],
    ['New releases', 'genre.php?g=new', 'images/posters/thelastofus.jpg'],
    ['My List', 'index.php#my-list', 'images/posters/pokerface.jpg'],
];
?>
<header class="header">
  <div class="header-left">
    <button class="mobile-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span class="toggle-line"></span>
      <span class="toggle-line"></span>
      <span class="toggle-line"></span>
    </button>
    <a href="<?= url('index.php') ?>"><img class="logo" src="<?= url('images/logo.png') ?>" alt="BING"></a>
    <ul class="header-menu">
      <li class="main-page<?= $page === 'home' ? ' current' : '' ?>"><a href="<?= url('index.php') ?>">HOME</a></li>
      <li class="tv-series<?= $page === 'series' ? ' current' : '' ?>"><a href="<?= url('series.php') ?>">TV SERIES</a></li>
      <li class="movies<?= $page === 'movies' ? ' current' : '' ?>"><a href="<?= url('movies.php') ?>">MOVIES</a></li>
      <li class="categories<?= $page === 'genre' ? ' current' : '' ?>" tabindex="0">CATEGORIES
        <div class="categories-popup">
          <div class="categories-left-panel">
            <p class="popup-title">Browse</p>
            <ul class="best-categories-content">
              <?php foreach ($raccourcis as [$nom, $lien, $image]): ?>
              <li><a href="<?= url($lien) ?>">
                <div class="category-box" style="background-image: url('<?= url($image) ?>')"></div>
                <span><?= e($nom) ?></span>
              </a></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="categories-right-panel">
            <div class="genres">
              <p class="popup-title">Genres</p>
              <ul class="genres-content">
                <?php foreach ($genres as $genre): ?>
                <li><a href="<?= url('genre.php?g=' . urlencode($genre['slug'])) ?>"><?= e($genre['nom']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </div>

  <div class="header-right">
    <div class="search-container">
      <i class="fas fa-search search-icon"></i>
      <input type="search" class="search-input" placeholder="Search movies, TV shows..." id="searchInput" autocomplete="off" aria-label="Search">
      <div class="search-results" id="searchResults"></div>
    </div>
    <button class="mobile-search-btn" id="mobileSearchBtn" aria-label="Search">
      <i class="fas fa-search"></i>
    </button>

    <div class="profile-container" tabindex="0">
      <span class="profile-name"><?= e($profil['nom']) ?></span>
      <img class="profile-picture" src="<?= url('images/avatars/' . $profil['avatar']) ?>" alt="">
      <ul class="profile-menu">
        <li><a href="<?= url('account.php') ?>"><i class="fas fa-user-cog"></i> Account</a></li>
        <li><a href="<?= url('help.php') ?>"><i class="fas fa-question-circle"></i> Help</a></li>
        <li><a href="<?= url('profiles.php') ?>"><i class="fas fa-users"></i> Switch profile</a></li>
        <?php if (utilisateur_courant()['est_admin']): ?>
        <li><a href="<?= url('admin/') ?>"><i class="fas fa-chart-line"></i> Admin</a></li>
        <?php endif; ?>
        <li>
          <form method="post" action="<?= url('logout.php') ?>"><?= champ_csrf() ?>
            <button type="submit"><i class="fas fa-sign-out-alt"></i> Sign out</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</header>

<div class="mobile-search-overlay" id="mobileSearchOverlay">
  <div class="mobile-search-header">
    <h3>Search</h3>
    <button class="mobile-search-close" id="mobileSearchClose" aria-label="Close">&times;</button>
  </div>
  <div class="mobile-search-field">
    <i class="fas fa-search mobile-search-icon"></i>
    <input type="search" class="mobile-search-input" placeholder="Search movies, TV shows..." id="mobileSearchInput" autocomplete="off">
  </div>
  <div class="mobile-search-results" id="mobileSearchResults"></div>
</div>
