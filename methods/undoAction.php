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


// check if faction is in the room and is admin
$faction = null;
foreach($room["players"] as $key => $player){
    if($player["faction"] == $query["faction"]){
        if($player["admin"] != true){
            echo json_encode(["ok"=>false, "result" => "You are not admin"]);
            http_response_code(400);
            exit;
        }
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


if(!$room["undo"]){
    echo json_encode(["ok"=>false, "result" => "Not possible to undo action"]);
    http_response_code(400);
    exit;
}

// undo action
$undo_key = array_key_last($room["undo"]);
$undo = $room["undo"][$undo_key];

$room["players"] = $undo["old_players"];

foreach($room["players"] as $key => $player){
    if($player["currently_playing"] == true){
        $room["players"][$key]["unix_playing_since"] = time() ;
        $room["players"][$key]["unix_playing_until"] = time() + $room["game"]["time_action"] + $room["players"][$key]["bonus_time"];
        break;
    }
}

$room["game"] = $undo["old_game"];
$room["logs"] = $undo["old_logs"];

unset($room["undo"][$undo_key]);



// response
$response = [
    "ok" => true,
    "result" => "Last action undone",
    "data" => [
        "game" => $room["game"],
        "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
        "players" => $room["players"],
    ]
];



//set database
$db->executeQuery("UPDATE rooms SET game = :game, players = :players, logs = :logs, undo = :undo WHERE id = :id", [
    'game' => $room["game"],
    'players' => $room["players"],
    'logs' => $room["logs"],
    'undo' => $room["undo"],
    'id' => $id
]);


echo json_encode($response, JSON_PRETTY_PRINT);
http_response_code(200);
exit;

