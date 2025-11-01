<?php
$jsondata = file_get_contents("admin.json");
$json = json_decode($jsondata, true);
if ($_COOKIE["admin"] != "true") {
    header("location:" . "../../index.php");
}
$message = '<div class="alert alert-success" id="flash-msg"><h4><i class="icon fa fa-check"></i>Items Updated!</h4></div>';

if (isset($_POST["submit"])) {
    $jsondata = file_get_contents("admin.json");
    $replacementData = array(
        'username'   => $_POST["username"],
        'password'   => $_POST["password"],
        'sitename'   => $_POST["sitename"]
    );
    $newArrayData = array_replace_recursive($json, $replacementData);
    $newJsonData = json_encode($newArrayData, JSON_PRETTY_PRINT);
    file_put_contents("admin.json", $newJsonData);
    header("Location: edit_admin.php?message=$message");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Admin Page</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
</head>
<style>
body {
    background-color: black;
    color: white;
    display: flex;
}
.sidebar {
    width: 200px;
    background-color: #333;
    padding-top: 20px;
    position: fixed;
    height: 100%;
}
.sidebar a {
    color: white;
    padding: 15px;
    text-decoration: none;
    display: block;
    text-align: left;
}
.sidebar a:hover {
    background-color: #ddd;
    color: black;
}
.container {
    margin-left: 220px;
    padding: 20px;
    width: calc(100% - 220px);
}
.card {
    margin-bottom: 20px;
    border: 1px solid #444;
    border-radius: 8px;
    padding: 20px;
    background-color: #222;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
}
.center-div {
    text-align: center;
    margin-top: 20px;
}
input[type="text"] {
    width: 100%;
    background-color: #333;
    color: white;
    border: 1px solid #555;
    border-radius: 5px;
    padding: 10px;
}
input[type="text"]::placeholder {
    color: #bbb;
}
</style>
<body>

<!-- Menú Vertical con nuevo orden -->
<div class="sidebar">
    <a href="../../includes/dns/edit_dns.php">DNS Settings Page</a>
    <a href="../../includes/admin/edit_admin.php">Change Admin User/Pass</a>
    <a href="../../index.php">Login Page</a>
</div>

<div class="container">
  <form method="POST">
    <?php if (isset($_GET['message'])) { echo $_GET['message']; } ?>
    <br><br>
    <h2>Change Admin User/Pass</h2>
    <br>
    <!-- card -->
    <div class="card bg-dark text-white">
        <div class="card-body">
            <div class="form-group">
                <label for="sitename">Website Name:</label>
                <input type="text" class="form-control" name="sitename" value="<?=$json['sitename']?>"><br>
                <label for="username">Username:</label>
                <input type="text" class="form-control" name="username" value="<?=$json['username']?>"><br>
                <label for="password">Password:</label>
                <input type="text" class="form-control" name="password" value="<?=$json['password']?>">
            </div>
        </div>
    </div>
    
    <div class="center-div">
        <button type="submit" name="submit" class="block btn btn-primary">Update</button>
    </div>
  </form>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script> 
$(document).ready(function () {
    $("#flash-msg").delay(3000).fadeOut("slow");
});
</script>
</body>
</html>
