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
    // SQL query to select all teams from the database (only name and crest)
    $sql = "SELECT team_name, crest_url FROM Teams ORDER BY team_name ASC";
    $stmt = $pdo->query($sql);
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
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
            <a href="#teams" class="cta">Jetzt entdecken</a>
        </div>
    </section>

    <!-- Teams Section -->
    <section class="teams" id="teams">
    <h2 style="margin-bottom: 4rem;">Champions League Teams</h2> <!-- Erhöhter Abstand durch margin-bottom -->

    <?php if (count($teams) > 0): ?>
        <?php foreach ($teams as $index => $team): ?>
            <!-- Display first 6 teams, hide the rest -->
            <div class="team-card <?php if ($index >= 6) echo 'hidden-team'; ?>">
                <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                <img src="<?php echo htmlspecialchars($team['crest_url']); ?>" alt="Wappen von <?php echo htmlspecialchars($team['team_name']); ?>" width="100">
            </div>
        <?php endforeach; ?>
        <!-- Toggle Button -->
        <button id="toggleButton" class="toggle-button">Mehr Teams anzeigen ▼</button>
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
