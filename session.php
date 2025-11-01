<?php

session_start();
if (!isset($_SESSION["username"])) {
    header("Location: ./login");
    exit;
}
$get_dns = $_SESSION["server"];
$username = $_SESSION["username"];
$password = $_SESSION["password"];
echo "\n";

?>