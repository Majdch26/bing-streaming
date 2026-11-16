/* Questions fréquentes en accordéon ; un lien help.php#ancre ouvre directement la bonne question */
document.querySelectorAll('.faq-question').forEach((question) => {
  question.addEventListener('click', () => {
    const ouverte = question.classList.contains('active');
    document.querySelectorAll('.faq-question').forEach((q) => {
      q.classList.remove('active');
      q.setAttribute('aria-expanded', 'false');
      q.nextElementSibling.classList.remove('show');
    });
    if (!ouverte) {
      question.classList.add('active');
      question.setAttribute('aria-expanded', 'true');
      question.nextElementSibling.classList.add('show');
    }
  });
});

if (window.location.hash) {
  const cible = document.querySelector(window.location.hash);
  if (cible && cible.classList.contains('faq-question')) {
    cible.click();
    setTimeout(() => cible.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
  }
}
