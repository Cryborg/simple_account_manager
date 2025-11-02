<?php
require_once 'config.php';

$error = '';
$message = '';
$validToken = false;
$email = '';

// Vérifier le token
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $db = getDB();
    $stmt = $db->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reset) {
        // Vérifier si le token n'a pas expiré
        if (strtotime($reset['expires_at']) > time()) {
            $validToken = true;
            $email = $reset['email'];
        } else {
            $error = 'Ce lien de réinitialisation a expiré. Veuillez refaire une demande.';
        }
    } else {
        $error = 'Lien de réinitialisation invalide.';
    }
} else {
    $error = 'Token manquant.';
}

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = 'Veuillez saisir un mot de passe';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);

        // Supprimer le token utilisé
        $stmt = $db->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $message = 'Votre mot de passe a été réinitialisé avec succès !';
        $validToken = false; // Empêcher de soumettre à nouveau
    }
}

$pageTitle = 'Réinitialiser le mot de passe';
$pageIcon = '🔐';
$pageHeading = 'Nouveau mot de passe';
$pageSubtitle = 'Définissez votre nouveau mot de passe';
$includePasswordToggle = true;

require_once 'includes/auth_page_header.php';
?>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
                <div class="login-footer">
                    <p><a href="forgot_password.php">Faire une nouvelle demande</a></p>
                    <p><a href="login.php">Retour à la connexion</a></p>
                </div>
            <?php elseif ($message): ?>
                <div class="success"><?= htmlspecialchars($message) ?></div>
                <div class="login-footer">
                    <p><a href="login.php" class="btn-login" style="display: inline-block; text-decoration: none;">Se connecter</a></p>
                </div>
            <?php else: ?>
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required autofocus
                                   minlength="6"
                                   placeholder="Au moins 6 caractères">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   minlength="6"
                                   placeholder="Retapez le mot de passe">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Réinitialiser le mot de passe</button>
                </form>

                <div class="login-footer">
                    <p><a href="login.php">← Retour à la connexion</a></p>
                </div>
            <?php endif; ?>
<?php require_once 'includes/auth_page_footer.php'; ?>
