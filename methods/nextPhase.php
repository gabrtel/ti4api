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

//save old room values
$old_room = $room;

// check if faction is in the room
if(isset($query["faction"])){
    $faction = null;
    foreach($room["players"] as $key => $player){
        if($player["faction"] == $query["faction"]){
            $faction = $player["faction"];
            break;
        }
    }
    if($faction == null){
        echo json_encode(["ok"=>false, "result" => "Faction not found"]);
        http_response_code(400);
        exit;
    }
}


if(!isset($query["phase"])){
    echo json_encode(["ok"=>false, "result" => "No phase parameter specified"]);
    http_response_code(400);
    exit;
}

//check if player is admin
$is_admin = false;
foreach($room["players"] as $key => $player){
    if($player["faction"] == $query["faction"] and $player["admin"] == true){
        $is_admin = true;
        break;
    }
}
if($is_admin == false){
    echo json_encode(["ok"=>false, "result" => "Player is not admin"]);
    http_response_code(400);
    exit;
}


$phase = $query["phase"];


//check if game is paused
if($room["game"]["paused"] and $phase != "end_phase"){
    echo json_encode(["ok"=>false, "result" => "Game is curently paused"]);
    http_response_code(400);
    exit;
}




if($phase == "strategy_phase"){
    if(
        $room["game"]["current_phase"] == "strategy_phase" or
        $room["game"]["current_phase"] == "action_phase" or
        $room["game"]["current_phase"] == "status_phase" or
        $room["game"]["current_phase"] == "game_end"
    ){
        echo json_encode(["ok"=>false, "result" => "Current phase is not waiting_start or agenda_phase"]);
        http_response_code(400);
        exit;
    }
    
    /*check if all players have 
        strategy_card null, 
        already_used_strategy_card false, 
        already_passed_turn false,
        will_use_secondary null,
        unix_playing_since 0,
        unix_playing_until 0,
        currently_playing false, 
    */
    foreach($room["players"] as $key => $player){
        $room["players"][$key]["strategy_card"] = null;
        $room["players"][$key]["already_used_strategy_card"] = false;
        $room["players"][$key]["already_passed_turn"] = false;
        $room["players"][$key]["will_use_secondary"] = null;
        $room["players"][$key]["unix_playing_since"] = 0;
        $room["players"][$key]["unix_playing_until"] = 0;
        $room["players"][$key]["currently_playing"] = false;
    }

    //set current phase to strategy_phase
    $old_phase = $room["game"]["current_phase"];
    $room["game"]["current_phase"] = "strategy_phase";
    $room["game"]["current_turn"]++;
    $room["game"]["unix_waiting_secondary_until"] = false;
    $room["game"]["unix_time_in_phase"] = time();

    // order players
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];
    $current_player_id = $o_players["current_player_id"];
    $current_player_faction = $o_players["current_player_faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];

    //set first player as currently_playing
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
    $room["logs"][]["strategy_phase"][] = [
        "_" => "start_phase",
        "unix_time_start" => time(),
        "phase" => "strategy_phase",
    ];

    //put first player in the logs
    $round_key = array_key_last($room["logs"]);
    $phase_key = array_key_last($room["logs"][$round_key]);
    $room["logs"][$round_key][$phase_key][] = [
        "_" => "choosing_strategy",
        "faction" => $players[0]["faction"],
        "unix_time_start" => time(),
    ];


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

    //set you
    $you=[];
    $you["player_type"] = $player_type;
    if($player_type == "player"){
        foreach($room["players"] as $key => $player){
            if($player["faction"] == $faction){
                $you = array_merge($you, $player);
                break;
            }
        }
    }

    // order players
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];
    $current_player_id = $o_players["current_player_id"];
    $current_player_faction = $o_players["current_player_faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];

    //response
    $response = [
        "ok" => true,
        "result" => "Phase changed to strategy_phase",
        "data" => [
            "game" => $room["game"],
            "players" => $players,
            "you" => $you
        ]
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    http_response_code(200);
    exit;
}



elseif($phase == "agenda_phase"){
    if(
        $room["game"]["current_phase"] == "waiting_start" or
        $room["game"]["current_phase"] == "strategy_phase" or
        $room["game"]["current_phase"] == "action_phase" or
        $room["game"]["current_phase"] == "agenda_phase" or
        $room["game"]["current_phase"] == "game_end"
    ){
        echo json_encode(["ok"=>false, "result" => "Current phase is not status_phase"]);
        http_response_code(400);
        exit;
    }
    
    //check if all players have 
    //strategy_card null, 
    //already_used_strategy_card false, 
    //already_passed_turn false,
    //will_use_secondary null,
    //unix_playing_since 0,
    //unix_playing_until 0,
    //currently_playing false, 
    //foreach($room["players"] as $key => $player){
    //    $room["players"][$key]["strategy_card"] = null;
    //    $room["players"][$key]["already_used_strategy_card"] = false;
    //    $room["players"][$key]["already_passed_turn"] = false;
    //    $room["players"][$key]["will_use_secondary"] = null;
    //    $room["players"][$key]["unix_playing_since"] = 0;
    //    $room["players"][$key]["unix_playing_until"] = 0;
    //    $room["players"][$key]["currently_playing"] = false;
    //}

    //set current phase to agenda_phase
    $old_phase = $room["game"]["current_phase"];
    $room["game"]["current_phase"] = "agenda_phase";
    $room["game"]["unix_waiting_secondary_until"] = false;
    $room["game"]["unix_time_in_phase"] = time();
    
    //order players
    $o_players = orderPlayers($room["players"], $room["game"]["current_phase"]);
    $players = $o_players["players"];
    $current_player_id = $o_players["current_player_id"];
    $current_player_faction = $o_players["current_player_faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];

    //end last phase time in logs
    $round_key = array_key_last($room["logs"]);
    $phase_key = array_key_last($room["logs"][$round_key]);
    $room["logs"][$round_key][$phase_key][] = [
        "_" => "end_phase",
        "phase" => $old_phase,
        "unix_time_end" => time(),
    ];
    //start new phase in logs
    $room["logs"][$round_key]["agenda_phase"][] = [
        "_" => "start_phase",
        "unix_time_start" => time(),
        "phase" => "agenda_phase",
    ];

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

    //set you
    $you=[];
    $you["player_type"] = $player_type;
    if($player_type == "player"){
        foreach($room["players"] as $key => $player){
            if($player["faction"] == $faction){
                $you = array_merge($you, $player);
                break;
            }
        }
    }

    //response
    $response = [
        "ok" => true,
        "result" => "Phase changed to agenda_phase",
        "data" => [
            "game" => $room["game"],
            "players" => $players,
            "you" => $you
        ]
    ];
    echo json_encode($response);
    http_response_code(200);
    exit;

}

elseif($phase == "game_end"){
    if(
        $room["game"]["current_phase"] == "strategy_phase" or
        $room["game"]["current_phase"] == "game_end"
    ){
        echo json_encode(["ok"=>false, "result" => "Phase is not a valid phase"]);
        http_response_code(400);
        exit;
    }
    
   //set current phase to end_phase
   $old_phase = $room["game"]["current_phase"];
    $room["game"]["current_phase"] = "game_end";
    $room["game"]["unix_waiting_secondary_until"] = false;
    $room["game"]["unix_time_in_phase"] = time();

    //end last phase time in logs
    $round_key = array_key_last($room["logs"]);
    $phase_key = array_key_last($room["logs"][$round_key]);
    $room["logs"][$round_key][$phase_key][] = [
        "_" => "end_phase",
        "phase" => $old_phase,
        "unix_time_end" => time(),
    ];
    //start new phase in logs
    $room["logs"][$round_key]["game_end"][] = [
        "_" => "start_phase",
        "unix_time_start" => time(),
        "phase" => "game_end",
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

    //set you
    $you=[];
    $you["player_type"] = $player_type;
    if($player_type == "player"){
        foreach($room["players"] as $key => $player){
            if($player["faction"] == $faction){
                $you = array_merge($you, $player);
                break;
            }
        }
    }
    //response
    $response = [
        "ok" => true,
        "result" => "The game has ended!",
        "data" => [
            "game" => $room["game"],
            "players" => $room["players"],
            "you" => $you
        ]
    ];
    echo json_encode($response);
    http_response_code(200);
    exit;
}
else {
    echo json_encode(["ok"=>false, "result" => "Phase is not a valid phase"]);
    http_response_code(400);
    exit;
}