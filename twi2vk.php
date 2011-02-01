<?
//Beta 0.4
$n=new twi2vk;
$n->init();							  //INIT
$n->svar("user","");				  //TWITTER LOGIN
$n->svar("id","");					  //VK ID
$n->svar("email","");				  //VK EMAIL
$n->svar("pass","");				  //VK PASSWD
$n->main();
echo($n->shwdbg());

class twi2vk
{
	//MAIN
	var $check_next_if_no_replay=1; 
	var $twitter_name_to_link=1; //"@test" to "@test (http://twitter.com/test)"
	var $request_twitter_fullname=1; //"@test" to "Hellow World (http://twitter.com/test)" 
	var $add=" ";//." (via Twitter)";
	var $no="#nvk"; //Message with this hashtag not for export/import
	//END MAIN
	var $cfg=Array();
	var $is_debug=1;
	var $cache_time=1;
	var $file="last.txt";
	var $no_rt=0;
	var $no_rp=1;
	var $error=0;
	var $time=0;
	var $debug=Array();
	var $tmp,$tmp2,$tmp3,$twiname,$tid;
	

	function init()
	{
		$this->dbg("[init]");
		$this->fg=file_get_contents($this->file); //Load last message
		$this->dbg("Loading file: ".$this->file);
		$this->ft=filemtime($this->file); //get file time
		$this->now=time(); //now time
		$this->o=$this->now-$this->ft; //diff
		if($this->o>$this->cache_time){$this->time=1;}
		$this->dbg("[/init]");
		$this->counter=0;
	}
	function main()
	{
		$this->dbg("[main]");
		if($this->time==1){
			$this->load_twitter();
			$this->twitter();
			if(trim($this->msg)!==trim($this->fg) AND $this->error==0 AND trim($this->msg)!==""){
				$this->vk();
			}else{
				$this->dbg("OLD: $this->msg");
			}
		}else{
			$this->dbg("CACHE: ".$this->fg);
		}
		$this->dbg("[/main]");
	}
	function vk()
	{
		$this->dbg("[vk]");
		file_put_contents($this->file,$this->msg);
		$this->dbg("Save last message to ".$this->file);
		$this->dbg("NEW STATUS: ".$this->msg);
		if($this->request_twitter_fullname==1){
			$this->linkify_tweet_2();
			$this->get_twitter_name($this->tmp2);
			$this->linkify_tweet_2();
		}
		if($this->twitter_name_to_link==1 AND $this->request_twitter_fullname==0){
			$this->linkify_tweet();
		}
		$this->tmp=$this->msg.$this->add;
		$login=$this->login();
		$this->dbg("Login done...");
		$this->tmp=$this->curl("http://vkontakte.ru/al_wall.php","act=post&al=1&hash=".$login."&message=".urlencode($this->msg)."&note_title=&official=&status_export=0&to_id=".$this->id."&type=all");
		$this->dbg("UPDATE done...");
		$this->dbg("[/vk]");
	}

	function load_twitter()
	{
		$this->dbg("[load_twitter]");
		$this->tmp = $this->curl("http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=".$this->user,''); 
		$this->dbg("Loading Twitter API...");
		preg_match_all("#<text>(.*)</text>#iU",$this->tmp,$this->msgs); 
		preg_match_all("#<in_reply_to_screen_name>(.*)</in_reply_to_screen_name>#iU",$this->tmp,$this->rpl); 
		if($this->msgs[1][0] and $this->rpl[1][0]){$this->dbg("It's work!");}
		$this->msgs=$this->msgs[1];
		$this->rpl=$this->rpl[1];
		$this->dbg("[/load_twitter]");
	}
	
	function get_twitter_name($name)
	{
		$this->dbg("[get_twi_name]");
		$this->tmp3 = $this->curl("http://api.twitter.com/1/users/show.xml?screen_name=".$name,''); 
		$this->dbg("Loading Twitter API (#2)...");
		preg_match_all("#<name>(.*)</name>#iU",$this->tmp3,$this->twiname); 
		$this->twiname=$this->twiname[1][0];
		$this->twiname=html_entity_decode($this->twiname, ENT_NOQUOTES,'UTF-8');
		$this->dbg("NAME: ".$this->twiname);
		$this->dbg("[/get_twi_name]");
	}

	function twitter()
	{
		$this->error=0;
		$this->dbg("[twitter]");
		$this->msg=$this->msgs[$this->counter];
		$this->msg=html_entity_decode($this->msg, ENT_NOQUOTES,'UTF-8');
		$this->dbg("Twitter: $this->msg");
		if(strpos($this->msg,$this->no)){$this->dbg("Stop-word: ".$this->no.". Try next...");$this->counter++;$this->twitter();}
		$this->rp=$this->rpl[$this->counter];
		if($this->no_rp==1 and $this->rp!==""){
			$this->error=1;$this->dbg("ERROR: NO @!");
			if($this->check_next_if_no_replay==1){
				$this->counter++;
				$this->twitter();
			}
		}
		$this->rt=$this->expl("<retweeted>","</retweeted>",$this->tmp);
		if($this->no_rt==1 and $this->rt!=="false"){$this->error=1;$this->dbg("ERROR: NO RT!");}
		$this->dbg("[/twitter]");
	}
	
	function login()
	{
		$this->dbg("[login]");
		$this->dbg("Login to VK");
		$result = $this->curl("http://vkontakte.ru/login.php","email=".$this->email."&pass=".$this->pass); 
		preg_match_all("#post_hash\":\"(.*)\"#iU",$result,$regs); 
		$this->dbg("Parse hash...");
		$this->dbg("[/login]");
		return ($regs[1][0]);
	}


	function curl($url,$post) 
	{ 
		$this->dbg("[curl]");
		$cfile = 'cookies.txt'; 
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_HEADER, 0); 
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US, PHP) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.6 Safari/534.16");
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cfile); 
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cfile); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if($post!==""){
			curl_setopt($ch, CURLOPT_POST, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
		}
		$result = curl_exec($ch); 
		$this->dbg("CURL EXEC... URL: $url | POST: $post");
		curl_close($ch); 
		$this->dbg("[/curl]");
		return $result; 
	} 
	function dbg($msg)
	{
		if($this->is_debug==1){
			$this->debug[]=$msg;
		}else{
			return (0);
		}
	}
	function shwdbg()
	{
		$this->tmp="";
		foreach($this->debug as $k=>$v){
			$this->tmp.=$v."<br>\r\n";
		}
		return ($this->tmp);
	}
	function svar($var,$value)
	{
		$this->dbg("[set_var] $var:$value [/set_var]");
		$this->$var=$value;
	}

	function linkify_tweet() {
		$this->dbg("[lt] Start linkify_tweet [/lt]");
		$this->msg = preg_replace('/(^|\s)@(\w+)/','\1@\2 (http://twitter.com/\2)',$this->msg);
		$this->msg = preg_replace('/(^|\s)#(\w+)/','\1#\2 (http://search.twitter.com/search?q=%23\2)',$this->msg);
	}

	function linkify_tweet_2() {
		if(!$this->tmp2){
			$this->dbg("[lt2] Start linkify_tweet_2");
			preg_match_all("/@([A-Za-z0-9_]+)/i",$this->msg,$regs); 
			$this->tmp2 = $regs[1][0];
			$this->dbg("GET:".$this->tmp2." [/lt2]");
		}else{
			$this->dbg("[lt2] Start linkify_tweet_2 (#2) [/lt2]");
			$this->msg  = str_replace("@".$this->tmp2,$this->twiname." (http://twitter.com/".$this->tmp2.")",$this->msg);
		}
		
	}

	function expl($f,$t,$text)
	{
		//$this->dbg("[expl] FROM $f to $t. [/expl]");
		$e=explode($f,$text);
		$e=explode($t,$e[1]);
		return ($e[0]);

	}
}
//END
?>