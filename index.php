<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Récupérer les paramètres utilisateur
$userSettings = getUserSettings();

// Gestion de l'ajout de transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $type = $_POST['type'];
    $amount = (float) $_POST['amount'];
    $description = $_POST['description'] ?? '';
    $date = $_POST['transaction_date'];
    $periodicity = $_POST['periodicity'] ?? 'mensuel';
    $categoryName = trim($_POST['category'] ?? '');

    // Gérer la catégorie
    $categoryId = null;
    if (!empty($categoryName)) {
        // Vérifier si la catégorie existe déjà
        $stmt = $db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?");
        $stmt->execute([$userId, $categoryName, $type]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($category) {
            $categoryId = $category['id'];
        } else {
            // Créer la nouvelle catégorie avec icône et couleur par défaut
            $defaultIcon = '📁';
            $defaultColor = '#4a9eff';
            $stmt = $db->prepare("INSERT INTO categories (user_id, name, type, icon, color, is_default) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$userId, $categoryName, $type, $defaultIcon, $defaultColor]);
            $categoryId = $db->lastInsertId();
        }
    }

    // Gérer la récurrence : nombre de mois OU date de fin
    $recurringMonths = (int) ($_POST['recurring_months'] ?? 0);
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // Validation : recurring_months doit être 0 (infini) ou >= 1, jamais négatif
    if ($recurringMonths < 0) {
        $recurringMonths = 0;
    }

    // Si une date de fin est spécifiée, on passe en mode répétition illimitée avec date limite
    if ($endDate) {
        $recurringMonths = 0;
    }

    $remainingOccurrences = $recurringMonths > 1 ? $recurringMonths : null;

    $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, description, transaction_date, periodicity, category_id, recurring_months, remaining_occurrences, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $type, $amount, $description, $date, $periodicity, $categoryId, $recurringMonths, $remainingOccurrences, $endDate]);

    setFlash('success', 'Transaction ajoutée avec succès !');
    header('Location: index.php?type=' . urlencode($type));
    exit;
}

// Gestion de l'édition de transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int) $_POST['id'];
    $type = $_POST['type'];
    $amount = (float) $_POST['amount'];
    $description = $_POST['description'] ?? '';
    $date = $_POST['transaction_date'];
    $periodicity = $_POST['periodicity'] ?? 'mensuel';
    $categoryName = trim($_POST['category'] ?? '');

    // Gérer la catégorie
    $categoryId = null;
    if (!empty($categoryName)) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? AND type = ?");
        $stmt->execute([$userId, $categoryName, $type]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($category) {
            $categoryId = $category['id'];
        } else {
            // Créer la nouvelle catégorie avec icône et couleur par défaut
            $defaultIcon = '📁';
            $defaultColor = '#4a9eff';
            $stmt = $db->prepare("INSERT INTO categories (user_id, name, type, icon, color, is_default) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$userId, $categoryName, $type, $defaultIcon, $defaultColor]);
            $categoryId = $db->lastInsertId();
        }
    }

    // Gérer la récurrence : nombre de mois OU date de fin
    $recurringMonths = (int) ($_POST['recurring_months'] ?? 0);
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // Validation : recurring_months doit être 0 (infini) ou >= 1, jamais négatif
    if ($recurringMonths < 0) {
        $recurringMonths = 0;
    }

    // Si une date de fin est spécifiée, on passe en mode répétition illimitée avec date limite
    if ($endDate) {
        $recurringMonths = 0;
    }

    $remainingOccurrences = $recurringMonths > 1 ? $recurringMonths : null;

    $stmt = $db->prepare("UPDATE transactions SET type = ?, amount = ?, description = ?, transaction_date = ?, periodicity = ?, category_id = ?, recurring_months = ?, remaining_occurrences = ?, end_date = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$type, $amount, $description, $date, $periodicity, $categoryId, $recurringMonths, $remainingOccurrences, $endDate, $id, $userId]);

    setFlash('success', 'Transaction modifiée avec succès !');
    header('Location: index.php#transaction-' . $id);
    exit;
}

// Gestion de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int) $_POST['id'];
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);

    setFlash('success', 'Transaction supprimée avec succès !');
    header('Location: index.php');
    exit;
}

// Gérer le mois sélectionné
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Récupérer la date min et max des transactions pour la navigation
$stmt = $db->prepare("SELECT MIN(transaction_date) as min_date, MAX(transaction_date) as max_date FROM transactions WHERE user_id = ?");
$stmt->execute([$userId]);
$dateRange = $stmt->fetch(PDO::FETCH_ASSOC);

$minMonth = $dateRange['min_date'] ? date('Y-m', strtotime($dateRange['min_date'])) : date('Y-m');
$maxMonth = date('Y-m', strtotime('+1 year')); // Jusqu'à 1 an dans le futur

// Calculer le mois précédent et suivant
$prevMonth = date('Y-m', strtotime($selectedMonth . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($selectedMonth . '-01 +1 month'));

$canGoPrev = $prevMonth >= $minMonth;
$canGoNext = $nextMonth <= $maxMonth;

$currentMonth = $selectedMonth;
$currentDate = date('Y-m-d');

// Calculer les totaux en tenant compte des transactions récurrentes actives
$stmt = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'recette' THEN amount ELSE 0 END), 0) as month_recettes,
        COALESCE(SUM(CASE WHEN type = 'depense' THEN amount ELSE 0 END), 0) as month_depenses
    FROM transactions
    WHERE user_id = ?
    AND (
        -- Transactions du mois en cours (ponctuelles qui tombent ce mois exactement)
        (strftime('%Y-%m', transaction_date) = ? AND transaction_date <= date('now') AND periodicity != 'annuel')
        OR
        -- Transactions MENSUELLES récurrentes actives (avec nombre de mois)
        (
            periodicity = 'mensuel'
            AND transaction_date <= ?
            AND recurring_months > 0
            AND (remaining_occurrences > 0 OR recurring_months > 1)
            AND julianday(?) - julianday(transaction_date) >= 0
            AND CAST((julianday(?) - julianday(transaction_date)) / 30.44 AS INTEGER) < recurring_months
        )
        OR
        -- Transactions MENSUELLES à répétition illimitée (sans limite de nombre, optionnellement limitées par date)
        (
            periodicity = 'mensuel'
            AND recurring_months = 0
            AND transaction_date <= ?
            AND (end_date IS NULL OR ? <= end_date)
        )
        OR
        -- Transactions HEBDOMADAIRES récurrentes actives (avec nombre)
        (
            periodicity = 'hebdo'
            AND transaction_date <= ?
            AND recurring_months > 0
            AND (remaining_occurrences > 0 OR recurring_months > 1)
            AND julianday(?) - julianday(transaction_date) >= 0
            AND CAST((julianday(?) - julianday(transaction_date)) / 7 AS INTEGER) < recurring_months
        )
        OR
        -- Transactions HEBDOMADAIRES à répétition illimitée (sans limite de nombre, optionnellement limitées par date)
        (
            periodicity = 'hebdo'
            AND recurring_months = 0
            AND transaction_date <= ?
            AND (end_date IS NULL OR ? <= end_date)
        )
        OR
        -- Transactions ANNUELLES récurrentes actives (avec nombre d'années)
        (
            periodicity = 'annuel'
            AND strftime('%m', transaction_date) = strftime('%m', ?)
            AND transaction_date <= ?
            AND recurring_months > 0
            AND CAST((strftime('%Y', ?) - strftime('%Y', transaction_date)) AS INTEGER) < recurring_months
        )
        OR
        -- Transactions ANNUELLES à répétition illimitée (sans limite de nombre, optionnellement limitées par date)
        (
            periodicity = 'annuel'
            AND strftime('%m', transaction_date) = strftime('%m', ?)
            AND transaction_date <= ?
            AND recurring_months = 0
            AND (end_date IS NULL OR ? <= end_date)
        )
    )
");
$stmt->execute([$userId, $currentMonth, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate, $currentDate]);
$monthTotals = $stmt->fetch(PDO::FETCH_ASSOC);

// Gérer le tri
$sortBy = $_GET['sort'] ?? 'date';
$sortOrder = $_GET['order'] ?? 'desc';

$allowedSort = ['date', 'type', 'category', 'description', 'periodicity', 'amount'];
if (!in_array($sortBy, $allowedSort)) {
    $sortBy = 'date';
}

$orderBy = match($sortBy) {
    'date' => 't.transaction_date',
    'type' => 't.type',
    'category' => 'c.name',
    'description' => 't.description',
    'periodicity' => 't.periodicity',
    'amount' => 't.amount',
};

$direction = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

// Récupérer les transactions avec les catégories
$stmt = $db->prepare("
    SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color
    FROM transactions t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ?
    ORDER BY {$orderBy} {$direction}, t.created_at DESC
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer le type sélectionné (pour le garder après ajout)
$selectedType = $_GET['type'] ?? 'depense';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Comptes</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4a9eff">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <!-- Modale d'édition -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier la transaction</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <!-- Ligne 1 : Type, Catégorie, Description, Montant -->
                <div class="modal-form-row">
                    <div class="form-group">
                        <label for="edit_type">Type</label>
                        <select id="edit_type" name="type" required>
                            <option value="recette">Recette</option>
                            <option value="depense">Dépense</option>
                        </select>
                    </div>
                    <div class="form-group autocomplete-wrapper">
                        <label for="edit_category">Catégorie</label>
                        <input type="text" id="edit_category" name="category" class="category-input" autocomplete="off">
                        <div class="autocomplete-list" id="edit_category_list"></div>
                    </div>
                </div>
                <div class="modal-form-row">
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <input type="text" id="edit_description" name="description">
                    </div>
                    <div class="form-group">
                        <label for="edit_amount">Montant</label>
                        <input type="number" id="edit_amount" name="amount" step="0.01" min="0.01" required>
                    </div>
                </div>

                <!-- Ligne 2 : Date, Périodicité -->
                <div class="modal-form-row">
                    <div class="form-group">
                        <label for="edit_transaction_date">Date</label>
                        <input type="date" id="edit_transaction_date" name="transaction_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_periodicity">Périodicité</label>
                        <select id="edit_periodicity" name="periodicity" required>
                            <option value="hebdo">Hebdomadaire</option>
                            <option value="mensuel" selected>Mensuel</option>
                            <option value="annuel">Annuel</option>
                        </select>
                    </div>
                </div>

                <!-- Ligne 3 : Récurrence -->
                <div class="modal-form-row">
                    <div class="form-group">
                        <label>Type de récurrence</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="edit_recurrence_type" value="no_limit" checked>
                                <span>Pas de limite</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="edit_recurrence_type" value="count">
                                <span>Nombre d'occurrences</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="edit_recurrence_type" value="date">
                                <span>Jusqu'à une date</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-form-row">
                    <div class="form-group" id="edit_recurring_months_group" style="display: none;">
                        <label for="edit_recurring_months">
                            Nombre d'occurrences
                            <span class="tooltip-icon" data-tooltip="Nombre de fois que la transaction se répète">?</span>
                        </label>
                        <input type="number" id="edit_recurring_months" name="recurring_months" min="1" value="1">
                    </div>
                    <div class="form-group" id="edit_end_date_group" style="display: none;">
                        <label for="edit_end_date">
                            Date de fin
                            <span class="tooltip-icon" data-tooltip="Définir une date limite pour la récurrence">?</span>
                        </label>
                        <input type="date" id="edit_end_date" name="end_date">
                        <small class="field-hint" id="edit_end_date_count"></small>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-confirm">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p>Êtes-vous sûr de vouloir supprimer cette transaction ?</p>
            <div class="delete-transaction-info" id="deleteTransactionInfo"></div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Annuler</button>
                    <button type="submit" class="btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="container">
        <?php include 'includes/migrations_alert.php'; ?>
        <?php include 'includes/flash_messages.php'; ?>

        <header>
            <div class="month-navigation">
                <a href="?month=<?= $prevMonth ?>" class="month-nav <?= !$canGoPrev ? 'disabled' : '' ?>" <?= !$canGoPrev ? 'onclick="return false"' : '' ?>>
                    ◀
                </a>
                <h1><?= formatMonthYear($currentMonth) ?></h1>
                <a href="?month=<?= $nextMonth ?>" class="month-nav <?= !$canGoNext ? 'disabled' : '' ?>" <?= !$canGoNext ? 'onclick="return false"' : '' ?>>
                    ▶
                </a>
            </div>
            <div class="header-stats-inline">
                <div class="header-stat recettes">
                    <span class="stat-label">Recettes</span>
                    <span class="stat-value positive counter" data-target="<?= $monthTotals['month_recettes'] ?>" data-prefix="+">+0,00 €</span>
                </div>
                <div class="header-stat depenses">
                    <span class="stat-label">Dépenses</span>
                    <span class="stat-value negative counter" data-target="<?= $monthTotals['month_depenses'] ?>" data-prefix="-">-0,00 €</span>
                </div>
                <div class="header-stat balance">
                    <span class="stat-label">Solde du mois</span>
                    <?php $monthBalance = $monthTotals['month_recettes'] - $monthTotals['month_depenses']; ?>
                    <span class="stat-value <?= $monthBalance >= 0 ? 'positive' : 'negative' ?> counter" data-target="<?= $monthBalance ?>" data-prefix="<?= $monthBalance >= 0 ? '' : '' ?>">0,00 €</span>
                </div>
            </div>
        </header>

        <div class="add-transaction">
            <div class="section-header" onclick="toggleAddTransaction()">
                <h3>Ajouter une transaction</h3>
                <button type="button" class="toggle-btn" id="toggleAddTransactionBtn">▼</button>
            </div>
            <div class="section-content" id="addTransactionContent">
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <!-- Ligne 1 : Type, Catégorie, Description, Montant -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="recette" <?= $selectedType === 'recette' ? 'selected' : '' ?>>Recette</option>
                            <option value="depense" <?= $selectedType === 'depense' ? 'selected' : '' ?>>Dépense</option>
                        </select>
                    </div>
                    <div class="form-group autocomplete-wrapper">
                        <label for="category">Catégorie</label>
                        <input type="text" id="category" name="category" class="category-input" autocomplete="off" placeholder="Optionnel">
                        <div class="autocomplete-list" id="category_list"></div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" placeholder="Optionnel">
                    </div>
                    <div class="form-group">
                        <label for="amount">Montant</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                    </div>
                </div>

                <!-- Ligne 2 : Date, Périodicité -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="transaction_date">Date</label>
                        <input type="date" id="transaction_date" name="transaction_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="periodicity">Périodicité</label>
                        <select id="periodicity" name="periodicity" required>
                            <option value="hebdo">Hebdomadaire</option>
                            <option value="mensuel" selected>Mensuel</option>
                            <option value="annuel">Annuel</option>
                        </select>
                    </div>
                </div>

                <!-- Ligne 3 : Récurrence -->
                <div class="form-row recurring-options">
                    <div class="form-group">
                        <label>Type de récurrence</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="recurrence_type" value="no_limit" checked>
                                <span>Pas de limite</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="recurrence_type" value="count">
                                <span>Nombre d'occurrences</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="recurrence_type" value="date">
                                <span>Jusqu'à une date</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="recurring_months_group" style="display: none;">
                        <label for="recurring_months">
                            Nombre d'occurrences
                            <span class="tooltip-icon" data-tooltip="Nombre de fois que la transaction se répète">?</span>
                        </label>
                        <input type="number" id="recurring_months" name="recurring_months" min="1" value="1">
                    </div>

                    <div class="form-group" id="end_date_group" style="display: none;">
                        <label for="end_date">
                            Date de fin
                            <span class="tooltip-icon" data-tooltip="Définir une date limite pour la récurrence">?</span>
                        </label>
                        <input type="date" id="end_date" name="end_date">
                        <small class="field-hint" id="end_date_count"></small>
                    </div>

                    <div class="form-group form-submit">
                        <button type="submit" class="btn-add">Ajouter</button>
                    </div>
                </div>
            </form>
            </div>
        </div>

        <div class="transactions">
            <div class="transactions-header">
                <h3>Historique</h3>
                <div class="view-toggle">
                    <button type="button" class="view-toggle-btn active" data-view="table" title="Vue tableau">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </button>
                    <button type="button" class="view-toggle-btn" data-view="cards" title="Vue cartes">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                        </svg>
                    </button>
                </div>
            </div>
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h4 class="empty-state-title">Aucune transaction pour le moment</h4>
                    <p class="empty-state-message">Commence par ajouter ta première transaction pour suivre tes finances !</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php
                            function getSortLink($column, $label) {
                                global $sortBy, $sortOrder;
                                $newOrder = ($sortBy === $column && $sortOrder === 'desc') ? 'asc' : 'desc';
                                $arrow = '';
                                if ($sortBy === $column) {
                                    $arrow = $sortOrder === 'desc' ? ' ▼' : ' ▲';
                                }
                                return "<a href='?sort={$column}&order={$newOrder}' class='sort-link'>{$label}{$arrow}</a>";
                            }
                            ?>
                            <th><?= getSortLink('date', 'Date') ?></th>
                            <th><?= getSortLink('category', 'Catégorie') ?></th>
                            <th><?= getSortLink('description', 'Description') ?></th>
                            <th><?= getSortLink('periodicity', 'Périodicité') ?></th>
                            <th>Récurrence</th>
                            <th><?= getSortLink('amount', 'Montant') ?></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr id="transaction-<?= $transaction['id'] ?>">
                                <td><?= formatDate($transaction['transaction_date'], $userSettings) ?></td>
                                <td>
                                    <?php if ($transaction['category_name']): ?>
                                        <span class="category-display">
                                            <?php if ($transaction['category_color']): ?>
                                                <span class="category-color-dot" style="background-color: <?= htmlspecialchars($transaction['category_color']) ?>;"></span>
                                            <?php endif; ?>
                                            <?php if ($transaction['category_icon']): ?>
                                                <span class="category-icon"><?= htmlspecialchars($transaction['category_icon']) ?></span>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($transaction['category_name']) ?></span>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($transaction['description'] ?: '-') ?></td>
                                <td><?= ucfirst($transaction['periodicity'] ?? 'mensuel') ?></td>
                                <td>
                                    <?php if ($transaction['recurring_months'] === 0 && $transaction['end_date']): ?>
                                        <span class="recurring-badge" title="Jusqu'au <?= date('d/m/Y', strtotime($transaction['end_date'])) ?>">
                                            → <?= formatDate($transaction['end_date'], $userSettings) ?>
                                        </span>
                                    <?php elseif ($transaction['recurring_months'] === 0): ?>
                                        <span class="recurring-badge infinite" title="Infini">∞</span>
                                    <?php elseif ($transaction['recurring_months'] > 1): ?>
                                        <?php
                                        // Calculer le nombre de mois écoulés depuis la transaction initiale
                                        $startDate = new DateTime($transaction['transaction_date']);
                                        $today = new DateTime();
                                        $interval = $startDate->diff($today);

                                        $elapsedMonths = 0;
                                        switch ($transaction['periodicity']) {
                                            case 'mensuel':
                                                $elapsedMonths = ($interval->y * 12) + $interval->m + 1;
                                                break;
                                            case 'hebdo':
                                                $elapsedMonths = floor($interval->days / 7) + 1;
                                                break;
                                            case 'annuel':
                                                $elapsedMonths = $interval->y + 1;
                                                break;
                                        }

                                        // Limiter au nombre total de récurrences
                                        $currentOccurrence = min($elapsedMonths, $transaction['recurring_months']);
                                        $percentage = ($currentOccurrence / $transaction['recurring_months']) * 100;
                                        $isCompleted = $currentOccurrence >= $transaction['recurring_months'];
                                        ?>
                                        <div class="recurring-progress-container" title="<?= $currentOccurrence ?>/<?= $transaction['recurring_months'] ?> <?= $transaction['periodicity'] === 'mensuel' ? 'mois' : ($transaction['periodicity'] === 'hebdo' ? 'semaines' : 'ans') ?>">
                                            <div class="recurring-progress-bar">
                                                <div class="recurring-progress-fill <?= $isCompleted ? 'completed' : '' ?>" style="width: <?= $percentage ?>%;"></div>
                                            </div>
                                            <span class="recurring-progress-text"><?= $currentOccurrence ?>/<?= $transaction['recurring_months'] ?></span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="amount <?= $transaction['type'] ?>">
                                    <?= $transaction['type'] === 'recette' ? '+' : '-' ?>
                                    <?= number_format($transaction['amount'], 2, ',', ' ') ?> €
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-edit" onclick='openEditModal(<?= json_encode($transaction) ?>)'>✎</button>
                                    <button type="button" class="btn-delete" onclick='openDeleteModal(<?= json_encode($transaction) ?>)'>✕</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Vue cartes pour mobile -->
                <div class="transactions-cards">
                    <?php foreach ($transactions as $transaction): ?>
                        <div class="transaction-card" id="transaction-card-<?= $transaction['id'] ?>">
                            <div class="transaction-card-header">
                                <span class="transaction-card-date"><?= formatDate($transaction['transaction_date'], $userSettings) ?></span>
                                <span class="transaction-card-amount <?= $transaction['type'] ?>">
                                    <?= $transaction['type'] === 'recette' ? '+' : '-' ?><?= number_format($transaction['amount'], 2, ',', ' ') ?> €
                                </span>
                            </div>
                            <div class="transaction-card-body">
                                <?php if ($transaction['category_name']): ?>
                                    <div class="transaction-card-row">
                                        <span class="transaction-card-label">Catégorie</span>
                                        <span class="transaction-card-value category-display">
                                            <?php if ($transaction['category_color']): ?>
                                                <span class="category-color-dot" style="background-color: <?= htmlspecialchars($transaction['category_color']) ?>;"></span>
                                            <?php endif; ?>
                                            <?php if ($transaction['category_icon']): ?>
                                                <span class="category-icon"><?= htmlspecialchars($transaction['category_icon']) ?></span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($transaction['category_name']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($transaction['description']): ?>
                                    <div class="transaction-card-row">
                                        <span class="transaction-card-label">Description</span>
                                        <span class="transaction-card-value"><?= htmlspecialchars($transaction['description']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="transaction-card-row">
                                    <span class="transaction-card-label">Périodicité</span>
                                    <span class="transaction-card-value"><?= ucfirst($transaction['periodicity'] ?? 'mensuel') ?></span>
                                </div>
                                <?php if ($transaction['recurring_months'] > 1): ?>
                                    <?php
                                    $startDate = new DateTime($transaction['transaction_date']);
                                    $today = new DateTime();
                                    $interval = $startDate->diff($today);
                                    $elapsedMonths = 0;
                                    switch ($transaction['periodicity']) {
                                        case 'mensuel':
                                            $elapsedMonths = ($interval->y * 12) + $interval->m + 1;
                                            break;
                                        case 'hebdo':
                                            $elapsedMonths = floor($interval->days / 7) + 1;
                                            break;
                                        case 'annuel':
                                            $elapsedMonths = $interval->y + 1;
                                            break;
                                    }
                                    $currentOccurrence = min($elapsedMonths, $transaction['recurring_months']);
                                    $percentage = ($currentOccurrence / $transaction['recurring_months']) * 100;
                                    $isCompleted = $currentOccurrence >= $transaction['recurring_months'];
                                    ?>
                                    <div class="transaction-card-row">
                                        <span class="transaction-card-label">Récurrence</span>
                                        <div class="recurring-progress-container" style="flex: 1; justify-content: flex-end;">
                                            <div class="recurring-progress-bar">
                                                <div class="recurring-progress-fill <?= $isCompleted ? 'completed' : '' ?>" style="width: <?= $percentage ?>%;"></div>
                                            </div>
                                            <span class="recurring-progress-text"><?= $currentOccurrence ?>/<?= $transaction['recurring_months'] ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="transaction-card-actions">
                                <button type="button" class="btn-edit" onclick='openEditModal(<?= json_encode($transaction) ?>)'>✎ Modifier</button>
                                <button type="button" class="btn-delete" onclick='openDeleteModal(<?= json_encode($transaction) ?>)'>✕ Supprimer</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/recurring.js"></script>
    <script src="js/periodicity.js"></script>
    <script src="js/sidebar.js"></script>
    <script src="js/counter-animation.js"></script>
    <script src="js/confetti.js"></script>
    <script src="js/form-validation.js"></script>
    <script src="js/view-toggle.js"></script>
    <script>
        // Enregistrer le Service Worker pour le mode hors connexion
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('Service Worker enregistré'))
                .catch(err => console.log('Erreur Service Worker:', err));
        }
    </script>
</body>
</html>
