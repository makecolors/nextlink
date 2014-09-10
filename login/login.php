<?php
session_start();
require_once('../settings/config.php');
require_once('../settings/function.php');

//var_dump($_SESSION['me']);

if(!empty($_SESSION['me'])){
	header('Location: '.SITE_URL."index.php?username=".$_SESSION['me']['username']);
	exit();
}

function getUser($username, $password, $dbh){
	$sql = "select * from users where username = :username and password = :password limit 1";
	$stmt = $dbh->prepare($sql);
	$stmt->execute(array(":username"=>$username, ":password"=>getSha1Password($password)));
	$user = $stmt->fetch();
	//var_dump($user);
	return $user ? $user : false;
}

if($_SERVER['REQUEST_METHOD'] != 'POST'){
	setToken();
}else{
	checkToken();

	$username = $_POST['username'];
	$password = $_POST['password'];

	$dbh = connectDB();
	$err = array();
	$me = getUser($username, $password, $dbh);

	if($username == ''){
		$err['username'] = 'write your username.';
	}
	if($password == ''){
		$err['password'] = 'write your password.';
	}
	if(!$me){
		$err['password'] = 'not valid';
	}

	if(empty($err)){
		session_regenerate_id(true);
		$_SESSION['me'] = $me;
		//var_dump($_SESSION['me']);
		header('Location: '.SITE_URL."index.php?username=".$_SESSION['me']['username']);
		exit();
	}
}
?>
<html>
<head>
	<meta charset="UTF-8">
	<title>ログイン画面</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
	<!-- Bootstrap -->
	<link href="../css/bootstrap.min.css" rel="stylesheet">
	<link href="../css/main.css" rel="stylesheet">

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body>
<div id="header">
	<h1>Next Link</h1>
</div>
<div id="wrap">
<p id="description">友達の友達とつながるアプリ</p>
	<div id="login">
		<form action="" method="POST" id="login_form">
			<p>username</p>
			<div class="form"><input type="text" name="username" class="form-control" value="<?php echo(h($username)); ?>"><?php echo(h($err['username'])); ?></div>
			<p>password</p>
			<div class="form"><input type="password" name="password" class="form-control" value=""><?php echo(h($err['password'])); ?></div>
			<p><input type="hidden" name="token" value="<?php echo $_SESSION['token']?>"></p>
			<div class="button"><button type="submit" class="btn btn-primary btn-lg">ログイン</button></div>
			<div class="button"><button type="button" class="btn btn-default btn-lg"><a href="signup.php">新規登録</a></button></div>
		</form>
	</div>
</div>
<div id="footer">
</div>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<!-- Include all compiled plugins (below), or include individual files as needed -->
	<script src="../js/bootstrap.min.js"></script>
</body>
</html>