<?php
require_once 'config.php';
require_once 'email_config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Veuillez saisir votre adresse email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } else {
        $db = getDB();

        // Vérifier si l'email existe
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Générer un token unique
            $token = generateResetToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Supprimer les anciens tokens pour cet email
            $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);

            // Enregistrer le nouveau token
            $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expiresAt]);

            // Envoyer l'email
            if (sendPasswordResetEmail($email, $token)) {
                $message = 'Un email de réinitialisation a été envoyé à votre adresse.';
            } else {
                $error = 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.';
            }
        } else {
            // Pour des raisons de sécurité, on affiche le même message
            // même si l'email n'existe pas (évite l'énumération d'emails)
            $message = 'Si cette adresse email existe, un email de réinitialisation a été envoyé.';
        }
    }
}

$pageTitle = 'Mot de passe oublié';
$pageIcon = '🔑';
$pageHeading = 'Mot de passe oublié';
$pageSubtitle = 'Entrez votre email pour réinitialiser';
$includePasswordToggle = false;

require_once 'includes/auth_page_header.php';
?>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="success"><?= htmlspecialchars($message) ?></div>
                <div class="login-footer">
                    <p><a href="login.php">Retour à la connexion</a></p>
                </div>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" required autofocus
                               placeholder="votre@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-login">Envoyer le lien de réinitialisation</button>
                </form>

                <div class="login-footer">
                    <p><a href="login.php">← Retour à la connexion</a></p>
                </div>
            <?php endif; ?>
<?php require_once 'includes/auth_page_footer.php'; ?>
