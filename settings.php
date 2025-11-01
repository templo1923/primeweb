<?php
include "session.php";
$pagename = "Settings";
include "header.php";
include "config.php";

if (isset($_COOKIE["settings_array"])) {
    $SettingArray = json_decode($_COOKIE["settings_array"], true);
    $stream_type = $SettingArray["stream_type"];
} else {
    $stream_type = "m3u8";
}

echo "<style>
.settings-colors {
    display: inline-flex;
    flex-wrap: wrap;
}
span.checkmark {
    width: 36px;
    height: 36px;
    display: inline-block;
    border-radius: 50%;
    margin: 8px;
    text-align: center;
    box-shadow: 0 4px 20px 1px rgba(0,0,0,.06), 0 1px 4px rgba(0,0,0,.03);
    cursor: pointer;
}
.themecontainer {
    display: block;
    position: relative;
    padding-left: 35px;
    margin-bottom: 12px;
    cursor: pointer;
    font-size: 22px;
    user-select: none;
    margin: 8px;
}
.themecontainer input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}
.checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 25px;
    width: 25px;
    background-color: #eee;
    border-radius: 50%;
}
.themecontainer:hover input ~ .checkmark {
    background-color: #ccc;
}
.themecontainer input:checked ~ .checkmark {
    background-color: #2196F3;
}
.checkmark:after {
    content: \"\";
    position: absolute;
    display: none;
}
.themecontainer input:checked ~ .checkmark:after {
    display: block;
}
.checkmark:after {
    top: 13px;
    left: 13px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ffffff1f;
}
.setitem {
    margin-bottom: 0px;
    width: 95%;
    text-align: center;
    padding: 50px;
    border-radius: 15px;
    margin-right: 10px;
}
.setitem p .icon {
    font-size: 40px;
}
.button {
    width: 100%;
    text-align: center;
    color: #414141;
    font-size: 20px;
    padding: 15px;
}
.set_type {
    color: #414141;
    font-size: 20px;
    padding: 15px;
}
.setitem a {
    color: #ffffff !important;
    text-decoration: none;
}
.setitem a:focus, a:hover {
    color: #ffffff;
    text-decoration: underline;
}
@media (max-width: 767px) { 
.icon_sec {
    display: block !important;
    padding: 10px 15px;
}
.top_backgrund_sec1 form {
    padding: 15px;
}
}
</style>";

echo '<!--section topmoviestext start-->
<div class="topmoviestext2">
    </div>
    <div class="item">
        <img src="http://ltqweb.com/assets/images/adminimg.png">
    </div>
</div>';

echo '<form action="setting/save" method="POST">
<div class="icon_sec">
    <div class="setitem">
        <h4 class="notification-heading"><i class="fa fa-play-circle icon" aria-hidden="true"></i> Stream Formats</h4>
        <p class="set_type">
            <label class="themecontainer">MPEG(ts) 
                <input name="stream_format" id="stream_format" type="radio" class="set_type" value="ts" ' . ($stream_type == "ts" ? 'checked' : '') . '>
                <span class="checkmark"></span>
            </label><br>
            <label class="themecontainer">HLS(m3u8)
                <input name="stream_format" id="stream_format" type="radio" class="set_type" value="m3u8" ' . ($stream_type == "m3u8" ? 'checked' : '') . '>
                <span class="checkmark"></span>
            </label>
        </p>
    </div>
</div>';

echo '<div class="button">
    <input name="submit" type="submit" class="btn btn-danger set_type">
</div>
</form>';

echo '<script>
$(function() {
    $("#settings_nav").addClass("active_menu");
});
</script>';
?>
