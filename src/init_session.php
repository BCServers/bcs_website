<?php

require "frontend_data_generation.php";

session_start();

$USER_DB = load_json("../user_db.json");
$SERVER_DB = load_json("../server_db.json");

$logged_in = false;

if(isset($_REQUEST['login'])){
	$username = $_REQUEST['username'];
	$password = $_REQUEST['password'];
	$pwd_hash = find_user($username, $USER_DB)->password_hash ?? null;

	if( password_verify($password, $pwd_hash) ) {
		$_SESSION['username'] = $username;
		$_SESSION['LOGIN_TIME'] = time();
	}
}

if(isset($_REQUEST['logout'])){
	session_unset();
	session_destroy();
	header("Location: /");
	exit();
}

if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME'] > 600)) {
	session_unset();
	session_destroy();
	session_start();
}  

if (!empty($_SESSION['username'])) {
	$logged_in = true;
	$username = $_SESSION['username'];

	$FRONTEND_DATA = generate_frontend_data(
		find_user($username, $USER_DB), $SERVER_DB, 'dummy3');
}

?>
