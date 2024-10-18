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

        // Log to check if the efficiency is being calculated correctly
        console.log(`Team: ${team.team_name}, Final Efficiency: ${finalEfficiency}`);

        return {
            ...team,
            finalEfficiency: parseFloat(finalEfficiency),  // Calculate final efficiency
            efficienciesOverTime: efficienciesOverTime
        };
    });

    // Sort teams by final efficiency in descending order (before anything else)
    calculatedTeams.sort((a, b) => b.finalEfficiency - a.finalEfficiency);

    // Function to display efficiency and ranking in the team title
    function displayEfficiencyAndRanking() {
        calculatedTeams.forEach((team, index) => {
            // Find the corresponding team card by data-team-id
            const teamCard = document.querySelector(`.team-card[data-team-id='${team.team_id}']`);

            if (teamCard) {
                // Display the ranking number next to the team name in the card title
                const titleElement = teamCard.querySelector('h3');
                if (titleElement) {
                    // Add ranking number next to team name
                    titleElement.textContent = `${index + 1}. ${team.team_name}`;
                }

                // Check if the efficiency paragraph already exists, if not, create it
                let efficiencyElement = teamCard.querySelector('.team-efficiency');
                if (!efficiencyElement) {
                    efficiencyElement = document.createElement('p');
                    efficiencyElement.classList.add('team-efficiency');
                    teamCard.querySelector('a').appendChild(efficiencyElement);
                }

                // Ensure efficiency is displayed correctly
                if (!isNaN(team.finalEfficiency)) {
                    // Display efficiency if it's a valid number
                    efficiencyElement.textContent = `Effizienz: ${team.finalEfficiency.toFixed(2)}%`;
                } else {
                    // Handle cases where efficiency calculation might fail
                    console.warn(`Efficiency not found for team: ${team.team_name}`);
                    efficiencyElement.textContent = 'Effizienz: Daten fehlen';
                }

                // Ensure individual team line charts get displayed correctly
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
            } else {
                console.warn(`Team card not found for team_id: ${team.team_id}`);
            }
        });
    }

    // Function to reorder the team cards based on efficiency, without interfering with chart initialization
    function reorderTeamCards() {
        const teamSection = document.querySelector('.teams');  // The section containing team cards
        const teamCards = Array.from(document.querySelectorAll('.team-card'));  // All team cards in the DOM

        // Sort the team card elements based on calculated team efficiency
        teamCards.sort((a, b) => {
            const efficiencyAElement = a.querySelector('.team-efficiency');
            const efficiencyBElement = b.querySelector('.team-efficiency');

            // Check if efficiency elements exist before trying to sort
            if (efficiencyAElement && efficiencyBElement) {
                const efficiencyA = parseFloat(efficiencyAElement.textContent.match(/\d+\.\d+/)[0]);
                const efficiencyB = parseFloat(efficiencyBElement.textContent.match(/\d+\.\d+/)[0]);
                return efficiencyB - efficiencyA;
            }

            return 0; // If elements are missing, do not change order
        });

        // Re-append the sorted cards to the team section
        teamCards.forEach(card => teamSection.appendChild(card));

        // Move the toggle button to the bottom of the team section
        const toggleButton = document.getElementById('toggleButton');
        teamSection.appendChild(toggleButton);

        // Reinitialize the toggle button after reordering the cards
        initializeToggleButton();
    }

    // Function to initialize charts
    function initializeCharts() {
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
                    autoSkip: false,          // Disable automatic skipping of labels
                    maxRotation: 45,         // Rotate labels to fit better
                    minRotation: 45           // Prevent too much rotation
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
                },
                ticks: {
                    autoSkip: true,         // Automatically skip some labels to prevent crowding
                    maxTicksLimit: 15,      // Limit the number of ticks shown
                    maxRotation: 90,        // Rotate labels to fit better
                    minRotation: 0
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

        // Make the charts mobile responsive
        function makeChartsResponsive() {
            if (window.innerWidth < 600) {
                // Mobile-specific settings
                barChart.options.scales.x.ticks.autoSkip = true;
                barChart.options.scales.x.ticks.maxTicksLimit = 5; // Show fewer labels on mobile
                barChart.options.scales.x.ticks.maxRotation = 45;
                barChart.options.scales.x.ticks.minRotation = 45;

                lineChart.options.scales.x.ticks.autoSkip = true;
                lineChart.options.scales.x.ticks.maxTicksLimit = 5; // Show fewer labels on mobile
                lineChart.options.scales.x.ticks.maxRotation = 45;
                lineChart.options.scales.x.ticks.minRotation = 45;

                lineChart.options.scales.y.max = 150; // Set maximum y-axis value to 150 on mobile

                // Keep the legend at the top on mobile
                barChart.options.plugins.legend.position = 'top';
                lineChart.options.plugins.legend.position = 'top';

                // Optional: Hide the legend if there are too many teams on mobile
                lineChart.options.plugins.legend.display = false;
            } else {
                // Reset to default settings on larger screens
                barChart.options.scales.x.ticks.autoSkip = true;
                barChart.options.scales.x.ticks.maxTicksLimit = 15;
                barChart.options.scales.x.ticks.maxRotation = 90;
                barChart.options.scales.x.ticks.minRotation = 0;

                lineChart.options.scales.x.ticks.autoSkip = true;
                lineChart.options.scales.x.ticks.maxTicksLimit = 15;
                lineChart.options.scales.x.ticks.maxRotation = 90;
                lineChart.options.scales.x.ticks.minRotation = 0;

                lineChart.options.scales.y.max = 150; // Ensure maximum y-axis value is 150 on larger screens too

                // Reset the legend position and display
                barChart.options.plugins.legend.position = 'top';
                lineChart.options.plugins.legend.position = 'top';
                lineChart.options.plugins.legend.display = true;
            }

            // Update the charts after changing the options
            barChart.update();
            lineChart.update();
        }

        // Adjust the charts when the window is resized
        window.addEventListener('resize', makeChartsResponsive);

        // Call the function initially to apply settings based on current window size
        makeChartsResponsive();
    }

    // Function to hide/show extra teams
    function initializeToggleButton() {
        const toggleButton = document.getElementById('toggleButton');
        const teamCards = document.querySelectorAll('.team-card');
        const visibleTeamsCount = 5; // Show 5 teams by default
        let showAll = false; // Flag to track current toggle state

        // Initially hide all teams beyond the first 5
        teamCards.forEach((card, index) => {
            if (index >= visibleTeamsCount) {
                card.style.display = 'none';
            }
        });

        // Toggle the visibility of additional teams when the button is clicked
        toggleButton.addEventListener('click', function () {
            showAll = !showAll; // Toggle state

            teamCards.forEach((card, index) => {
                if (index >= visibleTeamsCount) {
                    card.style.display = showAll ? 'block' : 'none';
                }
            });

            // Update button text based on the current state
            toggleButton.textContent = showAll ? 'Weniger Teams anzeigen ▲' : 'Mehr Teams anzeigen ▼';
        });
    }

    // Filter Button Handlers
    function updateCharts(filteredTeams) {
        const filteredTeamNames = filteredTeams.map(team => team.team_name);
        const filteredEfficiencies = filteredTeams.map(team => team.finalEfficiency);
        const filteredMarketValues = filteredTeams.map(team => team.market_value);

        // Update Bar Chart
        const barChart = Chart.getChart("efficiencyBarChart");
        barChart.data.labels = filteredTeamNames;
        barChart.data.datasets[0].data = filteredEfficiencies;
        barChart.data.datasets[1].data = filteredMarketValues;
        barChart.update();

        // Update Line Chart (Performance Over Time)
        const lineChart = Chart.getChart("performanceLineChart");
        const filteredEfficienciesOverTime = filteredTeams.map(team => {
            const teamData = teamEfficienciesOverTime.find(t => t.name === team.team_name);

            // Check if the teamData exists before updating it
            if (teamData) {
                return {
                    label: teamData.name,
                    data: teamData.efficiencies,
                    fill: false,
                    borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
                    tension: 0.1
                };
            } else {
                console.warn(`No efficiency data found for team: ${team.team_name}`);
                return null;  // Handle undefined teamData
            }
        }).filter(dataset => dataset !== null); // Filter out any null datasets

        lineChart.data.datasets = filteredEfficienciesOverTime;
        lineChart.update();
    }

    // Filter based on time (This Year, Last 6 Months, Last Month)
    function filterByTime(startDate) {
        const filteredMatchDates = matchDates.filter(date => new Date(date) >= startDate);

        // Update Line Chart with filtered dates
        const lineChart = Chart.getChart("performanceLineChart");
        lineChart.data.labels = filteredMatchDates;

        teamEfficienciesOverTime.forEach((teamData, index) => {
            if (teamData) {
                const filteredEfficiencies = teamData.efficiencies.slice(-filteredMatchDates.length);
                if (lineChart.data.datasets[index]) {
                    lineChart.data.datasets[index].data = filteredEfficiencies;
                } else {
                    console.warn(`Dataset for team at index ${index} is undefined.`);
                }
            }
        });

        lineChart.update();
    }

    // Filter button for time-based filtering
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

    // Filter by top efficiency or market value
    document.getElementById('topEfficiencyBtn').addEventListener('click', function () {
        const top5EfficientTeams = calculatedTeams.slice(0, 5);
        updateCharts(top5EfficientTeams);
    });

    document.getElementById('topMarketValueBtn').addEventListener('click', function () {
        const top5MarketValueTeams = [...calculatedTeams].sort((a, b) => b.market_value - a.market_value).slice(0, 5);
        updateCharts(top5MarketValueTeams);
    });

    document.getElementById('allTeamsBtn').addEventListener('click', function () {
        updateCharts(calculatedTeams); // Reset to show all teams
    });

    // Step 1: Display efficiency and ranking on team cards
    displayEfficiencyAndRanking();

    // Step 2: Reorder the team cards by efficiency
    reorderTeamCards();

    // Step 3: Initialize the charts (Bar Chart and Line Chart)
    initializeCharts();
});
