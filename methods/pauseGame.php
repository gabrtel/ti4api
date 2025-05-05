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

if($query["pause"] === false or $query["pause"] === true){
    //ok
} else{
    echo json_encode(["ok"=>false, "result" => "pause parameter is not a valid boolean"]);
    http_response_code(400);
    exit;
}



// check if the game is paused
if($query["pause"] === true and $room["game"]["unix_paused_since"] <= time() and time() < $room["game"]["unix_paused_until"] ){
    echo json_encode(["ok"=>false, "result" => "Game is already paused"]);
    http_response_code(400);
    exit;
}

// check if the game is unpaused
if($query["pause"] === false and !$room["game"]["paused"] and ($room["game"]["unix_paused_since"] > time() or time() >= $room["game"]["unix_paused_until"]) ){
    echo json_encode(["ok"=>false, "result" => "Game is already unpaused"]);
    http_response_code(400);
    exit;
}

$player_key = null;
$faction_playing = null;
// check faction currently playing to give paused time
foreach($room["players"] as $key => $player){
    if($player["currently_playing"] == true){
        $faction_playing = $player["faction"];
        $player_key = $key;
        break;
    }
}

//pause the game
if($query["pause"] === true){
    $room["game"]["unix_paused_since"] = time();
    $room["game"]["paused"] = true;
    if(isset($player_key)){
        $room["game"]["faction_to_give_pause_time"] = $player_key;
    } else{
        $room["game"]["faction_to_give_pause_time"] = null;
    }

    // response
    $response = [
        "ok" => true,
        "result" => "Game paused",
        "data" => [
            "game" => $room["game"],
            "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
            "players" => $room["players"],
        ]
    ];


} else{
    $time_paused = time() - $room["game"]["unix_paused_since"];
    $room["game"]["unix_paused_until"] = time();
    $room["game"]["paused"] = false;
    if($room["game"]["faction_to_give_pause_time"] != null){
        $room["players"][$room["game"]["faction_to_give_pause_time"]]["unix_playing_since"] += $time_paused ;
        $room["players"][$room["game"]["faction_to_give_pause_time"]]["unix_playing_until"] += $time_paused;
    }
    $room["game"]["faction_to_give_pause_time"] = null;

    // response
    $response = [
        "ok" => true,
        "result" => "Game unpaused",
        "data" => [
            "game" => $room["game"],
            "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
            "players" => $room["players"],
        ]
    ];
}


//set database
$db->executeQuery("UPDATE rooms SET game = :game, players = :players, logs = :logs WHERE id = :id", [
    'game' => $room["game"],
    'players' => $room["players"],
    'logs' => $room["logs"],
    'id' => $id
]);


echo json_encode($response, JSON_PRETTY_PRINT);
http_response_code(200);
exit;

