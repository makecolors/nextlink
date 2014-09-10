<?php
session_start();
require_once('config.php');
require_once('function.php');
//var_dump($_SESSION['me']);
if(empty($_SESSION['me'])){
  header('Location: '.SITE_URL.'login.php');
  exit();
}

$dbh = connectDB();

// d3.jsで描画するユーザ名をjson形式でエンコードしてjavascriptの変数へ送る
$friendlist = getUserFriendlist($dbh, $_SESSION['me']['username']);
$graphlist_keys = array();
//var_dump($poslist);
foreach ($friendlist as $key => $value) {
  if($value == FRIEND_FOLLOW){
    array_push($graphlist_keys, $key);
  }
}
$graphlist = array();
array_unshift($graphlist_keys, $_SESSION['me']['username']);

$enc_graphlist_keys = json_encode($graphlist_keys);
//var_dump($graphlist_keys);
//exit();

// 部屋の人数を取り出す
$rooms_count = array();
foreach ($graphlist_keys as $key) {
  //var_dump($key);
  $sql = "select id from avater.users where username = :username limit 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':username', $key, PDO::PARAM_STR);
  $stmt->execute();
  $search_id = $stmt->fetch();
  //var_dump($search_id['id']);
  $sql = "select count(*) from avater.users where position = :search_id";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':search_id', $search_id['id'], PDO::PARAM_INT);
  $stmt->execute();
  array_push($rooms_count, $stmt->fetch());
}
$enc_rooms_count = json_encode($rooms_count);
//var_dump($rooms_count);

// フレンドの人数を取り出す
$rooms_friends_count = array();
foreach ($graphlist_keys as $key) {
  /*
  print_r("step1 :今注目する部屋");
  var_dump($key);
  print_r("<br>");
  */

  // step1: フレンドのidを取り出し、焦点を当てたroomに誰がいるのかを全員取り出す($roomusersはusernameの連想配列)
  $sql = "select id from avater.users where username = :username limit 1";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':username', $key, PDO::PARAM_STR);
  $stmt->execute();
  $user = $stmt->fetch();
  
  $sql = "select username from avater.users where position = :position";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':position', $user['id'], PDO::PARAM_STR);
  $stmt->execute();
  $user2 = $stmt->fetchAll();

  $roomusers = array();
  foreach($user2 as $key){
    array_push($roomusers, $key['username']);
  }
  /*
  print_r("step1 :roomに誰がいるのかを取り出す");
  var_dump($roomusers);
  print_r("<br>");
  */
  
  // step2.1: セッションしている人のフレンドリスト(statusも含めた連想配列)を取得する
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
  /*
  print_r("step2.1 :セッションしている人");
  var_dump($_SESSION['me']['username']);
  print_r("<br>");
  print_r("step2.2 :セッションしている人の友達リスト(認証中も含む)");
  var_dump($temp);
  print_r("<br>");
  */
  // step2.2: セッションしている人のフレンドリストの中でフォローしている人のみを取得する($friendfollowはusernameの純粋な配列)
  $friendfollow = array();
  foreach ($temp as $key => $value) {
    if($value == FRIEND_FOLLOW){
      array_push($friendfollow, $key);
    }
  }
  /*
  print_r("step2.3 :セッションしている人のfollowしている友達のみ取り出す");
  var_dump($friendfollow);
  print_r("<br>");
  */
  // step2.3: セッションしている人のフレンドリストの中でフォローしている人 + "自分"のみを取得する($friendfollowはusernameの純粋な配列)
  array_push($friendfollow, $_SESSION['me']['username']);
  /*
  print_r("step2.4 :セッションしている人のfollowしている友達+自分");
  var_dump($friendfollow);
  print_r("<br>");
  */

  // step3: "roomにいる人と"セッションしている人のfollowしている友達+自分"の共通項を取り出す
  $intersect_friends = array_intersect($roomusers, $friendfollow);
  array_push($rooms_friends_count, count($intersect_friends));

}
// json形式にエンコードしてjavascriptに渡す
$enc_rooms_friends_count = json_encode($rooms_friends_count);


?>
<html>
<head>
<meta charset="UTF-8">
<title>Position</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

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
<style>
  .map_button{
    background-color: #DDDDDD !important;
  }
  body{
    background-image: url("image/sativa.png");
  }
</style>
<style>
.link {
  stroke: #000;
  stroke-width: 5px;
  stroke-opacity: .3;
}
.node {
  cursor: move;
  fill: #ccc;
  stroke: #000;
  stroke-width: 1.5px;
}
.text_svg_css {
  font-size: 20px;
  font-weight: bold;
}
.node-active{
  stroke: #555;
  stroke-width: 1.5px;
}
.link-active {
  stroke-opacity: 1;
}
</style>
<script src="position.js"></script>
</head>
<body>
<div id="header">
  <h1>Map<a href="logout.php"><i class="fa fa-sign-out"></i></a></h1>
</div>

<div id="wrap">
<script src="http://d3js.org/d3.v3.min.js"></script>
<script>
  // forceレイアウトに表示するテキスト
  var namelist = <?php echo($enc_graphlist_keys);?>;
  var rooms_count = <?php echo($enc_rooms_count); ?>;
  var rooms_friends_count = <?php echo($enc_rooms_friends_count);?>;

  // graph(nodes,links)で構成される疑似クラス
  var nodes = new Array(namelist.length);
  var links = new Array(namelist.length);
  var graph = new Graph(nodes, links);

  // 初期化
  var pos = concentricCircle(namelist.length, 10);
  var margin = {"x":"160", "y":"200"};
  for(var i = 0; i < namelist.length; i++){
    nodes[i] = new Node();
    nodes[i].x = Number(pos[i][0] + margin.x);
    nodes[i].y = Number(pos[i][1] + margin.y);
    nodes[i].username = namelist[i];
    nodes[i].avater = "image/chrome.png";
    nodes[i].countPerson = Number(rooms_count[i][0]);
    nodes[i].countFriend = Number(rooms_friends_count[i]);
  }
  for(var i = 0; i < namelist.length; i++){
    links[i] = new Link();
    links[i].source = nodes[0];
    links[i].target = nodes[i];
    links[i].distance = ((Math.random()*4)+1)*40;
  }

var width = 320,
    height = 448;

var force = d3.layout.force()
    .size([width, height])
    .charge(-600)
    .linkDistance(function(d) { return d.distance; })
    .on("tick", tick);

var zoom = d3.behavior.zoom()
    .scaleExtent([1, 10])
    .on("zoom", zoomed);

var drag = d3.behavior.drag()
            .origin(function(d) { return d; })
            .on("dragstart", dragstarted)
            .on("drag", dragged)
            .on("dragend", dragended);

// 描画の根幹であるsvgタグ(attr:width,height)
var svg = d3.select("div#wrap").append("svg")
    .attr("width", width)
    .attr("height", height)
    .append("g")
    .attr("transform", "translate(0,0)")
    .call(zoom);
// 透明なrectをsvg全体に描き、その範囲にあるすべてのオブジェクトに対しdragとzoomを適用させる
var rect = svg.append("rect")
    .attr("width", width)
    .attr("height", height)
    .style("fill", "none")
    .style("pointer-events", "all");
// linkとnodeをまとめるコンテナ
var container = svg.append("g"),
    link = container.selectAll(".link"),
    node = container.selectAll(".node");
    
// forceレイアウトを起動する呪文
force
  .nodes(graph.nodes)
  .links(graph.links)
  .start();

link = link.data(graph.links)
    .enter().append("line")
    .attr("class", "link");

node = node.data(graph.nodes)
      .enter().append("g").call(drag);

anchor = node.append("a")
        .attr("xlink:href", function(d){return "index.php?username="+d.username;})
image = anchor.append("image")
      .attr("xlink:href", function(d){return d.avater})
      .attr("height","60")
      .attr("width","60");
text = node.append("text")
      .text(function(d, i){return d.username})
      .attr({
        'text-anchor': "middle",
        'dy': ".65em",
        'fill': "black",
        'stroke-width': "0.5px",
        'stroke': "white",
        'x': "30",
        'y': "-20",
        'class': "text_svg_css"
      });
text = node.append("text")
      .text(function(d, i){return d.countFriend.toString() + "/" + d.countPerson.toString();})
      .attr({
        'text-anchor': "middle",
        'dy': ".65em",
        'fill': "black",
        'stroke-width': "0.5px",
        'stroke': "white",
        'x': "30",
        'y': "70",
        'class': "text_svg_css"
      });
node.on("mouseover", function(d){
                        
        node.classed("node-active", function(o) {
            thisOpacity = isConnected(d, o) ? true : false;
            this.setAttribute('fill-opacity', thisOpacity);
            return thisOpacity;
        });

        link.classed("link-active", function(o) {
            return o.source === d || o.target === d ? true : false;
        });
        
        d3.select(this).classed("node-active", true);
        d3.select(this).select("image").transition()
                .duration(750)
                .attr("width", (d.weight * 2+ 12)*1.5)
                .attr("height", (d.weight * 2+ 12)*1.5);
})
.on("mouseout", function(d){
                    
        node.classed("node-active", false);
        link.classed("link-active", false);
    
        d3.select(this).select("image").transition()
                .duration(750)
                .attr("width", (d.weight * 2+ 12)*1.5)
                .attr("height", (d.weight * 2+ 12)*1.5);
        //console.log(d3.select(this).select("image")[0][0]);
});
function tick() {
  link.attr("x1", function(d) { return d.source.x+30; })
      .attr("y1", function(d) { return d.source.y+30; })
      .attr("x2", function(d) { return d.target.x+30; })
      .attr("y2", function(d) { return d.target.y+30; });

  node.attr("transform", function(d) { return "translate("+d.x+","+d.y+")"; });
}
var linkedByIndex = {};
graph.links.forEach(function(d) {
    linkedByIndex[d.source.index + "," + d.target.index] = 1;
});
function isConnected(a, b) {
    return linkedByIndex[a.index + "," + b.index] || linkedByIndex[b.index + "," + a.index];
}
function zoomed() {
  container.attr("transform", "translate(" + d3.event.translate + ")scale(" + d3.event.scale + ")");
}

function dragstarted(d) {
  d3.event.sourceEvent.stopPropagation();
  d3.select(this).classed("dragging", true);
  force.start();
}
function dragged(d) {
  d3.select(this).attr("cx", d.x = d3.event.x).attr("cy", d.y = d3.event.y);
}
function dragended(d) {
  d3.select(this).classed("dragging", false);
}

</script>
</div>
<div id="footer">
  <div class="container">
    <div class="row">
      <a href="index.php"><div class="col-xs-3 room_button"><i class="fa fa-home fa-2x"></i>room</div></a>
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