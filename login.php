<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $db = getDB();

    $stmt = $db->prepare("SELECT id, password, is_admin FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = (bool) $user['is_admin'];

        header('Location: index.php');
        exit;
    } else {
        $error = 'Identifiants incorrects';
    }
}

$pageTitle = 'Connexion';
$pageIcon = '€';
$pageHeading = 'Mes Comptes';
$pageSubtitle = 'Gérez vos finances simplement';
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
                    <label for="password">Mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login">Se connecter</button>
            </form>

            <div class="login-footer">
                <p><a href="forgot_password.php">Mot de passe oublié ?</a></p>
                <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
            </div>
<?php require_once 'includes/auth_page_footer.php'; ?>
