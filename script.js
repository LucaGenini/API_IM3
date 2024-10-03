const ctx = document.getElementById('efficiencyChart').getContext('2d');
const efficiencyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Spiel 1', 'Spiel 2', 'Spiel 3', 'Spiel 4', 'Spiel 5'],
        datasets: [
            {
                label: 'Team A (Premier League)',
                data: [0.8, 0.7, 0.9, 0.85, 0.8],
                borderColor: 'rgba(54, 162, 235, 1)',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Team B (Eredivisie)',
                data: [0.7, 0.6, 0.75, 0.65, 0.6],
                borderColor: 'rgba(255, 159, 64, 1)',
                fill: false,
                tension: 0.1
            },
            {
                label: 'Team C (Kroatien)',
                data: [0.6, 0.55, 0.65, 0.6, 0.55],
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false,
                tension: 0.1
            }
        ]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Effizienz'
                }
            }
        }
    }
});
