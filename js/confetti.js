// Confettis sur grosse recette (> 1000€)
// Utilisation d'une implémentation vanilla simple

function triggerConfetti() {
    const count = 200;
    const defaults = {
        origin: { y: 0.7 }
    };

    function fire(particleRatio, opts) {
        const canvas = document.createElement('canvas');
        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '99999';
        document.body.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const particles = [];
        const particleCount = count * particleRatio;

        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height - canvas.height,
                vx: (Math.random() - 0.5) * 5,
                vy: Math.random() * 3 + 2,
                rotation: Math.random() * 360,
                rotationSpeed: (Math.random() - 0.5) * 10,
                size: Math.random() * 8 + 4,
                color: opts.colors[Math.floor(Math.random() * opts.colors.length)]
            });
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach((p, i) => {
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.1; // gravity
                p.rotation += p.rotationSpeed;

                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rotation * Math.PI / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
                ctx.restore();

                if (p.y > canvas.height) {
                    particles.splice(i, 1);
                }
            });

            if (particles.length > 0) {
                requestAnimationFrame(animate);
            } else {
                document.body.removeChild(canvas);
            }
        }

        animate();
    }

    fire(0.25, {
        spread: 26,
        startVelocity: 55,
        ...defaults,
        colors: ['#4caf50', '#66bb6a', '#81c784', '#ffd700', '#ffeb3b']
    });

    fire(0.2, {
        spread: 60,
        ...defaults,
        colors: ['#4caf50', '#66bb6a', '#81c784', '#ffd700', '#ffeb3b']
    });

    fire(0.35, {
        spread: 100,
        decay: 0.91,
        scalar: 0.8,
        ...defaults,
        colors: ['#4caf50', '#66bb6a', '#81c784', '#ffd700', '#ffeb3b']
    });

    fire(0.1, {
        spread: 120,
        startVelocity: 25,
        decay: 0.92,
        scalar: 1.2,
        ...defaults,
        colors: ['#4caf50', '#66bb6a', '#81c784', '#ffd700', '#ffeb3b']
    });

    fire(0.1, {
        spread: 120,
        startVelocity: 45,
        ...defaults,
        colors: ['#4caf50', '#66bb6a', '#81c784', '#ffd700', '#ffeb3b']
    });
}

// Détecter l'ajout d'une grosse recette
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si on vient d'ajouter une transaction
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('type') === 'recette') {
        // Récupérer la dernière recette ajoutée
        const transactions = document.querySelectorAll('tbody tr');
        if (transactions.length > 0) {
            const firstTransaction = transactions[0];
            const amountCell = firstTransaction.querySelector('.amount.recette');
            if (amountCell) {
                const amountText = amountCell.textContent.trim();
                const amount = parseFloat(amountText.replace(/[^\d,]/g, '').replace(',', '.'));

                // Si > 1000€, lancer les confettis !
                if (amount > 1000) {
                    setTimeout(() => {
                        triggerConfetti();
                    }, 300);
                }
            }
        }
    }
});
