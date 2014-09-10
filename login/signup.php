<?php
session_start();

require_once('../settings/config.php');
require_once('../settings/function.php');

if($_SERVER['REQUEST_METHOD'] != 'POST'){
	setToken();
}else{
	checkToken();

	$username = $_POST['username'];
	$password = $_POST['password'];

	$dbh = connectDB();
	$err = array();

	if($username == '')
		$err['username'] = 'write your username';
	if($password == '')
		$err['password'] = 'write your password';

	if(empty($err)){
		$sql = "insert into users (username, password, created, modified) values (:username, :password, now(), now());
				update avater.users set position = last_insert_id() where id = last_insert_id();";
		$stmt = $dbh->prepare($sql);
		$params = array(
			":username" => $username,
			":password" => getSha1Password($password)
		);
		$stmt->execute($params);
		header('Location: '.SITE_URL.'login/login.php');
		exit();
	}
}
?>
<html>
<head>
	<meta charset="UTF-8">
	<title>新規ユーザ登録画面</title>
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
	<h1>New Account</h1>
</div>
<div id="wrap">
	<div id="signup">
		<form action="" method="POST" id="signup_form">
			<p>username</p>
			<div class="form"><input type="text" name="username" class="form-control" value="<?php echo(h($username)); ?>"><?php echo(h($err['username'])); ?></div>
			<p>password</p>
			<div class="form"><input type="password" name="password" class="form-control" value=""><?php echo(h($err['password'])); ?></div>
			<p><input type="hidden" name="token" value="<?php echo $_SESSION['token']?>"></p>
			<div class="button"><button type="submit" class="btn btn-primary btn-lg">新規登録</button></div>
			<p><a href="../index.php">戻る</a></p>
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