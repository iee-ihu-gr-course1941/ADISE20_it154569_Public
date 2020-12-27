<?php
require_once "db_config.php";

global $mysqli;


if (isset($_REQUEST["nickname"]) && isset($_REQUEST["color"])) {
    $nickname = $_REQUEST["nickname"];
    $color = $_REQUEST["color"];
    $token = uniqid();


    $activePlayersCounts = getActivePlayerCount();
    $isColorValid = checkColorValid($color);

    if ($isColorValid) {
        if ($activePlayersCounts == 0) {
            // prepare and bind
            $stmt = $mysqli->prepare("insert into players (nickname, pawn_color, token) VALUES (?, ?, ?)");
            // set parameters and execute
            $stmt->bind_param("sss", $nickname, $color, $token);
            $stmt->execute();


            $game_status = "initialized";
            $sql = "update game_status set status=?, p_turn=?";
            $st = $mysqli->prepare($sql);
            $st->bind_param("ss", $game_status, $color);
            $st->execute();


            header('Content-type: application/json');
            echo json_encode(array("nickname" => $nickname, "token" => $token, "color" => $color));

        } elseif ($activePlayersCounts == 1) {
            $isColorTaken = checkTakenColor($color);
            $isNicknameTaken = checkTakenNickname($nickname);

            if (!$isColorTaken && !$isNicknameTaken) {
                // prepare and bind
                $stmt = $mysqli->prepare("insert into players (nickname, pawn_color, token) VALUES (?, ?, ?)");
                // set parameters and execute
                $stmt->bind_param("sss", $nickname, $color, $token);
                $stmt->execute();


                $game_status = "started";
                $sql = "update game_status set status=?";
                $st = $mysqli->prepare($sql);
                $st->bind_param("s", $game_status);
                $st->execute();



                header('Content-type: application/json');
                echo json_encode(array("nickname" => $nickname, "token" => $token, "color" => $color));

            } else {
                http_response_code(400);
                header('Content-type: application/json');
                if ($isColorTaken) {
                    echo json_encode(array("error" => "Color is taken"));
                } else {
                    echo json_encode(array("error" => "Nickname is taken"));
                }
            }
        } else {
            http_response_code(400);
            header('Content-type: application/json');
            echo json_encode(array("error" => "Maximum 2 players allowed. Wait for them to finish."));
        }

    } else {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode(array("error" => "Color is invalid"));
    }
} else {
    http_response_code(400);
    header('Content-type: application/json');
    if (!isset($_REQUEST["nickname"])) {
        echo json_encode(array("error" => "No nickname given"));
    } elseif (!isset($_REQUEST["color"])) {
        echo json_encode(array("error" => "No color given"));
    }
}


function getActivePlayerCount()
{
    global $mysqli;
    $activePlayers = -1;

    $checkActivePlayersSQL = "select count(*) from players";

    $result = $mysqli->query($checkActivePlayersSQL);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activePlayers = $row["count(*)"];
        }
    } else {
        $activePlayers = 0;
    }

    return $activePlayers;
}


function checkColorValid($color)
{
    if ($color != "B" && $color != "b" && $color != "Y" && $color != "y") {
        return false;
    }
    return true;
}


function checkTakenColor($color)
{
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM players WHERE pawn_color = ? ");
    $stmt->bind_param("s", $color);
    $stmt->execute();
    $result = $stmt->get_result();
    $result_count = $result->num_rows;

    if ($result_count > 0) {
        return true;
    }
    return false;
}


function checkTakenNickname($nickname)
{
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM players WHERE nickname = ? ");
    $stmt->bind_param("s", $nickname);
    $stmt->execute();
    $result = $stmt->get_result();
    $result_count = $result->num_rows;

    if ($result_count > 0) {
        return true;
    }
    return false;
}







