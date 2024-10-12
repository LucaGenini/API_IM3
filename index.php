<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file for DB connection
require_once 'etl/config.php';

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Funktion, um die Teamdaten abzurufen (mit den neuen Spalten der "Teams"-Tabelle)
function fetchTeamData($pdo) {
    $sql = "
        SELECT 
            t.team_id,
            t.team_name,
            t.short_name,
            t.tla,
            t.crest_url,
            t.founded,
            t.address,
            t.website,
            t.venue,
            t.coach_name,
            t.coach_nationality,
            t.market_value,
            t.league_weight,
            
            -- Siege zählen, wenn das Team zu Hause gespielt hat und der Gewinner HOME_TEAM ist
            -- oder wenn das Team auswärts gespielt hat und der Gewinner AWAY_TEAM ist.
            SUM(CASE WHEN (m.home_team_id = t.team_id AND m.winner = 'HOME_TEAM') 
                        OR (m.away_team_id = t.team_id AND m.winner = 'AWAY_TEAM') 
                    THEN 1 ELSE 0 END) AS wins,
            
            -- Niederlagen zählen, wenn das Team zu Hause gespielt hat und der Gewinner AWAY_TEAM ist
            -- oder wenn das Team auswärts gespielt hat und der Gewinner HOME_TEAM ist.
            SUM(CASE WHEN (m.home_team_id = t.team_id AND m.winner = 'AWAY_TEAM') 
                        OR (m.away_team_id = t.team_id AND m.winner = 'HOME_TEAM') 
                    THEN 1 ELSE 0 END) AS losses,
            
            -- Unentschieden zählen, wenn der Gewinner DRAW ist, unabhängig davon, ob zu Hause oder auswärts
            SUM(CASE WHEN m.winner = 'DRAW' THEN 1 ELSE 0 END) AS draws,
            
            -- Match-Datum für die Chart-Anzeige
            MAX(m.match_date) as match_date
        
        FROM Teams t
        LEFT JOIN Matches m ON (m.home_team_id = t.team_id OR m.away_team_id = t.team_id)
        GROUP BY t.team_id  -- Gruppiere nur nach Team
        ORDER BY t.team_name ASC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Funktion, um die letzten 5 Spiele abzurufen (angepasst für die neuen Spalten der "Matches"-Tabelle)
function fetchRecentMatches($pdo) {
    $sql = "
        SELECT 
            m.match_id, 
            t1.team_name AS home_team_name, 
            t2.team_name AS away_team_name, 
            m.match_date, 
            m.score, 
            m.halftime_home, 
            m.halftime_away, 
            m.winner, 
            m.referee_name, 
            m.referee_nationality 
        FROM Matches m
        -- Verknüpfung mit der Teams-Tabelle für Heim- und Auswärtsteam
        JOIN Teams t1 ON m.home_team_id = t1.team_id
        JOIN Teams t2 ON m.away_team_id = t2.team_id
        ORDER BY m.match_date DESC 
        LIMIT 5";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch team data and recent matches
$teams = fetchTeamData($pdo);
$matches = fetchRecentMatches($pdo);

// Function to calculate efficiency
function calculate_efficiency($market_value, $league_weight, $wins, $losses, $draws) {
    if ($market_value == 0 || $league_weight == 0) {
        return 0;
    }
    return ($wins * $league_weight) / $market_value;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CL Capital</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/png" href="images/icon.png">
</head>
<body>

    <header>
        <h1>CL Capital 💰</h1>
    </header>

    <!-- Intro Section -->
    <section class="hero">
        <div>
            <h2>Wie stark beeinflusst Geld den Erfolg in der Champions League?</h2>
            <p>Erkunde die Teams, ihre Marktwerte und deren Performance</p>
            <a href="#teams" class="button">Jetzt entdecken</a>
        </div>
    </section>

    <!-- Team Cards Section -->
    <section class="teams" id="teams">
        <h2>Champions League Teams</h2>
        <div class="spacer"></div>
        <?php if (count($teams) > 0): ?>
            <?php foreach ($teams as $index => $team): ?>
                <div class="team-card <?php if ($index >= 6) echo 'hidden-team'; ?>">
                    <a href="team-details.php?team_id=<?php echo urlencode($team['team_id']); ?>" style="text-decoration: none; color: inherit;">
                        <h3><?php echo htmlspecialchars($team['team_name']); ?> (<?php echo htmlspecialchars($team['tla']); ?>)</h3>
                        <img src="<?php echo htmlspecialchars($team['crest_url']); ?>" alt="Wappen von <?php echo htmlspecialchars($team['team_name']); ?>" width="100">
                        <p>Marktwert: <?php echo $team['market_value']; ?> Mio. €</p>
                        <p>Liga-Gewichtung: <?php echo $team['league_weight']; ?></p>
                        <p>Trainer: <?php echo htmlspecialchars($team['coach_name']); ?> (<?php echo htmlspecialchars($team['coach_nationality']); ?>)</p>
                        <p>Effizienz: <?php echo round(calculate_efficiency($team['market_value'], $team['league_weight'], $team['wins'], $team['losses'], $team['draws']), 4); ?></p>
                        <p>Siege: <?php echo $team['wins']; ?> | Niederlagen: <?php echo $team['losses']; ?> | Unentschieden: <?php echo $team['draws']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
            <button id="toggleButton" class="button">Mehr Teams anzeigen ▼</button>
        <?php else: ?>
            <p>Keine Teams gefunden.</p>
        <?php endif; ?>
    </section>

    <!-- Efficiency Comparison Chart Section -->
    <section class="charts">
        <h2>Effizienz-Vergleich nach jedem Spiel</h2>
        <div class="chart">
            <canvas id="efficiencyChart" width="800" height="400"></canvas>
        </div>
    </section>

    <!-- Recent Matches Section -->
    <section class="upcoming-matches">
        <h2>Letzte 5 Spiele</h2>
        <table>
            <thead>
                <tr>
                    <th>Heimteam</th>
                    <th>Auswärtsteam</th>
                    <th>Datum</th>
                    <th>Ergebnis</th>
                    <th>Halbzeit</th>
                    <th>Sieger</th>
                    <th>Schiedsrichter</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                <tr>
                    <td><?php echo htmlspecialchars($match['home_team_name']); ?></td>
                    <td><?php echo htmlspecialchars($match['away_team_name']); ?></td>
                    <td><?php echo htmlspecialchars($match['match_date']); ?></td>
                    <td><?php echo htmlspecialchars($match['score']); ?></td>
                    <td><?php echo htmlspecialchars($match['halftime_home']); ?> - <?php echo htmlspecialchars($match['halftime_away']); ?></td>
                    <td><?php echo $match['winner'] === 'DRAW' ? 'Unentschieden' : ($match['winner'] === 'HOME_TEAM' ? htmlspecialchars($match['home_team_name']) : htmlspecialchars($match['away_team_name'])); ?></td>
                    <td><?php echo htmlspecialchars($match['referee_name']); ?> (<?php echo htmlspecialchars($match['referee_nationality']); ?>)</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <footer>
        <p>&copy; 2024 CL Capital | Alle Rechte vorbehalten</p>
    </footer>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fetching dynamic data from PHP (team efficiency data for each match)
            const teamData = <?php echo json_encode($teams); ?>;

            // Initializing an object to store each team's efficiency over matches
            const teamsEfficiency = {};

            // Function to group efficiency data by team and matches
            teamData.forEach(team => {
                const teamName = team.team_name;
                if (!teamsEfficiency[teamName]) {
                    teamsEfficiency[teamName] = {
                        label: teamName,
                        data: [],
                        borderColor: getRandomColor(),
                        fill: false,
                        tension: 0.1
                    };
                }

                const efficiency = calculate_efficiency(team.market_value, team.league_weight, team.wins, team.losses, team.draws);
                teamsEfficiency[teamName].data.push(efficiency);
            });

            // Convert object into array of datasets for Chart.js
            const datasets = Object.values(teamsEfficiency);

            // Ensure there is data before rendering the chart
            const ctx = document.getElementById('efficiencyChart').getContext('2d');
            const efficiencyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: teamData.map(team => team.team_name),  // Use team names for the X-axis
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'category',
                            title: {
                                display: true,
                                text: 'Teams'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Effizienz'
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

            function getRandomColor() {
                const letters = '0123456789ABCDEF';
                let color = '#';
                for (let i = 0; i < 6; i++) {
                    color += letters[Math.floor(Math.random() * 16)];
                }
                return color;
            }

            // Calculate efficiency function
            function calculate_efficiency(market_value, league_weight, wins, losses, draws) {
                return ((wins * league_weight) / market_value).toFixed(4);
            }
        });
    </script>

</body>
</html>
