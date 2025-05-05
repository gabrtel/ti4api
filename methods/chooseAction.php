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

if(!isset($query["faction"])){
    echo json_encode(["ok"=>false, "result" => "No faction parameter specified"]);
    http_response_code(400);
    exit;
}

//check if game is paused
if($room["game"]["paused"]){
    echo json_encode(["ok"=>false, "result" => "Game is curently paused"]);
    http_response_code(400);
    exit;
}

//save old room values
$old_room = $room;






if(!isset($query["action"])){
    echo json_encode(["ok"=>false, "result" => "No strategy_card parameter specified"]);
    http_response_code(400);
    exit;
} elseif(in_array($query["action"], ["tatic", "component", "faction", "strategy", "pass_turn"])){
    //do nothing
} else {
    echo json_encode(["ok"=>false, "result" => "Invalid action"]);
    http_response_code(400);
    exit;
}


//check if player in his turn
if($room["players"][$player_key]["currently_playing"] == false){
    echo json_encode(["ok"=>false, "result" => "Player not currently playing"]);
    http_response_code(400);
    exit;
}

//check if player has already passed
if($room["players"][$player_key]["already_passed_turn"] == true){
    echo json_encode(["ok"=>false, "result" => "Player already passed turn"]);
    http_response_code(400);
    exit;
}

//if player choose strategy, check if player has already used strategy card
if($query["action"] == "strategy" and $room["players"][$player_key]["already_used_strategy_card"] == true){
    echo json_encode(["ok"=>false, "result" => "Player already used strategy card"]);
    http_response_code(400);
    exit;
}

// if strategy is 3 politics, check if user send valid speaker parameter
if($query["action"] == "strategy" and $room["players"][$player_key]["strategy_card"] == 3){
    if(!isset($query["speaker"])){
        echo json_encode(["ok"=>false, "result" => "No speaker parameter specified"]);
        http_response_code(400);
        exit;
    }
    $has_speaker = false;

    foreach($room["players"] as $key => $player){
        if($player["faction"] === $query["speaker"]){
            $speaker_key = $key;
            $has_speaker = true;
        }
    }
    if(!$has_speaker){
        echo json_encode(["ok"=>false, "result" => "Speaker parameter is not valid"]);
        http_response_code(400);
        exit;
    }
    //unset old speaker
    foreach($room["players"] as $key => $player){
        $room["players"][$key]["speaker"] = false;
    }
    //set new speaker
    $room["players"][$speaker_key]["speaker"] = true;
}


//order players
$o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
$players = $o_players["players"];
$current_player_id = $o_players["current_player_id"];
$current_player_faction = $o_players["current_player_faction"];
$next_player_key = $next_player_id = $o_players["next_player_id"];
$next_player_faction = $o_players["next_player_faction"];






//if player choose strategy, set already_used_strategy_card to false
if($query["action"] == "strategy"){
    $room["players"][$player_key]["already_used_strategy_card"] = true;
}
//if player choose pass_turn, set already_passed_turn to true
if($query["action"] == "pass_turn"){
    $room["players"][$player_key]["already_passed_turn"] = true;
}

//check if all players passed turn
$all_passed = true;
foreach($room["players"] as $key => $player){
    if($player["already_passed_turn"] == false){
        $all_passed = false;
        break;
    }
}


//choose action
$room["players"][$player_key]["currently_playing"] = false;
$room["players"][$player_key]["bonus_time"] = $room["players"][$player_key]["unix_playing_until"] - $room["players"][$player_key]["unix_playing_since"];


//end current player in logs
$round_key = array_key_last($room["logs"]);
$phase_key = array_key_last($room["logs"][$round_key]);
$room["logs"][$round_key][$phase_key][] = [
    "_" => "choosing_action",
    "faction" => $room["players"][$player_key]["faction"],
    "action" => $query["action"],
    "speaker" => $query["speaker"]??null,
    "unix_time_end" => time(),
];


//if action is strategy, add secondary time to current time
if($query["action"] == "strategy"){
    $time = time() + $room["game"]["time_secondary"]+ $room["game"]["time_secondary_delay"] ;
} else{
    $time = time();
}

//if everybody passed turn, go to next phase
if($all_passed){
    //go to next phase
    $old_phase = $room["game"]["current_phase"];
    $room["game"]["current_phase"] = "status_phase";
    $room["game"]["unix_time_in_phase"] = $time;
    //set all players to not currently playing
    foreach($room["players"] as $key => $player){
        $room["players"][$key]["currently_playing"] = false;
        $room["players"][$key]["already_passed_turn"] = false;
        $room["players"][$key]["already_used_strategy_card"] = true;
    }
    
    //end phase in logs
    $round_key = array_key_last($room["logs"]);
    $phase_key = array_key_last($room["logs"][$round_key]);
    $room["logs"][$round_key][$phase_key][] = [
        "_" => "end_phase",
        "unix_time_end" => $time,
    ];

    //start status_phase in logs
    $room["logs"][$round_key]["status_phase"][] = [
        "_" => "start_phase",
        "unix_time_start" => $time,
        "phase" => "status_phase",
    ];



    //order players
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];
    $current_player_id = $o_players["current_player_id"];
    $current_player_faction = $o_players["current_player_faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];

     //response
     $response = [
        "ok" => true,
        "result" => "Sucess! No next player, going to next phase",
        "data" => [
            "game" => $room["game"],
            "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
            "players" => $players,
        ]
    ];

} else {

    if($next_player_key === null){
        $next_player_key = $current_player_id;
    }

    //there is next player
    $room["players"][$next_player_key]["currently_playing"] = true;
    $room["players"][$next_player_key]["unix_playing_since"] = $time;
    $room["players"][$next_player_key]["unix_playing_until"] = $time + $room["game"]["time_action"] + $room["players"][$next_player_key]["bonus_time"];

    //start new player in logs
    $round_key = array_key_last($room["logs"]);
    $phase_key = array_key_last($room["logs"][$round_key]);
    $room["logs"][$round_key][$phase_key][] = [
        "_" => "choosing_action",
        "faction" => $room["players"][$next_player_key]["faction"],
        "unix_time_start" => $time,
    ];


    //order players
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];
    $current_player_id = $o_players["current_player_id"];
    $current_player_faction = $o_players["current_player_faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];

    //response
    $response = [
        "ok" => true,
        "result" => "Action sucessfully chosen!",
        "data" => [
            "game" => $room["game"],
            "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
            "players" => $players,
        ]
    ];

}


//if action is strategy, set waiting_secondary to time()
if($query["action"] == "strategy"){
    $room["game"]["unix_waiting_secondary_until"] = $time;
    $room["game"]["last_strategy"] = $room["players"][$player_key]["strategy_card"];
}

//open secondary time in logs
$round_key = array_key_last($room["logs"]);
$phase_key = array_key_last($room["logs"][$round_key]);
$room["logs"][$round_key]["action_phase"][] = [
    "_" => "start_secondary",
    "unix_time_start" => $time,
];

//removes old room values in undo
if (count($room["undo"]) > 3) {
    array_shift($room["undo"]); // removes the first element
}
if (count($room["undo"]) > 3) {
    array_shift($room["undo"]); // removes the first element
}
//save old room values in undo
$room["undo"][] = [
    "old_game" => $old_room["game"],
    "old_players" => $old_room["players"],
    "old_logs" => $old_room["logs"],
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





