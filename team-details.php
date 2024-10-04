<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file for DB connection
require_once 'etl/config.php';

// Get team name from query string
$team_name = isset($_GET['team_name']) ? $_GET['team_name'] : null;

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch team details from the API or database
$team_details = null;
if ($team_name) {
    try {
        $sql = "SELECT * FROM Teams WHERE team_name = :team_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['team_name' => $team_name]);
        $team_details = $stmt->fetch(PDO::FETCH_ASSOC);

        // Optionally, call an API to get more information about the team
        // You could use curl or a similar library to make a request to an external API
        // Example: $api_response = file_get_contents("https://api.example.com/team?name=" . urlencode($team_name));
        // Then, parse and use the $api_response as needed.
        
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
    <title>Team Details - <?php echo htmlspecialchars($team_name); ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header>
        <h1>CL Capital 💰</h1>
    </header>

    <section class="team-details">
        <?php if ($team_details): ?>
            <h2><?php echo htmlspecialchars($team_details['team_name']); ?></h2>
            <img src="<?php echo htmlspecialchars($team_details['crest_url']); ?>" alt="Wappen von <?php echo htmlspecialchars($team_details['team_name']); ?>" width="150">
            <p><strong>Land:</strong> <?php echo htmlspecialchars($team_details['country'] ?? 'Nicht verfügbar'); ?></p>
            <p><strong>Trainer:</strong> <?php echo htmlspecialchars($team_details['coach'] ?? 'Nicht verfügbar'); ?></p>
            <!-- Weitere Details hinzufügen -->
        <?php else: ?>
            <p>Team nicht gefunden.</p>
        <?php endif; ?>
    </section>

    <footer>
        <p>&copy; 2024 CL Capital | Alle Rechte vorbehalten</p>
    </footer>
    
</body>
</html>
