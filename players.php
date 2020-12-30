<?php
require_once "db_config.php";


$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];


if ($method == "POST") {
    if (isset($_REQUEST["nickname"])) {
        if (isset($_REQUEST["color"])) {
            $nickname = $_REQUEST["nickname"];
            $color = $_REQUEST["color"];
            if (checkColorValid($color)) {
                doRegister($nickname, $color);
            } else {
                http_response_code(400);
                header('Content-type: application/json');
                echo json_encode(array("error" => "Color is invalid. Valid colors are B and Y"));
            }
        } else {
            http_response_code(400);
            header('Content-type: application/json');
            echo json_encode(array("error" => "No color given"));
        }
    } else {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode(array("error" => "No nickname given"));

    }
} elseif ($method == "PUT") {
    if (isset($_REQUEST["nickname"])) {
        $nickname = $_REQUEST["nickname"];

        // pairnw to token apo to json pou stelnei o client giati den mou aresei to token na vrisketai san query parameter
        // h logikh einai pws an 8elei enas paikths na alla3ei to nickname tou prepei na dwsei kai to token tou

        $json_data = json_decode(file_get_contents('php://input'), true);
        if (isset($json_data["token"])) {
            $token = $json_data["token"];
            changeNickname($nickname, $token);
        } else {
            http_response_code(400);
            header('Content-type: application/json');
            echo json_encode(array("error" => "No token given in json"));
        }

    } else {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode(array("error" => "No nickname given"));
    }

} elseif ($method == "GET") {
    if (empty($_GET)) {
        getAllPlayers();
    } else {
        if (isset($_GET["color"])) {
            $color = $_GET["color"];
            if (checkColorValid($color)) {
                getPlayerByColor($color);
            } else {
                http_response_code(400);
                header('Content-type: application/json');
                echo json_encode(array("error" => "Color is invalid. Valid colors are B and Y"));
            }
        } else {
            http_response_code(400);
            header('Content-type: application/json');
            echo json_encode(array("error" => "No color query parameter given"));
        }
    }
} else {
    http_response_code(400);
    header('Content-type: application/json');
    echo json_encode(array("error" => "Method not allowed for this URI"));
}


function doRegister($nickname, $color)
{
    global $mysqli;

    $activePlayersCounts = getActivePlayerCount();


    if ($activePlayersCounts == 0) {
        $token = uniqid();

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
        $token = uniqid();

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
}


function changeNickname($nickname, $token)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT * FROM players where token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "update players set nickname=? where token=?";
        $st = $mysqli->prepare($sql);
        $st->bind_param("ss", $nickname, $token);
        $st->execute();

        header('Content-type: application/json');
        echo json_encode(array("response" => "Nickname updated!"));

    } else {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode(array("error" => "Token not associated to any active players"));
    }

}


function getAllPlayers()
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT * FROM players");
    $stmt->execute();
    $result = $stmt->get_result();
    $playerList = array();

    while ($row = $result->fetch_assoc()) {
        array_push($playerList, array("nickname" => $row["nickname"], "pawnColor" => $row["pawn_color"]));
    }
    header('Content-type: application/json');
    echo json_encode($playerList);
}


function getPlayerByColor($color)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT * FROM players WHERE pawn_color = ? ");
    $stmt->bind_param("s", $color);
    $stmt->execute();
    $result = $stmt->get_result();

    // Epeidh 3erw pws to xrwma mporei na antistoixei apokleistika se enan paikth
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $nickname = $row["nickname"];
        $color = $row["pawn_color"];

        header('Content-type: application/json');
        echo json_encode(array("nickname" => $nickname, "color" => $color));
    } else {
        http_response_code(400);
        header('Content-type: application/json');
        echo json_encode(array("error" => "Color has not been assigned to any player"));
    }
}


function getActivePlayerCount()
{
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM players");
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows;
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







