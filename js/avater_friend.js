function initialize(global){
	global.csvfile = "frienddummy.csv";
	return global;
}
function num2friendstatus(num){
	switch(num){
		case 0: return "not friend";
		case 1: return "request";
		case 2: return "friend";
	}
}