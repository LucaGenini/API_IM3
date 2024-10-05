<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the config file for DB connection
require_once 'config.php';

// Function to fetch football data from the API (Champions League Teams)
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

// Function to compare teams from the database with API teams
function compareTeamsWithAPI($pdo, $apiTeams) {
    // Fetch teams from the database
    $sql = "SELECT team_name FROM Teams";
    $stmt = $pdo->query($sql);
    $dbTeams = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Extract team names from the API response
    $apiTeamNames = array_column($apiTeams['teams'], 'name');

    // Compare the teams in the database with the API teams
    $missingTeams = array_diff($apiTeamNames, $dbTeams);
    $extraTeams = array_diff($dbTeams, $apiTeamNames);

    if (!empty($missingTeams)) {
        echo "Teams missing from the database: <br>";
        print_r($missingTeams);
    } else {
        echo "All teams from the API are present in the database.<br>";
    }

    if (!empty($extraTeams)) {
        echo "Teams in the database but not in the API: <br>";
        print_r($extraTeams);
    }
}

// Function to fetch matches of a team from the API
function fetchTeamMatches($teamId, $competition) {
    $url = "http://api.football-data.org/v4/teams/$teamId/matches?competitions=$competition";
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
        return false;
    }
    curl_close($ch);

    // Return decoded JSON data
    return json_decode($response, true);
}

// Function to check if a team already exists in the database
function checkTeamExists($pdo, $team_name) {
    $sql = "SELECT team_id FROM Teams WHERE team_name = :team_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':team_name' => $team_name]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to insert missing team into the database
function checkAndInsertTeam($pdo, $team) {
    // Check if the team already exists using team name to prevent duplicates
    $existingTeam = checkTeamExists($pdo, $team['name']);
    
    if (!$existingTeam) {
        // Insert the team if it does not exist in the database
        $sqlInsert = "INSERT INTO Teams (team_id, team_name, crest_url) VALUES (:team_id, :team_name, :crest_url)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':team_id' => $team['id'],
            ':team_name' => $team['name'],
            ':crest_url' => isset($team['crest']) ? $team['crest'] : null
        ]);
        echo "Inserted missing team: " . $team['name'] . "<br>";
    } else {
        echo "Team " . $team['name'] . " already exists in the database.<br>";
    }
}

// Function to insert match data into the database
function insertMatchIntoDB($pdo, $match, $leagueType) {
    // Check if both home and away teams exist in the Teams table
    if (isset($match['homeTeam']) && isset($match['awayTeam'])) {
        checkAndInsertTeam($pdo, $match['homeTeam']);
        checkAndInsertTeam($pdo, $match['awayTeam']);
        
        // Proceed with match insertion after checking teams
        $team_id = $match['homeTeam']['id'];
        $opponent_id = $match['awayTeam']['id'];
        $match_date = isset($match['utcDate']) ? $match['utcDate'] : null;
        $score = isset($match['score']['fullTime']['homeTeam']) && isset($match['score']['fullTime']['awayTeam']) 
            ? $match['score']['fullTime']['homeTeam'] . "-" . $match['score']['fullTime']['awayTeam'] 
            : "N/A";
        $possession = isset($match['statistics']['possession']) ? $match['statistics']['possession'] : 0;
        $shots = isset($match['statistics']['shotsOnGoal']) ? $match['statistics']['shotsOnGoal'] : 0;

        if ($team_id && $opponent_id && $match_date) {
            $sql = "INSERT INTO Matches (team_id, opponent_id, match_date, league, score, possession, shots) 
                    VALUES (:team_id, :opponent_id, :match_date, :league, :score, :possession, :shots)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':team_id' => $team_id,
                ':opponent_id' => $opponent_id,
                ':match_date' => $match_date,
                ':league' => $leagueType,
                ':score' => $score,
                ':possession' => $possession,
                ':shots' => $shots
            ]);
        } else {
            echo "Skipping match due to missing essential data (team_id, opponent_id, or match_date).<br>";
        }
    } else {
        echo "Skipping match due to missing homeTeam or awayTeam.<br>";
    }
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

// Compare teams in database with API teams
compareTeamsWithAPI($pdo, $data);

// The rest of your logic remains unchanged...
try {
    // Iterate through the teams and insert data
    foreach ($data['teams'] as $team) {
        if (isset($team['name']) && isset($team['crest']) && isset($team['id'])) {
            
            // Check if the team already exists in the database
            checkAndInsertTeam($pdo, $team);

            // Fetch matches for the team in Champions League and National League
            $championsLeagueMatches = fetchTeamMatches($team['id'], "CL");
            $nationalLeagueMatches = fetchTeamMatches($team['id'], "PL"); // Example for Premier League

            // Insert the match data into the database (assuming Matches table exists)
            if ($championsLeagueMatches && isset($championsLeagueMatches['matches'])) {
                foreach ($championsLeagueMatches['matches'] as $match) {
                    insertMatchIntoDB($pdo, $match, 'Champions League');
                }
            }

            if ($nationalLeagueMatches && isset($nationalLeagueMatches['matches'])) {
                foreach ($nationalLeagueMatches['matches'] as $match) {
                    insertMatchIntoDB($pdo, $match, 'National League');
                }
            }

        } else {
            echo "Skipping team due to missing essential data (name, crest, or id).<br>";
        }
    }

    echo "Data successfully inserted into the database.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>