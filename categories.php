<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $icon = $_POST['icon'];
        $color = $_POST['color'];

        $stmt = $db->prepare("INSERT INTO categories (user_id, name, type, icon, color, is_default) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$userId, $name, $type, $icon, $color]);

        header('Location: categories.php');
        exit;
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $icon = $_POST['icon'];
        $color = $_POST['color'];

        $stmt = $db->prepare("UPDATE categories SET name = ?, type = ?, icon = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $type, $icon, $color, $id, $userId]);

        header('Location: categories.php');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];

        // Vérifier si la catégorie est utilisée
        $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = "Impossible de supprimer cette catégorie car elle est utilisée par $count transaction(s).";
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            header('Location: categories.php');
            exit;
        }
    }
}

// Récupérer toutes les catégories
$stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
$stmt->execute([$userId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouper par type
$categoriesByType = [
    'recette' => [],
    'depense' => []
];

// Créer un index des icônes utilisées
$usedIcons = [];
foreach ($categories as $cat) {
    $categoriesByType[$cat['type']][] = $cat;
    if (!isset($usedIcons[$cat['icon']])) {
        $usedIcons[$cat['icon']] = [];
    }
    $usedIcons[$cat['icon']][] = $cat['name'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - Mes Comptes</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <header>
            <h1>Gestion des catégories</h1>
        </header>

        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="add-transaction">
            <h3>Ajouter une catégorie</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nom</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="depense">Dépense</option>
                            <option value="recette">Recette</option>
                        </select>
                    </div>
                </div>
                <div class="form-row form-row-icon-color">
                    <div class="form-group">
                        <label for="icon">Icône (emoji)</label>
                        <input type="text" id="icon" name="icon" value="📁" required maxlength="4" readonly onclick="openEmojiPicker('icon')" style="cursor: pointer;">
                    </div>
                    <div class="form-group">
                        <label for="color">Couleur</label>
                        <input type="color" id="color" name="color" value="#4a9eff" required>
                    </div>
                </div>
                <div class="form-submit">
                    <button type="submit">Ajouter</button>
                </div>
            </form>
        </div>

        <div class="categories-grid">
            <div class="category-section">
                <h2>Dépenses</h2>
                <div class="categories-list">
                    <?php foreach ($categoriesByType['depense'] as $cat): ?>
                        <div class="category-card" style="border-left: 4px solid <?= htmlspecialchars($cat['color']) ?>">
                            <div class="category-info">
                                <span class="category-icon" style="font-size: 2rem;"><?= htmlspecialchars($cat['icon']) ?></span>
                                <div class="category-details">
                                    <h4><?= htmlspecialchars($cat['name']) ?></h4>
                                    <span class="category-meta">
                                        <?= $cat['is_default'] ? '🔒 Par défaut' : '👤 Personnalisée' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="category-actions">
                                <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($cat)) ?>)">✎</button>
                                <?php if (!$cat['is_default']): ?>
                                    <button class="btn-delete" onclick="openDeleteModal(<?= $cat['id'] ?>)">✕</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="category-section">
                <h2>Recettes</h2>
                <div class="categories-list">
                    <?php foreach ($categoriesByType['recette'] as $cat): ?>
                        <div class="category-card" style="border-left: 4px solid <?= htmlspecialchars($cat['color']) ?>">
                            <div class="category-info">
                                <span class="category-icon" style="font-size: 2rem;"><?= htmlspecialchars($cat['icon']) ?></span>
                                <div class="category-details">
                                    <h4><?= htmlspecialchars($cat['name']) ?></h4>
                                    <span class="category-meta">
                                        <?= $cat['is_default'] ? '🔒 Par défaut' : '👤 Personnalisée' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="category-actions">
                                <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($cat)) ?>)">✎</button>
                                <?php if (!$cat['is_default']): ?>
                                    <button class="btn-delete" onclick="openDeleteModal(<?= $cat['id'] ?>)">✕</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Modifier la catégorie</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label for="edit_name">Nom</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_type">Type</label>
                    <select id="edit_type" name="type" required>
                        <option value="depense">Dépense</option>
                        <option value="recette">Recette</option>
                    </select>
                </div>
                <div class="modal-form-row">
                    <div class="form-group">
                        <label for="edit_icon">Icône (emoji)</label>
                        <input type="text" id="edit_icon" name="icon" required maxlength="4" readonly onclick="openEmojiPicker('edit_icon')" style="cursor: pointer;">
                    </div>
                    <div class="form-group">
                        <label for="edit_color">Couleur</label>
                        <input type="color" id="edit_color" name="color" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Delete -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-confirm">
            <div class="modal-header">
                <h3>Confirmer la suppression</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <p>Êtes-vous sûr de vouloir supprimer cette catégorie ?</p>
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

    <!-- Modal Emoji Picker -->
    <div id="emojiPickerModal" class="modal">
        <div class="modal-content modal-emoji">
            <div class="modal-header">
                <h3>Choisir une icône</h3>
                <button class="modal-close" onclick="closeEmojiPicker()">&times;</button>
            </div>
            <div class="emoji-grid" id="emojiGrid"></div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script>
        const usedIcons = <?= json_encode($usedIcons) ?>;

        const emojis = {
            finance: ['💰', '💵', '💴', '💶', '💷', '💳', '💸', '🏦', '📊', '📈', '📉', '💹'],
            maison: ['🏠', '🏡', '🏘️', '🏚️', '⚡', '💡', '🚰', '🔧', '🔨', '🪛', '🧰'],
            transport: ['🚗', '🚕', '🚙', '🚌', '🚎', '🏎️', '🚓', '🚑', '🚒', '🚐', '🛻', '🚚', '🚛', '🚜', '🏍️', '🛵', '🚲', '✈️', '🚁', '⛴️', '🚢', '⛽'],
            nourriture: ['🍕', '🍔', '🍟', '🌭', '🍿', '🥗', '🍱', '🍜', '🍝', '🍛', '🍣', '🍞', '🧀', '🥐', '🥖', '🥨', '🥯', '🥞', '🧇', '🍖', '🍗', '🥩', '🍖'],
            shopping: ['🛒', '🛍️', '👕', '👔', '👗', '👘', '👙', '👚', '👛', '👜', '🎒', '👞', '👟', '👠', '👡', '👢'],
            sante: ['💊', '💉', '🩺', '🩹', '🏥', '⚕️', '🔬', '🧬', '🧪', '🩻', '🧑‍⚕️'],
            loisirs: ['🎮', '🎯', '🎲', '🎰', '🎳', '🎸', '🎹', '🎺', '🎻', '🥁', '🎬', '🎭', '🎪', '🎨', '🖼️', '📚', '📖', '✏️', '📝', '⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🏉'],
            travail: ['💼', '👔', '📊', '📈', '💻', '⌨️', '🖥️', '🖨️', '📱', '📞', '☎️', '📠', '📧'],
            famille: ['👨‍👩‍👧‍👦', '👶', '🧒', '👦', '👧', '🧑', '👨', '👩', '🧓', '👴', '👵', '💑', '👪', '🎓', '🏫'],
            assurance: ['🛡️', '🔒', '🔐', '🔑', '🗝️', '⚠️', '☂️'],
            impots: ['🏛️', '📜', '📋', '🧾', '📄', '📃', '🗂️', '📁'],
            abonnements: ['📱', '📺', '📻', '📡', '💿', '📀', '🎵', '🎶', '🎧', '📞', '☎️', '💌'],
            autres: ['📁', '📂', '🗃️', '📋', '📊', '📌', '📍', '✅', '❌', '⭐', '🌟', '💫', '✨', '🔔', '🔕', '🎁', '🎀']
        };

        let currentEmojiInput = null;

        function openEmojiPicker(inputId) {
            currentEmojiInput = inputId;
            const grid = document.getElementById('emojiGrid');
            grid.innerHTML = '';

            Object.keys(emojis).forEach(category => {
                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'emoji-category';

                const title = document.createElement('h4');
                title.textContent = category.charAt(0).toUpperCase() + category.slice(1);
                categoryDiv.appendChild(title);

                const emojiContainer = document.createElement('div');
                emojiContainer.className = 'emoji-list';

                emojis[category].forEach(emoji => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'emoji-item-wrapper';

                    const emojiBtn = document.createElement('button');
                    emojiBtn.type = 'button';
                    emojiBtn.className = 'emoji-item';
                    emojiBtn.textContent = emoji;

                    // Vérifier si l'icône est déjà utilisée
                    if (usedIcons[emoji]) {
                        emojiBtn.classList.add('emoji-used');
                        const badge = document.createElement('span');
                        badge.className = 'emoji-badge';
                        badge.textContent = usedIcons[emoji].length;
                        wrapper.appendChild(badge);

                        // Créer le tooltip
                        const tooltip = document.createElement('div');
                        tooltip.className = 'emoji-tooltip';
                        tooltip.innerHTML = `<strong>Utilisé par :</strong><br>${usedIcons[emoji].join('<br>')}`;
                        wrapper.appendChild(tooltip);

                        // Afficher/masquer le tooltip au survol avec repositionnement
                        wrapper.addEventListener('mouseenter', () => {
                            tooltip.style.display = 'block';

                            // Vérifier si le tooltip dépasse en haut
                            const rect = tooltip.getBoundingClientRect();
                            const gridRect = grid.getBoundingClientRect();

                            if (rect.top < gridRect.top) {
                                // Afficher en dessous au lieu d'au-dessus
                                tooltip.style.bottom = 'auto';
                                tooltip.style.top = 'calc(100% + 10px)';
                                tooltip.classList.add('emoji-tooltip-below');
                            } else {
                                tooltip.style.bottom = 'calc(100% + 10px)';
                                tooltip.style.top = 'auto';
                                tooltip.classList.remove('emoji-tooltip-below');
                            }
                        });
                        wrapper.addEventListener('mouseleave', () => {
                            tooltip.style.display = 'none';
                        });
                    }

                    emojiBtn.onclick = () => selectEmoji(emoji);

                    wrapper.appendChild(emojiBtn);
                    emojiContainer.appendChild(wrapper);
                });

                categoryDiv.appendChild(emojiContainer);
                grid.appendChild(categoryDiv);
            });

            document.getElementById('emojiPickerModal').style.display = 'flex';
        }

        function closeEmojiPicker() {
            document.getElementById('emojiPickerModal').style.display = 'none';
        }

        function selectEmoji(emoji) {
            if (currentEmojiInput) {
                document.getElementById(currentEmojiInput).value = emoji;
                closeEmojiPicker();
            }
        }

        function showEmojiInfo(emoji, categories) {
            const message = `${emoji} est déjà utilisé par : ${categories.join(', ')}`;

            // Créer une notification temporaire
            const notification = document.createElement('div');
            notification.className = 'emoji-notification';
            notification.textContent = message;
            document.body.appendChild(notification);

            // Afficher avec animation
            setTimeout(() => notification.classList.add('show'), 10);

            // Masquer et supprimer après 3 secondes
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function openEditModal(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_type').value = category.type;
            document.getElementById('edit_icon').value = category.icon;
            document.getElementById('edit_color').value = category.color;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openDeleteModal(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Fermer les modales en cliquant en dehors
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            const emojiModal = document.getElementById('emojiPickerModal');
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
            if (event.target === emojiModal) {
                closeEmojiPicker();
            }
        }
    </script>
</body>
</html>
