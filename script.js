document.addEventListener('DOMContentLoaded', function () {
    // Fetch team data from PHP (injected as JSON)
    const teamData = JSON.parse(document.getElementById('teamData').textContent);

    // Prepare arrays to store data for the charts
    const teamNames = [];
    const marketValues = [];
    const efficiencies = [];
    const matchDates = [];
    const teamEfficienciesOverTime = [];

    // Calculate efficiency for each team and store results
    const calculatedTeams = teamData.map((team, index) => {
        const marketValue = team.market_value;
        const leagueWeight = team.league_weight;
        let cumulativeEfficiency = 100;

        // Effizienzänderungen über die Zeit
        const efficienciesOverTime = [];

        if (team.efficiency_changes) {
            const efficiencyChanges = team.efficiency_changes.split(',').map(Number);
            efficiencyChanges.forEach(change => {
                if (marketValue > 0) {
                    const adjustedChange = (change * leagueWeight) / marketValue;
                    cumulativeEfficiency += adjustedChange;
                }
                efficienciesOverTime.push(cumulativeEfficiency.toFixed(2));
            });
        }

        const finalEfficiency = efficienciesOverTime.length > 0 ? efficienciesOverTime[efficienciesOverTime.length - 1] : cumulativeEfficiency;

        // Speichern Sie die Effizienzen über die Zeit für das große Linechart
        teamEfficienciesOverTime.push({
            name: team.team_name,
            efficiencies: efficienciesOverTime
        });

        return {
            ...team,
            finalEfficiency: parseFloat(finalEfficiency),
            efficienciesOverTime: efficienciesOverTime
        };
    });

    // Sortiere die Teams nach finaler Effizienz
    calculatedTeams.sort((a, b) => b.finalEfficiency - a.finalEfficiency);

    // Aktualisiere die Team-Cards im HTML
    const teamsContainer = document.querySelector('.teams');
    const spacerElement = document.querySelector('.spacer');
    teamsContainer.innerHTML = '';
    teamsContainer.appendChild(spacerElement);

    calculatedTeams.forEach((team, rank) => {
        const teamCard = document.createElement('div');
        teamCard.classList.add('team-card');
        if (rank >= 6) {
            teamCard.classList.add('hidden-team');
            teamCard.style.display = 'none';
        }

        // HTML-Inhalte der Team-Card erstellen
        teamCard.innerHTML = `
            <a href="team-details.php?team_id=${encodeURIComponent(team.team_id)}" style="text-decoration: none; color: inherit;">
                <h3>${rank + 1}. ${team.team_name} (${team.tla})</h3>
                <img src="${team.crest_url}" alt="Wappen von ${team.team_name}" width="100">
                <p>Marktwert: ${team.market_value} Mio. €</p>
                <p>Liga-Gewichtung: ${team.league_weight}</p>
                <p>Trainer: ${team.coach_name} (${team.coach_nationality})</p>
                <p>Effizienz: ${team.finalEfficiency.toFixed(2)} %</p>
                <p>Siege: ${team.wins} | Niederlagen: ${team.losses} | Unentschieden: ${team.draws}</p>
            </a>
            <canvas id="lineChart-${team.team_id}" width="300" height="150"></canvas>
        `;

        teamsContainer.appendChild(teamCard);

        // Erstelle das Linechart für jedes Team
        const lineChartCtx = document.getElementById(`lineChart-${team.team_id}`).getContext('2d');
        new Chart(lineChartCtx, {
            type: 'line',
            data: {
                labels: team.efficienciesOverTime.map((_, i) => `Spiel ${i + 1}`),
                datasets: [{
                    label: `Effizienz über Zeit - ${team.team_name}`,
                    data: team.efficienciesOverTime,
                    fill: false,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Spiele'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Effizienz (%)'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });

    // Füge den Toggle-Button hinzu, um zusätzliche Teams ein-/auszublenden
    const toggleButton = document.createElement('button');
    toggleButton.id = 'toggleButton';
    toggleButton.classList.add('button');
    toggleButton.textContent = 'Mehr Teams anzeigen ▼';
    teamsContainer.appendChild(toggleButton);

    // Toggle visibility of additional teams
    toggleButton.addEventListener('click', function () {
        const hiddenTeams = document.querySelectorAll('.hidden-team');
        hiddenTeams.forEach(team => {
            if (team.style.display === 'none' || team.style.display === '') {
                team.style.display = 'block';
                this.textContent = 'Weniger Teams anzeigen ▲';
            } else {
                team.style.display = 'none';
                this.textContent = 'Mehr Teams anzeigen ▼';
            }
        });
    });

    // Daten für die beiden großen Charts vorbereiten
    calculatedTeams.forEach(team => {
        teamNames.push(team.team_name);
        marketValues.push(team.market_value);
        efficiencies.push(team.finalEfficiency);

        // Sammle Spieldaten für die Zeitachse
        if (!matchDates.length && team.first_match_date) {
            let startDate = new Date(team.first_match_date);
            let endDate = new Date(team.last_match_date);
            while (startDate <= endDate) {
                matchDates.push(startDate.toISOString().split('T')[0]);
                startDate.setDate(startDate.getDate() + 7);
            }
        }
    });

    // Create the bar chart (2 bars per team: Efficiency and Market Value)
    const barChartCtx = document.getElementById('efficiencyBarChart').getContext('2d');
    const efficiencyBarChart = new Chart(barChartCtx, {
        type: 'bar',
        data: {
            labels: teamNames,
            datasets: [
                {
                    label: 'Effizienz (%)',
                    data: efficiencies,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Marktwert (Mio. €)',
                    data: marketValues,
                    backgroundColor: 'rgba(255, 159, 64, 0.5)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Teams'
                    },
                    ticks: {
                        autoSkip: false
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Wert'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                }
            }
        }
    });

    // Create the line chart (Team performance over time: efficiency changes)
    const lineChartCtx = document.getElementById('performanceLineChart').getContext('2d');
    const performanceLineChart = new Chart(lineChartCtx, {
        type: 'line',
        data: {
            labels: matchDates,
            datasets: teamEfficienciesOverTime.map(team => ({
                label: team.name,
                data: team.efficiencies,
                fill: false,
                borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
                tension: 0.1
            }))
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Spieldatum'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Effizienz (%)'
                    }
                }
            }
        }
    });
});
