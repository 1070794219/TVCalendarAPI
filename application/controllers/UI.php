<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class UI extends CI_Controller {	
	public function __construct(){
		parent::__construct();
		$this->load->config('config',true);
		$this->load->library('session');
		$this->CalUrl = 'http://www.pogdesign.co.uk';
		$this->dateFormat = '/^\d{4}-[0-1][1-9]-[0-3]\d$/';
		header("Content-type: text/html; charset=utf-8");			
	}

	//主页
	public function index()
	{
		$this->load->model('ShowModel');

		$date = date('Y-m-d');
		$data['today'] = $this->ShowModel->searchOneDateBrief($date);
		#$data['showsNumber'] = $this->ShowModel->getNumberOfShows();
		$data['CUrl'] = $this->CalUrl;
		$this->load->view('header');
		$this->load->view('home',$data);
		$this->load->view('footer');
	}

	//登陆页面,可以跳转
	public function webLogin()
	{
		$url = $this->input->get('url',TRUE);
		if ($url == '') 
		{
			$data['url'] = '/TVCalendarAPI/index.php/UI/index';
		}
		else
		{
			$data['url'] = $url;
		}
		$header['title'] = '用户登录';
		$this->load->view('headerLogin',$header);
		$this->load->view('login',$data);
		$this->load->view('footer_simple');

	}

	//登出页面
	public function webLogout()
	{
		$u_data = array('u_id','u_name','u_phone','u_token');
		$this->session->unset_userdata($u_data);
		header("Location: /TVCalendarAPI/index.php/UI/index");
	}

	//注册界面
	public function webreg()
	{
		$header['title'] = '注册';
		$this->load->view('headerLogin',$header);
		$this->load->view('webreg');
		$this->load->view('footer_simple');
	}

	//查看某年某月全部剧集的方法页面，参数形如2016-01
	public function viewMonth($month = '')
	{
		//验证是否登录
		$login_flag = $this->checkLogin(FALSE,'');

		$this->load->model('ShowModel');
		//强制验证month是否符合规范
		$monthFormat = '/^\d{4}-\d{2}$/';
		if(!preg_match($monthFormat,$month)){
			$month = '';
		}

		if (empty($month)) 
		{
			$dateStart = date('Y-m-01');
			$dateEnd = date('Y-m-d', strtotime("$dateStart +1 month -1 day"));
			$month = date('Y-m');
		}
		else
		{
			$dateStart = $month."-01";
			$dateEnd = date('Y-m-d', strtotime("$dateStart +1 month -1 day"));
		}

		$shows = $this->ShowModel->searchDates($dateStart,$dateEnd);

		foreach ($shows as $dayDate => &$oneday) 
		{
			foreach ($oneday as &$aShow) 
			{
				if ($login_flag) 
				{
					$flag = $this->ShowModel->checkSubscribe($this->session->u_id,$aShow['s_id']);
					if ($flag) 
					{
						$aShow['sub'] = "1";
					}
					else
					{
						$aShow['sub'] = "0";
					}
				}
				else
				{
					$aShow['sub'] = "0";
				}
				
			}
		}
		$data['login_flag'] = $login_flag;
		$data['shows'] = $shows;
		$data['dateStart'] = $dateStart;
		$header['title'] = $month."月剧集";
		$this->load->view('header',$header);
		$this->load->view('viewMonth',$data);
		$this->load->view('footer');
	}

	//查看一部剧的summary，即一部剧全部内容的方法
	public function showSummary($sid)
	{
		//验证是否登录
		$login_flag = $this->checkLogin(FALSE,'');

		$this->load->model('ShowModel');
		//验证sid是否符合规范
		$idFormat = '/^\d*$/';
		if(!preg_match($idFormat,intval($sid)))
		{
			//出现问题直接崩回首页，问题一般出现于利用URL注入
			header("Location: /TVCalendarAPI/index.php/UI/index.php");
			exit();
		}

		$data['login_flag'] = $login_flag;
		$data['showInfo'] = $this->ShowModel->searchByShowId($sid);
		$data['episodeInfo'] = $this->ShowModel->searchEpsBySid($sid);
		if ($login_flag) 
		{
			$data['subOrNot'] = $this->ShowModel->checkSubscribe($this->session->u_id,$sid);
		}
		else
		{
			$data['subOrNot'] = FALSE;
		}
		$data['s_id'] = $sid;

		foreach ($data['episodeInfo'] as &$anEpisode) 
		{
			if ($login_flag) 
			{
				$synFlag = $this->ShowModel->checkSyn($this->session->u_id,$anEpisode['e_id']);
				if ($synFlag) 
				{
					$anEpisode['syn'] = 1;
				}
				else
				{
					$anEpisode['syn'] = 0;
				}
			}
			else
			{
				$anEpisode['syn'] = 0;
			}
			
		}

		$data['CUrl'] = $this->CalUrl;
		$header['title'] = $data['showInfo']['s_name'].'的信息';

		$this->load->view('header',$header);
		$this->load->view('viewShow',$data);
		$this->load->view('footer');
	}

	//注册用户“我的剧集功能”
	public function myShows()
	{
		//验证是否登录
		$this_url = '/TVCalendarAPI/index.php/'.$this->uri->uri_string();
		$this->checkLogin(TRUE,$this_url);
		$this->load->model('ShowModel');

		$data['rescentEps'] = $this->ShowModel->searchRecentByUid($this->session->u_id,7,7,date('Y-m-d'),'+00');
		$data['mySubscribe'] = $this->ShowModel->searchByUidOrderByDate($this->session->u_id);

		foreach ($data['rescentEps'] as &$anEpisode) 
		{
			$synFlag = $this->ShowModel->checkSyn($this->session->u_id,$anEpisode['e_id']);
			if ($synFlag) 
			{
				$anEpisode['syn'] = 1;
			}
			else
			{
				$anEpisode['syn'] = 0;
			}
		}
		foreach ($data['mySubscribe'] as &$oneSubcribe) 
		{
			$oneSubcribe['percent'] = $this->ShowModel->getSynPercent($this->session->u_id,$oneSubcribe['s_id']);
		}

		$data['CUrl'] = $this->CalUrl;
		$header['title'] = '我的剧集';

		$this->load->view('header',$header);
		$this->load->view('viewMyShows',$data);
		$this->load->view('footer');

	}

	//注册用户浏览自己信息和修改密码的页面方法
	public function myCenter()
	{
		//验证是否登录
		$this_url = '/TVCalendarAPI/index.php/'.$this->uri->uri_string();
		$this->checkLogin(TRUE,$this_url);
		$this->load->model('UserModel');

		$data['userInfo'] = $this->UserModel->getUserData($this->session->u_id);
		$header['title'] = '用户中心';

		$this->load->view('header',$header);
		$this->load->view('userCenter',$data);
		$this->load->view('footer');
	}

	//搜索
	public function search($words='',$fullResult='')
	{
		//不验证是否登录
		
		$this->load->model('ShowModel');

		$data['result'] = array();
		if (!empty($words)) {
			$words = urldecode($words);
			$words = str_replace('\'', "\\'", $words);
			$words = str_replace('%20', ' ', $words);
			$words = str_replace('%2F', '/', $words);

			$st = 0;
			$lg = 30;
			if ($fullResult == "fullresult") {
				$st = 0;
				$lg = 300;
			}
			$data['result'] = $this->ShowModel->searchByName($words,$st,$lg);
		}
		if(count($data['result']) == 30)
		{
			$data['fullRequest'] = True;
		}
		else
		{
			$data['fullRequest'] = False;
		}
		$header['title'] = '查找剧集-'.$words;
		$data['CUrl'] = $this->CalUrl;
		

		$this->load->view('header',$header);
		$this->load->view('viewSearch',$data);
		$this->load->view('footer');
	}

	//推荐页面
	public function recommend($guessILike='0')
	{
		//验证是否登录
		$login_flag = $this->checkLogin(FALSE,'');
		$this_url = '/TVCalendarAPI/index.php/'.$this->uri->uri_string();
		//$this->checkLogin();
		$this->load->model('ShowModel');

		$area = '';
		$header['title'] = '推荐';
		if ($this->input->get('area',true))
		{
			$area = urldecode($area);
			if ($area == 'USA') {
				$area = 'WHERE area = USA or area = United States';
			}else if ($area == 'United Kingdom') {
				$area = 'WHERE area = UK or area = United Kingdom';
			}
			#$area = $this->db->escape($this->input->get('area',true));
			#$area = str_replace('\'', "\\'", $area);
			#$area = str_replace('%20', ' ', $area);
			#$area = str_replace('%2F', '/', $area);
			#$area = "".$area;
		}
		if ($guessILike == '1') 
		{
			$this->checkLogin(TRUE,$this_url);
			$data['iLike'] = $this->ShowModel->getLikeRecommend($this->session->u_id,5);
			$data['hot'] = $this->ShowModel->getHotRecommend($area,5);
			$header['title'] = '猜我喜欢';
		}
		elseif ($guessILike == '2') 
		{
			$data['downCount'] = $this->ShowModel->getDownloadCount(10);
			$header['title'] = '下载排行榜';
		}
		else
		{
			$data['hot'] = $this->ShowModel->getHotRecommend($area,10);
		}
		if ($login_flag) 
		{
			$data['tag'] = $this->ShowModel->getAllTagWithStatus($this->session->u_id);
		}
		else
		{
			$data['tag'] = [];
		}
		
		$data['CUrl'] = $this->CalUrl;
		$this->load->view('header',$header);
		$this->load->view('viewRecommend',$data);
		$this->load->view('footer');
	}

	//文档类方法
	public function docs($value)
	{
		if ($value == 'letter') 
		{
			$this->load->model('UserModel');
			$header['title'] = '致用户的一封信';
			$data['budget'] = $this->UserModel->getBudget();
			$data['kind'] = 'letter';
		}
		else if($value == 'license')
		{
			$header['title'] = '法律信息';
			$data['kind'] = 'license';
		}
		else
		{
			$header['title'] = '关于我们';
			$data['kind'] = 'about';
		}

		$this->load->view('header',$header);
		$this->load->view('viewDocs',$data);
		$this->load->view('footer_simple');
	}

	//浏览下载链接的方法
	public function download()
	{
		$this_url = '/TVCalendarAPI/index.php/'.$this->uri->uri_string();
		$this->checkLogin(TRUE,$this_url);
		$this->load->model('ShowModel');
		
		$r_id = $this->db->escape($this->input->get('r_id',TRUE));
		#$s_name =  $this->db->escape($s_name);
		$se_id = $this->db->escape($this->input->get('se_id',TRUE));
		$e_num =  $this->db->escape($this->input->get('e_num',TRUE));
		$data = array();
		if ($r_id != '0' && !empty($se_id) && !empty($e_num)) {
			$data['link'] = $this->ShowModel->getDownloadLink($r_id,$se_id,$e_num);
		}
		$header['title'] = '第'.$se_id.'季第'.$e_num.'集-下载链接';
		$this->load->view('header',$header);
		$this->load->view('viewDownload',$data);
		$this->load->view('footer');

	}

	public function QandA()
	{
		$this->load->view('header');
		$this->load->view('viewQA');
		$this->load->view('footer');
	}

	public function test_2()
	{
		$u_phone = $this->db->escape($this->input->get('u_p',TRUE));
		$u_pwhash = $this->db->escape($this->input->get('u_h',TRUE));
		$this->load->model('UserModel');
		$result = $this->UserModel->reset_pwd($u_phone,$u_pwhash);
		if ($result) 
		{
			echo "ok";
		}
		else
		{
			echo "error";
		}
	}


	//ajax验证密码并写入cookies的方法
	public function ajaxCheckPw()
	{
		$this->load->model('UserModel');
		$u_phone = $this->db->escape($this->input->post('u_phone',true));
		$u_passwd = $this->db->escape($this->input->post('u_passwd',true));
		#$u_passwd = $this->filter($this->input->post('u_passwd',true));
		$u_passwd = substr($u_passwd, 1,-1);
		#echo $u_passwd;
		if (!empty($u_phone) && !empty($u_passwd))
		#if($_GET['u_phone'] && $_GET['u_passwd'])
		{
			$u_phone = $u_phone;
			$u_passwd = md5($u_passwd);
			#$u_phone = $_GET['u_phone'];
			#$u_passwd = md5($_GET['u_passwd']);
			$rs = $this->UserModel->login($u_phone,$u_passwd);
			if (isset($rs['u_id'])) 
			{
				$u_data = array(
					'u_id' => $rs['u_id'],
					'u_name' => $rs['u_name'],
					'u_phone' => $rs['u_phone'],
					'u_token' => $rs['u_token']
				);
				$this->session->set_userdata($u_data);
				echo "OK";
			}
			else
			{
				echo "WrongPW";
			}
		}
		else
		{
			echo "Error";
		}
	}

	//ajax注册验证
	public function ajaxReg()
	{
		$this->load->model('UserModel');
		$u_phone = $this->db->escape($this->input->post('u_phone',true));
		$u_passwd = $this->db->escape($this->input->post('u_passwd',true));
		$u_passwd = substr($u_passwd, 1,-1);
		$u_name = $this->db->escape($this->input->post('u_name',true));
		$code = strtolower($this->input->post('captcha'));
		$code_check = strtolower($this->session->userdata('code'));
		if($code != $code_check){
			echo "WrongCaptcha";
			exit();
		}
		if (!empty($u_phone) && !empty($u_passwd))
		{
			$u_phone = $u_phone;
			$u_passwd = md5($u_passwd);
			$rs = $this->UserModel->register($u_name,$u_phone,$u_passwd);
			if (!empty($rs)) 
			{
				if ($rs == "PHONEREPEAT") 
				{
					echo "WrongPh";
				}
				else
				{
					$u_data = array(
						'u_id' => $rs['u_id'],
						'u_name' => $rs['u_name'],
						'u_phone' => $rs['u_phone'],
						'u_token' => $rs['u_token']
					);
					$this->session->set_userdata($u_data);
					echo "OK";
				}
			}
			else
			{
				echo "Error";
			}
		}
	}

	//ajax执行订阅某部剧的方法
	public function ajaxSubscribe()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$u_id = $this->db->escape($this->input->post('u_id',true));
		$s_id = $this->db->escape($this->input->post('s_id',true));
		if (!empty($u_id) && !empty($s_id)) 
		{
			$rs = $this->ShowModel->insertSubscribe($u_id,$s_id,date('Y-m-d H:i:s'));
			if (!empty($rs)) 
			{
				echo $rs;
			}
			else
			{
				echo "Error";
			}
		}
	}

	//ajax执行不再订阅某部剧的方法
	public function ajaxUnsubscribe()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$u_id = $this->db->escape($this->input->post('u_id',true));
		$s_id = $this->db->escape($this->input->post('s_id',true));
		if (!empty($u_id) && !empty($s_id))
		{
			$rs = $this->ShowModel->deleteSubscribe($u_id,$s_id);
			if (!empty($rs)) 
			{
				echo $rs;
			}
			else
			{
				echo "Error";
			}
		}
	}

	//Ajax执行观剧同步的方法
	public function ajaxSynchron()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$u_id = $this->db->escape($this->input->post('u_id',true));
		$e_id = $this->db->escape($this->input->post('e_id',true));
		if (!empty($u_id) && !empty($this->input->post('e_id'))) 
		{
			$rs = $this->ShowModel->insertSynchron($u_id,$e_id,date('Y-m-d H:i:s'));
			if (!empty($rs)) 
			{
				echo $rs;
			}
			else
			{
				echo "Error";
			}
		}
	}

	//ajax执行不再订阅某部剧的方法
	public function ajaxUnsynchron()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$u_id = $this->db->escape($this->input->post('u_id',true));
		$e_id = $this->db->escape($this->input->post('e_id',true));
		if (!empty($u_id) && !empty($e_id))
		{
			$rs = $this->ShowModel->deleteSynchron($u_id,$e_id);
			if (!empty($rs)) 
			{
				echo $rs;
			}
			else
			{
				echo "Error";
			}
		}
	}

	//ajax更新用户密码以及个人信息的方法
	public function ajaxUpdateUser()
	{
		$this->ajaxCheckLogin();
		$this->load->model('UserModel');

		$name = $this->db->escape($this->input->post('name',true));
		#$name = substr($name, 1,-1);
		$pwd = $this->db->escape($this->input->post('pwd',true));
		$pwd = substr($pwd, 1,-1);
		$pwd = md5($pwd);
		$pwdNew = $this->db->escape($this->input->post('pwdNew',true));
		$pwdNew = substr($pwdNew, 1,-1);
		$pwdNew = md5($pwdNew);
		if (empty($name)) 
		{
			$name = "Undefined";
		}
		if (empty($pwd) || empty($pwdNew)) 
		{
			echo "Empty";
			exit();
		}
		if ($pwd == $pwdNew) 
		{
			$rs = $this->UserModel->updateNameOnly($this->session->u_id,$pwd,$name);
			if (!empty($rs)) 
			{
				if ($rs == "OK") 
				{
					$this->session->set_userdata('u_name', $name);
				}
				echo $rs;
				
			}
			else
			{
				echo "Error";
			}		
		}
		else
		{
			$rs = $this->UserModel->updateUserInfo($this->session->u_id,$pwd,$pwdNew,$name);
			if (!empty($rs)) 
			{
				if ($rs == "OK") 
				{
					$this->session->set_userdata('u_name', $name);
				}
				echo $rs;
			}
			else
			{
				echo "Error";
			}		
		}
	}

	//ajax执行关注标签的方法
	public function ajaxLike()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$u_id = $this->db->escape($this->input->post('u_id',true));
		$t_id = $this->db->escape($this->input->post('t_id',true));

		if (!empty($u_id) && !empty($t_id)) 
		{
			$rs = $this->ShowModel->insertLike($u_id,$t_id);
			if (!empty($rs)) 
			{
				echo $rs;
			}
			else
			{
				echo "Error";
			}
		}
	}

	//ajax执行不再关注标签的方法
	public function ajaxUnlike()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$u_id = $this->db->escape($this->input->post('u_id',true));
		$t_id = $this->db->escape($this->input->post('t_id',true));
		if (!empty($u_id) && !empty($t_id))
		{
			$rs = $this->ShowModel->deleteLike($u_id,$t_id);
			if (!empty($rs)) 
			{
				echo $rs;
			}
			else
			{
				echo "Error";
			}
		}
	}

	public function ajaxCountDownload()
	{
		$this->ajaxCheckLogin();
		$this->load->model('ShowModel');
		$e_id = intval($this->input->post('e_id',true));
		$this->ShowModel->plusOneDownload($e_id);
		echo "OK";
	}

	//检查是否已登录，
	//第一个参数为是否强制验证，强制验证模式下未登录强制跳转到$redirect_url
	//非强制验证模式下未登录返回FALSE，已登录返回TRUE
	public function checkLogin($is_enforce = TRUE,$redirect_url = '/')
	{
		if ($this->session->userdata('u_id') == null)
		{
			if ($is_enforce) 
			{
				header("Location: /TVCalendarAPI/index.php/UI/webLogin?url={$redirect_url}");
				exit();
			}
			
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	public function checkLogin_ease()
	{
		echo intval("'1");
	}

	//ajax方法调用前验证登陆的方法
	public function ajaxCheckLogin()
	{
		if ($this->session->userdata('u_id') == null)
		{
			echo "Forbidden";
			exit();
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function filter($input)
	{
		$mid = str_replace("'", ' ', $input);
		$mid = str_replace('"', ' ', $mid);
		$mid = str_replace(';', ' ', $mid);
		$mid = str_replace('?', ' ', $mid);
		$mid = str_replace('*', ' ', $mid);
		return $mid;
	}

	public function get_code()
	{
		$this->load->library('captha_new');
		$code = $this->captha_new->getCaptcha();
		$this->session->set_userdata('code', $code);
		$this->captha_new->showImg();
	}

}