<?php
// Déterminer la page actuelle
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Mes Comptes</h2>
        <button class="sidebar-close" onclick="toggleSidebar()">&times;</button>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav-top">
            <a href="index.php" class="sidebar-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <span class="sidebar-icon">🏠</span>
                <span>Accueil</span>
            </a>
            <a href="categories.php" class="sidebar-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                <span class="sidebar-icon">🏷️</span>
                <span>Catégories</span>
            </a>
            <a href="stats.php" class="sidebar-link <?= $currentPage === 'stats.php' ? 'active' : '' ?>">
                <span class="sidebar-icon">📊</span>
                <span>Statistiques</span>
            </a>
        </div>
        <div class="sidebar-nav-bottom">
            <?php if (isAdmin()): ?>
                <a href="admin.php" class="sidebar-link <?= $currentPage === 'admin.php' ? 'active' : '' ?>">
                    <span class="sidebar-icon">🔧</span>
                    <span>Administration</span>
                </a>
            <?php endif; ?>
            <a href="settings.php" class="sidebar-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                <span class="sidebar-icon">⚙️</span>
                <span>Paramètres</span>
            </a>
            <a href="logout.php" class="sidebar-link">
                <span class="sidebar-icon">🚪</span>
                <span>Déconnexion</span>
            </a>
        </div>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<button class="hamburger" onclick="toggleSidebar()">
    <span></span>
    <span></span>
    <span></span>
</button>
