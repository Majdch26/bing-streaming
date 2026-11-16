"""
Tests de bout en bout : le site tourne sur le serveur intégré de PHP avec une base
temporaire, et un navigateur (Playwright) parcourt les pages comme un utilisateur.

Installation : pip install playwright && playwright install chromium
Lancement    : python3 tests/test_site.py            (ajouter --captures pour enregistrer des captures d'écran)
"""

import os
import re
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request
from pathlib import Path

from playwright.sync_api import sync_playwright, expect

RACINE = Path(__file__).resolve().parent.parent
CAPTURES = "--captures" in sys.argv
DOSSIER_CAPTURES = RACINE / "docs" / "captures"

reussis, echecs = 0, []


def verifier(nom, condition):
    global reussis
    if condition:
        reussis += 1
        print(f"  ok  {nom}")
    else:
        echecs.append(nom)
        print(f"  ÉCHEC  {nom}")


def port_libre():
    with socket.socket() as s:
        s.bind(("127.0.0.1", 0))
        return s.getsockname()[1]


def statut(url, methode="GET", donnees=None):
    """Code HTTP sans suivre les redirections"""
    class SansRedirection(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *args, **kwargs):
            return None
    ouvreur = urllib.request.build_opener(SansRedirection)
    requete = urllib.request.Request(url, method=methode, data=donnees)
    try:
        return ouvreur.open(requete).status
    except urllib.error.HTTPError as e:
        return e.code


def capture(page, nom, pleine_page=False):
    if CAPTURES:
        DOSSIER_CAPTURES.mkdir(parents=True, exist_ok=True)
        page.wait_for_timeout(400)
        chemin = DOSSIER_CAPTURES / f"{nom}.png"
        page.screenshot(path=str(chemin), full_page=pleine_page)
        try:   # JPEG, bien plus léger pour le README
            from PIL import Image
            Image.open(chemin).convert("RGB").save(chemin.with_suffix(".jpg"), quality=82, optimize=True)
            chemin.unlink()
        except ImportError:
            pass


def connexion(page, base, email, mot_de_passe):
    page.goto(base + "/login.php")
    page.fill("#email", email)
    page.fill("#password", mot_de_passe)
    page.click("button[type=submit]")
    page.wait_for_load_state()


def choisir_profil(page, base, nom):
    page.goto(base + "/profiles.php")
    page.locator(".profile-item", has_text=nom).locator("button[name=select_profile]").click()
    page.wait_for_load_state()


def main():
    base_sqlite = Path(tempfile.mkdtemp()) / "test.sqlite"
    port = port_libre()
    base = f"http://127.0.0.1:{port}"
    serveur = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{port}", "router.php"], cwd=RACINE,
        env={**os.environ, "BING_DB": str(base_sqlite)},
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
    )
    time.sleep(0.8)

    try:
        with sync_playwright() as p:
            chrome = os.environ.get("CHROME")
            navigateur = p.chromium.launch(executable_path=chrome) if chrome else p.chromium.launch()
            contexte = navigateur.new_context(viewport={"width": 1366, "height": 850})
            page = contexte.new_page()
            erreurs_js = []
            page.on("pageerror", lambda e: erreurs_js.append(str(e)))
            dialogues = []
            page.on("dialog", lambda d: (dialogues.append(d.message), d.accept()))

            print("Accès sans être connecté")
            for chemin in ["/", "/index.php", "/movies.php", "/watch.php?movie=1", "/account.php", "/admin/"]:
                verifier(f"{chemin} redirige vers la connexion", statut(base + chemin) == 302)
            verifier("api/search.php refuse sans connexion", statut(base + "/api/search.php?q=in") == 401)
            verifier("api/favorite.php refuse le GET", statut(base + "/api/favorite.php") == 405)
            for chemin in ["/data/test.sqlite", "/database/schema.sql", "/includes/bootstrap.php", "/tests/test_site.py"]:
                verifier(f"{chemin} n'est pas accessible", statut(base + chemin) == 404)
            verifier("POST sans jeton CSRF refusé", statut(base + "/login.php", "POST", b"email=a&password=b") == 403)

            print("Inscription et paiement")
            page.goto(base + "/pricing.php")
            capture(page, "offres")
            verifier("la page des offres affiche les 3 offres de la base", page.locator(".plan-card").count() == 3)
            page.locator(".plan-card", has_text="Basic").click()
            page.fill("#name", "Alice Test")
            page.fill("#email", "demo@bing.test")
            page.fill("#password", "motdepasse1")
            page.fill("#confirmation", "motdepasse2")
            page.click("button[type=submit]")
            texte = page.locator(".error-message").all_inner_texts()
            verifier("email déjà utilisé signalé", any("already exists" in t for t in texte))
            verifier("mots de passe différents signalés", any("do not match" in t for t in texte))
            page.fill("#email", "alice@bing.test")
            page.fill("#password", "motdepasse1")
            page.fill("#confirmation", "motdepasse1")
            page.click("button[type=submit]")
            verifier("étape de paiement", page.url.endswith("/payment.php"))
            verifier("le mot de passe n'est pas renvoyé dans la page de paiement", "motdepasse1" not in page.content())
            page.fill("#card", "4242 4242 4242 4241")
            page.fill("#expiry", "12/40")
            page.fill("#cvv", "123")
            page.click("button[type=submit]")
            verifier("numéro de carte invalide refusé (Luhn)", "not valid" in page.locator(".error-message").inner_text())
            page.fill("#card", "4242 4242 4242 4242")
            page.fill("#expiry", "01/20")
            page.fill("#cvv", "123")
            page.click("button[type=submit]")
            verifier("carte expirée refusée", "expiry" in page.locator(".error-message").inner_text())
            page.fill("#card", "4242 4242 4242 4242")
            page.fill("#expiry", "12/40")
            page.fill("#cvv", "123")
            page.click("button[type=submit]")
            verifier("paiement accepté : page de bienvenue", "Account Created" in page.content())
            capture(page, "bienvenue")
            page.click(".continue-button")
            verifier("premier profil : le formulaire s'ouvre directement", page.locator("#addProfileForm.active").count() == 1)

            print("Profils")
            page.fill("#profile_name", "<script>alert(1)</script>")
            page.click("button[name=create_profile]")
            verifier("le nom de profil est affiché sans être exécuté", "&lt;script&gt;" in page.content() and not dialogues)
            verifier("offre Basic : plus de bouton « Add Profile »", page.locator(".add-profile").count() == 0)
            verifier("quota de profils affiché", "1 of 1 profile used" in page.content())

            print("Navigation dans le catalogue")
            choisir_profil(page, base, "<script>")
            verifier("le choix du profil mène à l'accueil", page.url.endswith("/index.php"))
            verifier("l'accueil affiche le carrousel et les rangées", page.locator(".hero-slide").count() >= 3 and page.locator(".movie-section").count() >= 4)
            verifier("aucune rangée « Continue Watching » pour un nouveau profil", page.locator("#continue").count() == 0)
            compteur = page.locator(".watchlist-count")
            avant = int(compteur.inner_text())
            page.locator("#movies .movie-card").first.hover()
            page.locator("#movies .movie-card").first.locator(".expanded-add-button").click()
            expect(compteur).to_have_text(str(avant + 1))
            verifier("« Ma liste » se met à jour sans recharger la page", int(compteur.inner_text()) == avant + 1)
            page.reload()
            verifier("le favori est enregistré", page.locator("#my-list .movie-card").count() == 1)

            reponse = page.request.get(base + "/api/search.php?q=squid")
            verifier("recherche : Squid Game trouvé", any(v["titre"] == "Squid Game" for v in reponse.json()))
            reponse = page.request.get(base + "/api/search.php?q=" + "%25")
            verifier("recherche : « % » ne renvoie pas tout le catalogue", reponse.json() == [])
            page.fill("#searchInput", "inter")
            page.wait_for_selector("#searchResults .search-result-item")
            verifier("la recherche instantanée affiche Interstellar", "Interstellar" in page.locator("#searchResults").inner_text())

            page.goto(base + "/movies.php")
            total = page.locator(".movie-card").count()
            page.select_option("#genreFilter", "Horror")
            visibles = page.locator(".movie-card:visible").count()
            verifier("le filtre par genre fonctionne (plusieurs genres par titre)", 0 < visibles < total)
            page.goto(base + "/genre.php?g=sci-fi")
            verifier("page d'un genre", page.locator(".movie-card").count() >= 2)

            print("Lecture et reprise")
            page.goto(base + "/serie.php?serie=4")
            verifier("page de la série : 2 saisons", page.locator(".season-tab").count() == 2)
            page.click(".season-tab[data-season='2']")
            verifier("changement de saison", page.locator("#season-2").is_visible() and not page.locator("#season-1").is_visible())
            page.goto(base + "/watch.php?episode=2")
            verifier("le lecteur Plyr est chargé", page.locator(".plyr").count() == 1)
            page.goto(base + "/watch.php?episode=1")
            verifier("pistes de sous-titres anglais et français", sorted(page.locator("video track").evaluate_all("l => l.map(t => t.srclang)")) == ["en", "fr"])
            page.goto(base + "/watch.php?episode=2")
            verifier("bouton « épisode suivant »", page.locator("a.play-button", has_text="Next Episode").count() == 1)
            jeton = page.locator('meta[name="csrf-token"]').get_attribute("content")
            reponse = page.request.post(base + "/api/progress.php", form={"csrf": jeton, "episode": "2", "position": "1200", "duration": "3300"})
            verifier("la position de lecture est enregistrée", reponse.ok and reponse.json()["saved"])
            reponse = page.request.post(base + "/api/progress.php", form={"episode": "2", "position": "10", "duration": "3300"})
            verifier("enregistrement refusé sans jeton CSRF", reponse.status == 403)
            page.goto(base + "/watch.php?episode=2")
            verifier("la page annonce la reprise à 20:00", "Resuming at 20:00" in page.content())
            page.goto(base + "/index.php")
            verifier("rangée « Continue Watching » avec la série", "Stranger Things" in page.locator("#continue").inner_text())
            page.goto(base + "/serie.php?serie=4")
            verifier("le bouton principal reprend l'épisode en cours", "Resume S1 E2" in page.locator(".play-button").inner_text())
            page.goto(base + "/watch.php?movie=1")

            print("Changement d'offre")
            page.goto(base + "/account.php")
            page.click("#changePlanBtn")
            page.locator(".plan-option", has_text="Premium").click()
            page.click("#planModal button[type=submit]")
            verifier("passage à Premium : paiement de la différence", page.url.endswith("/payment.php") and "Amount due today" in page.content())
            montant = float(re.search(r"Amount due today: <strong>\$([\d.]+)", page.content()).group(1))
            verifier("montant au prorata (au plus 17.99 - 7.99)", 0 < montant <= 10.0)
            page.fill("#card", "4242424242424242")
            page.fill("#expiry", "12/40")
            page.fill("#cvv", "123")
            page.click("button[type=submit]")
            verifier("passage à Premium confirmé", "Premium" in page.content())
            page.goto(base + "/profiles.php")
            verifier("Premium : 4 profils autorisés", "1 of 4 profiles used" in page.content())
            page.click(".add-profile")
            page.fill("#profile_name", "Enfant")
            page.fill("#profile_age", "12")
            page.click("button[name=create_profile]")
            verifier("profil enfant créé avec le badge Kids", page.locator(".profile-kids").count() == 1)

            print("Contrôle d'âge")
            choisir_profil(page, base, "Enfant")
            page.goto(base + "/watch.php?episode=16")
            verifier("un profil de 12 ans ne peut pas ouvrir Squid Game (16+)", page.url.endswith("/index.php"))
            reponse = page.request.get(base + "/api/search.php?q=squid")
            verifier("Squid Game n'apparaît pas dans sa recherche", reponse.json() == [])
            page.goto(base + "/series.php")
            verifier("seules les séries 12+ sont listées", sorted(page.locator(".series-title").all_inner_texts()) == ["Poker Face", "Stranger Things"])

            print("Passage à une offre moins chère")
            page.goto(base + "/account.php")
            page.click("#changePlanBtn")
            page.locator(".plan-option", has_text="Basic").click()
            page.click("#planModal button[type=submit]")
            verifier("confirmation : liste du profil qui sera supprimé", page.locator(".profiles-to-delete li").count() == 1)
            verifier("le profil en cours est gardé", "Enfant" not in page.locator(".profiles-to-delete").inner_text())
            page.click(".confirm-btn")
            verifier("passage à Basic sans paiement", "changed to Basic" in page.content())
            page.goto(base + "/profiles.php")
            verifier("il ne reste qu'un profil", page.locator(".profile-item").count() == 1)

            print("Mot de passe et sécurité du compte")
            page.goto(base + "/account.php")
            page.fill("#current_password", "mauvais")
            page.fill("#new_password", "nouveaumdp1")
            page.fill("#confirm_password", "nouveaumdp1")
            page.click("#change-password button[type=submit]")
            verifier("mauvais mot de passe actuel refusé", "incorrect" in page.content())
            page.fill("#current_password", "motdepasse1")
            page.fill("#new_password", "nouveaumdp1")
            page.fill("#confirm_password", "nouveaumdp1")
            page.click("#change-password button[type=submit]")
            verifier("mot de passe changé", "changed successfully" in page.content())

            page.goto(base + "/logout.php")
            verifier("la déconnexion par un simple lien (GET) est ignorée", statut(base + "/logout.php") == 302 and page.request.get(base + "/account.php").url.endswith("/account.php"))
            autre = page.request.post(base + "/profiles.php", form={"csrf": page.locator('meta[name="csrf-token"]').get_attribute("content"),
                                                                      "select_profile": "1", "profile_id": "2"})
            page.goto(base + "/index.php")
            verifier("impossible de choisir le profil d'un autre compte", "Majd" not in page.locator(".profile-name").first.inner_text())

            page.locator(".profile-container").click()
            page.locator(".profile-menu button", has_text="Sign out").click()
            verifier("déconnexion", page.url.endswith("/login.php"))
            connexion(page, base, "alice@bing.test", "motdepasse1")
            verifier("l'ancien mot de passe ne fonctionne plus", "Invalid email or password" in page.content())
            connexion(page, base, "alice@bing.test", "nouveaumdp1")
            verifier("le nouveau mot de passe fonctionne", page.url.endswith("/profiles.php"))
            verifier("un abonné n'accède pas à l'administration", page.request.get(base + "/admin/").status == 403)

            print("Administration")
            contexte_admin = navigateur.new_context(viewport={"width": 1366, "height": 900})
            admin = contexte_admin.new_page()
            admin.on("dialog", lambda d: d.accept())
            connexion(admin, base, "admin@bing.test", "Admin1234!")
            verifier("l'administrateur arrive sur le tableau de bord", admin.url.endswith("/admin/"))
            verifier("indicateurs affichés", admin.locator(".stat-card").count() == 6)
            capture(admin, "admin-tableau-de-bord")
            admin.click(".tab[data-tab='videos']")
            admin.click("[data-open='videoModal']")
            admin.fill("#videoModal [name=titre]", "Big Buck Bunny")
            admin.fill("#videoModal [name=date_publication]", "2008-05-20")
            admin.fill("#videoModal [name=langue_originale]", "English")
            admin.fill("#videoModal [name=image]", "images/posters/inception.jpg")
            admin.fill("#videoModal [name=duree]", "10")
            admin.fill("#videoModal [name=url_video]", "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4")
            admin.fill("#videoModal [name=description]", "A giant rabbit takes revenge on three bullies.")
            admin.locator("#videoModal label", has_text="Comedy").click()
            admin.click("#videoModal button[type=submit]")
            verifier("ajout d'un film", "Title saved" in admin.content() and "Big Buck Bunny" in admin.locator("#videos-tab").inner_text())
            admin.locator("#videos-tab tr", has_text="Big Buck Bunny").locator(".edit-button").click()
            verifier("le formulaire de modification est pré-rempli", admin.input_value("#videoModal [name=duree]") == "10")
            admin.locator("#videoModal .close-modal").click()
            admin.locator("#videos-tab tr", has_text="Big Buck Bunny").locator(".delete-button").click()
            verifier("suppression d'un film (avec confirmation)", "Big Buck Bunny" not in admin.locator("#videos-tab tbody").inner_text())
            admin.click(".tab[data-tab='plans']")
            admin.locator("#plans-tab tr", has_text="Basic").locator(".edit-button").click()
            admin.fill("#planModal [name=prix_mensuel]", "8.99")
            admin.click("#planModal button[type=submit]")
            verifier("modification du prix d'une offre", "$8.99" in admin.locator("#plans-tab").inner_text())
            admin.click(".tab[data-tab='users']")
            admin.fill(".table-filter", "alice")
            verifier("filtre des utilisateurs", admin.locator("#users-table tbody tr:visible").count() == 1)
            capture(admin, "admin-utilisateurs")

            print("Suppression du compte")
            page.goto(base + "/account.php")
            page.fill("#delete_password", "nouveaumdp1")
            page.click(".danger-button")
            verifier("compte supprimé", page.url.endswith("/login.php") and "deleted" in page.content())
            connexion(page, base, "alice@bing.test", "nouveaumdp1")
            verifier("le compte supprimé ne peut plus se connecter", "Invalid email or password" in page.content())

            if CAPTURES:
                print("Captures d'écran du compte de démonstration")
                demo = navigateur.new_context(viewport={"width": 1366, "height": 850}).new_page()
                connexion(demo, base, "demo@bing.test", "Demo1234!")
                capture(demo, "profils")
                choisir_profil(demo, base, "Majd")
                capture(demo, "accueil")
                demo.mouse.wheel(0, 560)
                demo.wait_for_timeout(500)
                capture(demo, "accueil-rangees")
                demo.goto(base + "/serie.php?serie=4")
                capture(demo, "serie", pleine_page=True)
                demo.goto(base + "/movies.php")
                capture(demo, "films")
                demo.goto(base + "/watch.php?episode=2")
                capture(demo, "lecture")
                demo.goto(base + "/account.php")
                capture(demo, "compte")
                demo.click("#changePlanBtn")
                capture(demo, "changement-offre")

            verifier("aucune erreur JavaScript", not erreurs_js)
            for erreur in erreurs_js:
                print("    ", erreur)
            navigateur.close()
    finally:
        serveur.terminate()

    print(f"\n{reussis} / {reussis + len(echecs)} tests réussis")
    for echec in echecs:
        print("  ÉCHEC :", echec)
    sys.exit(1 if echecs else 0)


if __name__ == "__main__":
    main()
