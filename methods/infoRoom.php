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
$db = new SQLiteConnection("db.sqlite3", true);
// check if the room exists
$stmt = $db->executeQuery("SELECT * FROM rooms WHERE id = :id", ['id' => $id]);
$room = $db->fetch($stmt);
$db->closeConnection();

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
if($player_type == null){
    echo json_encode(["ok"=>false, "result" => "Hash not found"]);
    http_response_code(400);
    exit;
}

$faction = null;
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

//sucessfull response
$game = $room["game"];
$you=[];
$you["player_type"] = $player_type;

//send "you" by player faction
if($player_type == "player" and isset($query["faction"])){
    foreach($room["players"] as $key => $player){
        if($player["faction"] == $faction){
            $you = array_merge($you, $player);
            break;
        }
    }
}


$o_players = orderPlayers($room["players"], $game["current_phase"]);
$players = $o_players["players"];
$you = isset($query["faction"]) ? $players[array_search($query["faction"], array_column($players, 'faction'))] : null;


$update_db = false;

//handle secondary
if ($game["unix_waiting_secondary_until"] <= time()) {
    $game["unix_waiting_secondary_until"] = null;
    foreach($room["players"] as $key => $player){
        $room["players"][$key]["will_use_secondary"] = null;
    }
    $update_db = true;
}


//check if player was online
if(isset($query["faction"]) and $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))]["last_online"] <= time() + 5){
    $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))]["last_online"] = time();
    $update_db = true;
}


if($update_db){
    $db = new SQLiteConnection("db.sqlite3", true);
    //set database
    $db->executeQuery("UPDATE rooms SET game = :game, players = :players, logs = :logs WHERE id = :id", [
        'game' => $room["game"],
        'players' => $room["players"],
        'logs' => $room["logs"],
        'id' => $id
    ]);
}


$current_player_id = array_search(true, array_column($room["players"], 'currently_playing'));

$data["current_server_time"] = time();


$what_front_should_do = whatFrontShouldDo($query["hash"], $faction, $room);


$data = [
    "game" => $game,
    "you" => $you,
    "players" => $players,
    "server_time" => time(),
    "what_front_should_do" => $what_front_should_do,
];

$response = [
    "ok" => true,
    "result" => "",
    "data" => $data
];

//send response
echo json_encode($response, JSON_PRETTY_PRINT);
http_response_code(200);
exit;
