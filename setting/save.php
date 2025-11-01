<?php

$stream_format = $_POST["stream_format"];
$theme_color = $_POST["theme_color"];
$pincode = "1111";
$CookieArray = ["color" => $theme_color, "pincode" => $pincode, "stream_type" => $stream_format];
$CookieArray = json_encode($CookieArray);
setcookie("settings_array", $CookieArray, time() + 1209600, "/", $_SERVER["SERVER_NAME"], false);
header("Location: ../settings");

?>