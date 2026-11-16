/* Couleur de l'icône du champ actif */
document.querySelectorAll('.form-group input').forEach((champ) => {
  const icone = champ.parentElement.querySelector('.input-icon');
  if (!icone) return;
  champ.addEventListener('focus', () => { icone.style.color = 'var(--primary)'; });
  champ.addEventListener('blur', () => { icone.style.color = 'var(--gray)'; });
});
