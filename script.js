// Beispiel-Daten für das Diagramm (diese Daten kannst du dynamisch aus PHP generieren)
const efficiencyData = {
    labels: ['Team A', 'Team B', 'Team C'],  // Diese Labels können dynamisch aus PHP kommen
    datasets: [{
        label: 'Effizienz',
        data: [0.8, 0.7, 0.6],  // Diese Werte kannst du ebenfalls dynamisch aus PHP generieren
        backgroundColor: ['rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 159, 64, 0.2)'],
        borderColor: ['rgba(75, 192, 192, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 159, 64, 1)'],
        borderWidth: 1
    }]
};

// Chart.js für das Effizienz-Diagramm
const ctx = document.getElementById('efficiencyChart').getContext('2d');
const efficiencyChart = new Chart(ctx, {
    type: 'bar',
    data: efficiencyData,
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
