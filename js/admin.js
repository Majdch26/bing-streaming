/* Administration : onglets, fenêtres d'ajout et de modification, confirmations */

(function () {
  'use strict';

  /* Onglets, mémorisés dans l'adresse (#videos, #users...) */
  const onglets = document.querySelectorAll('.tab');
  function ouvrirOnglet(nom) {
    if (!document.getElementById(nom + '-tab')) nom = 'dashboard';
    onglets.forEach((o) => o.classList.toggle('active', o.dataset.tab === nom));
    document.querySelectorAll('.tab-content').forEach((c) => c.classList.toggle('active', c.id === nom + '-tab'));
  }
  onglets.forEach((o) => o.addEventListener('click', () => {
    history.replaceState(null, '', '#' + o.dataset.tab);
    ouvrirOnglet(o.dataset.tab);
  }));
  ouvrirOnglet(location.hash.slice(1) || 'dashboard');
  window.addEventListener('hashchange', () => ouvrirOnglet(location.hash.slice(1)));

  /* Fenêtres */
  function fermer() {
    document.querySelectorAll('.modal-overlay.active').forEach((m) => m.classList.remove('active'));
  }

  function afficherChampsSelonType(fenetre) {
    const type = fenetre.querySelector('[name="type"]');
    if (!type) return;
    fenetre.querySelectorAll('[data-type]').forEach((bloc) => {
      const visible = bloc.dataset.type === type.value;
      bloc.hidden = !visible;
      bloc.querySelectorAll('input').forEach((champ) => {
        if (champ.type !== 'checkbox') champ.required = visible;
      });
    });
  }

  function ouvrir(id, donnees) {
    const fenetre = document.getElementById(id);
    const formulaire = fenetre.querySelector('form');
    formulaire.reset();
    const titre = fenetre.querySelector('.modal-title');
    if (titre && titre.dataset.titleNew) titre.textContent = donnees ? titre.dataset.titleEdit : titre.dataset.titleNew;
    formulaire.querySelectorAll('[data-new-only]').forEach((champ) => { champ.disabled = !!donnees; });

    if (donnees) {
      Object.entries(donnees).forEach(([cle, valeur]) => {
        const champ = formulaire.elements[cle];
        if (!champ || champ.type === 'hidden' && cle !== 'id') return;
        if (champ.type === 'checkbox') champ.checked = Number(valeur) === 1;
        else champ.value = valeur == null ? '' : valeur;
      });
      const genres = String(donnees.ids_categories || '').split(',');
      formulaire.querySelectorAll('[name="categories[]"]').forEach((c) => { c.checked = genres.includes(c.value); });
    } else if (formulaire.elements.id) {
      formulaire.elements.id.value = 0;
    }
    afficherChampsSelonType(fenetre);
    fenetre.classList.add('active');
    formulaire.querySelector('input:not([type=hidden]), select, textarea')?.focus();
  }

  document.querySelectorAll('[data-open]').forEach((b) => b.addEventListener('click', () => ouvrir(b.dataset.open, null)));
  document.querySelectorAll('[data-edit]').forEach((b) => b.addEventListener('click', () => ouvrir(b.dataset.edit, JSON.parse(b.dataset.json))));
  document.querySelectorAll('.close-modal').forEach((b) => b.addEventListener('click', fermer));
  document.querySelectorAll('.modal-overlay').forEach((m) => m.addEventListener('click', (e) => { if (e.target === m) fermer(); }));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fermer(); });
  document.querySelectorAll('[name="type"]').forEach((s) => s.addEventListener('change', () => afficherChampsSelonType(s.closest('.modal-overlay'))));

  /* Confirmation avant suppression */
  document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
    if (!window.confirm(f.dataset.confirm)) e.preventDefault();
  }));

  /* Filtre de la table des utilisateurs */
  document.querySelectorAll('[data-filter]').forEach((champ) => {
    const lignes = document.querySelectorAll('#' + champ.dataset.filter + ' tbody tr');
    champ.addEventListener('input', () => {
      const texte = champ.value.trim().toLowerCase();
      lignes.forEach((l) => { l.hidden = texte !== '' && !l.textContent.toLowerCase().includes(texte); });
    });
  });
})();
