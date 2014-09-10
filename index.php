<?php
session_start();
require_once('settings/config.php');
require_once('settings/function.php');
require_once('settings/log.php');

if(empty($_SESSION['me'])){
	header('Location: '.SITE_URL.'login/login.php');
	exit();
}

$username = $_GET['username'];
$dbh = connectDB();

if($_GET['username']){
	$username = $_GET['username'];
//	print_r($username);
	// GETの中に入っている友人が本当に自分の友達なのかのチェック(対策しないといろんな人のログを見れてしまう)

	// ユーザ名からidを取得する
	$sql = "select id from avater.users where username = :username limit 1";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':username', $username, PDO::PARAM_STR);
	$stmt->execute();
	$user = $stmt->fetch();
	$_SESSION['me']['position'] = $user['id'];
	//var_dump($user['id']);

	// 現在地の移動
	$sql = "update avater.users set position = :position where username = :username";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':position', $_SESSION['me']['position'], PDO::PARAM_STR);
	$stmt->bindValue(':username', $_SESSION['me']['username'], PDO::PARAM_STR);
	$stmt->execute();
	$user = $stmt->fetch();

	// var_dump($username);
}

$sql = "select username from avater.users where id = :position limit 1";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':position', $_SESSION['me']['position'], PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch();
if($user['username']!=$_SESSION['me']['username']){
	$roomname = $user['username'] . " Room";
}else{
	$roomname = 'MyRoom';
}

$enc_jsondata = returnAllLog($_SESSION['me']['position']);

?>


<html>
<head>
	<meta charset="UTF-8">
	<title>top menu</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
	<!-- Bootstrap -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/font-awesome.css" rel="stylesheet">
	<link href="css/main.css" rel="stylesheet">
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script>
	    var myname = <?php echo '"' . $_SESSION['me']['username'] . '"'?>;
//	    console.log(myname);
    </script>
    <script src="js/chat.js"></script>
    
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<style>
		.room_button{
			background-color: #DDDDDD !important;
		}
	</style>
</head>
<body>


<div id="header">
	<h1 <?php echo 'style="font-size: '. (55-strlen($roomname)*2) .'"'; ?>><?php echo($roomname); ?><a href="login/logout.php"><i class="fa fa-sign-out"></i></a></h1>
</div>

<div id="wrap">
	<div id="chat_view_container">
		<div id="chat_view">
			<ul id="chat_view_ul"></ul>

		</div>
	</div>
	<div id="chat_control_container">
	<div id="chat_control">
	<input type="hidden" name="roomid" id="roomid" value="<?php echo $_SESSION['me']['position']; ?>">
	<input type="hidden" name="chatname" id="chatname" value="<?php echo $_SESSION['me']['username']; ?>">
	<input type="text" name="str" id="str" class="form-control">
	<button type='submit' name="button1" id="button1" class='btn btn-info'>送信</button>
	</div>
	</div>

</div>
<div id="footer">
	<div class="container">
		<div class="row">
			<a href="index.php?username=<?php echo(position2username($dbh, $_SESSION['me']['position'])); ?>"><div class="col-xs-3 room_button"><i class="fa fa-home fa-2x"></i>room</div></a>
			<a href="friend.php"><div class="col-xs-3 friend_button"><i class="fa fa-users fa-2x"></i>friend</div></a>
			<a href="position.php"><div class="col-xs-3 map_button"><i class="fa fa-map-marker fa-2x"></i></br>map</div></a>
			<a href="settings.php"><div class="col-xs-3 setting_button"><i class="fa fa-cog fa-2x"></i>setting</div></a>
		</div>
	</div>
</div>
	<!-- Include all compiled plugins (below), or include individual files as needed -->
	<script src="js/bootstrap.min.js"></script>
</body>
</html>