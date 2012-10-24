<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';

/*
 *  protocol specifiction:
 *  mutiple message  delimiter '-//-'
 *  [op] [player] [msg]
 *  [op]: 0  [player/info] normal message
 *  [op]: 1	 [player] highlight & enable player 
 *  [op]: 2  [] enable player
 *  [op]: 3  [] disable player
 *  [op]: 4  [pid] Pid notice
 *  [op]: 5  [player] green player
 *  [op]: 6  [player] red player dead
 *  [op]: 7  [player] blur all but info
 *  [op]: g  []  ghost message
 *  [op]; gi [gid] tell ghost friend
 *  [op]: dg []  disable ghost info
 *  [op]: st [info]  stage info
 *  [op]: cs []  choose on
 *  [op]: ce []  choose off
 *  [op]: nb []  clear blue
 *  [op]: w  [word]
 * */


class Game{
	public static $round=0;
	public static $player=0;
	public static $mode='choose';
	public static $clients_role=array();
	public static $assert_op=array();
	public static $assert_op_count;
	public static $normals=array();
	public static $idiot=0;
	public static $ghosts=array();
	public static $N=6;
	public static $step=0;
	public static $ghostNum=2;
	public static $deadMan=0;
	public static $ghostChoose=0;
	public static $ghostChooseCount=0;
	public static $afterVote=0;
	public static $guessCount=array();
	public static $guessPeople;
	public static $votes=array();
	public static $voteCount;
	public static $kill=array();
	public static $alive=array();
	public static $aliveCount;
	public static $word;
	public static $trap;
	public static $sep="=__=";
}

function Initialize(){
	global $Server;
	foreach($Server->wsClients as $id=>$val){
		$Server->wsSend($id,"0 0 Let's Go!");
	}        
	//dispatch the words
	$seq=array(1,2,3,4,5,6);
	shuffle($seq);
	Game::$idiot=$seq[0];
	Game::$normals[0]=$seq[1];
	Game::$normals[1]=$seq[2];
	Game::$normals[2]=$seq[3];
	Game::$ghosts[0]=$seq[4];
	Game::$ghosts[1]=$seq[5];
	Game::$word='空调';
	Game::$trap='暖气';
	Game::$clients_role[$seq[0]]='idiot';
	//$Server->wsSend($seq[0],"dg 0");
	$Server->wsSend($seq[0],"w ".Game::$trap.Game::$sep."dg 0".Game::$sep."0 0 Your Word: <span class='h1'>".Game::$trap."</span>");
	foreach(Game::$normals as $i){
		Game::$clients_role[$i]='normal';
		//$Server->wsSend($i,"dg 0");
		$Server->wsSend($i,"w ".Game::$word.Game::$sep."dg 0".Game::$sep."0 0 Your Word: <span class='h1'>".Game::$word."</span>");
	}
	foreach(Game::$ghosts as $key=>$i){
		Game::$clients_role[$i]='ghost';
		$wordLen=(string)mb_strlen(Game::$word,'UTF8');
		$Server->wsSend($i,"w $wordLen"."个字".Game::$sep."gi ".(string)Game::$ghosts[1-$key].Game::$sep."0 0 You are Ghost! <span class='h1'>Word.length=".$wordLen."</span>");
	}	

	foreach($Server->wsClients as $id=>$val){ //all disabled
		$str="7 0".Game::$sep;
		foreach($Server->wsClients as $i=>$v) //notice green
			$str.="5 $i".Game::$sep;		
		$str.="st Choose"; //enable first one
		if(Game::$clients_role[$id]=='ghost'){
			$Server->wsSend($id,"cs 0".Game::$sep."3 0".Game::$sep.$str);
		}
		else{
			$Server->wsSend($id,"3 0".Game::$sep.$str);
		}
	}
	Game::$aliveCount=Game::$N;
}


// when a client sends data to the server
function wsOnMessage($clientID, $data, $messageLength, $binary) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}
	$ClientNum=sizeof($Server->wsClients);
	if(!Game::$alive[$clientID] && $clientID!=Game::$deadMan)
		return;	
	$args=explode(' ',$data,2);
	$op=$args[0];
	$message=str_replace("=__="," ",$args[1]); //replace sep in the message
	if($ClientNum ==Game::$N){ //Game is on
        echo Game::$mode."\n";
		switch($op){
		case 'c':

			break;
		case 'g':
			if(Game::$clients_role[$clientID]!='ghost')
				return;
			$newmessage=str_replace("\n","<br/>",$message);
			if(Game::$ghosts[0]==$clientID){
				$Server->wsSend(Game::$ghosts[1],"g ".$newmessage);
			}else{
				$Server->wsSend(Game::$ghosts[0],"g ".$newmessage);	
			}
			break;
		case 'm':
			switch(Game::$mode){
			case 'choose':
				if(Game::$clients_role[$clientID]=='ghost'){
					$ch=(int)$message;
					++Game::$ghostChooseCount;
					if(Game::$ghostChoose==0){
						Game::$ghostChoose=$ch;//first choose
					}
					else if(Game::$ghostChooseCount==Game::$ghostNum && Game::$ghostChoose != $ch){ //not same
						Game::$ghostChooseCount=0;
                        Game::$ghostChoose=0;
						foreach(Game::$ghosts as $id){
							$Server->wsSend($id,"0 0 Choice not the Same one!<br/> <span class='h1'>ReChoose!</span>");
						}
						return;
					}
					if(Game::$ghostNum==Game::$ghostChooseCount){
                        Game::$alive[$ch]=false;
						Game::$player=$ch-1; //dead man
						do{
							Game::$player=(Game::$player+1)%Game::$N; //next one
						}while(!Game::$alive[Game::$player+1]);  //make sure it's alive
						$str="ce 0".Game::$sep."7 0".Game::$sep."st LastWords".Game::$sep."0 0 Ghost choose Player <span class='h1'>#".$ch."</span><br/>It's a ".Game::$clients_role[$ch]."<br/>wait for his lastWords<br/>".Game::$sep."6 ".$ch.Game::$clients_role[$ch].Game::$sep."3 0".Game::$sep."1 ".$ch;  //message
						foreach($Server->wsClients as $id=>$val){						
							if($id!=$ch){
								$Server->wsSend($id,$str);
							}
							else{
								$Server->wsSend($id,"st LastWords".Game::$sep."6 ".$ch.Game::$clients_role[$ch].Game::$sep."2 0".Game::$sep."0 0 <span class='h2'>You are Dead!</span><br/>Your LastWords?");
							}
						}				
						Game::$mode='lastWords';
						Game::$afterVote=0;
						Game::$deadMan=$ch;						
					}
				}
				break;
			case 'lastWords':
				if($clientID==Game::$deadMan){
					if(Game::$afterVote==0){
						$str="st Describe".Game::$sep."0 0  <span class='h1'>Deadman: </span>".$message."<br/>OK! Let's Describe: ".Game::$sep."3 0".Game::$sep."7 0".Game::$sep."1 ".(string)(Game::$player+1);
						Game::$mode='describe';
                        Game::$step=0;
					}
					else{
						$str="cs 0".Game::$sep."st Choose".Game::$sep."0 0  <span class='h1'>Deadman: </span>".$message."<br/>OK! Waiting for Ghost: Round<span class='h1'>".(string)(Game::$round+1)."</span>".Game::$sep."3 0".Game::$sep."7 0".Game::$sep."1 ".(string)(Game::$player+1);
						Game::$mode='choose';
                        ++Game::$round;
					}
					foreach($Server->wsClients as $id=>$val){
						if($id!=Game::$deadMan){
							$Server->wsSend($id,$str);
						}
						else{
							$Server->wsSend($id,$str.Game::$sep."3 0"); //disable dead man
						}
					}
				}
                Game::$deadMan=0;
				break;
			case 'describe':
				if($clientID==Game::$player+1){ //circle
					do{
						Game::$player=(Game::$player+1)%Game::$N; //next one
					}while(!Game::$alive[Game::$player+1]);  //make sure it's alive
					$newmessage=str_replace("\n","<br/>",$message);
                    $str="0 $clientID $newmessage";
                    if(Game::$step!=Game::$N-Game::$round*2-2){
                        $str.=Game::$sep."1 ".(string)(Game::$player+1);  //message
                    }
                    else{
                        $str.=Game::$sep."nb 0";
                    }
					foreach($Server->wsClients as $id=>$val){						
						if($id==$clientID){ //sender
							$Server->wsSend($id,$str.Game::$sep."3 0");  //disable
						}
                        else{
                            $Server->wsSend($id,$str);
                        }
					}				
					if(++Game::$step==Game::$N-Game::$round*2-1){//round end
						Game::$mode='assert';					
						Game::$assert_op=array();
						Game::$assert_op_count=0;
						Game::$guessPeople=0;
						Game::$step=0;
						foreach($Server->wsClients as $id=>$val){
							if(Game::$clients_role[$id]=='ghost'){
								$Server->wsSend($id,"2 0".Game::$sep."st Jump?".Game::$sep."0 0 Will you Jump?");
							}
							else{
								$Server->wsSend($id,"3 0".Game::$sep."st Waiting".Game::$sep."0 0 Waiting for the Ghost...");
							}
						}
					}
				}
				else{// almost never happen
					$Server->wsSend($clientID,"0 0 没到你发言！");
				}
				break;
			case 'disguss':
				if($clientID==Game::$player+1){ //circle
					do{
						Game::$player=(Game::$player+1)%Game::$N; //next one
					}while(!Game::$alive[Game::$player+1]);  //make sure it's alive
					$newmessage=str_replace("\n","<br/>",$message);
                    $str="0 $clientID $newmessage";
                    if(Game::$step!=Game::$N-Game::$round*2-2){
                        $str.=Game::$sep."1 ".(string)(Game::$player+1); //highlight & enable
                    }
                    else{
                        $str.=Game::$sep."nb 0";
                    }
					foreach($Server->wsClients as $id=>$val){
						if($id==$clientID){ //sender
							$Server->wsSend($id,$str.Game::$sep."3 0");  //disable
						}
                        else{
                            $Server->wsSend($id,$str);
                        }
					}

					if(++Game::$step==Game::$N-Game::$round*2-1){//round end
						Game::$mode='vote';
						Game::$voteCount=0;
						Game::$votes=array();
						Game::$step=0;
						foreach($Server->wsClients as $id=>$val){
							$Server->wsSend($id,"st Vote".Game::$sep."3 0".Game::$sep."0 0 OK！ <span class='h1'>Click</span> to Vote ...");
						}
					}
				}
				else{
					$Server->wsSend($clientID,"0 0 没到你发言！");
				}
				break;
			case 'vote':
				if(!array_key_exists($clientID,Game::$votes)){
					Game::$votes[$clientID]=(int)$message;				
					if(++Game::$voteCount==Game::$N-Game::$round*2-1){
						$res=array();
						foreach(Game::$votes as $val){
							if(array_key_exists($val,$res))
								++$res[$val];
							else
								$res[$val]=1;
						}
						arsort($res);
						$str="0 0 <span class='h1'>Vote Result:</span> <br/>";
						foreach($res as $id=>$val){
							$str.="Player <span class='h1'>#$id</span> : <span class='h3'>$val</span>"."<br/>";
						}
						$keys=array_keys($res);
						if(sizeof($keys)>=2 && $res[$keys[0]]==$res[$keys[1]]){//tie reVote
							foreach($Server->wsClients as $id=>$val){
								$Server->wsSend($id,$str."<span class='h1'>Tie! ReVote</span>");
							}
							Game::$votes=array();
							Game::$voteCount=0;
							return;
						}
						$str.="Player <span class='h1'>#".$keys[0]."</span> Dead. It's a ".Game::$clients_role[$keys[0]];	
						Game::$deadMan=$keys[0];
						Game::$alive[$keys[0]]=false;
						--Game::$aliveCount;
						if(Game::$player+1==$keys[0]){
							do{
								Game::$player=(Game::$player+1)%Game::$N; //next one
							}while(!Game::$alive[Game::$player+1]);  //make sure it's alive
						}

						if(Game::$clients_role[$keys[0]]=='ghost'){
							if(--Game::$ghostNum==0){
								$str.="<span class='h3'>Human Win!!!</span>";
								Game::$mode='GameOver';
								return;
							}						
						}
						$str.=Game::$sep."6 ".$keys[0].Game::$clients_role[$keys[0]].Game::$sep."3 0";
						foreach($Server->wsClients as $id=>$val){							
							if((int)$id==(int)$keys[0]){ // the Dead Man
								$Server->wsSend($id,$str.Game::$sep."0 0 <span class='h2'>You are Dead!</span><br/>Your LastWords?");
							}
							else{
								$Server->wsSend($id,$str);
							}
						}
						//echo $str;
						//next round
						if(Game::$round==2){
							foreach($Server->wsClients as $id=>$val){
								if(Game::$clients_role[$id]!='ghost'){
									$Server->wsSend($id,"st GameOver".Game::$sep."0 0 After 2 rounds,There still have Ghost.<br/><span class='h3'>You Lose!!!</span>");										
								}
								else{
									$Server->wsSend($id,"st GameOver".Game::$sep."0 0 <span class='h3'>You Win!!!</span>");
								}
							}
							Game::$mode='GameOver';
							return;
						}
						else{
							Game::$mode='lastWords';
							Game::$afterVote=1;
							foreach($Server->wsClients as $id=>$val){
								$Server->wsSend($id,"st LastWords".Game::$sep."3 0".Game::$sep."1 ".Game::$deadMan); //blur
							}
						}
					}
				}
				break;
			case 'assert':
				if(Game::$clients_role[$clientID]=='ghost'){
					if(!array_key_exists($clientID,Game::$assert_op)){
						Game::$assert_op[$clientID]=$message;
						if($message=='yes'){
							Game::$guessCount[$clientID]=3;
							++Game::$guessPeople;
						}
                        $Server->wsSend($clientID,"3 0");
						if(++Game::$assert_op_count==Game::$ghostNum){ //all option  received!
							if(Game::$guessPeople==0){
								foreach($Server->wsClients as $id=>$val){
									$Server->wsSend($id,"st Discuss".Game::$sep."7 0".Game::$sep."0 0 No Ghost Assert? Let's Disguss for <span class='h1'>Vote</span>".Game::$sep."1 ".(string)(Game::$player+1));
								}
								Game::$mode='disguss';
                                Game::$step=0;
								foreach(Game::$ghosts as $id){ //disable ghost
									$Server->wsSend($id,"3 0");
								}
							}
							else{
								Game::$mode='guess';
								foreach($Server->wsClients as $id=>$val){
									$Server->wsSend($id,"st Guess".Game::$sep."0 0 There is <span class='h1'>".Game::$guessPeople."</span>ghost Assert");										
								}
								foreach(Game::$guessCount as $id=>$val){
									$Server->wsSend($id,"2 0".Game::$sep."0 0 Guess the Word!... just <span class='h1'>3</span> times");
								}
							}
						}
					}
				}
				break;
			case 'guess':
				if(array_key_exists($clientID,Game::$guessCount)){
					--Game::$guessCount[$clientID];
					/*foreach($Server->wsClients as $id=>$val){
                        if($id!=$clientID){
                            $Server->wsSend($id,"0 0 Player# ".$clientID." guess: [".$message."]");
                        }							
					}*/
					if($message==Game::$word){
						foreach($Server->wsClients as $id=>$val){
							if($id!=$clientID){
								$Server->wsSend($id,"st GameOver".Game::$sep."0 0 Player <span class='h1'># ".$clientID."</span>'s guess [<span class='h3'>".$message."</span>] is <span class='h1'>right</span>! Ghosts win the Game");
							}
							else{
								$Server->wsSend($id,"st GameOver".Game::$sep."0 0 <span class='h3'>You Win!!!!</span>");
							}
						}
						Game::$mode='GameOver';
						return;
					}
					else{
						foreach($Server->wsClients as $id=>$val){
							if($id!=$clientID){
								$Server->wsSend($id,"0 0 Player <span class='h1'># ".$clientID."</span>'s guess [<span class='h3'>".$message."</span>] is <span class='h2'>wrong</span>!  <span class='h1'>".Game::$guessCount[$clientID]."</span>-time remaining");
							}
							else{
								$Server->wsSend($id,"0 0 <span class='h2'>Wrong</span>!!!! <span class='h1'>".Game::$guessCount[$clientID]."</span> time remaining");
							}
						}
						if(Game::$guessCount[$clientID]==0){
							Game::$guessCount[$clientID]=null;
							Game::$alive[$clientID]=false;
							--Game::$ghostNum;
							--Game::$aliveCount;
							if(--Game::$guessPeople == 0){
								if(Game::$ghostNum==0){
									foreach($Server->wsClients as $id=>$val){
										if(Game::$clients_role[$id]!='ghost'){
											$Server->wsSend($id,"st GameOver".Game::$sep."0 0 All Ghost dead! <span class='h3'>You Win!!!</span>");										
										}
										else{
											$Server->wsSend($id,"st GameOver".Game::$sep."0 0 <span class='h3'>You Lose!!!</span>");
										}
									}
									Game::$mode='GameOver';
									return;
								}
								else{ //next round
									if(Game::$round==2){
										foreach($Server->wsClients as $id=>$val){
											if(Game::$clients_role[$id]!='ghost'){
												$Server->wsSend($id,"st GameOver".Game::$sep."0 0 After 2 rounds,There still have Ghost.<span class='h3'>You Lose!!!</span>");										
											}
											else{
												$Server->wsSend($id,"st GameOver".Game::$sep."0 0 <span class='h3'>You Win!!!</span>");
											}
										}
										Game::$mode='GameOver';
										return;
									}
									else{
										foreach($Server->wsClients as $id=>$val){
											$str="7 0".Game::$sep."st Describe".Game::$sep."0 0 Round: <span class='h1'>".Game::$round."</span><br/>Let's Describe....";
											if(Game::$alive[$id]){
												$str.=Game::$sep."3 0".Game::$sep."1 ".(string)(Game::$player+1);
											}
											$Server->wsSend($id,$str);
										}
										Game::$mode='describe';
										Game::$player=0;
										while(!Game::$alive[Game::$player+1])
											Game::$player=(Game::$player+1)%Game::$N;
									}
								}
							}
						}
					}
				}
				break;
			}
			break;
		}
	}
	else{ //free talk
		$newmessage=str_replace("\n","<br/>",$message);
		foreach($Server->wsClients as $id=>$val)
			$Server->wsSend($id,"0 $clientID $newmessage");
	}
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	$Server->log( "$ip ($clientID) has connected." );
	$Server->wsSend($clientID,"st Waiting".Game::$sep."4 $clientID ");
	Game::$alive[$clientID]=true;
	//Send a join notice to everyone but the person who joined
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID )
			$Server->wsSend($id, "0 0 Player <span class='h1'>#$clientID </span> ($ip) has joined the room.");
	$ClientNum=sizeof($Server->wsClients);
	if($ClientNum==Game::$N){//everybody is here now
		Initialize();
	}
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has disconnected." );

	//Send a user left notice to everyone in the room
	foreach ( $Server->wsClients as $id => $client )
		$Server->wsSend($id, "0 0 Player <span class='h1'>#$clientID</span> ($ip) has left the room.",true);
}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('127.0.0.1', 9300);

?>
