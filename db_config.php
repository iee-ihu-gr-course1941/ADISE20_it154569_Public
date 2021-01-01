<?php
$host = 'localhost';
$db = 'ADISE2020_db';

$user = 'root';
$pass = '19977991AnyonE!';


if (gethostname() == 'users.iee.ihu.gr') {
    $mysqli = new mysqli($host, $user, $pass, $db, null, '/home/student/it/2015/it154569/mysql/run/mysql.sock');
} else {
    $mysqli = new mysqli($host, $user, $pass, $db);
}

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" .
        $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$GLOBALS["mysqli"] = $mysqli;
