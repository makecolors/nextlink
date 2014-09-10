<?php
session_start();
require_once('settings/config.php');
require_once('settings/function.php');

if(empty($_SESSION['me'])){
	header('Location: '.SITE_URL.'login/login.php');
	exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST'){
}else{
	if($_POST['friendname']){
		// 友達に申請する
		 $message = changeFriendStatus($_SESSION['me']['username'], $_POST['friendname'], FRIEND_REQUEST_SEND);
	}else if($_POST['befriend']){
		// 友達になる(友達の申請に反応する)
		// $message = 
		changeFriendStatus($_SESSION['me']['username'], $_POST['befriend'], FRIEND_FOLLOW);
	}else if($_POST['delete']){
		// 友達を解除する
		//$message = 
		changeFriendStatus($_SESSION['me']['username'], $_POST['delete'], FRIEND_NOTFRIEND);
	}
}

$dbh = connectDB();
$sql = "select * from avater.users where username = :username limit 1";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':username', $_SESSION['me']['username'], PDO::PARAM_STR);
$stmt->execute();
$frienddata = $stmt->fetch();
$friendlist = $frienddata['friendlist'];
//var_dump($friendlist);
if($friendlist != null){
	$unser_friendlist = unserialize($friendlist);
}else{
	$unser_friendlist = null;
}

// 自分のポジションにいる友達の集合を取得 ($frienddata['username']: あるpositionにいる人全員)
$dbh = connectDB();
$sql = "select username from avater.users where position = :position";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':position', $_SESSION['me']['position'], PDO::PARAM_STR);
$stmt->execute();
$frienddata = $stmt->fetchAll();

//var_dump($frienddata);
$list = array();
foreach ($frienddata as $key) {
	//var_dump($key['username']);
	array_push($list, $key['username']);
}
//var_dump($list);
//exit();
// 自分の他人以外のものをリストアップ
// 自分のフレンドリストで他人をリストアップする

//
$sql = "select friendlist from avater.users where username = :username";
$stmt = $dbh->prepare($sql);
$stmt->bindValue(':username', $_SESSION['me']['username'], PDO::PARAM_STR);
$stmt->execute();
$f = $stmt->fetch();

if($f['friendlist'] != null){
	$temp = unserialize($f['friendlist']);
}else{
	$temp = array();
}

//print_r("step1 :自分のいるポジションに存在する友達: ");
//var_dump($list);
//print_r("<br>");
//print_r("step2 :自分のフレンドリスト: ");
//$myfriendlist = array();
$myfriendlist = array_keys($temp);
//print_r("<br>");
//print_r("step3 :自分のフレンドリスト+自分: ");
array_push($myfriendlist, $_SESSION['me']['username']);
//print_r("<br>");
//print_r("step4 :1-3は?: ");
$difflist = array_diff($list, $myfriendlist);
//var_dump($difflist);
//exit();


?>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
	<title>FriendList</title>

	<!-- Bootstrap -->
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/font-awesome.css" rel="stylesheet">
	<link href="css/main.css" rel="stylesheet">
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
	<script src="js/avater_friend.js"></script>
	<script>
	// 友達申請時にアラートを出力する
		var message = '<?php echo $message; ?>';
		if (message != "") {
			alert(message);
		}
	</script>
	<style>
		.friend_button{
			background-color: #DDDDDD !important;
		}
	</style>
</head>
<body>
<div id="header">
	<h1>Friend<a href="login/logout.php"><i class="fa fa-sign-out"></i></a></h1>
</div>
<div id="wrap">
	<div id="friendadd">
		<p>ユーザ名で申請</p>
		<form action="" method="POST">
			<input type="text" class="form-control" name="friendname">
			<button type='submit' class='btn btn-info'>申請する</button>
		</form>
	</div>
	<!--未友達(REQUEST)の表示-->
	<div id="friendlisttable">
		<?php
			echo("<table>");
			echo("<tr id='friendlistheader'><th>friend</th><th>status</th><th>action</th></tr>");
			//var_dump($difflist);
			foreach ($difflist as $key) {
				echo("<tr><td>".$key."</td><td>".getfriendstatus(FRIEND_NOTFRIEND)."</td>
					<td>
						<form action='' method='POST'>
							<input type='hidden' class='form-control' name='friendname' value='".$key."'>
							<button type='submit' class='btn btn-info'>申請する</button>
						</form>
					</td>
					</tr>");
			}
			if($unser_friendlist != null){
				foreach ($unser_friendlist as $key => $value) {
					if($value == FRIEND_REQUEST_SEND){
						echo("<tr><td>".$key."</td><td>".getfriendstatus($value)."</td><td><button type='submit' class='btn btn-default' disabled>友達申請中</button></td></tr>");
					}
				}
				foreach ($unser_friendlist as $key => $value) {
					if($value == FRIEND_REQUEST_RECEIVE){
						echo("<tr><td>".$key."</td><td>".getfriendstatus($value)."</td><td><form action='' method='POST'><input type='hidden' name='befriend' value='".$key."'><button type='submit' class='btn btn-default'>友達承認</button></td></form></tr>");
					}
				}
				foreach ($unser_friendlist as $key => $value) {
					if($value == FRIEND_FOLLOW){
						echo("<tr><td>".$key."</td><td>".getfriendstatus($value)."</td><td><form action='' method='POST'><input type='hidden' name='delete' value='".$key."'><button type='submit' class='btn btn-default'>友達削除</button></td></form></tr>");
					}
				}
			}
			echo("</table>");
		?>
	</div>
	<div>
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
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<!-- Include all compiled plugins (below), or include individual files as needed -->
	<script src="js/bootstrap.min.js"></script>
</body>
</html>