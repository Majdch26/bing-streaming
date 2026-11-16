/* Afficher / masquer un mot de passe */
document.querySelectorAll('.toggle-password').forEach((icone) => {
  icone.setAttribute('role', 'button');
  icone.setAttribute('tabindex', '0');
  icone.setAttribute('aria-label', 'Show password');
  const basculer = () => {
    const champ = document.getElementById(icone.dataset.target);
    const visible = champ.type === 'password';
    champ.type = visible ? 'text' : 'password';
    icone.classList.toggle('fa-eye', !visible);
    icone.classList.toggle('fa-eye-slash', visible);
  };
  icone.addEventListener('click', basculer);
  icone.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); basculer(); } });
});

/* Mise en forme des champs de carte bancaire */
const carte = document.querySelector('input[name="card"]');
if (carte) {
  carte.addEventListener('input', (e) => {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 ');
  });
}
const expiration = document.querySelector('input[name="expiry"]');
if (expiration) {
  expiration.addEventListener('input', (e) => {
    let v = e.target.value.replace(/\D/g, '').slice(0, 4);
    if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
    e.target.value = v;
  });
}
