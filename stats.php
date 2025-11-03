<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Récupérer les dépenses par catégorie (incluant les récurrences)
$currentDate = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? AND type = 'depense'");
$stmt->execute([$userId]);
$allExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$expensesByCategory = [];

foreach ($allExpenses as $t) {
    if ($t['transaction_date'] > $currentDate) {
        continue;
    }

    $amount = (float) $t['amount'];
    $count = 1;

    if ($t['recurring_months'] > 1) {
        if ($t['periodicity'] === 'mensuel') {
            $elapsed = floor((strtotime($currentDate) - strtotime($t['transaction_date'])) / (30.44 * 86400)) + 1;
            $count = min($t['recurring_months'], $elapsed);
        } elseif ($t['periodicity'] === 'hebdo') {
            $elapsed = floor((strtotime($currentDate) - strtotime($t['transaction_date'])) / (7 * 86400)) + 1;
            $count = min($t['recurring_months'], $elapsed);
        }
    } elseif ($t['recurring_months'] === 0 && ($t['periodicity'] === 'mensuel' || $t['periodicity'] === 'hebdo')) {
        $endDate = $t['end_date'] ? min($t['end_date'], $currentDate) : $currentDate;

        if ($t['periodicity'] === 'mensuel') {
            $count = floor((strtotime($endDate) - strtotime($t['transaction_date'])) / (30.44 * 86400)) + 1;
        } elseif ($t['periodicity'] === 'hebdo') {
            $count = floor((strtotime($endDate) - strtotime($t['transaction_date'])) / (7 * 86400)) + 1;
        }
    }

    $total = $amount * $count;

    // Récupérer le nom de la catégorie
    $categoryName = $t['category_id'] ?
        $db->query("SELECT name FROM categories WHERE id = " . $t['category_id'])->fetchColumn() :
        'Sans catégorie';

    if (!isset($expensesByCategory[$categoryName])) {
        $expensesByCategory[$categoryName] = 0;
    }
    $expensesByCategory[$categoryName] += $total;
}

// Trier par montant décroissant
arsort($expensesByCategory);

// Récupérer les recettes/dépenses par mois (12 derniers mois)
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyData[$month] = ['recettes' => 0, 'depenses' => 0];
}

$stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ?");
$stmt->execute([$userId]);
$allTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allTransactions as $t) {
    $startDate = new DateTime($t['transaction_date']);
    $today = new DateTime();

    // Pour chaque mois des 12 derniers mois
    foreach ($monthlyData as $month => $data) {
        $monthStart = new DateTime($month . '-01');
        $monthEnd = new DateTime($month . '-01');
        $monthEnd->modify('last day of this month');

        // Vérifier si la transaction est active ce mois-là
        $isActive = false;

        if ($t['periodicity'] === 'mensuel' && $t['recurring_months'] > 1) {
            // Récurrence limitée
            $elapsed = floor(($monthStart->getTimestamp() - $startDate->getTimestamp()) / (30.44 * 86400));
            if ($elapsed >= 0 && $elapsed < $t['recurring_months']) {
                $isActive = true;
            }
        } elseif ($t['periodicity'] === 'mensuel' && $t['recurring_months'] === 0) {
            // Récurrence infinie
            if ($startDate <= $monthEnd && (!$t['end_date'] || new DateTime($t['end_date']) >= $monthStart)) {
                $isActive = true;
            }
        } elseif ($t['periodicity'] === 'annuel' && $startDate->format('Y-m') === $month) {
            // Annuel : seulement le mois exact
            $isActive = true;
        } elseif ($t['recurring_months'] === 1 && $startDate->format('Y-m') === $month) {
            // Ponctuel : seulement le mois exact
            $isActive = true;
        }

        if ($isActive) {
            if ($t['type'] === 'recette') {
                $monthlyData[$month]['recettes'] += $t['amount'];
            } else {
                $monthlyData[$month]['depenses'] += $t['amount'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Mes Comptes</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=<?= CSS_VERSION ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <header>
            <h1>Statistiques</h1>
        </header>

        <div class="stats-section">
            <div class="chart-card">
                <h3>Dépenses par catégorie</h3>
                <canvas id="categoryChart"></canvas>
            </div>

            <div class="chart-card">
                <h3>Évolution mensuelle (12 derniers mois)</h3>
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script>
        // Graphique des dépenses par catégorie
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($expensesByCategory)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($expensesByCategory)) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)',
                        'rgba(255, 99, 255, 0.8)',
                        'rgba(99, 255, 132, 0.8)'
                    ],
                    borderColor: '#1a1a1a',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e0e0e0',
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.toFixed(2) + ' €';
                            }
                        }
                    }
                }
            }
        });

        // Graphique évolution mensuelle
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($monthlyData)) ?>,
                datasets: [
                    {
                        label: 'Recettes',
                        data: <?= json_encode(array_column($monthlyData, 'recettes')) ?>,
                        borderColor: 'rgba(76, 175, 80, 1)',
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Dépenses',
                        data: <?= json_encode(array_column($monthlyData, 'depenses')) ?>,
                        borderColor: 'rgba(244, 67, 54, 1)',
                        backgroundColor: 'rgba(244, 67, 54, 0.2)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#e0e0e0',
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' €';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e0e0e0',
                            callback: function(value) {
                                return value + ' €';
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
