<?php
session_start();

require_once('../settings/config.php');
require_once('../settings/function.php');

$_SESSION = array();

if(isset($_COOKIE[session_name()])){
	setcookie(session_name(), '', time()-86400);
}

session_destroy();

header('Location: '.SITE_URL.'login/login.php');