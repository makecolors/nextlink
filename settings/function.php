<?php
// デバッグ用:本番では処理の読み込みを高速化するために抜いてください
/* 使い方: ChromePhp.php
 *	ChromePhp::log('Hello console!');
 *	ChromePhp::log($_SERVER);
 *	ChromePhp::warn('something went wrong!');
 */
include 'config.php';
include 'ChromePhp.php';


function connectDB(){
	try{
		return new PDO(DSN, DB_USER, DB_PASSWORD);
	}catch(PDOException $e){
		echo $e->getMessage();
		exit;
	}
}

function h($s){
	return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

function setToken(){
	$token = sha1(uniqid(mt_rand(), true));
	$_SESSION['token'] = $token;
}
function checkToken(){
	if(empty($_SESSION['token']) || ($_SESSION['token'] != $_POST['token'])){
		echo("不正なポスト");
		//echo("session: ".$_SESSION['token']);
		//echo("post: ".$_POST['token']);
		exit();
	}
}
function getSha1Password($s){
	return sha1(PASSWORD_KEY.$s);
}
function getAllUsername($dbh){
	// ユーザ名のみを取り出す
	$sql = "select * from avater.users";
	$stmt = $dbh->prepare($sql);
	$stmt->execute();
	$friendsdata = $stmt->fetchAll();
	$array = array();
	foreach ($friendsdata as $data) {
		array_push($array, $data['username']);
	}
	return $array;
}
function getfriendstatus($s){
	switch($s){
		case FRIEND_NOTFRIEND: $status = 'not friend'; break;
		case FRIEND_REQUEST_SEND: $status = '<i class="fa fa-star-o"></i>'; break;
		case FRIEND_FOLLOW:    $status = '<i class="fa fa-star"></i>'; break;
		case FRIEND_REQUEST_RECEIVE: $status = '<i class="fa fa-heart"></i>'; break;
		default: $status='error'; break;
	}
	return $status;
}
function getUserFriendlist($dbh, $username){
// 自分のフレンドリストを取り出す
	$sql = "select * from avater.users where username = :username limit 1";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':username', $username, PDO::PARAM_STR);
	$stmt->execute();
	$frienddata = $stmt->fetch();

	// フレンドが誰もいないときは型を調整する
	if($frienddata['friendlist'] != null){
		$friendarray = unserialize($frienddata['friendlist']);
	}else{
		$friendarray = array();
	}
	return $friendarray;
}
function updateFriendlist($dbh, $friendarray, $username){
	// usernameのフレンドリストを更新する
	$sql = "update avater.users set friendlist = :friendlist where username = :username";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':friendlist', serialize($friendarray), PDO::PARAM_STR);
	$stmt->bindValue(':username', $username, PDO::PARAM_STR);
	$stmt->execute();
}

function changeFriendStatus($myname, $friendname, $status){
	$dbh = connectDB();	
		
	if($status == FRIEND_REQUEST_SEND){
		// ユーザ名のみを取り出す
		$array = getAllUsername($dbh);

		// 友達になりたいユーザ名がこのアプリに登録されていたら
		if(array_search($friendname, $array) !== FALSE){
			// 自分のフレンドリストを取り出す
			$myfriendlist = getUserFriendlist($dbh, $myname);
			$yourfriendlist = getUserFriendlist($dbh, $friendname);

			if($myname!=$friendname){
				// フレンドリストに登録したい友達が自分のフレンドリストの中にいないとき
				if($myfriendlist[$friendname] == null){

					// 自分のフレンドリストに新たなフレンドを挿入する(nullの場合は注意)
					if($myfriendlist != null){
						$myfriendlist = array_merge($myfriendlist, array($friendname => $status));
					}else{
						$myfriendlist = array($friendname => $status);
					}

					// 友達のフレンドリストに自分が申請中であるという情報を挿入する
					if($yourfriendlist != null){
						$yourfriendlist = array_merge($yourfriendlist, array($myname => FRIEND_REQUEST_RECEIVE));
					}else{
						$yourfriendlist = array($myname => FRIEND_REQUEST_RECEIVE);
					}

					// 新たにフレンドを登録する
					updateFriendlist($dbh, $myfriendlist, $myname);
					updateFriendlist($dbh, $yourfriendlist, $friendname);

					//$message = 'send request to username: '.$friendname.'.';
				}else{
					$message = 'username: '.$friendname.' already sent request.';
				}
			}else{
				$message = '自分自身です！';
			}
		}else{
			$message = 'そのようなユーザは存在しません';
		}
	}
	else if($status == FRIEND_FOLLOW){
		// 自分のフレンドリストを取り出す
		$myfriendlist = getUserFriendlist($dbh, $myname);
		$yourfriendlist = getUserFriendlist($dbh, $friendname);
		
		$myfriendlist[$friendname] = $status;
		$yourfriendlist[$myname] = $status;
		
		updateFriendlist($dbh, $myfriendlist, $myname);
		updateFriendlist($dbh, $yourfriendlist, $friendname);		
		$message = 'follow username: '.$friendname;
	}else if($status == FRIEND_NOTFRIEND){
		// 自分のフレンドリストを取り出す
		$myfriendlist = getUserFriendlist($dbh, $myname);
		$yourfriendlist = getUserFriendlist($dbh, $friendname);
		
		unset($myfriendlist[$friendname]);
		unset($yourfriendlist[$myname]);

		
		updateFriendlist($dbh, $myfriendlist, $myname);
		updateFriendlist($dbh, $yourfriendlist, $friendname);
		$message = 'delete username: '.$friendname;
	}
	
	return $message;

}
// positionからusernameを取り出す
function position2username($dbh, $position){
	$sql = "select username from avater.users where id = :position limit 1";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':position', $position, PDO::PARAM_STR);
	$stmt->execute();
	$data = $stmt->fetch();
	return $data['username'];
}

// usernameからidを取り出す
function username2id($dbh, $username){
	$sql = "select id from avater.users where username = :username limit 1";
	$stmt = $dbh->prepare($sql);
	$stmt->bindValue(':username', $username, PDO::PARAM_STR);
	$stmt->execute();
	$data = $stmt->fetch();
	return $data['id'];
}