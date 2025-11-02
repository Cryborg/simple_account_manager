<?php
/**
 * Alerte de migrations en attente (visible uniquement pour les admins)
 */

if (isAdmin()) {
    require_once __DIR__ . '/../migrations.php';
    $manager = getMigrationManager();
    $pendingCount = $manager->getPendingCount();

    if ($pendingCount > 0):
?>
<div class="pending-migrations-alert">
    <span style="font-size: 1.5rem;">⚠️</span>
    <div style="flex: 1;">
        <strong>Migrations en attente :</strong>
        Il y a <?= $pendingCount ?> migration(s) à exécuter.
        <a href="admin.php">Aller dans l'administration</a>
    </div>
</div>
<?php
    endif;
}
?>
