<?php
//DONT MODIFY BELOW//

$jsondata = file_get_contents("./includes/admin/admin.json");
$json = json_decode($jsondata, true);

$sitename = $json['sitename']; 
$adminU = $json['username']; 
$adminP = $json['password']; 

if (isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $protocol = 'https://';
}else {
  $protocol = 'http://';
}
$basedir = $protocol.$_SERVER['HTTP_HOST'].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
$json_file = "./includes/dns/dns.json";
$jsondata = file_get_contents($json_file);
if (file_exists($json_file)) {
} else {
	$json ='{
    "default_color": "black",
    "dns_number": "no",
    "server1_name": "",
    "server1": "",
    "server2_name": "",
    "server2": "",
    "server3_name": "",
    "server3": "",
    "server4_name": "",
    "server4": "",
    "server5_name": "",
    "server5": "",
    "server6_name": "",
    "server6": ""
}';
    file_put_contents($json_file, $json);
}
$json = json_decode($jsondata, true);
$default_color = $json["default_color"];
$get_dir = $basedir;