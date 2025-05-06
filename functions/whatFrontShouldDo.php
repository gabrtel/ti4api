<?php


function whatFrontShouldDo($hash, $faction, $room){
    


    //strategy cards names
    $strategy_names =[
        1 => "Leadership",
        2 => "Diplomacy",
        3 => "Politics",
        4 => "Construction",
        5 => "Trade",
        6 => "Warfare",
        7 => "Tecnology",
        8 => "Imperium"
    ];



    $api_link = "https://server.gabr.tel/ti4/api/api1.php";


    $game = $room["game"];
    
    //order players
    $o_players = orderPlayers($room["players"], $game["current_phase"]);
    $current_player_id = array_search(true, array_column($room["players"], 'currently_playing'));
    $current_player_faction = $room["players"][$current_player_id]["faction"];
    $next_player_id = $o_players["next_player_id"];
    $next_player_faction = $o_players["next_player_faction"];
    $players = $o_players["players"];

    $own_player_id = array_search($faction, array_column($room["players"], 'faction'));

    $you = $room["players"][$own_player_id];

    $what_front_should_do = null;
//ask who is the player
if(!isset($faction)){
    $what_front_should_do["title"] = "Who are you?";
    $what_front_should_do["options"] = [];
    foreach($room["players"] as $key => $player){
        $what_front_should_do["options"][] = [
            "option" => $player["faction"],
            "link" => $api_link."?method=infoRoom&hash={$hash}&faction={$player["faction"]}",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $player["faction"]
            ]
        ];
    }
}


elseif($game["current_phase"] == "game_end"){
    $what_front_should_do["title"] = "Game has ended!";
}

//game is paused
elseif($room["game"]["paused"] and $room["players"][array_search($faction, array_column($room["players"], 'faction'))]["admin"] == true){
    $what_front_should_do["title"] = "Game is paused";
    $what_front_should_do["countdown"] = $room["game"]["unix_paused_since"];
    $what_front_should_do["options"] = [
        [
            "option" => "Continue the game",
            "link" => $api_link."?method=pauseGame&hash={$hash}&faction={$faction}&pause=false",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "pause" => false
            ]
        ],
        [
            "option" => "End Game",
            "link" => $api_link."?method=nextPhase&hash={$hash}&faction={$faction}&phase=game_end",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "phase" => "game_end"
            ]
        ],
    ];
}

elseif($room["game"]["paused"]){
    $what_front_should_do["title"] = "Game is paused, waiting for admin to continue";
    $what_front_should_do["countdown"] = $room["game"]["unix_paused_since"];
}


//admin must choose to start the game or go to strategy phase
elseif(
    ($game["current_phase"] == "waiting_start" or $game["current_phase"] == "agenda_phase") and isset($faction) and 
    $room["players"][array_search($faction, array_column($room["players"], 'faction'))]["admin"] == true
){
    if($game["current_phase"] == "waiting_start"){
        $what_front_should_do["title"] = "Press start to begin the game";
        $option = "Start the game";
    } else{
        $what_front_should_do["title"] = "You are currently in agenda phase";
        $option = "Go to Strategy phase";
    }
    $what_front_should_do["countdown"] = $game["unix_time_in_phase"];
    $what_front_should_do["options"] = [
        [
            "option" => $option,
            "link" => $api_link."?method=nextPhase&hash={$hash}&faction={$faction}&phase=strategy_phase",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "phase" => "strategy_phase"
            ]
        ],
        [
            "option" => "End Game",
            "link" => $api_link."?method=nextPhase&hash={$hash}&faction={$faction}&phase=game_end",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "phase" => "game_end"
            ]
        ],
    ];
}


//player must wait for admin to start the game
elseif($game["current_phase"] == "waiting_start"){
    $what_front_should_do["title"] = "Waiting for an admin to start the game";
}
//player must wait for admin to continue the game
elseif($game["current_phase"] == "agenda_phase"){
    $what_front_should_do["title"] = "Agenda Phase";
}


//admin must choose to continue the game
elseif($game["current_phase"] == "status_phase" and $room["players"][array_search($faction, array_column($room["players"], 'faction'))]["admin"] == true){
    $what_front_should_do["title"] = "You are currently in status phase";
    $what_front_should_do["countdown"] = $game["unix_time_in_phase"];
    $what_front_should_do["options"] = [
        [
            "option" => "Go to Agenda phase",
            "link" => $api_link."?method=nextPhase&hash={$hash}&faction={$faction}&phase=agenda_phase",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "phase" => "agenda_phase"
            ]
        ],
        [
            "option" => "End Game",
            "link" => $api_link."?method=nextPhase&hash={$hash}&faction={$faction}&phase=game_end",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "phase" => "game_end"
            ]
        ],
    ];
}

//player must wait for admin
elseif($game["current_phase"] == "status_phase"){
    $what_front_should_do["title"] = "Waiting for admin handle status phase";
    $what_front_should_do["countdown"] = $game["unix_time_in_phase"];
}




//player that use strategy is waiting for secondary
elseif(time() < $game["unix_waiting_secondary_until"] and $room["players"][array_search($faction, array_column($room["players"], 'faction'))]["strategy_card"] == $game["last_strategy"]){
    $what_front_should_do["title"] = "Waiting for other players to choose secondary";
    $what_front_should_do["countdown"] = $game["unix_waiting_secondary_until"];
}

else if(
    time() < $game["unix_waiting_secondary_until"] - $game["time_secondary_delay"] and 
    !isset($room["players"][array_search($faction, array_column($room["players"], 'faction'))]["will_use_secondary"])
){
    $what_front_should_do["title"] = "Use {$game["last_strategy"]}-{$strategy_names[$game["last_strategy"]]} secondary?";
    $what_front_should_do["countdown"] = $game["unix_waiting_secondary_until"]  - $game["time_secondary_delay"];
    $what_front_should_do["options"] = [
        [
            "option" => "Yes",
            "link" => $api_link."?method=chooseSecondary&hash={$hash}&faction={$faction}&secondary={$game["last_strategy"]}",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "secondary" => $game["last_strategy"]
            ]
        ],
        [
            "option" => "No",
            "link" => $api_link."?method=chooseSecondary&hash={$hash}&faction={$faction}&secondary=0",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $faction,
                "secondary" => 0
            ]
        ],
    ];
}

//waiting for secondary
elseif(time() < $game["unix_waiting_secondary_until"]){
    if($room["players"][array_search($faction, array_column($room["players"], 'faction'))]["will_use_secondary"]){
        $what_front_should_do["title"] = "You choose to use secondary";
    } else{
        $what_front_should_do["title"] = "You choose to NOT use secondary";
    }
    $what_front_should_do["countdown"] = $game["unix_waiting_secondary_until"];
}   


//player must choose strategy
else if($game["current_phase"] == "strategy_phase" and ($room["players"][array_search($faction, array_column($room["players"], 'faction'))]["admin"] == true or $you["currently_playing"] == true)){
    //get current player id
    
    if($you["currently_playing"] == false){
        $what_front_should_do["title"] = "NOT YOUR TURN!!
Choose a strategy card for {$room["players"][$current_player_id]["name"]} ({$room["players"][$current_player_id]["faction"]})";
    } else{
        $what_front_should_do["title"] = "Choose a strategy card";
    }

    $what_front_should_do["countdown"] = $players[0]["unix_playing_until"];

    //remove already choose strategy  from $not_choose_strategy_cards
    $not_choose_strategy_cards = [1,2,3,4,5,6,7,8];
    foreach($players as $key => $player){
        $not_choose_strategy_cards = array_diff($not_choose_strategy_cards, [$player["strategy_card"]]);
    }

    $what_front_should_do["options"] = [];
    foreach($not_choose_strategy_cards as $key => $strategy_number){
        $strategy_name = "{$strategy_number}) {$strategy_names[$strategy_number]}";
        $what_front_should_do["options"][]= [
            "option" => $strategy_name,
            "link" => $api_link."?method=chooseStrategy&hash={$hash}&faction={$room["players"][$current_player_id]["faction"]}&strategy_card={$strategy_number}",
            "method" => "POST",
            "data" => [
                "hash" => $hash,
                "faction" => $players[0]["faction"],
                "strategy_card" => $strategy_number
            ]
        ];
    }
}

//player must wait for other player to choose strategy
elseif($game["current_phase"] == "strategy_phase"){
    $what_front_should_do["title"] = "Waiting for {$players[0]["name"]} ({$players[0]["faction"]}) to choose strategy cards";
    $what_front_should_do["countdown"] = $players[0]["unix_playing_until"];
}

//player must choose action
elseif($game["current_phase"] == "action_phase" and ($you["currently_playing"] == true or $you["admin"] == true)){
    if($you["currently_playing"] == false){
        $what_front_should_do["title"] = "NOT YOUR TURN!!
Choose an action for {$room["players"][$current_player_id]["name"]} ({$room["players"][$current_player_id]["faction"]})";
    } else{
        $what_front_should_do["title"] = "Choose an action";
    }

    $what_front_should_do["countdown"] = $players[0]["unix_playing_until"];
    $what_front_should_do["options"][] = [
        [
            "option" => "Tatic",
            "link" => $api_link."?method=chooseAction&hash={$hash}&faction={$players[0]["faction"]}&action=tatic",
            "method" => "POST",
            "data" => [
                "method" => "chooseAction",
                "hash" => $hash,
                "faction" => $players[0]["faction"],
                "action" => "tatic"
            ]
        ],
    ];
    $what_front_should_do["options"][] = [
        [
            "option" => "Component",
            "link" => $api_link."?method=chooseAction&hash={$hash}&faction={$players[0]["faction"]}&action=component",
            "method" => "POST",
            "data" => [
                "method" => "chooseAction",
                "hash" => $hash,
                "faction" => $players[0]["faction"],
                "action" => "component"
            ]
        ],
    ];
    $what_front_should_do["options"][] = [
        [
            "option" => "Faction",
            "link" => $api_link."?method=chooseAction&hash={$hash}&faction={$players[0]["faction"]}&action=faction",
            "method" => "POST",
            "data" => [
                "method" => "chooseAction",
                "hash" => $hash,
                "faction" => $players[0]["faction"],
                "action" => "component"
            ]
        ],
    ];


    //if already used strategy, show option pass_turn
    if($players[0]["already_used_strategy_card"] == true){
        $what_front_should_do["options"][] = [
            [
                "option" => "Pass turn",
                "link" => $api_link."?method=chooseAction&hash={$hash}&faction={$players[0]["faction"]}&action=pass_turn",
                "method" => "POST",
                "data" => [
                    "method" => "chooseAction",
                    "hash" => $hash,
                    "faction" => $players[0]["faction"],
                    "action" => "pass_turn"
                ]
            ],
        ];
    } else{
        //If the action is “strategy”, and the player has Politics
        if($players[0]["strategy_card"] == 3){
            $m_options = [];
            foreach($players as $key => $player){
                $m_options[] = [
                    "option" => $player["name"]."({$player["faction"]})",
                    "link" => $api_link."?method=chooseAction&hash={$hash}&faction={$players[0]["faction"]}&action=strategy&speaker={$player["faction"]}",
                    "method" => "POST",
                    "data" => [
                        "method" => "chooseAction",
                        "hash" => $hash,
                        "faction" => $players[0]["faction"],
                        "action" => "politics",
                        "speaker" => $player["faction"]
                    ]
                ];
            }

            //if not player turn
            if($you["currently_playing"] == false){
                $m_title = "NOT YOUR TURN!!
Choose new speaker:";
            } else{
                $m_title = "Choose new speaker:";
            }

            $what_front_should_do["options"][] = [
                [
                    "option" => "Use {$strategy_names[$players[0]["strategy_card"]]}",
                    "m_title" => $m_title,
                    "m_options" => $m_options
                ],
            ];
        } else{
            
            $what_front_should_do["options"][] = [
                [
                    "option" => "Use {$strategy_names[$players[0]["strategy_card"]]}",
                    "link" => $api_link."?method=chooseAction&hash={$hash}&faction={$players[0]["faction"]}&action=strategy",
                    "method" => "POST",
                    "data" => [
                        "method" => "chooseAction",
                        "hash" => $hash,
                        "faction" => $players[0]["faction"],
                        "action" => "strategy"
                    ]
                ],
            ];
        }
    }
}


//player must wait for other player to choose strategy
elseif($game["current_phase"] == "action_phase"){
    $what_front_should_do["title"] = "Waiting for {$players[0]["name"]} ({$players[0]["faction"]}) to make action";
    $what_front_should_do["countdown"] = $players[0]["unix_playing_until"];
}


else{
    $what_front_should_do["title"] = "ERROR!! CONTACT DEVELOPER!!";
}


 return $what_front_should_do;

}