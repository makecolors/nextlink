function concentricCircle(num, r){
  var pos = new Array();
  var MAXTHETA = 360;
  for(var i = 0; i <= num; i++){
    var x, y;
    x = r * Math.sin(MAXTHETA/i);
    y = r * Math.cos(MAXTHETA/i);
    pos.push([x,y]);
  }
  return pos;
}
function Graph(nodes, links){
  this.nodes = nodes;
  this.links = links;
}
function Node(){
  this.x = 0;
  this.y = 0;
  this.username = "";
  this.countPerson = 0;
  this.countFriend = 0;
  this.avater = "../image/noimage.png";
  return this;
}
function Link(){
  this.source = 0;
  this.target = 0;
  this.distance = 0;
  return this;
}