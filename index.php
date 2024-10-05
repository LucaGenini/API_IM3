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

try {
    // SQL query to select all teams from the database (only name, crest, market value and league weight)
    $sql = "SELECT team_name, crest_url, market_value, league_weight FROM Teams ORDER BY team_name ASC";
    $stmt = $pdo->query($sql);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch upcoming matches (example for the next 5 matches)
    $sqlMatches = "SELECT * FROM Matches ORDER BY match_date ASC LIMIT 5";
    $stmtMatches = $pdo->query($sqlMatches);
    $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Function to calculate efficiency
function calculate_efficiency($market_value, $league_weight, $wins, $losses, $draws) {
    // Einfaches Berechnungsbeispiel: Effizienz = (Siege * Liga-Gewichtung) / Marktwert
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

    <section class="teams" id="teams">
        <h2>Champions League Teams</h2>
        <div class="spacer"></div>
        <?php if (count($teams) > 0): ?>
            <?php foreach ($teams as $index => $team): 
                // Simulation der Team-Performance (diese Daten kannst du durch echte Ergebnisse ersetzen)
                $wins = rand(0, 5);
                $losses = rand(0, 5);
                $draws = rand(0, 5);
                $efficiency = calculate_efficiency($team['market_value'], $team['league_weight'], $wins, $losses, $draws);
            ?>
                <div class="team-card <?php if ($index >= 6) echo 'hidden-team'; ?>">
                    <a href="team-details.php?team_name=<?php echo urlencode($team['team_name']); ?>" style="text-decoration: none; color: inherit;">
                        <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                        <img src="<?php echo htmlspecialchars($team['crest_url']); ?>" alt="Wappen von <?php echo htmlspecialchars($team['team_name']); ?>" width="100">
                        <p>Marktwert: <?php echo $team['market_value']; ?> Mio. €</p>
                        <p>Liga-Gewichtung: <?php echo $team['league_weight']; ?></p>
                        <p>Effizienz: <?php echo round($efficiency, 4); ?></p>
                        <p>Siege: <?php echo $wins; ?> | Niederlagen: <?php echo $losses; ?> | Unentschieden: <?php echo $draws; ?></p>
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

    <!-- Upcoming Matches Section -->
    <section class="upcoming-matches">
        <h2>Bevorstehende 5 Spiele</h2>
        <table>
            <thead>
                <tr>
                    <th>Team 1</th>
                    <th>Team 2</th>
                    <th>Datum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matches as $match): ?>
                <tr>
                    <td><?php echo htmlspecialchars($match['team_id']); ?></td>
                    <td><?php echo htmlspecialchars($match['opponent_id']); ?></td>
                    <td><?php echo htmlspecialchars($match['match_date']); ?></td>
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
    <script src="script.js"></script>

    <script>
        // Toggle function for hidden teams
        document.getElementById('toggleButton').addEventListener('click', function() {
            var hiddenTeams = document.querySelectorAll('.hidden-team');
            hiddenTeams.forEach(function(team) {
                if (team.style.display === 'none' || team.style.display === '') {
                    team.style.display = 'block';
                } else {
                    team.style.display = 'none';
                }
            });

            // Change button text based on visibility
            if (this.textContent === 'Mehr Teams anzeigen ▼') {
                this.textContent = 'Weniger Teams anzeigen ▲';
            } else {
                this.textContent = 'Mehr Teams anzeigen ▼';
            }
        });

        // Initial setup: Hide all hidden-team elements on page load
        document.querySelectorAll('.hidden-team').forEach(function(team) {
            team.style.display = 'none';
        });
    </script>
</body>
</html>
