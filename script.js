document.addEventListener('DOMContentLoaded', function () {
    // Fetch team data from PHP (injected as JSON)
    const teamData = JSON.parse(document.getElementById('teamData').textContent);

    // Arrays to store data for the charts
    let teamNames = [];
    let marketValues = [];
    let efficiencies = [];
    let matchDates = [];
    let teamEfficienciesOverTime = [];

    // Calculate efficiency for each team and store results
    const calculatedTeams = teamData.map((team) => {
        const marketValue = team.market_value;
        const leagueWeight = team.league_weight;
        let cumulativeEfficiency = 100;

        // Efficiency changes over time
        const efficienciesOverTime = [];
        if (team.efficiency_changes) {
            const efficiencyChanges = team.efficiency_changes.split(',').map(Number);
            efficiencyChanges.forEach((change) => {
                if (marketValue > 0) {
                    const adjustedChange = (change * leagueWeight) / (marketValue / 100);
                    cumulativeEfficiency += adjustedChange;
                }
                efficienciesOverTime.push(cumulativeEfficiency.toFixed(2));
            });
        }

        const finalEfficiency = efficienciesOverTime.length > 0 ? efficienciesOverTime[efficienciesOverTime.length - 1] : cumulativeEfficiency;

        // Store efficiency changes over time for the line chart
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

    // Sort teams by final efficiency
    calculatedTeams.sort((a, b) => b.finalEfficiency - a.finalEfficiency);

    // Populate team names, market values, and efficiencies for both charts
    calculatedTeams.forEach((team) => {
        teamNames.push(team.team_name);
        marketValues.push(team.market_value);
        efficiencies.push(team.finalEfficiency);

        if (!matchDates.length && team.first_match_date) {
            let startDate = new Date(team.first_match_date);
            let endDate = new Date(team.last_match_date);
            while (startDate <= endDate) {
                matchDates.push(startDate.toISOString().split('T')[0]);
                startDate.setDate(startDate.getDate() + 7);
            }
        }
    });

    // Initialize Bar Chart (Market Value and Efficiency Comparison)
    const barChartCtx = document.getElementById('efficiencyBarChart').getContext('2d');
    const barChart = new Chart(barChartCtx, {
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

    // Initialize Line Chart (Performance Over Time with y-axis range from 90 to 150)
    const lineChartCtx = document.getElementById('performanceLineChart').getContext('2d');
    const lineChart = new Chart(lineChartCtx, {
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
                    min: 90, // Set minimum y-axis value to 90
                    max: 150, // Set maximum y-axis value to 150
                    title: {
                        display: true,
                        text: 'Effizienz (%)'
                    },
                    beginAtZero: false
                }
            }
        }
    });

    // Utility function to filter by top 5
    function filterTop5TeamsByMetric(metric) {
        const sortedTeams = [...calculatedTeams].sort((a, b) => b[metric] - a[metric]);
        return sortedTeams.slice(0, 5);
    }

    // Utility function to update both charts with the filtered teams
    function updateBothCharts(filteredTeams) {
        const filteredTeamNames = filteredTeams.map(team => team.team_name);
        const filteredEfficiencies = filteredTeams.map(team => team.finalEfficiency);
        const filteredMarketValues = filteredTeams.map(team => team.market_value);

        // Update Bar Chart
        barChart.data.labels = filteredTeamNames;
        barChart.data.datasets[0].data = filteredEfficiencies;
        barChart.data.datasets[1].data = filteredMarketValues;
        barChart.update();

        // Update Line Chart
        const filteredEfficienciesOverTime = filteredTeams.map(team => {
            const teamData = teamEfficienciesOverTime.find(t => t.name === team.team_name);
            return {
                label: teamData.name,
                data: teamData.efficiencies,
                fill: false,
                borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
                tension: 0.1
            };
        });
        lineChart.data.datasets = filteredEfficienciesOverTime;
        lineChart.update();
    }

    // Bar Chart filters
    document.getElementById('topEfficiencyBtn').addEventListener('click', function () {
        const top5EfficientTeams = filterTop5TeamsByMetric('finalEfficiency');
        updateBothCharts(top5EfficientTeams);
    });

    document.getElementById('topMarketValueBtn').addEventListener('click', function () {
        const top5MarketValueTeams = filterTop5TeamsByMetric('market_value');
        updateBothCharts(top5MarketValueTeams);
    });

    document.getElementById('allTeamsBtn').addEventListener('click', function () {
        updateBothCharts(calculatedTeams); // Reset to show all teams
    });

    // Time-based filters for the line chart
    function filterByTime(startDate) {
        const filteredMatchDates = matchDates.filter(date => new Date(date) >= startDate);
        lineChart.data.labels = filteredMatchDates;

        teamEfficienciesOverTime.forEach((teamData, index) => {
            const filteredEfficiencies = teamData.efficiencies.slice(-filteredMatchDates.length);
            lineChart.data.datasets[index].data = filteredEfficiencies;
        });

        lineChart.update();
    }

    document.getElementById('thisYearBtn').addEventListener('click', function () {
        const thisYear = new Date(new Date().getFullYear(), 0, 1);
        filterByTime(thisYear);
    });

    document.getElementById('last6MonthsBtn').addEventListener('click', function () {
        const sixMonthsAgo = new Date();
        sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
        filterByTime(sixMonthsAgo);
    });

    document.getElementById('lastMonthBtn').addEventListener('click', function () {
        const oneMonthAgo = new Date();
        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
        filterByTime(oneMonthAgo);
    });

    // Add individual line charts to team cards (with the y-axis from 90 to 150)
    calculatedTeams.forEach(team => {
        const teamCardChartCtx = document.getElementById(`lineChart-${team.team_id}`).getContext('2d');
        new Chart(teamCardChartCtx, {
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
                        min: 90, // Set minimum y-axis value to 90
                        max: 150, // Set maximum y-axis value to 150
                        title: {
                            display: true,
                            text: 'Effizienz (%)'
                        },
                        beginAtZero: false
                    }
                }
            }
        });
    });
});

