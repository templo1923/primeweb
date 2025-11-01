<?php

include "../session.php";
$username = $_SESSION["username"];
$streamKey = $_POST["series_id"];
$channel_api = json_decode(file_get_contents($get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_info&series_id=" . $streamKey), true);
$channel_api2 = json_decode(json_encode($channel_api["info"]), true);
$title = $channel_api2["name"];
$img = $channel_api2["cover"];
if ($img == "") {
    $poster = "https://i.imgur.com/Mn7aXQD.jpg";
} else {
    if ($img == NULL) {
        $poster = "https://i.imgur.com/Mn7aXQD.jpg";
    } else {
        $poster = $img;
    }
}
$CookieArray = ["stream_id" => $streamKey, "stream_icon" => $poster, "title" => $title];
$CookieArray = json_encode($CookieArray);
if (isset($_COOKIE["series_fav_" . $username])) {
    $coords = trim($_COOKIE["series_fav_" . $username], "[]");
    $CookieArray = "[" . $coords . "," . $CookieArray . "]";
} else {
    $CookieArray = "[" . $CookieArray . "]";
}
setcookie("series_fav_" . $username, $CookieArray, time() + 1209600, "/", $_SERVER["SERVER_NAME"], false);
echo "{\"status\":\"Success\",\"message\":\"Favorit added\"}";

?>