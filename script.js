// Fetching dynamic data from PHP (simulated here for example purposes)
const teamsData = <?php echo json_encode(array_column($teams, 'team_name')); ?>;
const efficiencyValues = <?php echo json_encode(array_map(function($team) {
    return calculate_efficiency($team['market_value'], $team['league_weight'], $team['wins'], $team['losses'], $team['draws']);
}, $teams)); ?>;

// Chart.js for the Efficiency Line Chart
const ctx = document.getElementById('efficiencyChart').getContext('2d');
const efficiencyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: teamsData,  // Team names
        datasets: [{
            label: 'Effizienz',
            data: efficiencyValues,  // Efficiency data calculated in PHP
            borderColor: 'rgba(75, 192, 192, 1)',
            fill: false,
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Effizienz'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Teams'
                }
            }
        }
    }
});
