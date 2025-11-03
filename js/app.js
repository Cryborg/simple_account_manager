// Gestion des modales
function openEditModal(transaction) {
    const modal = document.getElementById('editModal');
    document.getElementById('edit_id').value = transaction.id;
    document.getElementById('edit_type').value = transaction.type;
    document.getElementById('edit_amount').value = transaction.amount;
    document.getElementById('edit_transaction_date').value = transaction.transaction_date;
    document.getElementById('edit_description').value = transaction.description || '';
    document.getElementById('edit_periodicity').value = transaction.periodicity || 'mensuel';
    document.getElementById('edit_category').value = transaction.category_name || '';
    document.getElementById('edit_recurring_months').value = transaction.recurring_months || 0;
    document.getElementById('edit_end_date').value = transaction.end_date || '';

    // Charger les catégories correspondant au type de transaction
    loadCategories(transaction.type);

    // Cocher le bon radio et afficher le bon champ
    const noLimitRadio = document.querySelector('input[name="edit_recurrence_type"][value="no_limit"]');
    const countRadio = document.querySelector('input[name="edit_recurrence_type"][value="count"]');
    const dateRadio = document.querySelector('input[name="edit_recurrence_type"][value="date"]');
    const editRecurringMonthsGroup = document.getElementById('edit_recurring_months_group');
    const editEndDateGroup = document.getElementById('edit_end_date_group');

    if (transaction.end_date) {
        // Cas 1 : date de fin définie → "Jusqu'à une date"
        dateRadio.checked = true;
        editRecurringMonthsGroup.style.display = 'none';
        editEndDateGroup.style.display = 'flex';
    } else if (transaction.recurring_months >= 1) {
        // Cas 2 : nombre d'occurrences défini → "Nombre d'occurrences"
        countRadio.checked = true;
        editRecurringMonthsGroup.style.display = 'flex';
        editEndDateGroup.style.display = 'none';
    } else {
        // Cas 3 : pas de limite (recurring_months = 0, pas de end_date) → "Pas de limite"
        noLimitRadio.checked = true;
        editRecurringMonthsGroup.style.display = 'none';
        editEndDateGroup.style.display = 'none';
    }

    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openDeleteModal(transaction) {
    const modal = document.getElementById('deleteModal');
    document.getElementById('delete_id').value = transaction.id;

    // Afficher les informations de la transaction
    const infoDiv = document.getElementById('deleteTransactionInfo');
    const typeLabel = transaction.type === 'recette' ? 'Recette' : 'Dépense';
    const typeClass = transaction.type;
    const amount = parseFloat(transaction.amount).toFixed(2).replace('.', ',');
    const sign = transaction.type === 'recette' ? '+' : '-';

    infoDiv.innerHTML = `
        <div class="delete-info-item">
            <span class="delete-info-label">Type :</span>
            <span class="badge ${typeClass}">${typeLabel}</span>
        </div>
        <div class="delete-info-item">
            <span class="delete-info-label">Description :</span>
            <strong>${transaction.description || '-'}</strong>
        </div>
        <div class="delete-info-item">
            <span class="delete-info-label">Montant :</span>
            <strong class="amount ${typeClass}">${sign} ${amount} €</strong>
        </div>
    `;

    modal.style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Fermer la modale en cliquant en dehors
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}

// Fermer avec la touche Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
        closeDeleteModal();
    }
});

// Autocomplétion pour les catégories
let currentCategories = [];

async function loadCategories(type) {
    try {
        const response = await fetch(`api/categories.php?type=${type}`);
        currentCategories = await response.json();
    } catch (error) {
        console.error('Erreur lors du chargement des catégories:', error);
        currentCategories = [];
    }
}

function setupAutocomplete(inputId, listId) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);

    if (!input || !list) return;

    function showCategories() {
        const value = input.value.toLowerCase();
        list.innerHTML = '';

        const filtered = value
            ? currentCategories.filter(cat => cat.name.toLowerCase().includes(value))
            : currentCategories;

        if (filtered.length === 0) {
            list.style.display = 'none';
            return;
        }

        filtered.forEach(category => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';

            // Créer un span pour l'icône
            if (category.icon) {
                const icon = document.createElement('span');
                icon.textContent = category.icon + ' ';
                icon.style.fontSize = '1.2rem';
                item.appendChild(icon);
            }

            // Ajouter le nom
            const name = document.createElement('span');
            name.textContent = category.name;
            item.appendChild(name);

            item.addEventListener('click', function() {
                input.value = category.name;
                list.style.display = 'none';
            });
            list.appendChild(item);
        });

        list.style.display = 'block';
    }

    input.addEventListener('focus', showCategories);
    input.addEventListener('input', showCategories);

    input.addEventListener('blur', function() {
        setTimeout(() => {
            list.style.display = 'none';
        }, 200);
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const editTypeSelect = document.getElementById('edit_type');

    // Charger les catégories au chargement de la page
    if (typeSelect) {
        loadCategories(typeSelect.value);
        setupAutocomplete('category', 'category_list');

        typeSelect.addEventListener('change', function() {
            loadCategories(this.value);
        });
    }

    if (editTypeSelect) {
        setupAutocomplete('edit_category', 'edit_category_list');

        editTypeSelect.addEventListener('change', function() {
            loadCategories(this.value);
            // Vider le champ catégorie car les catégories ont changé
            document.getElementById('edit_category').value = '';
        });
    }

    // Focus sur le montant après ajout
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('type')) {
        const amountInput = document.getElementById('amount');
        if (amountInput) {
            amountInput.focus();
        }
    }

    // Initialiser l'état du bloc "Ajouter une transaction"
    initAddTransactionToggle();

    // Auto-fermeture des messages flash après 5 secondes
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
            message.style.opacity = '0';
            message.style.transform = 'translateX(100%)';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
});

// Gestion du toggle du bloc "Ajouter une transaction"
function toggleAddTransaction() {
    const content = document.getElementById('addTransactionContent');
    const btn = document.getElementById('toggleAddTransactionBtn');

    if (!content || !btn) return;

    const isCollapsed = content.classList.contains('collapsed');

    if (isCollapsed) {
        content.classList.remove('collapsed');
        btn.classList.remove('collapsed');
        localStorage.setItem('addTransactionCollapsed', 'false');
    } else {
        content.classList.add('collapsed');
        btn.classList.add('collapsed');
        localStorage.setItem('addTransactionCollapsed', 'true');
    }
}

function initAddTransactionToggle() {
    const content = document.getElementById('addTransactionContent');
    const btn = document.getElementById('toggleAddTransactionBtn');

    if (!content || !btn) return;

    // Par défaut : visible (donc pas collapsed)
    // Mais on vérifie si l'utilisateur a sauvegardé une préférence
    const isCollapsed = localStorage.getItem('addTransactionCollapsed') === 'true';

    if (isCollapsed) {
        content.classList.add('collapsed');
        btn.classList.add('collapsed');
    }
}
