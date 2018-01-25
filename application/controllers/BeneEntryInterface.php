<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class BeneEntryInterface extends Bene_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->rt_name = 'BeneEntryInterface';
	}
	
	private function go_to_index_page()
	{
		header("Location: /user");
	}

	public function index(){
		//首先判断浏览器版本是否是IE8及以下
		if($this->isIE8Lower()){
            $this->login();
		}else{
            header("Location: ui_h5/index.html");
		}
	}

	//定义私有函数：判断浏览器是否是IE8 及以下版本
	private function isIE8Lower(){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if(strpos($agent,"Mozilla") || strpos($agent,"MOZILLA")){
		    return false;
        }
		if(strpos($agent,'MSIE') && (strpos($agent,'MSIE 8') || strpos($agent,'MSIE 7') || strpos($agent,'MSIE 6'))){
			return true;
		}
		return false;
	}
	
	public function get_current_account_Info()
	{
		$this->account = Helper_GetAuthInfo();
		if($this->account === FALSE)
			$this->exit_client_with_code(Rep_UnAuth);
		else
		{
			$this->load->model('Bene_User_Model');
			$user = $this->Bene_User_Model->load_user_info_by_id($this->account->ID, 'DownloadFlag');
			if($user === FALSE)
				return $this->exit_client_with_code(Rep_ServerError);
			$this->rep_obj->Version = BPNP_Version;
			$this->rep_obj->Feature = BPNP_Feature;
			$this->rep_obj->DownloadFlag = intval($user->DownloadFlag);
			if (defined('ZMQ_RepPort') && defined('ZMQ_PubPort'))
			{
				$this->rep_obj->RepPort= ZMQ_RepPort;
				$this->rep_obj->PubPort= ZMQ_PubPort;
				if(isset($_POST['MoreInfo']))
				{
					if($_POST['MoreInfo']==1)
					{
						$this->rep_obj->UserID=intval($this->account->ID);
						$this->rep_obj->Account=$this->account->Account;
						$this->rep_obj->HospitalID=intval($this->account->HospitalID);
						$this->rep_obj->DGSHospitalID=intval($this->account->DGSHospitalID);
						$this->rep_obj->GroupID=intval($this->account->GroupID);
						if(isset($this->account->ClientIP)===TRUE)
							$this->rep_obj->ClientIP=$this->account->ClientIP;
						else
							$this->rep_obj->ClientIP='';
					}
				}
			}
			$this->exit_client_with_code(Rep_OK);
		}
	}
	
	public function login()
	{
		$this->rt_method = 'login';
		
		$this->load->helper('Bene_Auth');
		$this->load->model('Bene_Login_Model');
		$chek_code = $this->Bene_Login_Model->login();
		
		$this->account = Helper_GetAuthInfo();
		if($chek_code==Rep_SkipPsdCheck)
		{
			if($this->account === FALSE)
			{
				if($this->software_client === TRUE) 
					$this->exit_client_with_code($chek_code);
			}
		}
		else if($chek_code!=Rep_OK)
		{
			Helper_MarkAuthInfo();
			if($this->software_client === TRUE) 
				$this->exit_client_with_code($chek_code);
		}
		
		//if($this->account === FALSE)
		//{
		//	$this->load->model('Bene_Login_Model');
		//	if($this->Bene_Login_Model->login() === TRUE) $this->account = Helper_GetAuthInfo();
		//}
		
		if($this->account === FALSE)
		{
			if($this->software_client === TRUE) $this->exit_client_with_code($chek_code);
			else
			{
				$this->lang->load('Bene_Title',$this->config->item('language'));
				$this->rt_title=$this->lang->line('login_title');
				$this->Bene_Login_Model->view();
			}
		}
		else if($this->account)
		{
			if($this->software_client === TRUE) 
			{
				//////////////////////////////////////////////////////////////////////////////////////
				$this->load->model('Bene_Online_Model');
				$user_id = $this->account->ID;
				$row = $this->Bene_Online_Model->get_online_user_info_by_user_id($user_id);
				if ($row===FALSE) $this->Bene_Online_Model->insert_new_online_user_info($user_id);
				else  $this->Bene_Online_Model->update_online_user_info($user_id);
				//////////////////////////////////////////////////////////////////////////////////////
				$this->exit_client_with_code(Rep_OK);
			}
			else $this->go_to_index_page();
		}
	}
	
	public function logout()
	{
		$this->rt_method = 'logout';
		
		if($this->account != FALSE)
		{
			$this->load->model('Bene_Action_Model');
			$this->load->model('Bene_Online_Model');
			$this->Bene_Action_Model->insert_new_action(ACTION_LOGOUT, OBJ_System, $this->account->ID);
			if($this->software_client === TRUE) 
			{
				$this->load->model('Bene_Online_Model');
				$user_id = $this->account->ID;
				$this->Bene_Online_Model->delete_online_user($user_id);
			}
		}
		
		//$this->load->helper('Bene_Auth');
		//Helper_MarkAuthInfo(NULL);
		session_start();
		$_SESSION = array();
		if(isset($_COOKIE[session_name()])){
			setcookie(session_name(),'',time()-42000,'/');
		}
		session_destroy();
		
		if($this->software_client === TRUE) $this->exit_client_with_code(Rep_OK);
		else header("Location: /");
	}
	
	public function test_connection()
	{
		if($this->software_client === TRUE)
		{
			$this->rep_obj->Version = BPNP_Version;
			$this->rep_obj->Feature = BPNP_Feature;
			$this->exit_client_with_code(Rep_OK);
		}
	}
	
	public function get_all_pending_notify_event()
	{
		$this->rt_method = 'get_all_pending_notify_event';
		//Filter UnPrivate Ip
		$ip = $this->client_ip;
		if(Helper_Is_Private_Ip($ip)==false) $this->go_to_index_page();
		
		$data_info_list = array();
		//prepare $data_info_list
		
		$uploaded_data_list = array();
		$this->load->model('Bene_DataInfo_Model');

		$period_begin = '';
		$period_end = '';
		Helper_Get_Period_Time($period_begin, $period_end);
		$this->Bene_DataInfo_Model->get_data_info_list($uploaded_data_list, -1, -1, -1, -1, Data_Uploaded, SuplStatus_NULL, -1, -1, 'SubmitTime', $period_begin, $period_end, array('SubmitTime' => 'ASC'), 'ID,UserID,DataID,RandomID,DGSHospitalID,DGSUserID');
		$op_user_list = array();
		foreach ($uploaded_data_list as $_data_list)
		{
			$op_user_list[] = $_data_list->UserID;
		}
		array_unique($op_user_list);               //去除重复的操作员
		$bing_map = array();
		//prepare bing_map
		$this->load->model('Bene_User_Binding_Model');
		$this->load->model('Bene_User_Model');
		$aly_list = array();
		foreach ($op_user_list as $key => $op_user)
		{
			if($this->Bene_User_Binding_Model->get_binding_aly_user_list($op_user,$aly_list))
			{
				foreach ($aly_list as $_bind)
				$bing_map[$op_user][]=$_bind->UserID_A;
			}
			else unset($op_user_list[$key]);
		}

		$notify_type = NewDataUploaded;
		foreach ($uploaded_data_list as $_data_list)
		{
			$user_list =array();
			if(in_array($_data_list->UserID,$op_user_list))
			{
				$user_list = $bing_map[$_data_list->UserID];
			}else
			{
				$group_ids = UG_Aly;
				$hospital_ids = $_data_list->DGSHospitalID;
				$_user_list = array();
				$this->Bene_User_Model->get_users_info_in_groups_and_hospitals($_user_list, $group_ids,  $hospital_ids, $select_fields = 'ID');
				foreach ($_user_list as $_user)
				{
					$user_list[] = intval($_user->ID);
				}
			}
			$data_info  = array('NotifyType' => $notify_type,
			                    'RecUID'     => $_data_list->DataID.'_'.$_data_list->RandomID,
							    'UserIDList' => $user_list);
			$data_info_list[] = $data_info;
		}
		//var_dump($data_info_list);
        //////////////////////////////////////////////////////////////////////////////////////////
		$uploaded_report_list = array();
		$this->Bene_DataInfo_Model->get_data_info_list($uploaded_report_list, -1, -1, -1, -1, Data_Diagnosed, SuplStatus_Uploaded, -1, -1, 'SubmitTime', $period_begin, $period_end, array('SubmitTime' => 'ASC'), 'ID,UserID,DataID,RandomID,DGSHospitalID,DGSUserID');
		
		$notify_type = NewReportUploaded;
	    foreach ($uploaded_report_list as $_data_list)
		{
			$user_list =array();
			$user_list[] = intval($_data_list->UserID);
			$data_info  = array('NotifyType' => $notify_type,
			                    'RecUID'     => $_data_list->DataID.'_'.$_data_list->RandomID,
							    'UserIDList' => $user_list);
			$data_info_list[] = $data_info;
		}
		
	    if($this->software_client === TRUE)
		{
			$this->rep_obj->DataInfoList = $data_info_list;
			$this->exit_client_with_code(Rep_OK);
		}else
		{
			$this->rep_obj = new stdClass();
			$this->rep_obj->DataInfoList = $data_info_list;
			$this->exit_client_with_code(Rep_OK);
		}
		//var_dump($data_info_list);
	}
	
//    private function test()
//	{
//		$user_list  = array('name' => "name",'gender' => "gender",'phone' => "Phone");
//		$data_info  = array('ObjectType' => "fileType",
//		                    'DataID' => "data_id",
//		                    'RandomID' => "random_id",
//							'UserList' => $user_list);
//        $sub_json =  json_encode($data_info);
//        $json_content = array(
//         						"ContentType" => 1,
//                                "Content"     => $sub_json,
//        );
//        $json_input = json_encode($json_content);
//		$curlPost = $json_input;
//		//$header = 'Content-Type: application/json';
//		$header = array(
//      					'Content-Type: application/json',
//        );
//		$ch = curl_init(); //初始化一个CURL对象
//		$url = "http://192.168.0.28:12998/BPNPNetwork/Notify";
//		curl_setopt($ch, CURLOPT_URL, $url);
//		//设置你所需要抓取的URL
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
//		curl_setopt($ch, CURLOPT_TIMEOUT,1);    //1s
//		//设置curl参数，要求结果是否输出到屏幕上，为true的时候是不返回到网页中
//		curl_setopt($ch, CURLOPT_POST, 1);
//		
//		//post提交
//		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//		$data = curl_exec($ch);
//		//运行curl,请求网页。
//		//$res=json_decode($data,true);
//		//var_dump($res);
//		//var_dump($data);
//		echo $data;
//		curl_close($ch);
//	}
}
?>