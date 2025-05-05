<?php


// Check if required parameters are set or is not array
if(!isset($query["new_players"])){
    echo json_encode(["ok"=>false, "result" => "No new_players parameter specified"]);
    http_response_code(400);
    exit;
}


if(!is_array($query["new_players"])){
    echo json_encode(["ok"=>false, "result" => "new_players parameter is not a valid array"]);
    http_response_code(400);
    exit;
}


//check if has time_action and it is a number
if(isset($query["time_action"])){
    if(!is_numeric($query["time_action"])){
        echo json_encode(["ok"=>false, "result" => "time_action parameter is not a valid number"]);
        http_response_code(400);
        exit;
    }
}
//check if has time_secondary and it is a number
if(isset($query["time_secondary"])){
    if(!is_numeric($query["time_secondary"])){
        echo json_encode(["ok"=>false, "result" => "time_secondary parameter is not a valid number"]);
        http_response_code(400);
        exit;
    }
}

//check if has time_secondary and it is a number
if(isset($query["time_secondary_delay"])){
    if(!is_numeric($query["time_secondary_delay"])){
        echo json_encode(["ok"=>false, "result" => "time_secondary_delay parameter is not a valid number"]);
        http_response_code(400);
        exit;
    }
}


//check if players is not empty
$new_players = [];
$a=0;
foreach($query["new_players"] as $key => $value){
    if(!isset($value["name"]) or !isset($value["faction"])){
        echo json_encode(["ok"=>false, "result" => "No name or faction parameter specified"]);
        http_response_code(400);
        exit;
    }
    if(isset($value["admin"]) and $value["admin"] == true){
        $at_least_one_admin = true;
    }

    //check if name is not duplicated
    foreach($new_players as $player){
        if(strcasecmp($player["name"], $value["name"]) == 0){
            echo json_encode(["ok"=>false, "result" => "Name is duplicated"]);
            http_response_code(400);
            exit;
        }
    }

    //check if faction is not duplicated
    foreach($new_players as $player){
        if(strcasecmp($player["faction"], $value["faction"]) == 0){
            echo json_encode(["ok"=>false, "result" => "Faction is duplicated"]);
            http_response_code(400);
            exit;
        }
    }

    //check if faction is valid
    if(validFaction($value["faction"]) == false){
        echo json_encode(["ok"=>false, "result" => "Faction is not valid"]);
        http_response_code(400);
        exit;
    }
    $new_players[] = [
        "name" => $value["name"],
        "faction" => strtolower($value["faction"]),
        "admin" => isset($value["admin"]) ? $value["admin"] : false,
        "speaker" => $a === 0 ? true : false,
        "strategy_card" => null,
        "already_used_strategy_card" => false,
        "already_passed_turn" => false,
        "will_use_secondary" => null,
        "time_playing"=> 0,
        "currently_playing"=> false,
        "unix_playing_since"=> 0,
        "unix_playing_until"=> 0,
        "bonus_time"=> 0,
        "last_online"=> 0
    ];
    $a++;
}

//check if has at least one admin
if(!isset($at_least_one_admin)){
    echo json_encode(["ok"=>false, "result" => "No player was specified as admin"]);
    http_response_code(400);
    exit;
}

//create hashes
$hashes = [
    ["player_type"=> "player", "hash"=> rtrim(strtr(uniqid().base64_encode(random_bytes(9)), '+/', '-_'), '=')],
    ["player_type"=> "spectator", "hash"=> rtrim(strtr(uniqid().base64_encode(random_bytes(9)), '+/', '-_'), '=')]  
];

//check 


//create game
$game = [
    "current_phase" => "waiting_start",
    "current_turn" => 0,
    "unix_waiting_secondary_until" => false,
    "last_strategy" => null,
    "unix_time_in_phase" => time(),
    "unix_paused_since" => 0,
    "unix_paused_until" => 0,
    "paused" => false,
    "faction_to_give_pause_time" => null,
    "time_action" => $query["time_action"] ?? 60,
    "time_secondary" => $query["time_secondary"] ?? 15,
    "time_secondary_delay" => $query["time_secondary_delay"] ?? 15,
];

//create logs
$logs = [
    [
        "waiting_start" => [
            "_" => "start_phase",
            "unix_time_start" => time(),
            "current_phase" => "waiting_start",
        ]
        
    ]
];

$db = new SQLiteConnection("db.sqlite3", false);
$resp = $db->executeQuery("INSERT INTO rooms (hashes, game, players, paused, logs, undo, others) VALUES (:hashes, :game, :players, :paused, :logs, :undo, :others)", [
    'hashes' => $hashes,
    'game' => $game,
    'players' => $new_players,
    'logs' => $logs,
    'undo' => [],
    'others' => []
]);
$last_id = $db->lastInsertId();
$db->closeConnection();

//format hashes
$f_hashes = [];
foreach($hashes as $key => $value){
    $hashes[$key]["hash"] = $last_id.":".$value["hash"];
}

echo json_encode(["ok"=>true, "result" => "Game was created", "data"=> $hashes]);
http_response_code(200);
exit;