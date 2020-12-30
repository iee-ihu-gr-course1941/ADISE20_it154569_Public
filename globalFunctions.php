<?php
// Edw einai oles oi functions pou xrhsimopiountai apo diaforetika arxeia kai oxi mono ena

require_once "db_config.php";


function getGameStatus()
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT status FROM game_status");
    $stmt->execute();
    $result = $stmt->get_result();
    $gameStatus = $result->fetch_all()[0][0];

    return $gameStatus;
}






