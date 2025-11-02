// Adapter le label "Nombre de mois" selon la périodicité
document.addEventListener('DOMContentLoaded', function() {
    const periodicitySelect = document.getElementById('periodicity');
    const editPeriodicitySelect = document.getElementById('edit_periodicity');
    const recurringLabel = document.querySelector('label[for="recurring_months"]');
    const editRecurringLabel = document.querySelector('label[for="edit_recurring_months"]');

    function updateRecurringLabel(periodicity, label) {
        if (!label) return;

        const tooltip = label.querySelector('.tooltip-icon');
        const baseText = periodicity === 'hebdo' ? 'Nombre de semaines' :
                        periodicity === 'annuel' ? 'Nombre d\'années' :
                        'Nombre de mois';

        label.childNodes[0].textContent = baseText + ' ';
    }

    if (periodicitySelect && recurringLabel) {
        periodicitySelect.addEventListener('change', function() {
            updateRecurringLabel(this.value, recurringLabel);
        });
    }

    if (editPeriodicitySelect && editRecurringLabel) {
        editPeriodicitySelect.addEventListener('change', function() {
            updateRecurringLabel(this.value, editRecurringLabel);
        });
    }
});
