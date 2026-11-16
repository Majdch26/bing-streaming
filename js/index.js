/* Page d'accueil : carrousel, défilement des rangées, fenêtre « Ma liste » */

(function () {
  'use strict';

  /* Carrousel */
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.carousel-dots .dot');
  let courant = 0;
  let minuteur;

  function afficher(n) {
    if (!slides.length) return;
    slides[courant].classList.remove('active');
    dots[courant]?.classList.remove('active');
    courant = (n + slides.length) % slides.length;
    slides[courant].classList.add('active');
    dots[courant]?.classList.add('active');
    relancer();
  }
  function relancer() {
    clearInterval(minuteur);
    if (slides.length > 1) minuteur = setInterval(() => afficher(courant + 1), 8000);
  }
  document.querySelector('.arrow-left')?.addEventListener('click', () => afficher(courant - 1));
  document.querySelector('.arrow-right')?.addEventListener('click', () => afficher(courant + 1));
  dots.forEach((dot) => dot.addEventListener('click', () => afficher(Number(dot.dataset.slide))));
  relancer();

  /* Flèches des rangées */
  document.querySelectorAll('.movie-section').forEach((section) => {
    const rangee = section.querySelector('.movies-container');
    if (!rangee) return;
    section.querySelector('.section-arrow.left')?.addEventListener('click', () => rangee.scrollBy({ left: -rangee.clientWidth * 0.8, behavior: 'smooth' }));
    section.querySelector('.section-arrow.right')?.addEventListener('click', () => rangee.scrollBy({ left: rangee.clientWidth * 0.8, behavior: 'smooth' }));
  });

  /* Fenêtre « Ma liste » */
  const fenetre = document.getElementById('watchlistPopup');
  document.querySelector('.watchlist-button')?.addEventListener('click', () => fenetre.classList.toggle('active'));
  document.querySelector('.watchlist-close')?.addEventListener('click', () => fenetre.classList.remove('active'));

  /* Compteur mis à jour quand on ajoute ou retire un favori */
  document.addEventListener('favoris:change', (e) => {
    const compteur = document.querySelector('.watchlist-count');
    if (compteur && typeof e.detail.total === 'number') compteur.textContent = e.detail.total;
  });
})();
