<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("error_log", "erro.txt");

//corrigir o horário do servidor para o horário internacional GMT.
date_default_timezone_set('Etc/GMT');

// include the SQLite3 database connection class
// include the SQLite3 database connection class
include "./functions/sqlite3.php";
include_once "./functions/validFaction.php";
include_once "./functions/orderPlayers.php";
include_once "./functions/whatFrontShouldDo.php";
include_once "./functions/validFaction.php";

$content = file_get_contents("php://input");

header('Content-Type: application/json');

$hash_simulation = "1:68191fd171ebaTSJFrcI4vTvX";


//SIMULATE POST newGame
/*
$query = [
    "new_players" => json_decode('[{"name":"player1", "faction":"arborec", "admin":true},{"name":"player2", "faction":"letnev"}]', true),
    "faction" => "arborec"
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "newGame"]);
//*/

///////////////simulate POST nextPhase
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "phase" => "strategy_phase",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "nextPhase"]);
//*/


/////////////qqsimulate POST chooseStrategy
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "strategy_card" => 3,
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseStrategy"]);
//*/

////////////qsimulate POST chooseStrategy
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "letnev",
    "strategy_card" => 1,
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseStrategy"]);
//*/

////////////qsimulate POST chooseAction
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "letnev",
    "action" => "strategy",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseAction"]);
//*/

//////////////simulate POST chooseAction
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "action" => "strategy",
    "speaker" => "letnev",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseAction"]);
//*/

/////////////simulate POST chooseAction
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "letnev",
    "action" => "pass_turn"
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseAction"]);
//*/


///////////////simulate POST chooseAction
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "action" => "tatic",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseAction"]);
//*/



///////////////simulate POST chooseAction
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "action" => "pass_turn",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "chooseAction"]);
//*/


//////////////simulate POST nextPhase
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "phase" => "agenda_phase",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "nextPhase"]);
//*/


//////////////qsimulate POST nextPhase
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "phase" => "game_end",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "nextPhase"]);
//*/


/////////////simulate POST pauseGame
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
    "pause" => false,
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "pauseGame"]);
//*/









//////////qqsimulate POST infoRoom
/*
$query = [
    "hash" => $hash_simulation,
    "faction" => "arborec",
];
$content = json_encode($query);
$_SERVER["QUERY_STRING"] = http_build_query(["method" => "infoRoom"]);
//*/
















//check if the request has a query string
if(!isset($_SERVER["QUERY_STRING"])){
    echo json_encode(["ok"=>false, "result" => "No query string specified"]);
    http_response_code(400);
    exit;
}



//foreach($content as $key => $value){
    //    if($new_value = json_decode($value, true)){
    //        $content[$key] = $new_value;
    //    }
    //}


//$content = isset($query) ? json_encode($query) : [];

parse_str($_SERVER["QUERY_STRING"], $query);

if($content){
    $query = $query + json_decode($content, true);
}









// Check if method is set
if(!isset($query["method"])){
    echo json_encode(["ok"=>false, "result" => "No method specified"]);
    http_response_code(400);
    exit;
}

// Handle method newGame
if(strcasecmp($query["method"], "newGame") == 0){
    require_once "./methods/newGame.php";
} 


// Handle method infoRoom
elseif(strcasecmp($query["method"], "infoRoom") == 0){
    require_once "./methods/infoRoom.php";
} 

// Handle method nextPhase
elseif(strcasecmp($query["method"], "nextPhase") == 0){
    require_once "./methods/nextPhase.php";
}

// Handle method chooseStrategy
elseif(strcasecmp($query["method"], "chooseStrategy") == 0){
    require_once "./methods/chooseStrategy.php";
}
// Handle method chooseAction
elseif(strcasecmp($query["method"], "chooseAction") == 0){
    require_once "./methods/chooseAction.php";
}
// Handle method chooseSecondary
elseif(strcasecmp($query["method"], "chooseSecondary") == 0){
    require_once "./methods/chooseSecondary.php";
}
// Handle method pauseGame
elseif(strcasecmp($query["method"], "pauseGame") == 0){
    require_once "./methods/pauseGame.php";
}
// Handle method undoAction
elseif(strcasecmp($query["method"], "undoAction") == 0){
    require_once "./methods/undoAction.php";
}

//method not valid
else {
    echo json_encode(["ok"=>false, "result" => "Method not valid"]);
    http_response_code(400);
    exit;
}
