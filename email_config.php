<?php
/**
 * Configuration pour l'envoi d'emails
 *
 * IMPORTANT : Configurez vos paramètres SMTP dans le fichier .env
 */

// Charger les variables d'environnement si pas déjà chargées
if (!function_exists('env')) {
    require_once __DIR__ . '/env.php';
}

// Configuration SMTP depuis .env
define('SMTP_HOST', env('SMTP_HOST', 'mail.example.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'noreply@example.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'noreply@example.com'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Mes Comptes'));

/**
 * Envoyer un email via la fonction mail() native PHP
 * Note : Nécessite que le serveur soit configuré pour envoyer des emails
 */
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>' . "\r\n";

    return mail($to, $subject, $message, $headers);
}

/**
 * Générer un token sécurisé pour la réinitialisation
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Créer un lien de réinitialisation
 */
function generateResetLink($token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return $protocol . '://' . $host . $path . '/reset_password_form.php?token=' . $token;
}

/**
 * Envoyer un email de réinitialisation de mot de passe
 */
function sendPasswordResetEmail($email, $token) {
    $resetLink = generateResetLink($token);

    $subject = "Réinitialisation de votre mot de passe - Mes Comptes";

    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4a9eff; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .button { display: inline-block; padding: 12px 24px; background: #4a9eff; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Réinitialisation de mot de passe</h1>
            </div>
            <div class="content">
                <p>Bonjour,</p>
                <p>Vous avez demandé à réinitialiser votre mot de passe pour votre compte <strong>Mes Comptes</strong>.</p>
                <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                <div style="text-align: center;">
                    <a href="' . $resetLink . '" class="button">Réinitialiser mon mot de passe</a>
                </div>
                <p>Ou copiez ce lien dans votre navigateur :</p>
                <p style="word-break: break-all; color: #4a9eff;">' . $resetLink . '</p>
                <p><strong>Ce lien est valide pendant 1 heure.</strong></p>
                <p>Si vous n\'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
            </div>
            <div class="footer">
                <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
                <p>&copy; ' . date('Y') . ' Mes Comptes - Tous droits réservés</p>
            </div>
        </div>
    </body>
    </html>
    ';

    return sendEmail($email, $subject, $message);
}
