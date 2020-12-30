<?php

require_once "db_config.php";
require_once "globalFunctions.php";


$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];

if ($method == "GET") {
    showGameStatus();
} else {
    http_response_code(400);
    header('Content-type: application/json');
    echo json_encode(array("error" => "Invalid path"));
}


function showGameStatus()
{
    header('Content-type: application/json');
    echo json_encode(array("response" => array("gameStatus" => getGameStatus())));
}

