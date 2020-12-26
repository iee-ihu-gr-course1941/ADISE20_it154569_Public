<?php
require_once "db_config.php";



if ($_SERVER["REQUEST_METHOD"]!="POST") {
    echo json_encode(array("error"=>"Only post request allowed"));
}else{
    require_once "db_config.php";
    global $mysqli;


    $player_token = json_decode(file_get_contents('php://input')) -> token;

    $attemptingPlayer = checkToken($player_token);


    if($attemptingPlayer!=null){
        $currentPlayer = getCurrentPlayer();

        if($attemptingPlayer==$currentPlayer){
            $move = json_decode(file_get_contents('php://input')) -> move;
            $isMoveValid = isMoveValid($move);

            if($isMoveValid){
                doMove($move);
            }

        }else{
            echo json_encode(array("error"=>"Wait for your turn to play."));
        }
    }else{
        echo json_encode(array("error"=>"token is not valid"));
    }


}



function checkToken($player_token){
    global $mysqli;

    $attemptingPlayer = null;

    $checkTokenSQL = "select * from players where token='$player_token'";

    $checkTokenResult = $mysqli->query($checkTokenSQL);

    if ($checkTokenResult->num_rows > 0) {
        while($row = $checkTokenResult->fetch_assoc()) {
            $attemptingPlayer = $row["nickname"];
        }
        return $attemptingPlayer;

    } else {
        return null;

    }
}




function getCurrentPlayer(){
    global $mysqli;

    $checkTurnSQL = "select nickname, min(last_change) from players";
    $checkTurnSQLResult = $mysqli -> query($checkTurnSQL);
    $currentPlayer = null;
    if($checkTurnSQLResult->num_rows>0){
        while($row = $checkTurnSQLResult->fetch_assoc()) {
            $currentPlayer = $row["nickname"];
        }
    }
    return $currentPlayer;
}



function isMoveValid($move){
    $move = (int)$move;

    if($move>7 or $move<0){
        return false;
    }

    $currentBoard = getBoard();

    for($i=0; $i<6; $i++){
        if($currentBoard[$i][$move-1]==null){
            return true;
        }
    }

    return false;
}




function doMove($move){

}


function checkGameStatus(){

}


function clearBoard(){

}




function getBoard(){
    global $mysqli;


    $board = array();
    for($i=0; $i<6; $i++){
        for($j=0; $j<7; $j++){
            $board[$i][$j] = null;
        }
    }

    $getBoardSQL = "select * from board";
    $getBoardSQLResult = $mysqli -> query($getBoardSQL);
    if($getBoardSQLResult->num_rows>0){
        while($row = $getBoardSQLResult->fetch_assoc()) {
            if($row["piece_color"]!=null){
                $x = (int)$row["x"];
                $y = (int)$row["y"];

                $board[$x-1][$y-1] = $row["piece_color"];
            }

        }
    }

    return $board;
}
