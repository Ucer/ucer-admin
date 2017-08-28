
<?php

class qq extends \think\Model{
	//回调地址
	public $return_url;
	public $app_id;
	public $app_key;
	public function __construct($config){

//		if($_COOKIE['is_mobile'] == 1)
//			$this->return_url = "http://".$_SERVER['HTTP_HOST']."/index.php?m=Mobile&c=LoginApi&a=callback&oauth=qq";
//		else
//	    $this->return_url = "http://".$_SERVER['HTTP_HOST']."/index.php?m=Home&c=LoginApi&a=callback&oauth=qq";
			
		$this->return_url = "http://".$_SERVER['HTTP_HOST']."/index.php/home/LoginApi/callback/oauth/qq";
										
		$this->app_id = $config['app_id'];
		$this->app_key = $config['app_key'];

	}
	//构造要请求的参数数组，无需改动
	public function login(){
		\think\Session::set('state',md5(uniqid(rand(), TRUE)));
		//拼接URL
		$dialog_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
			. $this->app_id . "&redirect_uri=" . urlencode($this->return_url) . "&state="
			. \think\Session::get('state');
		echo("<script> top.location.href='" . $dialog_url . "'</script>");
		exit();
	}

	public function respon(){
		if(input('state') == session('state'))
		{
			$code = $_REQUEST["code"];
			//拼接URL
			$token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
				. "client_id=" . $this->app_id . "&redirect_uri=" . urlencode($this->return_url)
				. "&client_secret=" . $this->app_key . "&code=" . $code;

			$response = $this->get_contents($token_url);
			if (strpos($response, "callback") !== false)
			{
				$lpos = strpos($response, "(");
				$rpos = strrpos($response, ")");
				$response  = substr($response, $lpos + 1, $rpos - $lpos -1);
				$msg = json_decode($response);
				if (isset($msg->error))
				{
					echo "<h3>error:</h3>" . $msg->error;
					echo "<h3>msg  :</h3>" . $msg->error_description;
					exit;
				}
			}

			//Step3：使用Access Token来获取用户的OpenID
			$params = array();
			parse_str($response, $params);

			$graph_url = "https://graph.qq.com/oauth2.0/me?access_token="
				.$params['access_token'];

			$str  = $this->get_contents($graph_url);
			if (strpos($str, "callback") !== false)
			{
				$lpos = strpos($str, "(");
				$rpos = strrpos($str, ")");
				$str  = substr($str, $lpos + 1, $rpos - $lpos -1);
			}
			$user = json_decode($str);
			if (isset($user->error))
			{
				echo "<h3>error:</h3>" . $user->error;
				echo "<h3>msg  :</h3>" . $user->error_description;
				exit;
			}
			//获取到openid
			$openid = $user->openid;
			//Step4：根据Access Token、appid、openid来获取用户的昵称、头像
//			$user_info = "https://graph.qq.com/user/get_user_info?openid=".$openid."&access_token=".$params['access_token']."&appid=".$this->app_id;
//			$strs  = $this->get_contents($user_info);
//			dd($strs);
			session('state',null); // 验证SESSION
			return array(
				'openid'=>$openid,//支付宝用户号
				'oauth'=>'qq',
				'nickname'=>'QQ用户',
			);
		}else{
			return false;
		}
	}


	public function get_contents($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response =  curl_exec($ch);
		curl_close($ch);

		//-------请求为空
		if(empty($response)){
			exit("50001");
		}

		return $response;
	}

}


?>
