<?php
require_once "db_config.php";
require_once "globalFunctions.php";

$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];


if ($method == "POST") {
    clearBoard();
} elseif ($method == "GET") {
    if (empty($_GET)) {
        showBoard();
    } else {
        if (isset($_GET["column"])) {
            $validColumns = array(1, 2, 3, 4, 5, 6, 7);
            $column = $_GET["column"];
            if (in_array($column, $validColumns)) {
                showColumnFromBoard($column);

            } else {
                http_response_code(400);
                header('Content-type: application/json');
                echo json_encode(array("error" => "Invalid column"));
            }

        } else {
            http_response_code(400);
            header('Content-type: application/json');
            echo json_encode(array("error" => "Column not given"));
        }
    }

} elseif ($method == "PUT") {
    $json_data = json_decode(file_get_contents('php://input'), true);
    if (isset($json_data["token"])) {
        if ($json_data["token"] != null && $json_data["move"] != null) {
            $player_token = $json_data["token"];
            $attemptingPlayer = getUserFromToken($player_token);


            if ($attemptingPlayer != null) {
                $currentPlayer = getCurrentPlayer();
                $currentPlayerColor = getCurrentPlayerColor();
                $gameStatus = getGameStatus();

                if ($gameStatus == "started") {
                    if ($attemptingPlayer == $currentPlayer) {

                        $move = json_decode(file_get_contents('php://input'))->move;
                        $validRow = isMoveValid($move);


                        if ($validRow != null) {
                            doMove($validRow, $move, $currentPlayerColor);

                            $response = array("posX" => $validRow, "posY" => $move);


                            $opponentColor = getOpponentColor($currentPlayerColor);


                            updateTurn($opponentColor);

                            $gameStatus = checkActiveGameStatus();


                            updateGameStatus($gameStatus);


                            $response["gameStatus"] = $gameStatus;

                            header('Content-type: application/json');
                            echo json_encode(array("response" => $response));
                        } else {
                            http_response_code(400);
                            header('Content-type: application/json');
                            echo json_encode(array("error" => "Invalid move."));
                        }

                    } else {
                        http_response_code(400);
                        header('Content-type: application/json');
                        echo json_encode(array("error" => "Wait for your turn to play."));
                    }
                } elseif ($gameStatus == "initialized") {
                    http_response_code(400);
                    header('Content-type: application/json');
                    echo json_encode(array("error" => "Game has not started yet. Wait for a second player to join!"));
                } elseif ($gameStatus == "ended") {
                    http_response_code(400);
                    header('Content-type: application/json');

                    $gameResult = getGameResult();

                    echo json_encode(array("error" => "Game has ended! The result is $gameResult"));
                } elseif ($gameStatus == "aborted") {
                    http_response_code(400);
                    header('Content-type: application/json');
                    echo json_encode(array("error" => "Game has been aborted..."));
                }


            } else {
                http_response_code(400);
                header('Content-type: application/json');
                echo json_encode(array("error" => "Token is not valid"));
            }

        } else {
            http_response_code(400);
            header('Content-type: application/json');
            echo json_encode(array("error" => "Token is empty"));
        }
    } else {
        http_response_code(400);
        header('Content-type: application/json');

        if ($json_data["token"] == null){
            echo json_encode(array("error" => "token key not found JSON"));
        }else{
            echo json_encode(array("error" => "move key not found JSON"));
        }
    }

} else {
    http_response_code(400);
    header('Content-type: application/json');
    echo json_encode(array("error" => "Method not allowed for this URI"));
}


function showColumnFromBoard($column)
{
    $board = getBoard();

    header('Content-type: application/json');
    echo json_encode($board[$column]);
}


function clearBoard()
{
    global $mysqli;

    $removePlayersSQL = "delete from players;";
    $stmt0 = $mysqli->prepare($removePlayersSQL);
    $stmt0->execute();


    $resetGameStatus = "update game_status set status='not active', p_turn=null, result=null, last_change=null";
    $stmt1 = $mysqli->prepare($resetGameStatus);
    $stmt1->execute();


    $resetBoard = "update board set piece_color=null";
    $stmt2 = $mysqli->prepare($resetBoard);
    $stmt2->execute();

    header('Content-type: application/json');
    echo json_encode(array("response" => "Board reset successfully"));
}


function getUserFromToken($player_token)
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT nickname FROM players WHERE token=?");
    $stmt->bind_param("s", $player_token);
    $stmt->execute();
    $result = $stmt->get_result();

    $result_count = $result->num_rows;
    if ($result_count == 0) {
        return null;
    }

    // Epeidh to token einai unique, ksero pos 8a epistrepsei 1 monadiko apotelesma an yparxei to token
    // opote pairnw apokleistika thn 1h grammh ton apotelesmaton kai thn 1h sthlh (dhladh to nickname)
    return $result->fetch_all()[0][0];
}


function getCurrentPlayer()
{
    global $mysqli;


    $checkTurnSQL = "select nickname from players join game_status gs on players.pawn_color=gs.p_turn";

    $checkTurnSQLResult = $mysqli->query($checkTurnSQL);
    $currentPlayer = null;
    if ($checkTurnSQLResult->num_rows > 0) {
        while ($row = $checkTurnSQLResult->fetch_assoc()) {
            $currentPlayer = $row["nickname"];
        }
    }
    return $currentPlayer;
}


function getCurrentPlayerColor()
{
    global $mysqli;


    $checkTurnSQL = "select pawn_color from players join game_status gs on players.pawn_color=gs.p_turn";

    $checkTurnSQLResult = $mysqli->query($checkTurnSQL);
    $currentPlayerColor = null;

    if ($checkTurnSQLResult->num_rows > 0) {
        while ($row = $checkTurnSQLResult->fetch_assoc()) {
            $currentPlayerColor = $row["pawn_color"];
        }
    }
    return $currentPlayerColor;
}


function isMoveValid($move)
{
    if (!is_int($move)) {
        return null;
    }

    if ($move < 1 || $move > 8) {
        return null;
    }

    $currentBoard = getBoard();

    for ($i = 1; $i <= 6; $i++) {
        if ($currentBoard[$i][$move] == null) {
            return $i;
        }
    }

    return null;
}


function doMove($x, $move, $color)
{
    global $mysqli;
    // prepare and bind
    $stmt = $mysqli->prepare("update board set piece_color=? where x=? and y=?");
    // set parameters and execute
    $stmt->bind_param("sii", $color, $x, $move);
    $stmt->execute();
}


function updateTurn($color)
{
    global $mysqli;

    $sql = "update game_status set p_turn=?";
    $st = $mysqli->prepare($sql);
    $st->bind_param("s", $color);
    $st->execute();

}


function checkActiveGameStatus()
{
    $gameWinnerOrDraw = checkGameWinnerOrDraw();

    if (checkGameWinnerOrDraw() != "playing") {
        return $gameWinnerOrDraw;
    } elseif (checkAborted()) {
        return "aborted";
    } else {
        return "started";
    }
}


function checkGameWinnerOrDraw()
{
    if (checkHorizontalWinner() != null) {
        return checkHorizontalWinner();
    } elseif (checkVerticalWinner() != null) {
        return checkVerticalWinner();
    } elseif (checkDiagonalWinner() != null) {
        return checkDiagonalWinner();
    } elseif (isBoardFull()) {
        return "draw";
    } else {
        return "playing";
    }

}


function updateGameStatus($gameStatus)
{
    global $mysqli;

    if ($gameStatus != "B" && $gameStatus != "Y" && $gameStatus != "draw") {
        $sql = "update game_status set status=?";
        $st = $mysqli->prepare($sql);
        $st->bind_param("s", $gameStatus);
        $st->execute();
    } elseif ($gameStatus == "draw") {
        $sql = "update game_status set status='ended', result='draw'";
        $st = $mysqli->prepare($sql);
        $st->execute();
    } else {
        $sql = "update game_status set status='ended', result=?";
        $st = $mysqli->prepare($sql);
        $st->bind_param("s", $gameStatus);
        $st->execute();
    }

}


function checkHorizontalWinner()
{
    $board = getBoard();
    for ($i = 1; $i < 7; $i++) {

        //CHECK IF BLUE IS WINNER HORIZONTALLY
        if ($board[$i][1] == 'B' && $board[$i][2] == 'B' && $board[$i][3] == 'B' && $board[$i][4] == 'B') {
            return 'B';
        }
        if ($board[$i][2] == 'B' && $board[$i][3] == 'B' && $board[$i][4] == 'B' && $board[$i][5] == 'B') {
            return 'B';
        }
        if ($board[$i][3] == 'B' && $board[$i][4] == 'B' && $board[$i][5] == 'B' && $board[$i][6] == 'B') {
            return 'B';
        }
        if ($board[$i][4] == 'B' && $board[$i][5] == 'B' && $board[$i][6] == 'B' && $board[$i][7] == 'B') {
            return 'B';
        }

        //CHECK IF YELLOW IS WINNER HORIZONTALLY
        if ($board[$i][1] == 'Y' && $board[$i][2] == 'Y' && $board[$i][3] == 'Y' && $board[$i][4] == 'Y') {
            return 'Y';
        }
        if ($board[$i][2] == 'y' && $board[$i][3] == 'Y' && $board[$i][4] == 'Y' && $board[$i][5] == 'Y') {
            return 'Y';
        }
        if ($board[$i][3] == 'Y' && $board[$i][4] == 'Y' && $board[$i][5] == 'Y' && $board[$i][6] == 'Y') {
            return 'Y';
        }
        if ($board[$i][4] == 'Y' && $board[$i][5] == 'Y' && $board[$i][6] == 'Y' && $board[$i][7] == 'Y') {
            return 'Y';
        }
    }
    return null;
}


function checkVerticalWinner()
{
    $board = getBoard();

    for ($j = 1; $j < 8; $j++) {

        //CHECK IF BLUE IS WINNER VERTICALLY
        if ($board[1][$j] == 'B' && $board[2][$j] == 'B' && $board[3][$j] == 'B' && $board[4][$j] == 'B') {
            return 'B';
        }
        if ($board[2][$j] == 'B' && $board[3][$j] == 'B' && $board[4][$j] == 'B' && $board[5][$j] == 'B') {
            return 'B';
        }
        if ($board[3][$j] == 'B' && $board[4][$j] == 'B' && $board[5][$j] == 'B' && $board[6][$j] == 'B') {
            return 'B';
        }


        //CHECK IF BLUE IS WINNER VERTICALLY
        if ($board[1][$j] == 'Y' && $board[2][$j] == 'Y' && $board[3][$j] == 'Y' && $board[4][$j] == 'Y') {
            return 'Y';
        }
        if ($board[2][$j] == 'Y' && $board[3][$j] == 'Y' && $board[4][$j] == 'Y' && $board[5][$j] == 'Y') {
            return 'Y';
        }
        if ($board[3][$j] == 'Y' && $board[4][$j] == 'Y' && $board[5][$j] == 'Y' && $board[6][$j] == 'Y') {
            return 'Y';
        }

    }
    return null;
}


function checkDiagonalWinner()
{
    $board = getBoard();

    return null;
}


function checkAborted()
{
    global $mysqli;

    $checkActivePlayersSQL = "select * from game_status where last_change<(now()-INTERVAL 5 MINUTE)";
    $result = $mysqli->query($checkActivePlayersSQL);

    if ($result->num_rows > 0) {
        return true;
    }
    return false;
}


function getOpponentColor($currentPlayerColor)
{
    if ($currentPlayerColor == "y" || $currentPlayerColor == "Y") {
        $opponentColor = "b";
    } else {
        $opponentColor = "y";
    }
    return $opponentColor;
}


function isBoardFull()
{
    $board = getBoard();

    for ($i = 1; $i <= 6; $i++) {
        for ($j = 1; $j <= 7; $j++) {
            if ($board[$i][$j] == null) {
                return false;
            }
        }
    }

    return true;
}


function getGameResult()
{
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT result FROM game_status");
    $stmt->execute();
    $result = $stmt->get_result();
    $gameStatus = $result->fetch_all()[0][0];

    return $gameStatus;
}


function getBoard()
{
    global $mysqli;


    $board = array();
    for ($i = 1; $i < 7; $i++) {
        for ($j = 1; $j < 8; $j++) {
            $board[$i][$j] = null;
        }
    }

    $getBoardSQL = "select * from board";
    $getBoardSQLResult = $mysqli->query($getBoardSQL);
    if ($getBoardSQLResult->num_rows > 0) {
        while ($row = $getBoardSQLResult->fetch_assoc()) {
            if ($row["piece_color"] != null) {
                $x = (int)$row["x"];
                $y = (int)$row["y"];

                $board[$x][$y] = $row["piece_color"];
            }

        }
    }

    return $board;
}


function showBoard()
{
    header('Content-type: application/json');
    print(json_encode(getBoard(), JSON_PRETTY_PRINT));
}



