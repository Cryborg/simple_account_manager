// Animation du compteur pour les stats
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.counter');

    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target'));
        const prefix = counter.getAttribute('data-prefix') || '';
        const duration = 1000; // 1 seconde
        const fps = 60;
        const frames = duration / (1000 / fps);
        const increment = target / frames;
        let current = 0;
        let frame = 0;

        const updateCounter = () => {
            if (frame < frames) {
                current += increment;
                frame++;

                // Formater le nombre : ajouter espaces pour milliers et virgule pour décimales
                const formatted = Math.abs(current).toLocaleString('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Ajouter le signe négatif si la valeur est négative et qu'il n'y a pas de prefix
                const sign = (target < 0 && prefix === '') ? '-' : '';
                counter.textContent = `${prefix}${sign}${formatted} €`;
                requestAnimationFrame(updateCounter);
            } else {
                // Valeur finale exacte
                const formatted = Math.abs(target).toLocaleString('fr-FR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                // Ajouter le signe négatif si la valeur est négative et qu'il n'y a pas de prefix
                const sign = (target < 0 && prefix === '') ? '-' : '';
                counter.textContent = `${prefix}${sign}${formatted} €`;
            }
        };

        updateCounter();
    });
});
