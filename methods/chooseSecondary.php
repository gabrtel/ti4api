<?php
// Check if required parameters are set or is not array
if(!isset($query["hash"])){
    echo json_encode(["ok"=>false, "result" => "No hash parameter specified"]);
    http_response_code(400);
    exit;
}
$hash = $query["hash"];
if(!is_string($hash) or empty($hash)){
    echo json_encode(["ok"=>false, "result" => "Hash parameter is not a valid string"]);
    http_response_code(400);
    exit;
}
$hash = explode(":", $hash);
$id = $hash[0];
$hash = $hash[1];

// open the database connection
$db = new SQLiteConnection("db.sqlite3", false);
// check if the room exists
$stmt = $db->executeQuery("SELECT * FROM rooms WHERE id = :id", ['id' => $id]);
$room = $db->fetch($stmt);
if(!$room){
    echo json_encode(["ok"=>false, "result" => "Room not found"]);
    http_response_code(400);
    exit;
}
// check if the hash is valid
$player_type = null;
foreach($room["hashes"] as $key => $h){
    if($h["hash"] == $hash){
        $player_type = $h["player_type"];
        break;
    }
}
if($player_type == null or $player_type != "player"){
    echo json_encode(["ok"=>false, "result" => "Hash not found"]);
    http_response_code(400);
    exit;
}


if(!isset($query["faction"])){
    echo json_encode(["ok"=>false, "result" => "No faction parameter specified"]);
    http_response_code(400);
    exit;
}


// check if faction is in the room
if(isset($query["faction"])){
    $faction = null;
    foreach($room["players"] as $key => $player){
        if($player["faction"] == $query["faction"]){
            $faction = $player["faction"];
            $player_key = $key;
            break;
        }
    }
    if($faction == null){
        echo json_encode(["ok"=>false, "result" => "Faction not found"]);
        http_response_code(400);
        exit;
    }
}

//check if game is paused
if($room["game"]["paused"]){
    echo json_encode(["ok"=>false, "result" => "Game is curently paused"]);
    http_response_code(400);
    exit;
}






if(!isset($query["secondary"])){
    echo json_encode(["ok"=>false, "result" => "No secondary parameter specified"]);
    http_response_code(400);
    exit;
}
if($query["secondary"] != $room["game"]["last_strategy"]){
    echo json_encode(["ok"=>false, "result" => "This secondary cannot be chosen"]);
    http_response_code(400);
    exit;
}





// get user key by faction
foreach($room["players"] as $key => $player){
    if($player["faction"] == $query["faction"]){
        $player_key = $key;
        break;
    }
}


//if user has the same secondary as the last strategy
if($query["secondary"] == $room["players"][$player_key]["strategy_card"]){
    echo json_encode(["ok"=>false, "result" => "You cannot use the same secondary as your strategy_card"]);
    http_response_code(400);
    exit;
}








// set the player_key
$room["players"][$player_key]["will_use_secondary"] = $query["secondary"];

//log the secondary action
//end current player in logs
$round_key = array_key_last($room["logs"]);
$phase_key = array_key_last($room["logs"][$round_key]);
$room["logs"][$round_key]["action_phase"][] = [
    "_" => "choosed_secondary",
    "faction" => $room["players"][$player_key]["faction"],
    "secondary" => $query["secondary"],
    "unix_time_end" => time(),
];


//set database
$db->executeQuery("UPDATE rooms SET game = :game, players = :players, logs = :logs WHERE id = :id", [
    'game' => $room["game"],
    'players' => $room["players"],
    'logs' => $room["logs"],
    'id' => $id
]);












//response
$response = [
    "ok" => true,
    "response" => "Secondary option was choosed.", 
    "data" => [
        "game" => $room["game"],
        "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
        "players" => $room["players"],

    ]
];


echo json_encode($response, JSON_PRETTY_PRINT);
http_response_code(200);
exit;
