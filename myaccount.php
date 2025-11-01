<?php

include "session.php";
$pagename = "My Account";
include "header.php";
include "config.php";

$panel_api = file_get_contents($get_dns . "/player_api.php?username=" . $username . "&password=" . $password);
$data = json_decode($panel_api, true);
$channel_api2 = json_decode(json_encode($data["user_info"]), true);
$exp = $channel_api2["exp_date"];
$acc_active = $channel_api2["status"];
$acc_user = $channel_api2["username"];

if ($exp == NULL) {
    $expire = "UNLIMITED";
} else {
    $expire = gmdate("d/m/Y", $exp);
}

echo '<div class="account-container" style="text-align: center; padding: 20px;">';
echo '    <div class="topmoviestext2" style="margin-bottom: 20px;">';
echo '        <div class="item toptext11">';
echo '            <h4 class="notification-heading">ACCOUNT</h4>';
echo '            <p class="notification-text">Take a look at your account details including all of your subscription details such as account expiry.</p>';
echo '        </div>';
echo '        <div class="item">';
echo '            <img src="assets/images/adminimg.png" style="max-width: 150px;">';
echo '        </div>';
echo '    </div>';
echo '    <div class="acc_id_sec" style="display: inline-block; text-align: left;">';
echo '        <div class="item" style="padding: 10px;">';
echo '            <p><strong>ACCOUNT ID:</strong> ' . $acc_user . '</p>';
echo '        </div>';
echo '        <div class="item" style="padding: 10px;">';
echo '            <p><strong>Expiry Date:</strong> ' . $expire . '</p>';
echo '        </div>';
echo '        <div class="item" style="padding: 10px;">';
echo '            <p><strong>Account Status:</strong> ' . $acc_active . '</p>';
echo '        </div>';
echo '    </div>';
echo '</div>';

echo '<script>';
echo '$(function() {';
echo '  $("#myaccount_nav").addClass("active_menu");';
echo '});';
echo '</script>';

?>
