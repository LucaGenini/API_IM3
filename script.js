const efficiencyChart = new Chart(ctx, {
    type: 'bar',  // Change 'line' to 'bar'
    data: {
        labels: teamsData,  // Team names for x-axis
        datasets: [{
            label: 'Effizienz',
            data: efficiencyValues,  // Efficiency data
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
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
