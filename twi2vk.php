<?
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
	var $cfg=Array();
	var $is_debug=1;
	var $cache_time=10;
	var $file="last.txt";
	var $no_rt=0;
	var $no_rp=1;
	var $error=0;
	var $time=0;
	var $debug=Array();
	//MAIN
	var $check_next_if_no_replay=1;
	var $add=" ";//." (via Twitter)";
	var $no="#nvk";

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
		$this->tmp=$this->msg.$this->add;
		$login=$this->login();
		$this->dbg("Login done...");
		$this->tmp=$this->curl("http://vkontakte.ru/al_wall.php","act=post&al=1&hash=".$login."&message=".$this->msg."&note_title=&official=&status_export=0&to_id=".$this->id."&type=all");
		$this->dbg("UPDATE done...");
		$this->dbg("[/vk]");
	}
	function load_twitter()
	{
		$this->tmp = $this->curl("http://api.twitter.com/1/statuses/user_timeline.xml?screen_name=".$this->user,''); 
		$this->dbg("Loading Twitter API...");
		preg_match_all("#<text>(.*)</text>#iU",$this->tmp,$this->msgs); 
		preg_match_all("#<in_reply_to_screen_name>(.*)</in_reply_to_screen_name>#iU",$this->tmp,$this->rpl); 
		$this->msgs=$this->msgs[1];
		$this->rpl=$this->rpl[1];
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
		$result = $this->curl("http://vkontakte.ru/login.php","email=".$this->email."&pass=".$this->pass); 
		$this->dbg("Login to VK");
		$result = $this->curl('http://vkontakte.ru/id'.$this->id,'');
		$this->dbg("Get hash...");
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