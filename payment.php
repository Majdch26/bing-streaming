<?php
/*
 * Paiement simulé. Trois cas, préparés dans $_SESSION['paiement'] :
 *  - signup  : création du compte et du premier abonnement
 *  - change  : passage à une offre plus chère (on paie la différence au prorata)
 *  - renew   : nouvel abonnement après la fin du précédent
 */
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$paiement = $_SESSION['paiement'] ?? null;
if (!$paiement) {
    rediriger(utilisateur_courant() ? 'account.php' : 'pricing.php');
}
if ($paiement['type'] !== 'signup' && !utilisateur_courant()) {
    unset($_SESSION['paiement']);
    rediriger('login.php');
}

$offre = requete('SELECT * FROM offre WHERE id = ?', [$paiement['offre']])->fetch();
$erreur = null;

/* Algorithme de Luhn : détecte les fautes de frappe dans un numéro de carte */
function luhn_valide(string $numero): bool
{
    $somme = 0;
    $double = false;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $chiffre = (int) $numero[$i];
        if ($double) {
            $chiffre *= 2;
            if ($chiffre > 9) {
                $chiffre -= 9;
            }
        }
        $somme += $chiffre;
        $double = !$double;
    }
    return $somme % 10 === 0;
}

if (est_post()) {
    verifier_csrf();
    $carte = preg_replace('/\D/', '', (string) ($_POST['card'] ?? ''));
    $expiration = (string) ($_POST['expiry'] ?? '');
    $cvv = (string) ($_POST['cvv'] ?? '');

    if (strlen($carte) < 13 || strlen($carte) > 19 || !luhn_valide($carte)) {
        $erreur = 'This card number is not valid. For the demo, use 4242 4242 4242 4242.';
    } elseif (!preg_match('#^(0[1-9]|1[0-2])/?(\d{2})$#', $expiration, $m)
              || (int) ('20' . $m[2] . $m[1]) < (int) date('Ym')) {
        $erreur = 'The expiry date is not valid or has passed.';
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $erreur = 'The security code (CVV) must have 3 or 4 digits.';
    } else {
        try {
            db()->beginTransaction();
            if ($paiement['type'] === 'signup') {
                requete('INSERT INTO utilisateur (nom, email, mot_de_passe_hash) VALUES (?, ?, ?)',
                        [$paiement['nom'], $paiement['email'], $paiement['hash']]);
                $idUtilisateur = (int) db()->lastInsertId();
            } else {
                $idUtilisateur = (int) utilisateur_courant()['id'];
            }
            souscrire($idUtilisateur, (int) $paiement['offre']);
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            $erreur = str_contains($e->getMessage(), 'UNIQUE')
                ? 'An account already exists with this email. Please sign in.'
                : message_erreur($e);
        }

        if ($erreur === null) {
            if ($paiement['type'] === 'signup') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $idUtilisateur;
            }
            $_SESSION['paiement_termine'] = [
                'type' => $paiement['type'],
                'offre' => $offre['nom'],
                'prix' => (float) $offre['prix_mensuel'],
                'montant' => (float) $paiement['montant'],
                'carte' => substr($carte, -4),
            ];
            unset($_SESSION['paiement']);
            rediriger('success.php');
        }
    }
}

$titres = ['signup' => 'Complete your subscription to start watching',
           'change' => 'Complete your plan upgrade',
           'renew'  => 'Renew your subscription'];
entete('Payment', ['payment.css']);
?>
    <div class="payment-container">
        <div class="payment-header">
            <h1 class="payment-title">Payment Details</h1>
            <p class="payment-subtitle"><?= e($titres[$paiement['type']]) ?></p>
            <div class="plan-badge"><?= e($offre['nom']) ?> · $<?= number_format((float) $offre['prix_mensuel'], 2) ?>/month</div>
            <div class="amount-due">Amount due today: <strong>$<?= number_format((float) $paiement['montant'], 2) ?></strong></div>
        </div>

        <div class="progress-steps">
            <div class="progress-step">
                <div class="step-number completed"><i class="fas fa-check"></i></div>
                <div class="step-label completed"><?= $paiement['type'] === 'signup' ? 'Account' : 'Plan' ?></div>
            </div>
            <div class="progress-step">
                <div class="step-number active">2</div>
                <div class="step-label active">Payment</div>
            </div>
            <div class="progress-step">
                <div class="step-number">3</div>
                <div class="step-label">Complete</div>
            </div>
        </div>

        <?php if ($erreur): ?>
            <p class="error-message"><?= e($erreur) ?></p>
        <?php endif; ?>

        <form class="payment-form" method="POST" action="<?= url('payment.php') ?>">
            <?= champ_csrf() ?>
            <div class="form-group">
                <label for="card">Card Number</label>
                <input type="text" id="card" name="card" placeholder="4242 4242 4242 4242" required
                       inputmode="numeric" autocomplete="cc-number" maxlength="23">
                <div class="card-icons">
                    <div class="card-icon"><i class="fab fa-cc-visa"></i></div>
                    <div class="card-icon"><i class="fab fa-cc-mastercard"></i></div>
                    <div class="card-icon"><i class="fab fa-cc-amex"></i></div>
                </div>
            </div>

            <div class="card-details">
                <div class="form-group">
                    <label for="expiry">Expiry Date</label>
                    <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required
                           inputmode="numeric" autocomplete="cc-exp" maxlength="5">
                </div>
                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" placeholder="123" required
                           inputmode="numeric" autocomplete="cc-csc" maxlength="4">
                </div>
            </div>

            <button type="submit" class="submit-button">
                Pay $<?= number_format((float) $paiement['montant'], 2) ?> <i class="fas fa-lock"></i>
            </button>

            <div class="secure-payment">
                <i class="fas fa-info-circle"></i>
                <span>Demo only: no payment is made and card details are never stored.</span>
            </div>
            <div class="secure-payment">
                <a href="<?= url($paiement['type'] === 'signup' ? 'pricing.php' : 'account.php') ?>">Cancel</a>
            </div>
        </form>
    </div>
<?php pied(['forms.js']); ?>
