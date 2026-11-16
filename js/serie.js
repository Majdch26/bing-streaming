/* Onglets des saisons */
document.querySelectorAll('.season-tab').forEach((onglet) => {
  onglet.addEventListener('click', () => {
    document.querySelectorAll('.season-tab').forEach((o) => o.classList.toggle('active', o === onglet));
    document.querySelectorAll('.season-content').forEach((c) => {
      c.hidden = c.id !== 'season-' + onglet.dataset.season;
    });
  });
});
