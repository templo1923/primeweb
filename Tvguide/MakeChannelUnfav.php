<?php

include "../session.php";
$username = $_SESSION["username"];
$streamKey = $_POST["stream_id"];
$CookieArray = json_decode($_COOKIE["movies_fav_" . $username], true);
$unset_queue = [];
foreach ($CookieArray as $key => $value) {
    if ($streamKey == $value["stream_id"]) {
        $unset_queue[] = $key;
    }
}
foreach ($unset_queue as $index) {
    unset($CookieArray[$index]);
}
$CookieArray = array_values($CookieArray);
$CookieArray2 = json_encode($CookieArray);
setcookie("movies_fav_" . $username, $CookieArray2, time() + 1209600, "/", $_SERVER["SERVER_NAME"], false);
echo "{\"status\":\"Success\",\"message\":\"Favorit deleted\"}";

?>