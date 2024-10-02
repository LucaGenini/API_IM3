<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file for DB connection
require_once 'config.php';

// Function to fetch football data from the API
function fetchFootballData() {
    $url = "http://api.football-data.org/v4/competitions/CL/teams";
    $apiToken = "2a30d1601444472c81c7bc36a8199b31";
    
    // Initialize a cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Auth-Token: $apiToken",
        "Content-Type: application/json"
    ));

    // Execute the cURL session and check for errors
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    }
    curl_close($ch);

    // Return decoded JSON data
    return json_decode($response, true);
}

// Fetch data from the API
$data = fetchFootballData();

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if data fetching was successful
if (!$data || !isset($data['teams'])) {
    die("Error: Failed to fetch or decode data from the API.");
}

try {
    // Prepare SQL statement for inserting teams into the database
    $sql = "INSERT INTO Teams (team_id, team_name, short_name, tla, crest_url, founded, address, website, venue, coach_name, coach_nationality) 
            VALUES (:team_id, :team_name, :short_name, :tla, :crest_url, :founded, :address, :website, :venue, :coach_name, :coach_nationality)";
    $stmt = $pdo->prepare($sql);

    // Iterate through the teams and insert data
    foreach ($data['teams'] as $team) {
        // Ensure essential data is present
        if (isset($team['id']) && isset($team['name'])) {
            // Get the coach information
            $coach_name = isset($team['coach']['name']) ? $team['coach']['name'] : 'Unknown';
            $coach_nationality = isset($team['coach']['nationality']) ? $team['coach']['nationality'] : 'Unknown';

            // Execute the SQL statement
            $stmt->execute([
                ':team_id' => $team['id'],
                ':team_name' => $team['name'],
                ':short_name' => $team['shortName'],
                ':tla' => $team['tla'],
                ':crest_url' => $team['crest'],
                ':founded' => $team['founded'],
                ':address' => $team['address'],
                ':website' => $team['website'],
                ':venue' => $team['venue'],
                ':coach_name' => $coach_name,
                ':coach_nationality' => $coach_nationality
            ]);

            echo "Inserted team: " . $team['name'] . "<br>";
        } else {
            echo "Skipping team due to missing essential data (ID or name).<br>";
        }
    }

    echo "Data successfully inserted into the database.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
