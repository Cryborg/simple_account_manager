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

    // Fonction pour gérer le changement de type de récurrence
    function handleRecurrenceTypeChange(value, elements) {
        const { recurringMonthsGroup, endDateGroup, recurringMonthsInput, endDateInput, endDateCount } = elements;

        if (value === 'no_limit') {
            // Pas de limite : cacher les deux champs
            recurringMonthsGroup.style.display = 'none';
            endDateGroup.style.display = 'none';
            recurringMonthsInput.value = '0';
            endDateInput.value = '';
            endDateInput.removeAttribute('required');
            recurringMonthsInput.removeAttribute('required');
            if (endDateCount) endDateCount.textContent = '';
        } else if (value === 'count') {
            // Nombre d'occurrences : afficher le champ nombre, cacher la date
            recurringMonthsGroup.style.display = 'flex';
            endDateGroup.style.display = 'none';
            endDateInput.value = '';
            endDateInput.removeAttribute('required');
            recurringMonthsInput.value = '1'; // Valeur par défaut : 1 occurrence minimum
            recurringMonthsInput.disabled = false;
            recurringMonthsInput.setAttribute('required', 'required');
            if (endDateCount) endDateCount.textContent = '';
        } else {
            // Jusqu'à une date : afficher la date, cacher le nombre
            recurringMonthsGroup.style.display = 'none';
            endDateGroup.style.display = 'flex';
            recurringMonthsInput.value = '0';
            recurringMonthsInput.removeAttribute('required');
            endDateInput.disabled = false;
            endDateInput.setAttribute('required', 'required');
        }
    }

    // Fonction pour créer l'updater du nombre d'échéances
    function createOccurrencesUpdater(transactionDateInput, endDateInput, periodicitySelect, endDateCount) {
        return function() {
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
        };
    }

    // Fonction pour initialiser la gestion de récurrence
    function initRecurrenceHandling(prefix) {
        const radioName = prefix ? `${prefix}_recurrence_type` : 'recurrence_type';
        const getId = (id) => prefix ? `${prefix}_${id}` : id;

        const recurrenceTypeRadios = document.querySelectorAll(`input[name="${radioName}"]`);
        const recurringMonthsGroup = document.getElementById(getId('recurring_months_group'));
        const endDateGroup = document.getElementById(getId('end_date_group'));
        const recurringMonthsInput = document.getElementById(getId('recurring_months'));
        const endDateInput = document.getElementById(getId('end_date'));
        const endDateCount = document.getElementById(getId('end_date_count'));
        const transactionDateInput = document.getElementById(getId('transaction_date'));
        const periodicitySelect = document.getElementById(getId('periodicity'));

        if (recurrenceTypeRadios.length > 0) {
            const elements = {
                recurringMonthsGroup,
                endDateGroup,
                recurringMonthsInput,
                endDateInput,
                endDateCount
            };

            // Gérer le changement de type de récurrence
            recurrenceTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    handleRecurrenceTypeChange(this.value, elements);
                });
            });

            // Calculer en temps réel le nombre d'échéances
            if (endDateInput && endDateCount && transactionDateInput && periodicitySelect) {
                const updateOccurrencesCount = createOccurrencesUpdater(
                    transactionDateInput,
                    endDateInput,
                    periodicitySelect,
                    endDateCount
                );

                endDateInput.addEventListener('input', updateOccurrencesCount);
                transactionDateInput.addEventListener('input', updateOccurrencesCount);
                periodicitySelect.addEventListener('change', updateOccurrencesCount);
            }
        }
    }

    // Initialiser pour le formulaire d'ajout et d'édition
    initRecurrenceHandling(null); // Formulaire d'ajout
    initRecurrenceHandling('edit'); // Formulaire d'édition
});
