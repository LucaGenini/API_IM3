<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Function to fetch all Champions League matches for the season
function fetchAllMatchesForCompetition($competition, $season) {
    $url = "https://api.football-data.org/v4/competitions/$competition/matches?season=$season"; // API endpoint for matches by competition and season
    $apiToken = "6a5dae7a03df4242810803e8236e8d5d";  // Your API token

    // Initialize a cURL session
    $ch = curl_init($url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Auth-Token: $apiToken",
        "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    // Execute the cURL request
    $response = curl_exec($ch);
    
    // Check for errors in the cURL session
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }
    
    // Get the HTTP status code
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if the response was successful
    if ($httpStatus !== 200) {
        echo "API request failed with status: $httpStatus\n";
        echo "Response: " . $response . "\n";
        return null;
    }

    // Decode and return the JSON response
    return json_decode($response, true);
}

// Function to insert match data into the database
function insertMatchIntoDB($pdo, $match, $competition) {
    // Check if both home and away teams exist
    if (isset($match['homeTeam']) && isset($match['awayTeam'])) {
        $team_id = $match['homeTeam']['id'];
        $opponent_id = $match['awayTeam']['id'];
        $match_date = isset($match['utcDate']) ? $match['utcDate'] : null;
        $score = isset($match['score']['fullTime']['homeTeam']) && isset($match['score']['fullTime']['awayTeam']) 
            ? $match['score']['fullTime']['homeTeam'] . "-" . $match['score']['fullTime']['awayTeam'] 
            : "N/A";

        // Additional match statistics can be added here if available from the API
        $possession = isset($match['statistics']['possession']) ? $match['statistics']['possession'] : 0;
        $shots = isset($match['statistics']['shotsOnGoal']) ? $match['statistics']['shotsOnGoal'] : 0;

        // Insert match data into the database if all required fields are available
        if ($team_id && $opponent_id && $match_date) {
            $sql = "INSERT INTO Matches (team_id, opponent_id, match_date, league, score, possession, shots) 
                    VALUES (:team_id, :opponent_id, :match_date, :competition, :score, :possession, :shots)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':team_id' => $team_id,
                ':opponent_id' => $opponent_id,
                ':match_date' => $match_date,
                ':competition' => $competition,
                ':score' => $score,
                ':possession' => $possession,
                ':shots' => $shots
            ]);
            echo "Match on $match_date between Team $team_id and $opponent_id inserted successfully in $competition.<br>";
        } else {
            echo "Skipping match due to missing essential data (team_id, opponent_id, or match_date).<br>";
        }
    } else {
        echo "Skipping match due to missing homeTeam or awayTeam.<br>";
    }
}

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Specify the season you want to fetch (e.g., 2023/24 season)
$season = 2023;  // Change as necessary

// Define the competitions to fetch (Champions League and national leagues)
$competitions = ['CL', 'PL', 'PD', 'BL1']; // CL = Champions League, PL = Premier League, PD = La Liga, BL1 = Bundesliga

// Iterate over competitions to fetch and store matches
foreach ($competitions as $competition) {
    $data = fetchAllMatchesForCompetition($competition, $season);

    // Check if match data was fetched successfully
    if ($data && isset($data['matches'])) {
        // Insert all matches into the database
        try {
            foreach ($data['matches'] as $match) {
                insertMatchIntoDB($pdo, $match, $competition);
            }
            echo "All $competition match data successfully inserted into the database.<br>";
        } catch (PDOException $e) {
            echo "Error inserting matches for $competition: " . $e->getMessage();
        }
    } else {
        echo "No match data found for $competition.<br>";
    }
}

?>
