	var Server;
	var pid;
	var gid='0';
	var choosef=0;
	var votef=0;		
	function log( text ) {
		$log = $('#log');
		//Add text to log
		$log.append(($log.val()?"\n":'')+text);
		//Autoscroll
		$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
	}
	function send( text ) {
		Server.send( 'message', text );
	}
	$(document).ready(function() {
			if(navigator.userAgent.toLowerCase().indexOf('chrome') < 0){
				openDiv('chrome',"<div id='error'>游戏暂只支持Chrome浏览器！<br/>请切换后访问....</div>");
			/*alert("游戏暂只支持Chrome浏览器！请切换后访问....");
			window.opener='x';
			window.close();*/
			}
			$('h1 a').attr('title',"<span class='h2'>paroid's BLOG</span>").tipTip({id:'p',defaultPosition:'bottom',delay:100});
			$('#conn').attr('title',"<span class='h2'>连接状态</span>").tipTip({id:'p',defaultPosition:'top',delay:100});
			$('#iden').attr('title',"<span class='h2'>你的ID</span>").tipTip({id:'p',defaultPosition:'top',delay:100});
			$('#stage').attr('title',"<span class='h2'>游戏阶段</span>").tipTip({id:'p',defaultPosition:'top',delay:100});
			$('#word').attr('title',"<span class='h2'>词</span>").tipTip({id:'p',defaultPosition:'top',delay:100});
			$('#gid').attr('title',"<span class='h2'>另一个鬼ID</span>").tipTip({id:'p',defaultPosition:'top',delay:100});
			$('#message').attr('title',"<span class='h1'>Ctrl+Enter 发送</span>").tipTip({id:'p',activation:'focus',defaultPosition:'left',delay:100});
			$('#ghost').attr('title',"<span class='h1'>Ctrl+Enter 发送</span>").tipTip({id:'p',activation:'focus',defaultPosition:'right',delay:100});
			log('Connecting...');
			Server = new FancyWebSocket('ws://paroid.gicp.net:9300');

			$('#message').keydown(function(e){
				if(e.ctrlKey && e.keyCode == 13){
					//log( 'You: ' + this.value );
					send('m '+this.value );
					$(this).val('');
					$('#info').blur();
				}
			});

			$('#ghost').keydown(function(e){
				if(e.ctrlKey && e.keyCode == 13){
					log( '>>To另一个鬼: ' + this.value );
					send('g '+this.value );
					$(this).val('');
				}
			});
			function clearVote () {
				votef=0;
				$('div[class^=pl]').removeClass('vote');
			}
			$('div.pl1').click(function(){
				if(votef==1){
					send("m 1");
					$('#info').attr('title',"You Vote: #1").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					clearVote();
				}
				if(choosef==1 && pid!='1' && gid!='1'){
					send("m 1");
					$('#info').attr('title',"You Vote: #1").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
				}
			});
			$('div.pl2').click(function(){
				if(votef==1){
					send("m 2");
					$('#info').attr('title',"You Vote: #2").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					clearVote();
				}
				if(choosef==1 && pid!='2' && gid!='2'){
					send("m 2");
					$('#info').attr('title',"You Vote: #2").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
				}
			});
			$('div.pl3').click(function(){
				if(votef==1){
					send("m 3");
					$('#info').attr('title',"You Vote: #3").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					clearVote();
				}
				if(choosef==1 && pid!='3' && gid!='3'){
					send("m 3");
					$('#info').attr('title',"You Vote: #3").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
				}
			});
			$('div.pl4').click(function(){
				if(votef==1){
					send("m 4");
					$('#info').attr('title',"You Vote: #4").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					clearVote();
				}
				if(choosef==1 && pid!='4' && gid!='4'){
					send("m 4");
					$('#info').attr('title',"You Vote: #4").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
				}
			});
			$('div.pl5').click(function(){
				if(votef==1){
					send("m 5");
					$('#info').attr('title',"You Vote: #5").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					clearVote();
				}
				if(choosef==1 && pid!='5' && gid!='5'){
					send("m 5");
					$('#info').attr('title',"You Vote: #5").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
				}
			});
			$('div.pl6').click(function(){
				if(votef==1){
					send("m 6");
					$('#info').attr('title',"You Vote: #6").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					clearVote();
				}
				if(choosef==1 && pid!='6' && gid!='6'){
					send("m 6");
					$('#info').attr('title',"You Vote: #6").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
				}
			});

			//Let the user know we're connected
			Server.bind('open', function() {
				log( "Connected." );
				$('div#conn').text('Online').addClass('on');
			});

			//OH NOES! Disconnection occurred.
			Server.bind('close', function( data ) {
				log( "Disconnected." );
				$('div#conn').text('Offline').removeClass('on');
			});

			//Log any messages sent from server
			function process( payload ) {				
				var str=payload;
				var op=str.split(' ',2);
				if(op[0]=='1'){		//highlight & enable
					$('div[class^=pl]').removeClass('bluebox');
					$('div.pl'+op[1]).addClass('bluebox');
					if(op[1]==pid){
						$('#message').removeAttr('disabled').removeClass('disabled').focus();
						setTimeout(function(){
                            $('#info').attr('title',"Your Turn!").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
                        },2000);
					}
				}
				else if(op[0]=='2'){ //enable
					$('#message').removeAttr('disabled').removeClass('disabled').focus();
				}
				else if(op[0]=='3'){ //disable
					$('#message').attr('disabled','disabled').addClass('disabled');
				}
				else if(op[0]=='4'){
					pid=op[1];
					$('#info').attr('title',"You are Player <span class='h1'>#"+pid+"</span>").tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
					$('div#iden').text(pid).addClass('on');
				}
				else if(op[0]=='5'){
					$('div.pl'+op[1]).addClass('greenbox');
				}
				else if(op[0]=='6'){
					$('div.pl'+op[1][0]).addClass('redbox').blur();
					$('div.pl'+op[1][0]+' span').text(op[1].substr(1));
				}
				else if(op[0]=='7'){
					$('div[class^=pl]').blur();
				}
				else if(op[0]=='0'){ //normal message
					var tmp=str.substr(str.indexOf(' ')+1);
					var msg=tmp.substr(tmp.indexOf(' ')+1);
					if(op[1]=='0'){
						$('#info').attr('title',msg).tipTip({id:'0',activation:'focus',defaultPosition:'top',delay:100}).focus();
						log(msg);
					}
					else{
						$('div[class^=pl]').removeClass('bluebox');
						$('div.pl'+op[1]).addClass('bluebox').attr('title',msg).tipTip({id:op[1],activation:'focus',defaultPosition:'top',delay:100}).focus();
						log("> #"+op[1]+" :"+msg);
					}
				}
				else if(op[0]=='g'){
					var msg=str.substr(str.indexOf(' ')+1);
						$('#ghostinfo').attr('title',msg).tipTip({id:'7',activation:'focus',defaultPosition:'left',delay:100}).focus();
						log('> 另一个鬼: '+msg);
				}
				else if(op[0]=='dg'){
					$('#ghost').attr('disabled','disabled').addClass('disabled');
				}
				else if(op[0]=='st'){
					var msg=str.substr(str.indexOf(' ')+1);					
					$('div#stage').text(msg).addClass('on');
					if(msg=='Vote'){					
						votef=1;
						$('div[class^=pl]').addClass('vote');
						log(">>开始投票...");
					}
					else{
						switch(msg){
							case 'Choose':
								log(">>开始选择...");	
								break;
							case 'LastWords':
								log(">>等待遗言...");	
								break;
							case 'Describe':
								log(">>开始描述...");	
								break;
							case 'Jump?':
								log(">>跳不跳？...");	
								break;
							case 'Waiting':
								log(">>等待...");	
								break;
							case 'GameOver':
								log(">>游戏结束...");	
								break;
							case 'Discuss':
								log(">>开始讨论...");	
								break;
							case 'Guess':
								log(">>开始猜词...");	
								break;
						}
					}
				}
				else if(op[0]=='gi'){
					gid=op[1];
					$('div#gid').text(op[1]).addClass('on');
					log("另一个鬼是: #"+gid);
				}
				else if(op[0]=='w'){
					$('div#word').text(op[1]).addClass('on');
				}
				else if(op[0]=='cs'){
					if(gid!='0'){
						choosef=1;
						$('div[class^=pl]').addClass('choose');
						$('div.pl'+pid+',div.pl'+gid).removeClass('choose');

					}
				}
				else if(op[0]=='ce'){
					if(gid!='0'){
						choosef=0;
						$('div.pl'+pid+',div.pl'+gid).removeClass('choose');
						log(">>选择结束...");
					}				
				}
                else if(op[0]=='nb'){
                    $('div[class^=pl]').removeClass('bluebox');
                }
			}
			Server.bind('message',function(payload){
				arr=payload.split('=__=');
				for(var ss in arr){
					process(arr[ss]);
				}
			});

			Server.connect();
		});


var FancyWebSocket = function(url)
{
	var callbacks = {};
	var ws_url = url;
	var conn;

	this.bind = function(event_name, callback){
		callbacks[event_name] = callbacks[event_name] || [];
		callbacks[event_name].push(callback);
		return this;// chainable
	};

	this.send = function(event_name, event_data){
		this.conn.send( event_data );
		return this;
	};

	this.connect = function() {
		if ( typeof(MozWebSocket) == 'function' )
			this.conn = new MozWebSocket(url);
		else
			this.conn = new WebSocket(url);

		// dispatch to the right handlers
		this.conn.onmessage = function(evt){
			dispatch('message', evt.data);
		};

		this.conn.onclose = function(){dispatch('close',null)}
		this.conn.onopen = function(){dispatch('open',null)}
	};

	this.disconnect = function() {
		this.conn.close();
	};

	var dispatch = function(event_name, message){
		var chain = callbacks[event_name];
		if(typeof chain == 'undefined') return; // no callbacks for this event
		for(var i = 0; i < chain.length; i++){
			chain[i]( message )
		}
	}
};


function openDiv(newDivID,content)  
   {  
    var newMaskID = "mask";  
    var newMaskWidth =document.body.scrollWidth;
    var newMaskHeight =document.body.scrollHeight;
    var newMask = document.createElement("div");
    newMask.id = newMaskID;
    newMask.style.position = "absolute";
    newMask.style.zIndex = "10000";
    newMask.style.width = newMaskWidth + "px";
    newMask.style.height = newMaskHeight + "px";
    newMask.style.top = "0px";
    newMask.style.left = "0px";
    newMask.style.background = "#000";
    newMask.style.filter = "alpha(opacity=60)";
    newMask.style.opacity = "0.6";
    document.body.appendChild(newMask);     
    var newDivWidth = 400;
    var newDivHeight = 200;
    var newDiv = document.createElement("div");
    newDiv.id = newDivID;
    newDiv.style.position = "absolute";
    newDiv.style.zIndex = "19999";
   
    newDiv.style.width = newDivWidth + "px";
    newDiv.style.height = newDivHeight + "px";
    var newDivtop=(document.body.scrollTop + document.body.clientHeight/2 - newDivHeight/2) ;
    var newDivleft=(document.body.scrollLeft + document.body.clientWidth/2  - newDivWidth/2);
    newDiv.style.top = newDivtop+ "px";
    newDiv.style.left = newDivleft + "px";
    newDiv.style.background = "#efefef";
    newDiv.style.border = "10px solid #333";
    newDiv.style.padding = "5px";
    newDiv.innerHTML = content;
    document.body.appendChild(newDiv);    
    var newA = document.createElement("span");  
    newA.href = "#";  
    newA.style.position = "absolute";
    newA.style.left="160px";  
	newA.style.bottom="12px";
	newA.style.fontSize="24px";
	newA.style.background="#2089cc";
	newA.style.padding="2px 12px";
    newA.innerHTML = "确定";  
    newA.onclick = function(){          
		window.close();
		/*document.body.removeChild(newMask);
        document.body.removeChild(newDiv);*/
		return false;  
    }  
    newDiv.appendChild(newA);
} 

//tipTip
(function($){
	$.fn.tipTip = function(options) {
		var defaults = { 
			id:'0',
			activation: "hover",
			keepAlive: false,
			maxWidth: "200px",
			edgeOffset: 3,
			defaultPosition: "bottom",
			delay: 400,
			fadeIn: 200,
			fadeOut: 200,
			attribute: "title",
			content: false, 
		  	enter: function(){},
		  	exit: function(){}
	  	};
	 	var opts = $.extend(defaults, options);
	 	
	 	// Setup tip tip elements and render them to the DOM
	 	if($("#tiptip_holder"+opts.id).length <= 0){
	 		var tiptip_holder = $('<div id="tiptip_holder'+opts.id+'" style="max-width:'+ opts.maxWidth +';"></div>');
			var tiptip_content = $('<div id="tiptip_content'+opts.id+'"></div>');
			var tiptip_arrow = $('<div id="tiptip_arrow'+opts.id+'"></div>');
			$("body").append(tiptip_holder.html(tiptip_content).prepend(tiptip_arrow.html('<div id="tiptip_arrow_inner'+opts.id+'"></div>')));
		} else {
			var tiptip_holder = $("#tiptip_holder"+opts.id);
			var tiptip_content = $("#tiptip_content"+opts.id);
			var tiptip_arrow = $("#tiptip_arrow"+opts.id);
		}
		
		return this.each(function(){
			var org_elem = $(this);
			if(opts.content){
				var org_title = opts.content;
			} else {
				var org_title = org_elem.attr(opts.attribute);
			}
			if(org_title != ""){
				if(!opts.content){
					org_elem.removeAttr(opts.attribute); //remove original Attribute
				}
				var timeout = false;
				
				if(opts.activation == "hover"){
					org_elem.hover(function(){
						active_tiptip();
					}, function(){
						if(!opts.keepAlive){
							deactive_tiptip();
						}
					});
					if(opts.keepAlive){
						tiptip_holder.hover(function(){}, function(){
							deactive_tiptip();
						});
					}
				} else if(opts.activation == "show"){
						active_tiptip();
				} else if(opts.activation == "hide"){
						deactive_tiptip();
				} else if(opts.activation == "focus"){
					org_elem.focus(function(){
						active_tiptip();
					}).blur(function(){
						deactive_tiptip();
					});
				} else if(opts.activation == "click"){
					org_elem.click(function(){
						active_tiptip();
						return false;
					}).hover(function(){},function(){
						if(!opts.keepAlive){
							deactive_tiptip();
						}
					});
					if(opts.keepAlive){
						tiptip_holder.hover(function(){}, function(){
							deactive_tiptip();
						});
					}
				}
			
				function active_tiptip(){
					opts.enter.call(this);
					tiptip_content.html(org_title);
					tiptip_holder.hide().removeAttr("class").css("margin","0");
					tiptip_arrow.removeAttr("style");
					
					var top = parseInt(org_elem.offset()['top']);
					var left = parseInt(org_elem.offset()['left']);
					var org_width = parseInt(org_elem.outerWidth());
					var org_height = parseInt(org_elem.outerHeight());
					var tip_w = tiptip_holder.outerWidth();
					var tip_h = tiptip_holder.outerHeight();
					var w_compare = Math.round((org_width - tip_w) / 2);
					var h_compare = Math.round((org_height - tip_h) / 2);
					var marg_left = Math.round(left + w_compare);
					var marg_top = Math.round(top + org_height + opts.edgeOffset);
					var t_class = "";
					var arrow_top = "";
					var arrow_left = Math.round(tip_w - 12) / 2;

                    if(opts.defaultPosition == "bottom"){
                    	t_class = "_bottom";
                   	} else if(opts.defaultPosition == "top"){ 
                   		t_class = "_top";
                   	} else if(opts.defaultPosition == "left"){
                   		t_class = "_left";
                   	} else if(opts.defaultPosition == "right"){
                   		t_class = "_right";
                   	}
					
					var right_compare = (w_compare + left) < parseInt($(window).scrollLeft());
					var left_compare = (tip_w + left) > parseInt($(window).width());
					
					if((right_compare && w_compare < 0) || (t_class == "_right" && !left_compare) || (t_class == "_left" && left < (tip_w + opts.edgeOffset + 5))){
						t_class = "_right";
						arrow_top = Math.round(tip_h - 13) / 2;
						arrow_left = -12;
						marg_left = Math.round(left + org_width + opts.edgeOffset);
						marg_top = Math.round(top + h_compare);
					} else if((left_compare && w_compare < 0) || (t_class == "_left" && !right_compare)){
						t_class = "_left";
						arrow_top = Math.round(tip_h - 13) / 2;
						arrow_left =  Math.round(tip_w);
						marg_left = Math.round(left - (tip_w + opts.edgeOffset + 5));
						marg_top = Math.round(top + h_compare);
					}
	
					var patch=80;//by paroid
					var top_compare = (top + org_height + opts.edgeOffset + tip_h + 8) > parseInt($(window).height() + $(window).scrollTop());
					var bottom_compare = ((top + org_height) - patch - (opts.edgeOffset + tip_h + 8)) < 0;
					
					if(top_compare || (t_class == "_bottom" && top_compare) || (t_class == "_top" && !bottom_compare)){
						if(t_class == "_top" || t_class == "_bottom"){
							t_class = "_top";
						} else {
							t_class = t_class+"_top";
						}
						arrow_top = tip_h;
						marg_top = Math.round(top - (tip_h + 5 + opts.edgeOffset));
					} else if(bottom_compare | (t_class == "_top" && bottom_compare) || (t_class == "_bottom" && !top_compare)){
						if(t_class == "_top" || t_class == "_bottom"){
							t_class = "_bottom";
						} else {
							t_class = t_class+"_bottom";
						}
						arrow_top = -12;						
						marg_top = Math.round(top + org_height + opts.edgeOffset);
					}
				
					if(t_class == "_right_top" || t_class == "_left_top"){
						marg_top = marg_top + 5;
					} else if(t_class == "_right_bottom" || t_class == "_left_bottom"){		
						marg_top = marg_top - 5;
					}
					if(t_class == "_left_top" || t_class == "_left_bottom"){	
						marg_left = marg_left + 5;
					}
					tiptip_arrow.css({"margin-left": arrow_left+"px", "margin-top": arrow_top+"px"});
					tiptip_holder.css({"margin-left": marg_left+"px", "margin-top": marg_top+"px"}).attr("class","tip"+t_class);
					
					if (timeout){ clearTimeout(timeout); }
					timeout = setTimeout(function(){ tiptip_holder.stop(true,true).fadeIn(opts.fadeIn); }, opts.delay);	
				}
				
				function deactive_tiptip(){
					opts.exit.call(this);
					if (timeout){ clearTimeout(timeout); }
					tiptip_holder.fadeOut(opts.fadeOut);
				}
			}				
		});
	}
})(jQuery);  	
