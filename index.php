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

// Funktion, um die Teamdaten abzurufen, einschließlich der Effizienzänderungen
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
            
            -- Siege zählen
            SUM(CASE WHEN (m.home_team_id = t.team_id AND m.winner = 'HOME_TEAM') 
                        OR (m.away_team_id = t.team_id AND m.winner = 'AWAY_TEAM') 
                    THEN 1 ELSE 0 END) AS wins,
            -- Niederlagen zählen
            SUM(CASE WHEN (m.home_team_id = t.team_id AND m.winner = 'AWAY_TEAM') 
                        OR (m.away_team_id = t.team_id AND m.winner = 'HOME_TEAM') 
                    THEN 1 ELSE 0 END) AS losses,
            -- Unentschieden zählen
            SUM(CASE WHEN m.winner = 'DRAW' THEN 1 ELSE 0 END) AS draws,
            -- Das früheste und das letzte Spieldatum für die Zeitachse
            MIN(m.match_date) AS first_match_date,
            MAX(m.match_date) AS last_match_date,
            
            -- Effizienzänderungen über die Zeit, ausgedrückt in Prozent
            GROUP_CONCAT(e.efficiency ORDER BY m.match_date ASC) AS efficiency_changes
        FROM Teams t
        LEFT JOIN Matches m ON (m.home_team_id = t.team_id OR m.away_team_id = t.team_id)
        
        -- Unterabfrage zur Berechnung der Effizienz als prozentuale Änderung für jedes Spiel
        LEFT JOIN (
            SELECT 
                m2.match_id,
                m2.home_team_id,
                m2.away_team_id,
                (CASE 
                    -- Sieg erhöht die Effizienz um 3%
                    WHEN (m2.home_team_id = t2.team_id AND m2.winner = 'HOME_TEAM') 
                         OR (m2.away_team_id = t2.team_id AND m2.winner = 'AWAY_TEAM') 
                    THEN 3
                    -- Unentschieden erhöht die Effizienz um 1%
                    WHEN m2.winner = 'DRAW' THEN 1
                    -- Niederlage senkt die Effizienz um 2%
                    ELSE -2
                END) AS efficiency
            FROM Teams t2
            LEFT JOIN Matches m2 ON (m2.home_team_id = t2.team_id OR m2.away_team_id = t2.team_id)
            ORDER BY m2.match_date ASC
        ) AS e ON e.match_id = m.match_id
        
        GROUP BY t.team_id
        ORDER BY t.team_name ASC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Funktion, um die letzten 5 Spiele abzurufen
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
                        <!-- Die Effizienz wird jetzt in JavaScript berechnet und in den Charts angezeigt -->
                        <p>Siege: <?php echo $team['wins']; ?> | Niederlagen: <?php echo $team['losses']; ?> | Unentschieden: <?php echo $team['draws']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
            <button id="toggleButton" class="button">Mehr Teams anzeigen ▼</button>
        <?php else: ?>
            <p>Keine Teams gefunden.</p>
        <?php endif; ?>
    </section>

    <!-- Efficiency Comparison Bar Chart Section -->
    <section class="charts">
        <h2>Team Vergleich: Effizienz und Marktwert</h2>
        <!-- Bar Chart für Effizienz und Marktwert -->
        <div class="chart">
            <canvas id="efficiencyBarChart" width="800" height="400"></canvas>
        </div>

        <!-- Line Chart für Team Performance (Effizienzänderungen) -->
        <div class="chart">
            <canvas id="performanceLineChart" width="800" height="400"></canvas>
        </div>
    </section>

    <footer>
        <p>&copy; 2024 CL Capital | Alle Rechte vorbehalten</p>
    </footer>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Insert team data into a script tag for use in JS -->
    <script id="teamData" type="application/json">
        <?php echo json_encode($teams); ?>
    </script>

    <!-- External JS for Charts -->
    <script src="script.js"></script>

</body>
</html>
