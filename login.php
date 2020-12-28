<?php
require_once "db_config.php";


global $mysqli;

if(isset($_REQUEST["username"])){
    $username = $_REQUEST["username"];

    $token = uniqid();
    $time = time();



    $checkActivePlayersSQL = "select count(*) from players";

    $result = $mysqli->query($checkActivePlayersSQL);
    $activePlayers = -1;

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $activePlayers = $row["count(*)"];
        }
    } else {
        $activePlayers = 0;
    }


    if($activePlayers==0){
        $insertFirstPlayerSQL = "INSERT INTO players (nickname, pawn_color, token, last_change) VALUES ('$username', 'B', '$token', null)";

        if (mysqli_query($mysqli, $insertFirstPlayerSQL)) {
            echo json_encode(array("token"=>$token));
        } else {
            echo "Error: " . $insertFirstPlayerSQL . "<br>" . mysqli_error($mysqli);
        }
    }
    elseif ($activePlayers==1){
        $insertSecondPlayerSQL = "INSERT INTO players (nickname, pawn_color, token, last_change) VALUES ('$username', 'Y', '$token', null)";

        if (mysqli_query($mysqli, $insertSecondPlayerSQL)) {
            echo json_encode(array("token"=>$token));
        } else {
            echo "Error: " . $insertSecondPlayerSQL . "<br>" . mysqli_error($mysqli);
        }
    }
    else{
        echo json_encode(array("error"=>"Maximum 2 players allowed. Wait for them to finish."));
    }
}else{
    echo json_encode(array("error"=>"No username given"));
}


