<?php

include "../session.php";
$username = $_SESSION["username"];
if (isset($_GET["movies"])) {
    if (isset($_COOKIE["movies_fav_" . $username])) {
        $id = $_POST["stream_id"];
        $SettingArray = json_decode($_COOKIE["movies_fav_" . $username], true);
        foreach ($SettingArray as $key => $value) {
            if ($id == $value["stream_id"]) {
                echo "{\"status\":\"Success\",\"message\":\"Favoris Exist\"}";
            }
        }
    }
} else {
    if (isset($_GET["series"])) {
        if (isset($_COOKIE["series_fav_" . $username])) {
            $id = $_POST["stream_id"];
            $SettingArray = json_decode($_COOKIE["series_fav_" . $username], true);
            foreach ($SettingArray as $key => $value) {
                if ($id == $value["stream_id"]) {
                    echo "{\"status\":\"Success\",\"message\":\"Favoris Exist\"}";
                }
            }
        }
    } else {
        if (isset($_GET["channels"]) && isset($_COOKIE["channels_fav_" . $username])) {
            $id = $_GET["stream_id"];
            $SettingArray = json_decode($_COOKIE["channels_fav_" . $username], true);
            foreach ($SettingArray as $key => $value) {
                if ($id == $value["stream_id"]) {
                    echo "{\"status\":\"Success\",\"message\":\"Favoris Exist\"}";
                }
            }
        }
    }
}

?>