<?php

include "../session.php";
$seasonKey = $_POST["seasonKey"];
$seriesKey = $_POST["seriesKey"];
header("Content-type: application/json");
echo "{\"status\":\"Success\",\"html\":\"";
$result1 = "<div class=\"owl-carousel owl-theme owl-carousel1\" style=\"opacity: 1; display: block;\">";
echo trim(json_encode($result1), "\"");
$channel_api = simplexml_load_file($get_dns . "/enigma2.php?username=" . $username . "&password=" . $password . "&type=get_series_streams&series_id=" . $seasonKey . "&season=" . $seriesKey);
foreach ($channel_api->channel as $value) {
    $path = parse_url($value->stream_url, PHP_URL_PATH);
    $filename = pathinfo($path, PATHINFO_FILENAME);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $title = base64_decode($value->title);
    $desc_image = $value->desc_image;
    $cats = str_replace("All", "SELECT A CATEGORY", $title);
    $play_url = "video_player?id=" . $filename . "&slug=series&ext=" . $extension . "&series_id=" . $seasonKey;
    $result = "\n\t\t<div class=\"item\">\n\t\t\t<a href=" . $play_url . ">\n\t\t\t\t<img class=\"se_epi_img\" src=" . $desc_image . ">\n\t\t\t\t<div class=\"hover_details\">\n\t\t\t\t\t<p class=\"se_epi_p\">" . $title . "</p>\n\t\t\t\t</div>\n\t\t\t</a>\n\t    </div>";
    echo trim(json_encode($result), "\"");
}
$result2 = "</div>";
echo trim(json_encode($result2), "\"");
echo "\"}";

?>