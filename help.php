<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$utilisateur = utilisateur_courant();
$profil = null;
if ($utilisateur && isset($_SESSION['profile_id'])) {
    $profil = requete('SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?', [$_SESSION['profile_id'], $utilisateur['id']])->fetch() ?: null;
}
$offres = requete('SELECT * FROM offre WHERE disponible = 1 ORDER BY prix_mensuel')->fetchAll();

/* Questions fréquentes : chaque réponse décrit ce que le site fait vraiment */
$questions = [
    'plans' => ['How do I change my plan?',
        '<p>Go to <a href="' . url('account.php#plan') . '">Account</a> and click <strong>Change plan</strong>.
         Moving to a more expensive plan takes effect immediately: you only pay the price difference for the days left in the month.
         Moving to a cheaper plan is free and also immediate.</p>'],
    'profiles' => ['How many profiles can I create?',
        '<p>It depends on your plan:</p><ul>' . implode('', array_map(
            fn ($o) => '<li>' . e($o['nom']) . ': ' . (int) $o['nb_profils_max'] . ' profile' . ($o['nb_profils_max'] > 1 ? 's' : '') . '</li>', $offres))
        . '</ul><p>Each profile has its own list and its own viewing history.
         If you move to a plan with fewer profiles, BING tells you which profiles will be removed before anything is deleted.</p>'],
    'kids' => ['How do kids profiles work?',
        '<p>Enter an age when you create or edit a profile. Titles rated above that age are hidden everywhere for that profile:
         home page, search, categories and direct links. Profiles under 13 show a <strong>Kids</strong> badge.</p>'],
    'resume' => ['Can I stop a video and finish it later?',
        '<p>Yes. Your position is saved every ten seconds and when you pause or leave the page.
         The title then appears in <strong>Continue Watching</strong> on the home page. For series, BING takes you to the next episode once you have finished one.</p>'],
    'subtitles' => ['How do I turn on subtitles?',
        '<p>When subtitles are available, they are listed under the player. Click the <strong>CC</strong> button in the player to turn them on,
         and the settings icon to switch language.</p>'],
    'buffering' => ['Why does my video keep buffering?',
        '<p>Buffering usually comes from the internet connection. Try to:</p>
         <ul><li>check your internet speed (5 Mbps or more for HD)</li><li>restart your router and device</li>
         <li>reduce the number of devices using your network</li></ul>'],
    'password' => ['I forgot my password. What can I do?',
        '<p>If you are signed in, change it from <a href="' . url('account.php#change-password') . '">Account</a>.
         Otherwise, contact support from the email address of your account.</p>'],
    'delete' => ['How do I delete my account?',
        '<p>At the bottom of the <a href="' . url('account.php#danger') . '">Account</a> page, confirm with your password.
         Your profiles, list, viewing history and subscriptions are deleted immediately.</p>'],
];

entete('Help Center', $profil ? ['navbar.css', 'help.css'] : ['help.css']);
if ($profil) {
    include __DIR__ . '/includes/navbar.php';
} else { ?>
    <div class="help-topbar"><a href="<?= url('login.php') ?>"><img src="<?= url('images/logo.png') ?>" alt="BING"></a></div>
<?php } ?>
    <div class="help-container">
        <div class="help-header">
            <h1 class="help-title">Help Center</h1>
            <p class="help-subtitle">Answers to the most common questions about your BING account, plans and playback.</p>
        </div>

        <div class="faq-section">
            <h2 class="section-title"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            <?php foreach ($questions as $ancre => [$question, $reponse]): ?>
                <div class="faq-item">
                    <button class="faq-question" id="<?= $ancre ?>" aria-expanded="false"><?= e($question) ?></button>
                    <div class="faq-answer"><?= $reponse ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="contact-section">
            <h2 class="section-title"><i class="fas fa-headset"></i> Contact Support</h2>
            <p class="contact-intro">Can't find what you're looking for? Write to us and we'll get back to you within 24 hours.</p>
            <div class="contact-methods">
                <div class="contact-method">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <h3 class="contact-title">Email</h3>
                    <p class="contact-info">Replies within 24 hours.</p>
                    <a href="mailto:support@bing.test" class="contact-link">support@bing.test</a>
                </div>
            </div>
        </div>
    </div>
<?php pied(['help.js']); ?>
