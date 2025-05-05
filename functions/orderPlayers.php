<?php 

function orderPlayers($players, $phase){
    $old_players = $players;

    if($phase == "action_phase"){
        //sort players by strategy card
        usort($players, function($a, $b) {
            return $a["strategy_card"] <=> $b["strategy_card"];
        });

        //put faction "naalu" in the beginning of the array
        usort($players, function($a, $b) {
            if ($a["faction"] == "naalu" && $b["faction"] != "naalu") {
                return -1;
            } elseif ($a["faction"] != "naalu" && $b["faction"] == "naalu") {
                return 1;
            } else {
                return 0;
            }
        });

    } else {
        //sort players by speaker
        foreach($players as $key => $player){
            if($player["speaker"] == true){
                break;
            }
            unset($players[$key]);
            $players = array_values($players);
            $players[] = $player;
        }
    }
    
    //put first players in end until currently_playing is true
    foreach($players as $key => $player){
        if($player["currently_playing"] == true){
            break;
        }
        $players[] = $player;
        unset($players[$key]);
    }
    $players = array_values($players);


    //put first players in end until already_passed_turn is true
    foreach($players as $key => $player){
        if($player["already_passed_turn"] == false){
            
        } else{
            $players[] = $player;
            unset($players[$key]);
        }
    }
    $players = array_values($players);

    //get current player faction if it has not already passed turn
    if($players[0]["already_passed_turn"] == false and $players[0]["currently_playing"] == true){
        $current_player_faction = $players[0]["faction"];
        $current_player_id = array_search($current_player_faction, array_column($old_players, 'faction'));
    } else{
        $current_player_faction = $players[0]["faction"];
        $current_player_id = array_search($current_player_faction, array_column($old_players, 'faction'));
    }

    //get next player faction if it has not already passed turn
    if($players[1]["already_passed_turn"] == false){
        $next_player_faction = $players[1]["faction"];
        $next_player_id = array_search($next_player_faction, array_column($old_players, 'faction'));
    } else{
        $next_player_faction = null;
        $next_player_id = null;
    }


    //return players in order
    return [
        "players" => $players,
        "current_player_id" => $current_player_id,
        "current_player_faction" => $current_player_faction,
        "next_player_id" => $next_player_id,
        "next_player_faction" => $next_player_faction
    ];

}