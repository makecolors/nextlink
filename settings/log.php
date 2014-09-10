<?php
require_once('config.php');
require_once('function.php');
$UnchangedDataFlag;
$dbh = connectDB();

    
$sql = 'SELECT id FROM users WHERE username = ?';
$stmt = $dbh->prepare($sql);
$stmt->execute(array($_GET['chatname']));
$result1 = $stmt->fetch();
$chatid = $result1['id'];
    
// モードの振り分け
switch ($_GET['mode']) {
            
    // 更新チェック
    case 'check':
        getData($_GET['roomid']);
        break;
            
    // データを保存
    case 'add':
        saveData($_GET['roomid'], $chatid, $_GET['chatdata']);
        break;
}

function getData($a) {
    /*
    global $UnchangedDataFlag;
    $UnchangedDataFlag = true;
    //保存イベントが発生するまで、ループで待機
    while ($UnchangedDataFlag) {
        sleep(1);
    }
    */
    
    global $dbh;
    $sql = 'SELECT U.username, C.chatdata, C.created FROM chatlog AS C LEFT OUTER JOIN users AS U ON C.charid = U.id WHERE C.roomid = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array($a));
    
    while ($result = $stmt->fetch(PDO::FETCH_BOTH)) {
        $tmp[] = array(
                        "chatname"=>$result[0],
                        "chatdata"=>$result[1],
                        "date"=>$result[2]
                        );
    }
    header('Content-type: application/json; charset=utf-8');
    $json_data = json_encode($tmp);
    echo $json_data;
}

function saveData($a, $b, $c) {
    /*
    $param = array( 'roomid' => $a, 'chatid' => $b, 'chatdata' => $c);
    header('Content-type: application/json; charset=utf-8');
    $data = json_encode($param);
    echo $data;
    */
    global $dbh;
    $sql = 'INSERT INTO chatlog (roomid,  charid, chatdata, created) VALUES (:roomid, :charid, :chatdata, now())';
    $stmt = $dbh->prepare($sql);
    $flag = $stmt->execute(array(':roomid'=>$a, ':charid'=>$b, ':chatdata'=>$c));
    
    /*
    保存イベントが発生したので、getData()でデータ取得を開始
    global $UnchangedDataFlag;
    $UnchangedDataFlag = false;
    */
    //var json = '{"roomid":"'+roomid+'","chatname":"'+chatname+'","chatdata":"'+chatdata+'"}';
    echo('{"roomid":"'.$a.'","charid":"'.$b.'","chatdata":"'.$c.'"}');
}


// idをusernameに変換する (index.php用)
function id2username($id){
    $sql = "select username from avater.users where id = :id limit 1";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $data = $stmt->fetch();
    return $data['username'];
}


// 全てのログをjson形式でレスポンスを返す　(index.php用)
function returnAllLog($roomid){
    global $dbh;
    $sql = "select * from avater.chatlog where roomid = :roomid";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':roomid', $roomid, PDO::PARAM_STR);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    $room_logs = array();
    foreach ($logs as $log) {
        $manlogdata = array();
        array_push($manlogdata, $log['charid']);
        array_push($manlogdata, $log['chatdata']);
        array_push($manlogdata, $log['created']);
        array_push($room_logs, $manlogdata);
    }
    // json形式でリスポンスを返す
    return json_encode($room_logs);
}
?>