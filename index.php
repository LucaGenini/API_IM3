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

// Function to fetch team data, including efficiency changes
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
            SUM(CASE WHEN (m.home_team_id = t.team_id AND m.winner = 'HOME_TEAM') 
                        OR (m.away_team_id = t.team_id AND m.winner = 'AWAY_TEAM') 
                    THEN 1 ELSE 0 END) AS wins,
            SUM(CASE WHEN (m.home_team_id = t.team_id AND m.winner = 'AWAY_TEAM') 
                        OR (m.away_team_id = t.team_id AND m.winner = 'HOME_TEAM') 
                    THEN 1 ELSE 0 END) AS losses,
            SUM(CASE WHEN m.winner = 'DRAW' THEN 1 ELSE 0 END) AS draws,
            MIN(m.match_date) AS first_match_date,
            MAX(m.match_date) AS last_match_date,
            GROUP_CONCAT(e.efficiency ORDER BY m.match_date ASC) AS efficiency_changes
        FROM Teams t
        LEFT JOIN Matches m ON (m.home_team_id = t.team_id OR m.away_team_id = t.team_id)
        LEFT JOIN (
            SELECT 
                m2.match_id,
                m2.home_team_id,
                m2.away_team_id,
                (CASE 
                    WHEN (m2.home_team_id = t2.team_id AND m2.winner = 'HOME_TEAM') 
                         OR (m2.away_team_id = t2.team_id AND m2.winner = 'AWAY_TEAM') 
                    THEN 10
                    WHEN m2.winner = 'DRAW' THEN 5
                    ELSE -10
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

// Function to fetch the last 5 matches
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

    ><section class="introduction">
    
    <p>Im Fußball wird oft behauptet, dass der Marktwert und die finanziellen Mittel eines Teams entscheidend für den sportlichen Erfolg sind. Doch wie groß ist der tatsächliche Einfluss von Geld auf die Leistung? Mit unserem Projekt wollen wir genau diese Frage untersuchen.
    Wir analysieren, wie sich Teams mit hohem Marktwert im Vergleich zu finanziell kleineren, ressourcenschwächeren Vereinen schlagen. Dazu betrachten wir alle Spiele der diesjährigen Champions-League-Mannschaften – sowohl nationale als auch internationale Wettbewerbe. Unser Ziel: Herauszufinden, ob finanzstarke Teams tatsächlich effizienter agieren und ob der Marktwert einen signifikanten Vorteil im Wettkampf verschafft.
    Erfahre, wie groß der Einfluss von Geld im Fußball wirklich ist und ob der Marktwert der Schlüssel zum Erfolg ist – oder ob andere Faktoren eine wichtigere Rolle spielen.
    <br> <br>Kernaussagen: Stabilität durch hohen Marktwert: Ein hoher Marktwert sorgt für Stabilität in den Ergebnissen, aber die Steigerung der Effizienz ist oft geringer, da die Erwartungshaltung an große Teams höher ist.
    Risikoarme Bewegungen für kleine Teams: Kleinere Teams mit geringeren Marktwerten können sich risikofreier bewegen und haben oft mehr Raum für Überraschungen, da die Erwartungshaltung niedriger ist.
    Effizienz hängt nicht nur vom Geld ab: Unsere Analyse zeigt, dass Marktstarke Teams nicht automatisch effizienter agieren als marktschwächere Teams. Andere Faktoren wie Taktik, Teamgeist und Trainerentscheidungen spielen eine wesentliche Rolle.
    <br> <br>Effizienzberechnung: Um die Effizienz eines Teams zu berechnen, verwenden wir eine Formel, die Marktwert, Liga-Stärke und Spielergebnisse berücksichtigt. Jede Effizienzänderung wird nach folgender Formel angepasst:
    Anpassung der Effizienzänderung: change: Dieser Wert wird in der SQL-Abfrage berechnet und beträgt 10, 5 oder -10, je nach Spielergebnis (Sieg, Unentschieden, Niederlage).
    leagueWeight: Berücksichtigt die Stärke der Liga. Ein höheres Gewicht bedeutet, dass Effizienzänderungen stärker wirken.
    marketValue: Der Marktwert des Teams. Ein höherer Marktwert bedeutet, dass Änderungen einen geringeren Einfluss auf die Effizienz haben. Ein großes, starkes Team reagiert also weniger empfindlich auf Änderungen als ein kleineres Team.</p>

    </section>

    <!-- Team Cards Section -->
    <section class="teams" id="teams">
        <h2>Champions League Teams</h2>
        <div class="spacer"></div>
        <?php if (count($teams) > 0): ?>
            <?php foreach ($teams as $index => $team): ?>
                <div class="team-card" data-team-id="<?php echo htmlspecialchars($team['team_id']); ?>">
                    <a href="team-details.php?team_id=<?php echo urlencode($team['team_id']); ?>" style="text-decoration: none; color: inherit;">
                        <h3><?php echo htmlspecialchars($team['team_name']); ?> (<?php echo htmlspecialchars($team['tla']); ?>)</h3>
                        <img src="<?php echo htmlspecialchars($team['crest_url']); ?>" alt="Wappen von <?php echo htmlspecialchars($team['team_name']); ?>" width="100">
                        <p>Marktwert: <?php echo $team['market_value']; ?> Mio. €</p>
                        <p>Liga-Gewichtung: <?php echo $team['league_weight']; ?></p>
                        <p>Trainer: <?php echo htmlspecialchars($team['coach_name']); ?> (<?php echo htmlspecialchars($team['coach_nationality']); ?>)</p>
                        <p>Siege: <?php echo $team['wins']; ?> | Niederlagen: <?php echo $team['losses']; ?> | Unentschieden: <?php echo $team['draws']; ?></p>
                    </a>
                    <!-- Line chart for each team (using unique canvas IDs) -->
                    <canvas id="lineChart-<?php echo $team['team_id']; ?>" width="300" height="150"></canvas>
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

        <!-- Bar Chart Filters -->
        <div class="chart-filters">
            <button id="topEfficiencyBtn" class="button">Top 5 Most Efficient Clubs</button>
            <button id="topMarketValueBtn" class="button">Top 5 Highest Market Value</button>
            <button id="allTeamsBtn" class="button">All Teams</button>
        </div>

        <!-- Bar Chart for Efficiency and Market Value -->
        <div class="chart">
            <canvas id="efficiencyBarChart" width="800" height="400"></canvas>
        </div>

        <!-- Line Chart Filters -->
        <div class="chart-filters">
            <button id="thisYearBtn" class="button">This Year</button>
            <button id="last6MonthsBtn" class="button">Last 6 Months</button>
            <button id="lastMonthBtn" class="button">Last Month</button>
        </div>

        <!-- Line Chart for Team Performance (Efficiency Changes) -->
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
