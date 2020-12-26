<?php

ini_set('display_errors', 'on');

require_once "db_config.php";
require_once "login.php";

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$input = json_decode(file_get_contents('php://input'), true);
if (isset($_SERVER['HTTP_X_TOKEN'])) {
    $input['token'] = $_SERVER['HTTP_X_TOKEN'];
}

switch ($r = array_shift($request)) {

    case 'login':write_user($method, $request,$input);
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        exit;
}

function write_user($method, $request, $input ){
    switch ($b=array_shift($request)) {
        case '':
        case null: if($method=='GET') {print "oti na nai";}
        else {header("HTTP/1.1 400 Bad Request");
            print json_encode(['errormesg'=>"Method $method not allowed here."]);}
            break;
        case 'B':
        case 'W': print"handle user";
            break;
        default: header("HTTP/1.1 404 Not Found");
            print json_encode(['errormesg'=>"Player $b not found."]);
            break;
    }
    print "user";
}

?>