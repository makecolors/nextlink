<?php
session_start();
require_once('settings/config.php');
require_once('settings/function.php');
//var_dump($_SESSION['me']);

if(empty($_SESSION['me'])){
	header('Location: '.SITE_URL.'login.php');
	exit();
}
$dbh = connectDB();
?>
<?php

// XHTMLとしてブラウザに認識させる
// (IE8以下はサポート対象外w)
//$header = 'Content-Type: application/xhtml+xml; charset=utf-8';
//header($header);

try {

    // データベースに接続
    $pdo = $dbh;

    /* アップロードがあったとき */
    if (isset($_FILES['upfile']['error']) && is_int($_FILES['upfile']['error'])) {

        // バッファリングを開始
        ob_start();

        try {

            // $_FILES['upfile']['error'] の値を確認
            switch ($_FILES['upfile']['error']) {
                case UPLOAD_ERR_OK: // OK
                    break;
                case UPLOAD_ERR_NO_FILE:   // ファイル未選択
                    throw new RuntimeException('ファイルが選択されていません', 400);
                case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
                case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
                    throw new RuntimeException('ファイルサイズが大きすぎます', 400);
                default:
                    throw new RuntimeException('その他のエラーが発生しました', 500);
            }

            // $_FILES['upfile']['mime']の値はブラウザ側で偽装可能なので
            // MIMEタイプを自前でチェックする
            if (!$info = @getimagesize($_FILES['upfile']['tmp_name'])) {
                throw new RuntimeException('有効な画像ファイルを指定してください', 400);
            }
            if (!in_array(
                $info[2],
                array(
                    IMAGETYPE_GIF,
                    IMAGETYPE_JPEG,
                    IMAGETYPE_PNG,
                ),
                true
            )) {
                throw new RuntimeException('未対応の画像形式です', 400);
            }

            // サムネイルをバッファに出力
            $tmp = explode('/', $info['mime']);
            $create = "imagecreatefrom{$tmp[1]}";
            $output = "image{$tmp[1]}";
            if ($info[0]>= $info[1]) {
                $dst_w = 300;
                $dst_h = ceil(300 * $info[1] / max($info[0], 1));
            } else {
                $dst_w = ceil(300 * $info[0] / max($info[1], 1));
                $dst_h = 300;
            }
            if (!$src = @$create($_FILES['upfile']['tmp_name'])) {
                throw new RuntimeException('画像リソースの生成に失敗しました', 500);
            }
            $dst = imagecreatetruecolor($dst_w, $dst_h);
            imagecopyresampled(
                $dst, $src,
                0, 0, 0, 0,
                $dst_w, $dst_h, $info[0], $info[1]
            );
            
/*            if ($dst_w > $dst_h) {
            	$dst = imagerotate($dst, -90, 0);
            } else {
				$dst = imagerotate($dst, 130, 0);	            
            }
*/
            
/*
            if ($info[1] < info[0]) {
	            $dst = imagerotate($dst, 130, 0);
            }
*/
            
            /*
else{
            	$dst = imagerotate($dst, 130, 0);
            }
*/
            

            
            $output($dst);
            imagedestroy($src);
            imagedestroy($dst);

            // INSERT処理
            $stmt = $pdo->prepare(implode(' ', array(
                'INSERT',
                'INTO image(id, name, type, raw_data, thumb_data, date)',
                'VALUES (?, ?, ?, ?, ?, ?)',
            )));
            $stmt->execute(array(
            	$_SESSION['me']['id'],
                $_FILES['upfile']['name'],
                $info[2],
                file_get_contents($_FILES['upfile']['tmp_name']),
                ob_get_clean(), // バッファからデータを取得してクリア
                date_format(
                    new DateTime('now', new DateTimeZone('Asia/Tokyo')),
                    'Y-m-d H:i:s'
                ),
            ));

            $msg = array('green', 'ファイルは正常にアップロードされました');

        } catch (PDOException $e) {

            ob_end_clean(); // バッファをクリア
            header($header, true, 500);
            $msg = array('red', 'INSERT処理中にエラーが発生しました');

        } catch (RuntimeException $e) {

            ob_end_clean(); // バッファをクリア
            header($header, true, $e->getCode()); 
            $msg = array('red', $e->getMessage());

        }

    /* ID指定があったとき */
    } elseif (isset($_GET['id'])) {

        try {

            $stmt = $pdo->prepare(implode(' ', array(
                'SELECT type, raw_data',
                'FROM image',
                'WHERE id = ?',
                'LIMIT 1',
            )));
            $stmt->bindValue(1, $_GET['id'], PDO::PARAM_INT);
            $stmt->execute();
            if (!$row = $stmt->fetch()) {
                throw new RuntimeException('該当する画像は存在しません', 404);
            }
            header('X-Content-Type-Options: nosniff');
            header('Content-Type: ' . image_type_to_mime_type($row['type']));
            echo $row['raw_data'];
            exit;

        } catch (PDOException $e) {

            header($header, true, 500); 
            $msg = array('red', 'SELECT処理中にエラーが発生しました');

        } catch (RuntimeException $e) {

            header($header, true, $e->getCode()); 
            $msg = array('red', $e->getMessage());

        }

    }

    // サムネイル一覧取得
    $rows = $pdo->query(implode(' ', array(
                'SELECT id, name, type, thumb_data, date',
                'FROM image',
                'ORDER BY date DESC',
            )))->fetchAll();

} catch (PDOException $e) { }

?>
<html>
<head>
	<meta charset="UTF-8">
	<title>Setting</title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
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
	<script src="http://code.jquery.com/jquery-latest.js" type="text/javascript"></script>
	<style>
		.setting_button{
			background-color: #DDDDDD !important;
		}
	</style>
</head>
<body>
<div id="header">
	<h1>Setting<a href="login/logout.php"><i class="fa fa-sign-out"></i></a></h1>
</div>

<div id="wrap">
<?php echo("<p id='name'>" . $_SESSION['me']['username'] . "</p>"); ?>
<?php if (!empty($rows)): ?>
<?php $imageCount=0; ?>
<?php foreach ($rows as $i => $row): ?>
<?php if ($_SESSION['me']['id']==$row['id']): $imageCount++;?>
       <?=sprintf(
           '<img class = "accountImage" src="data:%s;base64,%s" alt="%s" />',
           image_type_to_mime_type($row['type']),
           base64_encode($row['thumb_data']),
           h($row['name'])
       )?>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
<?php if ($imageCount==0):
	echo('<img onclick="$(');
	echo("'#file_input').click();");
	echo('" class = "accountImage" src="image/noimage.png">');
?>
<?php endif; ?>
	<form enctype="multipart/form-data" method="post" action="">
		<input id="file_input" onchange="$('#fake_input_file').val($(this).val())" style="display: none;" type="file" name="upfile">
		<img class="accountImage" src="" alt="" style="display: none;" />
		<input id="submitButton" class="btn btn-info" type="submit" value="保存" />
	</form>
	<script>
            $('#file_input').change(function(){
                if (!this.files.length) {
                    return;
                }
                var file = this.files[0],
                    $_img = $(this).siblings('img'),
                    fileReader = new FileReader();
                fileReader.onload = function(event) {
                    $_img.attr('src', event.target.result);
                    $_img.attr('style', "display: inline;");
                };
                fileReader.readAsDataURL(file);
            });
	</script>
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