<?php

include "../session.php";
$username = $_SESSION["username"];
$streamKey = $_POST["channel_id"];
$stream_icon = "";
$title = "";
$CookieArray = ["stream_id" => $streamKey, "stream_icon" => $stream_icon, "title" => $title];
$CookieArray = json_encode($CookieArray);
if (isset($_COOKIE["channels_fav_" . $username])) {
    $coords = trim($_COOKIE["channels_fav_" . $username], "[]");
    $CookieArray = "[" . $coords . "," . $CookieArray . "]";
} else {
    $CookieArray = "[" . $CookieArray . "]";
}
setcookie("channels_fav_" . $username, $CookieArray, time() + 1209600, "/", $_SERVER["SERVER_NAME"], false);
echo "{\"status\":\"Success\",\"message\":\"Favorit added\"}";

?>