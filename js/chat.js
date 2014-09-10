
const SITE_URL = "http://localhost/cyberagent_last/settings/log.php";

$(document).ready(function(){

    start();

    function start() {
		loadLog();
		setTimeout( function() {
		    start();
		}, 1000);
    }

    function loadLog() {
	var roomid =  $("#roomid").val();
	//alert(roomid);
	$.getJSON(
	    SITE_URL,
	    {
		"mode" : "check",
		"roomid": roomid
	    },
	    //受信した際に呼ばれる関数
	    function(json_data, status) {
	    $("#chat_view").html('');
		//alert(status);
		//alert(json_data[0].chatname);
		//$("#chat_view_ul").remove();
		$.each(json_data, function(i, items) {
		    //$("#chat_view_ul").remove();
		    showlog(items);
		});
		//loadLog();
	    }
	).success(function(json) {
	    //alert("success");
	}).error(function(jqXHR, textStatus, errorThrown) {
	    //alert("fail" + jqXHR.responseText);
	});
    }

    function showlog(json_data){
/*         var ul = document.getElementById("chat_view_ul"); */
		var div = document.getElementById("chat_view");
        var ul = document.createElement("ul");
        ul.setAttribute("id","chat_view_ul");

        var li = document.createElement("li");
		if(myname==json_data.chatname){
			li.setAttribute("class", "mycomment");
		}else{
				
		}
        var span = document.createElement("span");

        var label1 = document.createElement("label");
        label1.setAttribute("class", "name");
		label1.appendChild(document.createTextNode(json_data.chatname));
		
		var label2 = document.createElement("label");
		label2.setAttribute("class", "comment");			
		
		label2.appendChild(document.createTextNode(json_data.chatdata));
		
		var label3 = document.createElement("label");
		label3.setAttribute("class", "time");
		label3.appendChild(document.createTextNode(json_data.date));
		
		if(myname==json_data.chatname){
			span.appendChild(label3);				
			span.appendChild(label2);
		}else{
			span.appendChild(label1);
			span.appendChild(label2);
			span.appendChild(label3);				
		}

		
		li.appendChild(span);
		ul.appendChild(li);
		div.appendChild(ul);
		//d = document.getElementById("Hello");
		//dump(d.innerHTML);
    }


    $("#button1").click(function() {
	var roomid =  $("#roomid").val();
	var chatname = $("#chatname").val();
	var chatdata = $("#str").val();
	$('#str').val('');
	$.getJSON(
	    //送信先
	    SITE_URL,
	    
	    //GETにて送信する値
	    {
		"mode" : "add",
		"roomid" : roomid,
		"chatname" : chatname,
		"chatdata" : chatdata
	    },

	    //受信した際に呼ばれる関数
	    function(json_data){
                var label1 = document.createElement("label");
                label1.setAttribute("class", "name");
				label1.appendChild(document.createTextNode(json_data.charid));
				
				var label2 = document.createElement("label");
				label2.setAttribute("class", "comment");
				label2.appendChild(document.createTextNode(json_data.chatdata));
				
				var label3 = document.createElement("label");
				label3.setAttribute("class", "time");
				label3.appendChild(document.createTextNode(json_data.charid));
				
				span.appendChild(label1);
				span.appendChild(label2);
				span.appendChild(label3);
				
				li.appendChild(span);
				ul.appendChild(li);
            }
        );
    });

});

