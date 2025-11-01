<?php

include "../session.php";
if (isset($_COOKIE["channels_fav_" . $username])) {
    $id = $_GET["stream_id"];
    $SettingArray = json_decode($_COOKIE["channels_fav_" . $username], true);
    foreach ($SettingArray as $key => $value) {
        if ($id == $value["stream_id"]) {
            echo "{\"status\":\"Success\",\"message\":\"Favoris Exist\"}";
        }
    }
}

?>