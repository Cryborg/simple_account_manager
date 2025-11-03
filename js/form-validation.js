// Validation visuelle en temps réel des champs du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.add-transaction form');
    if (!form) return;

    // Champs à valider
    const amountInput = document.getElementById('amount');
    const dateInput = document.getElementById('transaction_date');
    const typeSelect = document.getElementById('type');

    function addValidationFeedback(input) {
        // Ne pas ajouter si déjà existant
        if (input.parentElement.querySelector('.validation-check')) return;

        const checkmark = document.createElement('span');
        checkmark.className = 'validation-check';
        checkmark.innerHTML = '✓';
        checkmark.style.display = 'none';
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(checkmark);
    }

    function validateField(input) {
        const checkmark = input.parentElement.querySelector('.validation-check');
        if (!checkmark) return;

        let isValid = false;

        if (input.type === 'number') {
            const value = parseFloat(input.value);
            isValid = value > 0;
        } else if (input.type === 'date') {
            isValid = input.value !== '';
        } else if (input.tagName === 'SELECT') {
            isValid = input.value !== '';
        }

        if (isValid) {
            checkmark.style.display = 'block';
            checkmark.classList.add('show');
            input.style.borderColor = 'var(--success)';
        } else {
            checkmark.style.display = 'none';
            checkmark.classList.remove('show');
            input.style.borderColor = '';
        }
    }

    // Ajouter les indicateurs
    if (amountInput) {
        addValidationFeedback(amountInput);
        amountInput.addEventListener('input', () => validateField(amountInput));
    }

    if (dateInput) {
        addValidationFeedback(dateInput);
        dateInput.addEventListener('input', () => validateField(dateInput));
        dateInput.addEventListener('change', () => validateField(dateInput));
    }

    if (typeSelect) {
        addValidationFeedback(typeSelect);
        typeSelect.addEventListener('change', () => validateField(typeSelect));
    }

    // Validation initiale si les champs ont déjà des valeurs
    if (amountInput) validateField(amountInput);
    if (dateInput) validateField(dateInput);
    if (typeSelect) validateField(typeSelect);
});
