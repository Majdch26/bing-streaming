/* Comportements communs : menu, recherche, « Ma liste » */

(function () {
  'use strict';

  const base = (window.BING && window.BING.base) || '';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const url = (chemin) => base + '/' + chemin.replace(/^\//, '');

  /* Échappe un texte avant de l'insérer dans du HTML */
  function echapper(texte) {
    const div = document.createElement('div');
    div.textContent = texte == null ? '' : String(texte);
    return div.innerHTML;
  }
  window.echapper = echapper;

  /* ---------- Menu mobile et catégories ---------- */
  const mobileToggle = document.querySelector('.mobile-toggle');
  const headerMenu = document.querySelector('.header-menu');
  if (mobileToggle && headerMenu) {
    mobileToggle.addEventListener('click', function () {
      const ouvert = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', String(!ouvert));
      this.classList.toggle('active');
      headerMenu.classList.toggle('active');
    });
  }

  const categories = document.querySelector('.categories');
  if (categories) {
    let delai;
    categories.addEventListener('mouseenter', function () {
      if (window.innerWidth > 768) { clearTimeout(delai); this.classList.add('hover-active'); }
    });
    categories.addEventListener('mouseleave', function () {
      if (window.innerWidth > 768) { delai = setTimeout(() => this.classList.remove('hover-active'), 200); }
    });
    categories.addEventListener('click', function (e) {
      if (e.target.closest('a')) return;
      e.stopPropagation();
      this.classList.toggle(window.innerWidth <= 768 ? 'active' : 'hover-active');
    });
    document.addEventListener('click', (e) => {
      if (!categories.contains(e.target)) categories.classList.remove('active', 'hover-active');
    });
  }

  const profil = document.querySelector('.profile-container');
  if (profil) {
    profil.addEventListener('click', function (e) {
      if (e.target.closest('a, button')) return;
      e.stopPropagation();
      this.classList.toggle('active');
    });
    profil.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target === this) this.classList.toggle('active');
    });
    document.addEventListener('click', () => profil.classList.remove('active'));
  }

  /* ---------- Recherche ---------- */
  function brancherRecherche(champ, resultats) {
    if (!champ || !resultats) return;
    let delai;
    let derniere = '';

    champ.addEventListener('input', () => {
      const texte = champ.value.trim();
      clearTimeout(delai);
      if (texte.length < 2) { resultats.innerHTML = ''; resultats.style.display = 'none'; return; }
      delai = setTimeout(async () => {
        derniere = texte;
        try {
          const reponse = await fetch(url('api/search.php?q=' + encodeURIComponent(texte)));
          if (!reponse.ok) throw new Error(reponse.status);
          const liste = await reponse.json();
          if (texte !== derniere) return;
          afficher(liste);
        } catch (erreur) {
          resultats.innerHTML = '<div class="no-results">Search is unavailable. Please try again.</div>';
          resultats.style.display = 'block';
        }
      }, 250);
    });

    function afficher(liste) {
      if (!liste.length) {
        resultats.innerHTML = '<div class="no-results">No results found</div>';
      } else {
        resultats.innerHTML = liste.map((v) => `
          <a class="search-result-item" href="${url(v.type === 'film' ? 'watch.php?movie=' + v.id : 'serie.php?serie=' + v.id)}">
            <img src="${echapper(url(v.image))}" class="search-result-poster" alt="">
            <div class="search-result-info">
              <div class="search-result-title">${echapper(v.titre)}</div>
              <div class="search-result-year">${echapper(String(v.date_publication).slice(0, 4))} •
                <span class="search-result-type">${v.type === 'film' ? 'Movie' : 'TV Show'}</span></div>
            </div>
          </a>`).join('');
      }
      resultats.style.display = 'block';
    }

    document.addEventListener('click', (e) => {
      if (!champ.contains(e.target) && !resultats.contains(e.target)) resultats.style.display = 'none';
    });
    champ.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { resultats.style.display = 'none'; champ.blur(); }
    });
  }

  brancherRecherche(document.getElementById('searchInput'), document.getElementById('searchResults'));
  brancherRecherche(document.getElementById('mobileSearchInput'), document.getElementById('mobileSearchResults'));

  const overlay = document.getElementById('mobileSearchOverlay');
  document.getElementById('mobileSearchBtn')?.addEventListener('click', () => {
    overlay.style.display = 'flex';
    document.getElementById('mobileSearchInput').focus();
  });
  document.getElementById('mobileSearchClose')?.addEventListener('click', () => { overlay.style.display = 'none'; });

  /* ---------- « Ma liste » sans recharger la page ---------- */
  document.addEventListener('submit', async (e) => {
    const formulaire = e.target.closest('.favorite-form');
    if (!formulaire) return;
    e.preventDefault();
    const bouton = formulaire.querySelector('button');
    bouton.disabled = true;
    try {
      const reponse = await fetch(formulaire.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf },
        body: new FormData(formulaire),
      });
      const donnees = await reponse.json();
      if (!reponse.ok) throw new Error(donnees.error || 'Error');
      const id = formulaire.querySelector('[name="video_id"]').value;
      document.querySelectorAll(`.favorite-form input[name="video_id"][value="${id}"]`).forEach((champ) => {
        const b = champ.form.querySelector('button');
        b.classList.toggle('added', donnees.favorite);
        b.title = donnees.favorite ? 'Remove from My List' : 'Add to My List';
        b.setAttribute('aria-label', b.title);
        const icone = b.querySelector('i');
        if (icone) icone.className = 'fas ' + (donnees.favorite ? 'fa-check' : 'fa-plus');
        const texte = b.querySelector('.label');
        if (texte) texte.textContent = donnees.favorite ? 'In My List' : 'My List';
      });
      document.dispatchEvent(new CustomEvent('favoris:change', { detail: { id, favori: donnees.favorite, total: donnees.total } }));
    } catch (erreur) {
      formulaire.submit();
    } finally {
      bouton.disabled = false;
    }
  });

  window.BING = Object.assign(window.BING || {}, { url, csrf });
})();
