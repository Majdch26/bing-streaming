/* Lecteur vidéo : reprise de la lecture, sauvegarde de la progression, épisode suivant */

(function () {
  'use strict';

  const video = document.getElementById('player');
  if (!video) return;

  const player = new Plyr(video, {
    controls: ['play-large', 'play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'captions', 'settings', 'pip', 'fullscreen'],
    settings: ['captions', 'speed'],
    captions: { active: false, update: true },
    keyboard: { focused: true, global: true },
    tooltips: { controls: true, seek: true },
    iconUrl: window.BING.url('vendor/plyr/plyr.svg'),
    blankVideo: '',
    ratio: '16:9',
  });

  const contenu = video.dataset.movie ? { movie: video.dataset.movie } : { episode: video.dataset.episode };
  const reprise = Number(video.dataset.resume) || 0;
  let derniereSauvegarde = -1;

  /* Reprendre là où le profil s'était arrêté */
  player.once('loadedmetadata', () => {
    if (reprise > 0 && reprise < player.duration - 5) player.currentTime = reprise;
  });

  function donnees() {
    const formulaire = new FormData();
    formulaire.append('csrf', window.BING.csrf);
    Object.entries(contenu).forEach(([cle, valeur]) => formulaire.append(cle, valeur));
    formulaire.append('position', Math.floor(player.currentTime));
    formulaire.append('duration', Math.floor(player.duration || 0));
    return formulaire;
  }

  function sauvegarder(finale) {
    if (!player.duration) return;
    const position = Math.floor(player.currentTime);
    if (position === derniereSauvegarde) return;
    derniereSauvegarde = position;
    const adresse = window.BING.url('api/progress.php');
    if (finale && navigator.sendBeacon) {
      navigator.sendBeacon(adresse, donnees());
    } else {
      fetch(adresse, { method: 'POST', body: donnees(), keepalive: true }).catch(() => {});
    }
  }

  setInterval(() => { if (player.playing) sauvegarder(false); }, 10000);
  player.on('pause', () => sauvegarder(false));
  player.on('seeked', () => sauvegarder(false));
  window.addEventListener('pagehide', () => sauvegarder(true));

  /* Fin de l'épisode : compte à rebours vers le suivant */
  const suivant = video.dataset.next;
  const overlay = document.getElementById('nextOverlay');
  let compteARebours;
  player.on('ended', () => {
    sauvegarder(false);
    if (!suivant || !overlay) return;
    let restant = 8;
    overlay.hidden = false;
    const affichage = document.getElementById('nextCountdown');
    compteARebours = setInterval(() => {
      restant -= 1;
      affichage.textContent = restant;
      if (restant <= 0) { clearInterval(compteARebours); window.location.href = suivant; }
    }, 1000);
  });
  document.getElementById('nextCancel')?.addEventListener('click', () => {
    clearInterval(compteARebours);
    overlay.hidden = true;
  });

  /* Message si la vidéo ne se charge pas */
  video.querySelector('source')?.addEventListener('error', () => {
    document.getElementById('videoError').hidden = false;
  });
})();
