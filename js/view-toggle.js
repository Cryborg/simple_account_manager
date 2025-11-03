// Toggle entre vue tableau et vue cartes
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.view-toggle-btn');
    const transactionsContainer = document.querySelector('.transactions');

    if (!toggleButtons.length || !transactionsContainer) return;

    // Charger la préférence sauvegardée
    const savedView = localStorage.getItem('transactionsView') || 'table';
    setView(savedView);

    // Gérer les clics sur les boutons
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            setView(view);
            localStorage.setItem('transactionsView', view);
        });
    });

    function setView(view) {
        // Mettre à jour l'attribut data-view
        transactionsContainer.setAttribute('data-view', view);

        // Mettre à jour les boutons actifs
        toggleButtons.forEach(btn => {
            if (btn.getAttribute('data-view') === view) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }
});
