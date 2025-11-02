<?php
require_once 'config.php';

// Si déjà connecté, rediriger vers l'accueil
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tous les champs sont obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        $db = getDB();

        // Vérifier si l'utilisateur ou l'email existe déjà
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = 'Ce nom d\'utilisateur ou cette adresse email est déjà utilisé(e)';
        } else {
            // Créer l'utilisateur
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);

            // Connexion automatique
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $username;

            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Inscription';
$pageIcon = '€';
$pageHeading = 'Créer un compte';
$pageSubtitle = 'Commencez à gérer vos finances';
$includePasswordToggle = true;

require_once 'includes/auth_page_header.php';
?>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" required placeholder="votre@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <small class="field-hint">Nécessaire pour la réinitialisation du mot de passe</small>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                    <small class="field-hint">Minimum 6 caractères</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">S'inscrire</button>
            </form>

            <div class="login-footer">
                <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
<?php require_once 'includes/auth_page_footer.php'; ?>
