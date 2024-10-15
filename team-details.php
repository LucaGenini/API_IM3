<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file for DB connection
require_once 'etl/config.php';

// Get team ID from query string
$team_id = isset($_GET['team_id']) ? $_GET['team_id'] : null;

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch team details from the database
$team_details = null;
if ($team_id) {
    try {
        $sql = "SELECT * FROM Teams WHERE team_id = :team_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['team_id' => $team_id]);
        $team_details = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Team Details - <?php echo htmlspecialchars($team_details['team_name'] ?? 'Team'); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header>
        <h1>Team Details</h1>
    </header>

    <section class="team-details-container">
        <?php if ($team_details): ?>
            <div class="team-details-card">
                <h2><?php echo htmlspecialchars($team_details['team_name']); ?></h2>
                <img src="<?php echo htmlspecialchars($team_details['crest_url']); ?>" alt="Wappen von <?php echo htmlspecialchars($team_details['team_name']); ?>" class="team-logo">
                <p><strong>Marktwert:</strong> <?php echo htmlspecialchars($team_details['market_value']); ?> Mio. €</p>
                <p><strong>Gegründet:</strong> <?php echo htmlspecialchars($team_details['founded'] ?? 'Nicht verfügbar'); ?></p>
                <p><strong>Trainer:</strong> <?php echo htmlspecialchars($team_details['coach_name'] ?? 'Nicht verfügbar'); ?></p>
                <p><strong>Stadion:</strong> <?php echo htmlspecialchars($team_details['venue'] ?? 'Nicht verfügbar'); ?></p>
                <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($team_details['website']); ?>" target="_blank"><?php echo htmlspecialchars($team_details['website']); ?></a></p>
            </div>
        <?php else: ?>
            <p>Team nicht gefunden.</p>
        <?php endif; ?>
    </section>

    <footer>
        <p>&copy; 2024 CL Capital | Alle Rechte vorbehalten</p>
    </footer>
    
</body>
</html>


