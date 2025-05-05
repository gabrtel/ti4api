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

if(!isset($query["strategy_card"]) or !preg_match('/^[1-8]$/', $query["strategy_card"])){
    echo json_encode(["ok"=>false, "result" => "No strategy_card parameter specified"]);
    http_response_code(400);
    exit;
}

//check if game is paused
if($room["game"]["paused"]){
    echo json_encode(["ok"=>false, "result" => "Game is curently paused"]);
    http_response_code(400);
    exit;
}

//check if player in his turn
if($room["players"][$player_key]["currently_playing"] == false){
    echo json_encode(["ok"=>false, "result" => "You are not currently playing"]);
    http_response_code(400);
    exit;
}

//check if strategy card was not already choosen by other player
foreach($room["players"] as $key => $player){
    if($player["strategy_card"] == $query["strategy_card"]){
        echo json_encode(["ok"=>false, "result" => "Strategy card already choosen"]);
        http_response_code(400);
        exit;
    }
}

//save old room values
$old_room = $room;


//order players
$o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
$players = $o_players["players"];
$current_player_id = $o_players["current_player_id"];
$current_player_faction = $o_players["current_player_faction"];
$next_player_id = $o_players["next_player_id"];
$next_player_faction = $o_players["next_player_faction"];


//choose strategy card
$room["players"][$player_key]["strategy_card"] = $query["strategy_card"];
$room["players"][$player_key]["currently_playing"] = false;
$room["players"][$player_key]["already_passed_turn"] = true;
$room["players"][$player_key]["already_used_strategy_card"] = false;
$room["players"][$player_key]["unix_playing_until"] = time();
$room["players"][$player_key]["bonus_time"] = $room["players"][$player_key]["unix_playing_until"] - $room["players"][$player_key]["unix_playing_since"];
$room["players"][$player_key]["time_playing"] = $room["players"][$player_key]["time_playing"] + $room["players"][$player_key]["unix_playing_until"] - $room["players"][$player_key]["unix_playing_since"];


//end current player in logs logs
$round_key = array_key_last($room["logs"]);
$phase_key = array_key_last($room["logs"][$round_key]);
$room["logs"][$round_key][$phase_key][] = [
    "_" => "choosing_strategy",
    "faction" => $room["players"][$player_key]["faction"],
    "strategy_card" => $query["strategy_card"],
    "unix_time_end" => time(),
];



//there is no next player, go to next phase
if($next_player_id === null or $next_player_id === false){

    //go to next phase
    $old_phase = $room["game"]["current_phase"];
    $room["game"]["current_phase"] = "action_phase";
    $room["game"]["unix_time_in_phase"] = time();

    //order players
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];
    $current_player_id = $o_players["current_player_id"];
    $current_player_faction = $o_players["current_player_faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];

    /*check if all players have 
        already_used_strategy_card false, 
        already_passed_turn false,
        will_use_secondary null,
        unix_playing_since 0,
        unix_playing_until 0,
        currently_playing false, 
    */
    foreach($room["players"] as $key => $player){
        $room["players"][$key]["already_used_strategy_card"] = false;
        $room["players"][$key]["already_passed_turn"] = false;
        $room["players"][$key]["will_use_secondary"] = null;
        $room["players"][$key]["unix_playing_since"] = 0;
        $room["players"][$key]["unix_playing_until"] = 0;
        $room["players"][$key]["currently_playing"] = false;
    }

    //set current player as currently_playing
    $room["players"][$current_player_id]["currently_playing"] = true;
    $room["players"][$current_player_id]["unix_playing_since"] = time();
    $room["players"][$current_player_id]["unix_playing_until"] = time() + $room["game"]["time_action"] + $room["players"][$current_player_id]["bonus_time"];

    //end last phase time in logs
    $round_key = array_key_last($room["logs"]);
    $phase_key = array_key_last($room["logs"][$round_key]);
    $room["logs"][$round_key][$phase_key][] = [
        "_" => "end_phase",
        "phase" => $old_phase,
        "unix_time_end" => time(),
    ];

    //start new phase in logs
    $room["logs"][$round_key]["action_phase"][] = [
        "_" => "start_phase",
        "unix_time_start" => time(),
        "phase" => "action_phase",
    ];

     //put next player in the logs
     $room["logs"][$round_key]["action_phase"][] = [
        "_" => "choosing_action",
        "faction" => $players[0]["faction"],
        "unix_time_start" => time(),
    ];

    //set database
    $db->executeQuery("UPDATE rooms SET game = :game, players = :players, logs = :logs WHERE id = :id", [
        'game' => $room["game"],
        'players' => $room["players"],
        'logs' => $room["logs"],
        'id' => $id
    ]);


    
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];


    //response
    $response = [
        "ok" => true,
        "response" => "Last strategy choosed sucessfully, going to next phase.",
        "data" => [
            "game" => $room["game"],
            "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
            "players" => $players,
        ]
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    http_response_code(200);
    exit;
}

//set next player as currently_playing
$room["players"][$next_player_id]["currently_playing"] = true;
$room["players"][$next_player_id]["unix_playing_since"] = time();
$room["players"][$next_player_id]["unix_playing_until"] = time() + $room["game"]["time_action"] + $room["players"][$next_player_id]["bonus_time"];


//put next player in the logs
$round_key = array_key_last($room["logs"]);
$phase_key = array_key_last($room["logs"][$round_key]);
$room["logs"][$round_key][$phase_key][] = [
    "_" => "choosing_strategy",
    "faction" => $players[0]["faction"],
    "unix_time_start" => time(),
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
    "response" => "Strategy sucessfully chosen.", 
    "data" => [
        "game" => $room["game"],
        "you" => $room["players"][array_search($query["faction"], array_column($room["players"], 'faction'))],
        "players" => $players,

    ]
];
echo json_encode($response, JSON_PRETTY_PRINT);
http_response_code(200);
exit;