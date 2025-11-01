<?php

session_start();

if (isset($_SESSION['username'])) {
	header('Location: login');
}
else {
	header('Location: login');
}

?>