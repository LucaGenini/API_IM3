<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Function to insert match data directly into the database (Manual entry method)
function insertMatchIntoDB($pdo, $match_data) {
    // Insert match data into the database if all required fields are available
    if ($match_data['home_team_id'] && $match_data['away_team_id'] && $match_data['match_date']) {
        $sql = "INSERT INTO Matches (
                    home_team_id, 
                    home_team_name, 
                    away_team_id, 
                    away_team_name, 
                    match_date, 
                    league, 
                    score, 
                    halftime_home, 
                    halftime_away, 
                    winner, 
                    referee_name, 
                    referee_nationality
                ) VALUES (
                    :home_team_id, :home_team_name, :away_team_id, :away_team_name, 
                    :match_date, :league, :score, :halftime_home, :halftime_away, :winner, 
                    :referee_name, :referee_nationality
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':home_team_id' => $match_data['home_team_id'],
            ':home_team_name' => $match_data['home_team_name'],
            ':away_team_id' => $match_data['away_team_id'],
            ':away_team_name' => $match_data['away_team_name'],
            ':match_date' => $match_data['match_date'],
            ':league' => $match_data['league'],
            ':score' => $match_data['score'],
            ':halftime_home' => $match_data['halftime_home'],
            ':halftime_away' => $match_data['halftime_away'],
            ':winner' => $match_data['winner'],
            ':referee_name' => $match_data['referee_name'],
            ':referee_nationality' => $match_data['referee_nationality']
        ]);
        echo "Match on " . $match_data['match_date'] . " between " . $match_data['home_team_name'] . " and " . $match_data['away_team_name'] . " inserted successfully.<br>";
    } else {
        echo "Skipping match due to missing essential data.<br>";
    }
}

// Establish database connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Example of manual match data based on the new table structure
$manual_matches = [
    [
        'home_team_id' => 1,
        'home_team_name' => 'Manchester City FC',
        'away_team_id' => 2,
        'away_team_name' => 'FC Porto',
        'match_date' => '2023-10-15',
        'league' => 'Champions League',
        'score' => '3-2',
        'halftime_home' => 1,
        'halftime_away' => 1,
        'winner' => 'Manchester City FC',
        'referee_name' => 'Mike Dean',
        'referee_nationality' => 'English'
    ],
    [
        'home_team_id' => 3,
        'home_team_name' => 'Real Madrid CF',
        'away_team_id' => 4,
        'away_team_name' => 'FC Barcelona',
        'match_date' => '2023-10-16',
        'league' => 'La Liga',
        'score' => '1-1',
        'halftime_home' => 1,
        'halftime_away' => 0,
        'winner' => 'draw',
        'referee_name' => 'Antonio Mateu',
        'referee_nationality' => 'Spanish'
    ]
    // Add more matches as needed
];

// Iterate over manual match data and insert each into the database
foreach ($manual_matches as $match) {
    insertMatchIntoDB($pdo, $match);
}

?>
