// Gestion du choix du type de récurrence avec radio buttons
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour calculer le nombre d'échéances
    function calculateOccurrences(startDate, endDate, periodicity) {
        if (!startDate || !endDate) return 0;

        const start = new Date(startDate);
        const end = new Date(endDate);

        if (end <= start) return 0;

        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        switch(periodicity) {
            case 'hebdo':
                return Math.floor(diffDays / 7) + 1;
            case 'mensuel':
                return Math.floor(diffDays / 30.44) + 1;
            case 'annuel':
                return Math.floor(diffDays / 365.25) + 1;
            default:
                return 0;
        }
    }

    // Formulaire d'ajout
    const recurrenceTypeRadios = document.querySelectorAll('input[name="recurrence_type"]');
    const recurringMonthsGroup = document.getElementById('recurring_months_group');
    const endDateGroup = document.getElementById('end_date_group');
    const recurringMonthsInput = document.getElementById('recurring_months');
    const endDateInput = document.getElementById('end_date');
    const endDateCount = document.getElementById('end_date_count');
    const transactionDateInput = document.getElementById('transaction_date');
    const periodicitySelect = document.getElementById('periodicity');

    if (recurrenceTypeRadios.length > 0) {
        recurrenceTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'count') {
                    recurringMonthsGroup.style.display = 'flex';
                    endDateGroup.style.display = 'none';
                    endDateInput.value = '';
                    recurringMonthsInput.disabled = false;
                    if (endDateCount) endDateCount.textContent = '';
                } else {
                    recurringMonthsGroup.style.display = 'none';
                    endDateGroup.style.display = 'flex';
                    recurringMonthsInput.value = '0';
                    endDateInput.disabled = false;
                }
            });
        });

        // Calculer en temps réel le nombre d'échéances
        if (endDateInput && endDateCount && transactionDateInput && periodicitySelect) {
            function updateOccurrencesCount() {
                const startDate = transactionDateInput.value;
                const endDate = endDateInput.value;
                const periodicity = periodicitySelect.value;

                if (startDate && endDate) {
                    const count = calculateOccurrences(startDate, endDate, periodicity);
                    const periodicityLabel = periodicity === 'hebdo' ? 'semaines' :
                                            periodicity === 'mensuel' ? 'mois' : 'ans';
                    endDateCount.textContent = count > 0 ? `≈ ${count} ${periodicityLabel}` : 'Date de fin invalide';
                    endDateCount.style.color = count > 0 ? 'var(--accent)' : 'var(--danger)';
                } else {
                    endDateCount.textContent = '';
                }
            }

            endDateInput.addEventListener('input', updateOccurrencesCount);
            transactionDateInput.addEventListener('input', updateOccurrencesCount);
            periodicitySelect.addEventListener('change', updateOccurrencesCount);
        }
    }

    // Formulaire d'édition
    const editRecurrenceTypeRadios = document.querySelectorAll('input[name="edit_recurrence_type"]');
    const editRecurringMonthsGroup = document.getElementById('edit_recurring_months_group');
    const editEndDateGroup = document.getElementById('edit_end_date_group');
    const editRecurringMonthsInput = document.getElementById('edit_recurring_months');
    const editEndDateInput = document.getElementById('edit_end_date');
    const editEndDateCount = document.getElementById('edit_end_date_count');
    const editTransactionDateInput = document.getElementById('edit_transaction_date');
    const editPeriodicitySelect = document.getElementById('edit_periodicity');

    if (editRecurrenceTypeRadios.length > 0) {
        editRecurrenceTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'count') {
                    editRecurringMonthsGroup.style.display = 'flex';
                    editEndDateGroup.style.display = 'none';
                    editEndDateInput.value = '';
                    editRecurringMonthsInput.disabled = false;
                    if (editEndDateCount) editEndDateCount.textContent = '';
                } else {
                    editRecurringMonthsGroup.style.display = 'none';
                    editEndDateGroup.style.display = 'flex';
                    editRecurringMonthsInput.value = '0';
                    editEndDateInput.disabled = false;
                }
            });
        });

        // Calculer en temps réel le nombre d'échéances (modale édition)
        if (editEndDateInput && editEndDateCount && editTransactionDateInput && editPeriodicitySelect) {
            function updateEditOccurrencesCount() {
                const startDate = editTransactionDateInput.value;
                const endDate = editEndDateInput.value;
                const periodicity = editPeriodicitySelect.value;

                if (startDate && endDate) {
                    const count = calculateOccurrences(startDate, endDate, periodicity);
                    const periodicityLabel = periodicity === 'hebdo' ? 'semaines' :
                                            periodicity === 'mensuel' ? 'mois' : 'ans';
                    editEndDateCount.textContent = count > 0 ? `≈ ${count} ${periodicityLabel}` : 'Date de fin invalide';
                    editEndDateCount.style.color = count > 0 ? 'var(--accent)' : 'var(--danger)';
                } else {
                    editEndDateCount.textContent = '';
                }
            }

            editEndDateInput.addEventListener('input', updateEditOccurrencesCount);
            editTransactionDateInput.addEventListener('input', updateEditOccurrencesCount);
            editPeriodicitySelect.addEventListener('change', updateEditOccurrencesCount);
        }
    }
});
