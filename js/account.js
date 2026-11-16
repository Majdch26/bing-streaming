/* Fenêtre de choix d'une offre */
const fenetre = document.getElementById('planModal');

function ouvrir() {
  fenetre.classList.add('active');
  document.body.style.overflow = 'hidden';
}
function fermer() {
  fenetre.classList.remove('active');
  document.body.style.overflow = '';
}

document.getElementById('changePlanBtn')?.addEventListener('click', ouvrir);
fenetre.querySelector('.close-modal')?.addEventListener('click', fermer);
fenetre.addEventListener('click', (e) => { if (e.target === fenetre) fermer(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fermer(); });

document.querySelectorAll('.plan-option').forEach((option) => {
  option.addEventListener('click', () => {
    document.querySelectorAll('.plan-option').forEach((o) => o.classList.remove('selected'));
    option.classList.add('selected');
    option.querySelector('input').checked = true;
  });
});

if (fenetre.classList.contains('active')) document.body.style.overflow = 'hidden';

/* Confirmation avant suppression du compte */
document.querySelector('.delete-account-form')?.addEventListener('submit', (e) => {
  if (!window.confirm('Delete your account permanently? This cannot be undone.')) e.preventDefault();
});
