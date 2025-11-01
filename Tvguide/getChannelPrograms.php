<?php

include "../session.php";
$streamKey = $_POST["streamKey"];
$channel_api = json_decode(file_get_contents($get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_simple_data_table&stream_id=" . $streamKey), true);
$channel_api2 = json_decode(json_encode($channel_api["epg_listings"]), true);
$i = 0;
foreach ($channel_api2 as $key => $value) {
    $title = base64_decode($value["title"]);
    $description = base64_decode($value["description"]);
    $start = $value["start"];
    $start = strtotime($start);
    $startdate = date("H:i", $start);
    $end = $value["end"];
    $end = strtotime($end);
    $enddate = date("H:i", $end);
    $date = date("M dS Y", $start);
    if (time() < $end && $start < time()) {
        $title2 = $title;
        $description2 = $description;
    }
}
header("Content-type: application/json");
if (empty($channel_api2)) {
    echo "{\"activeProgramDetails\":{\"title\":\"No data\",\"desc\":\"\",\"date\":\"\",\"start_time\":\"\"},\"status\":\"Success\",\"html\":\"\"}";
} else {
    echo "{\"activeProgramDetails\":{\"title\":\"" . $title2 . "\",\"desc\":\"" . $description2 . "\",\"date\":\"" . $date . "\",\"start_time\":\"" . $startdate . "\"},\"status\":\"Success\",\"html\":\"";
    foreach ($channel_api2 as $key => $value) {
        $title = base64_decode($value["title"]);
        $description = base64_decode($value["description"]);
        $start = $value["start"];
        $start = strtotime($start);
        $startdate = date("H:i", $start);
        $end = $value["end"];
        $end = strtotime($end);
        $enddate = date("H:i", $end);
        if (time() < $end) {
            $progHtml = "<li class=\"program-details\">\n\t\t\t\t\t\t\t\t<div class=\"programTimeContainer\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"programDate\" style=\"display:none;\"></div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"programStartTime\">" . $startdate . "</div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"programTimeSeperator\"> - </div>\n\t\t\t\t\t\t\t\t\t\t<div class=\"programStopTime\">" . $enddate . "</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"programTitle\">" . $title . "</div>\n\t\t\t\t\t\t\t\t<div class=\"prog-desc\" style=\"display:none\">" . $description . "</div>\n\t\t\t\t\t\t\t</li>";
            echo trim(json_encode($progHtml), "\"");
        }
    }
    echo "\"}";
}

?>