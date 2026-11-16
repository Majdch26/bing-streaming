/* Filtre par genre des pages Films et Séries (une vidéo peut avoir plusieurs genres) */
const filtre = document.getElementById('genreFilter');
if (filtre) {
  filtre.addEventListener('change', () => {
    const choix = filtre.value;
    document.querySelectorAll('[data-genres]').forEach((carte) => {
      const genres = carte.dataset.genres.split(', ');
      carte.style.display = choix === 'all' || genres.includes(choix) ? '' : 'none';
    });
  });
}
