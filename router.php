<?php
/*
 * Routeur pour le serveur intégré de PHP :
 *     php -S localhost:8000 router.php
 * Il refuse l'accès aux dossiers internes (base SQLite, scripts SQL, code partagé, tests),
 * comme le font les fichiers .htaccess avec Apache.
 */
$chemin = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if (preg_match('#^/(data|database|includes|tests|docs)(/|$)#', $chemin) || preg_match('#/\.#', $chemin)) {
    http_response_code(404);
    exit('Not found');
}
return false;   // le serveur intégré sert le fichier demandé normalement
