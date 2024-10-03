<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file for DB connection
require_once 'config.php';

// Function to fetch football data from the API
function fetchFootballData() {
    $url = "http://api.football-data.org/v4/competitions/CL/teams";
    $apiToken = "2a30d1601444472c81c7bc36a8199b31";  // Set your API token here
    
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
    $sql = "INSERT INTO Teams (team_name, crest_url) 
            VALUES (:team_name, :crest_url)";
    $stmt = $pdo->prepare($sql);

    // Iterate through the teams and insert data
    foreach ($data['teams'] as $team) {
        // Ensure essential data is present
        if (isset($team['name']) && isset($team['crest'])) {

            // Execute the SQL statement
            $stmt->execute([
                ':team_name' => $team['name'],
                ':crest_url' => $team['crest']
            ]);

            echo "Inserted team: " . $team['name'] . "<br>";
        } else {
            echo "Skipping team due to missing essential data (name or crest).<br>";
        }
    }

    echo "Data successfully inserted into the database.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
