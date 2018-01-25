<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends Bene_Controller
{
	const Search_My_RecData	= 100;
	const Search_My_Report		= 101;
	const Search_All_RecData	= 102;
	const Search_All_Report		= 103;
	const Search_GROUP_Report   = 104;
	const Search_GROUP_RecData  = 105;
	
	const List_Type_Normal		= 258;
	const List_Type_Profile		= 259;
	const List_Type_More		= 260;
	
	const Mange_User_List		= 300;
	const Mange_User_Create	    = 301;
	const Mange_User_Profile	= 302;
	const Mange_User_Edit		= 303;
	const Mange_User_Lock		= 304;
	
	const Mange_Group_List      = 350;
	const Mange_Group_Create    = 351;
	const Mange_Group_Edit      = 352;
	const Mange_Get_Group_List  = 353;
	
	const Operate_Get_AlyGroup  = 400;
	const Operate_Bind_AlyGroup = 401;
	
	const Mange_Hospital_List   = 450;
	const Mange_Hospital_Edit   = 451;
	const Mange_Hospital_Create = 452;
	const Mange_Hospital_Show   = 453;
	
	public $menu_list	= FALSE;
	public function __construct()
	{
		parent::__construct();
		$this->rt_name = 'user';
		
		// check user login
		$this->doPageIllegal();
		
		$this->load->model('Bene_User_Model');
		$this->load->model('Bene_Menu_Model');
		$this->load->model('Bene_UserView_Model');
		$this->load->model('Bene_UserGroup_Model');
		$this->load->model('Bene_Hospital_Model');
		$this->load->model('Bene_HospitalView_Model');
		$this->load->model('Bene_User_Binding_Model');
		
		$this->lang->load('Bene_User', $this->config->item('language'));
		$this->lang->load('Bene_Menu', $this->config->item('language'));
		$this->lang->load('Bene_DataInfo', $this->config->item('language'));
		$this->lang->load('Bene_CommonView', $this->config->item('language'));
		
		// prepare user group menu
		$this->prepare_user_group_menu();
	}
	
	private function prepare_user_group_menu()
	{
		// load user group menu list
		$user_group_menu_key_list = array();
		$this->Bene_Menu_Model->get_menu_list($user_group_menu_key_list, $this->account->GroupID);
		array_unique($user_group_menu_key_list);
		
		// load all user menu list
		$user_all_menu_list = & $this->lang->line('user_menu');
		
		$this->menu_list = array();
		$menu_list = & $this->menu_list;
		foreach($user_group_menu_key_list as $menu_key)
		{
			if( array_key_exists($menu_key, $user_all_menu_list) )
				$menu_list[$menu_key] = $user_all_menu_list[$menu_key];
		}
		asort($menu_list);
	}
	
	public function index()
	{
		$menu_list = & $this->menu_list;
		if( (! is_array($menu_list)) or (count($menu_list) <= 0) ) Helper_ShowErrorPage(REQ_UNKNOWN, FALSE);
		$menu = current($menu_list);
		list($menu_index, $menu_label, $menu_link) = explode('|', $menu);
		header("Location: $menu_link");
	}
	
	public function repeal_rec_data()
	{
		$req_params = array('RepealType', 'ID');
		$bvalid = Helper_CheckParamExist( $req_params, Req_Mode_GET );
		if( ! $bvalid ) { echo 'false'; exit(0); }
		
		$data_id = $_GET['ID'];
		if( ! is_numeric($data_id) ) { echo 'false'; exit(0); }
		$data_id = intval($_GET['ID']);
		
		$this->load->model('Bene_RepealOperation_Model');
		
		$repeal_type = FALSE;
		switch($_GET['RepealType'])
		{
			case 'Upload': $repeal_type = Bene_RepealOperation_Model::Repeal_Type_DeleteRecData; break;
			case 'Diagnosing': $repeal_type = Bene_RepealOperation_Model::Repeal_Type_GiveUpDiagnostic; break;
			case 'Diagnosed': $repeal_type = Bene_RepealOperation_Model::Repeal_Type_RetreatMyReport; break;
			case 'Retrieved': $repeal_type = Bene_RepealOperation_Model::Repeal_Type_SendBackReport; break;
			case 'Lock': $repeal_type = Bene_RepealOperation_Model::Repeal_Type_GiveUpDiagnosticAndLock; break;
			case 'Redownload':$repeal_type = Bene_RepealOperation_Model::Repeal_Type_RedownloadReport; break;
			default:  echo 'false'; exit(0);
		}
		
		$bsuccess = $this->Bene_RepealOperation_Model->do_rec_data_repeal($data_id, $this->account->ID, $this->account->GroupID, $this->account->HospitalID, $repeal_type, TRUE);
		if( $bsuccess ) echo 'true';
		else echo 'false';
		exit(0);
	}
	
	public function my_rec_data()
	{
		$this->rt_method = "my_rec_data";
		
		$data_object_type = Rec_Object_Data;
		if( isset($_GET['ListObject']) and $_GET['ListObject'] == 'report' ) $data_object_type = Rec_Object_Report;
		
		// check method  privilege
		if($data_object_type == Rec_Object_Data) $this->check_method_privilege(ACTION_LIST, OBJ_RecData, SCOPE_MY);
		else $this->check_method_privilege(ACTION_LIST, OBJ_Report, SCOPE_MY);
		
		// decide title and menu key
		$title_key = 'title_my_rec_data'; $menu_key = 'menu_my_rec_data'; $ListObject = 'data';
		if($data_object_type == Rec_Object_Report) { $title_key = 'title_my_report'; $menu_key = 'menu_my_report'; $ListObject = 'report'; }
		$this->rt_title = $this->lang->line($title_key);
		
		// check list type
		$list_status = FALSE; $profile_data = FALSE; $BackURL = FALSE;
		$list_type = self::List_Type_Normal;
		if( isset($_GET['ListType']) )
		{
			if( $_GET['ListType'] == 'More' and isset($_GET['ListStatus']) )
			{
				$this->load->model('Bene_Utility_Model');
				switch($data_object_type)
				{
					case Rec_Object_Data: $list_status = $this->Bene_Utility_Model->check_rec_data_status_valid($_GET['ListStatus']); break;
					default: $list_status = $this->Bene_Utility_Model->check_report_status_valid($_GET['ListStatus']); break;
				}
				if( $list_status !== FALSE) $list_type = self::List_Type_More;
			}
			else if( $_GET['ListType'] == 'Profile' and isset($_GET['ID']) )
			{
				$profile_id = $_GET['ID'];
				if( is_numeric($profile_id) and $profile_id != -1 )
				{
					$this->load->model('Bene_DataInfo_Model');
					$profile_data = $this->Bene_DataInfo_Model->load_owners_data_info_by_id($profile_id, $this->account->ID, $data_object_type);
					if( $profile_data != FALSE )
					{
						$data_list = array($profile_data);
						$this->load->model('Bene_Utility_Model');
						$this->Bene_Utility_Model->parse_data_info_to_client($data_list, $data_object_type, ClientType_WebBrowser);
						$list_type = self::List_Type_Profile;
						if( isset($_GET['BackURL']) ) $BackURL = urldecode($_GET['BackURL']);
						else $BackURL = "/user/my_rec_data?ListObject=$ListObject";
					}
				}
			}
		}
		
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js') );
		$this->Bene_UserView_Model->load_user_menu($menu_key);
		
		switch($list_type)
		{
			case self::List_Type_More:
				$begin=''; $end='';
				if( isset($_GET['period']) )
				{
					$this->load->helper('Bene_DateTime');
					$period = Helper_ComputeBeginAndEnd($_GET['period']);
					if( $period !== FALSE ) extract($period);
				}
				
				$period_type = 'SubmitTime';
				$user_id = -1; $dgs_user_id = -1;
				if($data_object_type == Rec_Object_Data) $user_id = $this->account->ID;
				else { $dgs_user_id = $this->account->ID; $period_type = 'DiagnosingTime'; }
					
				$data_list =array();
				$this->load->library('Bene_Pager');
				$this->bene_pager->setRecPerPage(20);
				$this->load->model('Bene_DataInfo_Model');
				$this->Bene_DataInfo_Model->get_data_info_list($data_list, -1, $user_id, -1, -1, $list_status, SuplStatus_NULL, $dgs_user_id, -1, $period_type, $begin, $end, array($period_type => 'DESC'), '*', $this->bene_pager);
					
				$status_list	= & $this->lang->line('data_status');
				$list_title = $status_list[$list_status];
				$ListStatus = & $_GET['ListStatus'];
				$this->Bene_UserView_Model->load_date_group("/user/my_rec_data?ListObject=$ListObject&ListType=More&ListStatus=$ListStatus");
				$BackURL = "/user/my_rec_data?ListObject=$ListObject&ListType=More&ListStatus=$ListStatus";
				if( isset($_GET['period']) ) $BackURL .= ("&period=".$_GET['period']);
				$BackURL = urlencode($BackURL);
				$this->Bene_UserView_Model->load_list_rec_data_html($data_list, "/user/my_rec_data?ListObject=$ListObject&ListType=Profile&BackURL=$BackURL", $this->bene_pager, $data_object_type, $list_title);
				break;
			case self::List_Type_Profile:
				$current_referee = "/user/my_rec_data?ListObject=$ListObject&ListType=Profile&ID=$profile_data->ID&BackURL=".urlencode($BackURL);
				$this->Bene_UserView_Model->load_profile_rec_data_html($profile_data, $data_object_type, $current_referee, $BackURL);
				break;
			default:
				$this->Bene_UserView_Model->load_my_rec_data_html($data_object_type);
				break;
		}
		
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function search_rec_data()
	{
		$this->rt_method = "search_rec_data";
		
		$search_mode = 'my';
		$search_object = 'data';
		
		if( isset($_GET['SearchMode']) ) $search_mode = $_GET['SearchMode'];
		if( isset($_GET['SearchObject']) ) $search_object = $_GET['SearchObject'];
		
		$search_mode_array = array( 'my' => SCOPE_MY, 'all' => SCOPE_ALL ,'group' => SCOPE_GROUP);
		$search_object_array = array( 'data' => OBJ_RecData, 'report' => OBJ_Report);
		
		if( ! array_key_exists($search_mode, $search_mode_array) ) $search_mode = 'my';
		if( ! array_key_exists($search_object, $search_object_array) ) $search_object = 'data';
		
		// check method  privilege
		$this->check_method_privilege(ACTION_LIST, $search_object_array[$search_object], $search_mode_array[$search_mode]);
		
		$search_type = -1;
		if($search_mode == 'my' and $search_object =='data') $search_type = self::Search_My_RecData;
		else if($search_mode == 'my' and $search_object =='report') $search_type = self::Search_My_Report;
		else if($search_mode == 'all' and $search_object =='data') $search_type = self::Search_All_RecData;
		else if($search_mode == 'group' and $search_object =='report') $search_type = self::Search_GROUP_Report;
		else if($search_mode == 'group' and $search_object =='data') $search_type = self::Search_GROUP_RecData;
		else $search_type = self::Search_All_Report;
		
		$req_params = FALSE; $menu_key = FALSE; $title_key = FALSE;
		switch($search_type)
		{
			case self::Search_My_RecData:
				$req_params = array('DataID', 'PatientID', 'PatientName', 'PatientGender', 'DataTypeID', 'DataClinic', 'DataInfo', 'DGSHospitalID', 'DGSResult', 'DataStatus', 'AcqFrom', 'AcqTo', 'UploadFrom', 'UploadTo');
				$menu_key = 'menu_search_rec_data';
				$title_key = 'title_search_my_record';
				break;
			case self::Search_My_Report:
				$req_params = array('DataID', 'PatientID', 'PatientName', 'PatientGender', 'DataTypeID', 'DataClinic', 'DataInfo', 'UploadHospitalID', 'DGSResult', 'DataStatus', 'StatusFrom', 'StatusTo');
				$menu_key = 'menu_search_report';
				$title_key = 'title_search_my_report';
				break;
			case self::Search_All_RecData:
				$req_params = array('DataID', 'PatientID', 'PatientName', 'PatientGender', 'DataTypeID', 'DataClinic', 'DataInfo', 'UploadUserID', 'DGSHospitalID', 'DGSResult', 'DataStatus', 'AcqFrom', 'AcqTo', 'UploadFrom', 'UploadTo');
				$menu_key = 'menu_manage_rec_data';
				$title_key = 'title_manage_rec_data';
				break;
			case self::Search_GROUP_Report:
				$req_params = array('DataID', 'PatientID', 'PatientName', 'PatientGender', 'DataTypeID', 'DataClinic', 'DataInfo', 'DGSUserID', 'UploadHospitalID', 'DGSResult', 'DataStatus', 'StatusFrom', 'StatusTo');
				$menu_key = 'menu_group_report';
				$title_key = 'title_search_group_report';
				break;
			case self::Search_GROUP_RecData:
				$req_params = array('DataID', 'PatientID', 'PatientName', 'PatientGender', 'DataTypeID', 'DataClinic', 'DataInfo', 'UploadUserID', 'DGSHospitalID', 'DGSResult', 'DataStatus', 'AcqFrom', 'AcqTo', 'UploadFrom', 'UploadTo');
				$menu_key = 'menu_group_rec_data';
				$title_key = 'title_search_group_record';
				break;
			default:
				$req_params = array('DataID', 'PatientID', 'PatientName', 'PatientGender', 'DataTypeID', 'DataClinic', 'DataInfo', 'DGSUserID', 'UploadHospitalID', 'DGSResult', 'DataStatus', 'StatusFrom', 'StatusTo');
				$menu_key = 'menu_manage_report';
				$title_key = 'title_manage_report';
				break;
		}
		$this->rt_title = $this->lang->line($title_key);
		
		// check params
		$bSearch = FALSE;
		$bSearch = Helper_CheckParamExist( $req_params, Req_Mode_GET );
   
		// load header and menu
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js','jquery.ui.datepicker-zh-CN.js','sorttable.js') );
		$this->Bene_UserView_Model->load_user_menu($menu_key);
		if( $bSearch )
		{
			$like_array = array();
			$where_array = array();
			$where_in_array	= array();
			$order_by_array = array();
			
			$like_field_list = array('DataID', 'PatientID', 'PatientName', 'DataClinic', 'DataInfo', 'DGSResult');
			Helper_FillDbFieldLikeArray($like_array, $like_field_list, Req_Mode_GET);
			
			$gender = $_GET['PatientGender'];
			$gender_array = array(Gender_Male, Gender_Female, Gender_Unknown);
			if( (is_numeric($gender)) and (in_array($gender, $gender_array)) ) $where_array['PatientGender'] = $gender;
			
			$data_type_id = $_GET['DataTypeID'];
			if( (is_numeric($data_type_id)) and $data_type_id != -1) $where_array['DataTypeID'] = $data_type_id;
			
			$hospital_id = -1;
			$user_field = FALSE;
			$status_array = FALSE;
			if( ($search_type === self::Search_My_RecData) or ($search_type === self::Search_All_RecData) or ($search_type === self::Search_GROUP_RecData) )
			{
				$hospital_id = $_GET['DGSHospitalID'];
				$user_field = 'DGSUserID';
				$status_array = array(Data_Uploading, Data_Uploaded, Data_Diagnosing, Data_Diagnosed, Data_Retrieved);
				
				if( Helper_CheckDateTextValid($_GET['AcqFrom'] ) ) $where_array['DataAcqTime >'] = $_GET['AcqFrom'];
				if( Helper_CheckDateTextValid($_GET['AcqTo'] ) ) $where_array['DataAcqTime <'] = $_GET['AcqTo']." 23:59:59";
				
				if( Helper_CheckDateTextValid($_GET['UploadFrom'] ) ) $where_array['SubmitTime >'] = $_GET['UploadFrom'];
				if( Helper_CheckDateTextValid($_GET['UploadTo'] ) ) $where_array['SubmitTime <'] = $_GET['UploadTo']." 23:59:59";
				
				$order_by_array['SubmitTime'] = 'DESC';
			}
			else
			{
				$hospital_id = $_GET['UploadHospitalID'];
				$user_field = 'UserID';
				$status_array = array(Data_Diagnosing, Data_Diagnosed, Data_Retrieved);
				
				if( Helper_CheckDateTextValid($_GET['StatusFrom'] ) ) $where_array['DiagnosingTime >'] = $_GET['StatusFrom'];
				if( Helper_CheckDateTextValid($_GET['StatusTo'] ) ) $where_array['DiagnosingTime <'] = $_GET['StatusTo']." 23:59:59";
				
				$order_by_array['DiagnosingTime'] = 'DESC';
			}
			if( (is_numeric($hospital_id)) and $hospital_id != -1 )
			{
				$where_in_array[$user_field] = array();
				$user_id_array = & $where_in_array[$user_field];
					
				$group_ids = -1;
				$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($user_id_array, $group_ids, $hospital_id);
				if( count($user_id_array) <= 0 ) unset($where_in_array[$user_field]);
			}
			$status = $_GET['DataStatus'];
			if( (is_numeric($status)) and (in_array($status, $status_array)) ) $where_array['Status'] = $status;
			
			$list_object = FALSE;
			switch($search_type)
			{
				case self::Search_My_RecData:
					$list_object = Rec_Object_Data;
					$where_array['UserID'] = $this->account->ID;
					break;
				case self::Search_My_Report:
					$list_object = Rec_Object_Report;
					$where_array['DGSUserID'] = $this->account->ID;
					break;
				case self::Search_All_RecData:
					$list_object = Rec_Object_Data;
					$user_id = $_GET['UploadUserID'];
					if( (is_numeric($user_id)) and $user_id != -1 ) $where_array['UserID'] = $user_id;
					$where_in_array['UserID'] = array();
					$user_id_array = & $where_in_array['UserID'];
					$this->load->model('Bene_User_Model');
					$group_ids = -1;
					$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($user_id_array, $group_ids, $this->account->HospitalID);
					break;
				case self::Search_GROUP_Report:
					$list_object = Rec_Object_Report;
					$user_id = $_GET['DGSUserID'];
					if( (is_numeric($user_id)) and $user_id != -1 ) $where_array['DGSUserID'] = $user_id;
					$where_in_array['DGSUserID'] = array();
					$group_user_list=array();
					$group_admin_id = $this->account->ID;
					$this->Bene_UserGroup_Model->get_user_group_by_admin_ids($group_admin_id,$group_user_list);
					$group_user_ids = array();
					foreach ($group_user_list as $user)
					{
						$group_user_ids[] = $user->UserID;
					}
					$where_in_array['DGSUserID'] = $group_user_ids;
					if(count($group_user_list) == 0)
						$where_array['DGSUserID'] = 0;
					break;
				case self::Search_GROUP_RecData:
					$list_object = Rec_Object_Data;
					$user_id = $_GET['UploadUserID'];
					if( (is_numeric($user_id)) and $user_id != -1 ) $where_array['UserID'] = $user_id;
					$where_in_array['UserID'] = array();
					$group_user_list=array();
					$group_admin_id = $this->account->ID;
					$this->Bene_UserGroup_Model->get_user_group_by_admin_ids($group_admin_id,$group_user_list);
					$group_user_ids = array();
					foreach ($group_user_list as $user)
					{
						$group_user_ids[] = $user->UserID;
					}
					$where_in_array['UserID'] = $group_user_ids;
					if(count($group_user_list) == 0)
						$where_array['UserID'] = 0;
					break;
				default:
					$list_object = Rec_Object_Report;
					$user_id = $_GET['DGSUserID'];
					if( (is_numeric($user_id)) and $user_id != -1 ) $where_array['DGSUserID'] = $user_id;
					$where_in_array['DGSUserID'] = array();
					$user_id_array = & $where_in_array['DGSUserID'];
					$this->load->model('Bene_User_Model');
					$group_ids = -1;
					$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($user_id_array, $group_ids, $this->account->HospitalID);
					break;
			}
			
			$data_list = array();
			$this->load->library('Bene_Pager');
			$this->load->model('Bene_DataInfo_Model');
			//$_select = 'DataID,'
			$this->Bene_DataInfo_Model->search_data_info_list($data_list, $like_array, $where_array, $where_in_array, $order_by_array, '*', $this->bene_pager);
			$this->load->helper('Bene_Utility');
			$search_list =array();
			$this->Bene_DataInfo_Model->search_data_info_list($search_list, $like_array, $where_array, $where_in_array, $order_by_array, '*');
			session_start();
			//将查询条件保存到session中
            $_SESSION["search_para_list"]=array(
            							        'like_array'      => $like_array,
                                                'where_array'     => $where_array,
                                                'where_in_array'  => $where_in_array,
                                                'order_by_array'  => $order_by_array
            );
			session_write_close();
			$BackURL = $_SERVER['PHP_SELF'].'?'.$_SERVER["QUERY_STRING"];
			$BackURL = urlencode($BackURL);
			$profile_referee = "/user/search_rec_data?SearchMode=$search_mode&SearchObject=$search_object&SearchOp=Profile&BackURL=$BackURL";
			$this->Bene_UserView_Model->load_list_rec_data_html($data_list, $profile_referee, $this->bene_pager, $list_object, $this->lang->line('search_result'), true);
		}
		else 
		{
			$bprofile = FALSE; $profile_data = FALSE; $BackURL = FALSE;
			if(isset($_GET['SearchOp']) and $_GET['SearchOp'] == 'Profile' and isset($_GET['ID']) )
			{
				$profile_id = $_GET['ID'];
				if( is_numeric($profile_id) and $profile_id != -1 )
				{
					$this->load->model('Bene_DataInfo_Model');
					$data_object_type = Rec_Object_Data;
					if( $search_object == 'report' ) $data_object_type = Rec_Object_Report;
					if( $search_type == self::Search_My_RecData or $search_type == self::Search_My_Report )
						$profile_data = $this->Bene_DataInfo_Model->load_owners_data_info_by_id($profile_id, $this->account->ID, $data_object_type);
					else
					{
						$privilege_list = array();
						$group_ids_array = array();
						$this->Bene_GroupPriv_Model->get_group_privileges($privilege_list, $this->account->GroupID, ACTION_MANAGE, OBJ_User, SCOPE_MY, 'ActionObjectType');
						foreach($privilege_list as $privilege) $group_ids_array[] = $privilege->ActionObjectType;
						unset($privilege_list);
						array_unique($group_ids_array);
							
						$user_ids = array();
						$this->load->model('Bene_User_Model');
						$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($user_ids, $group_ids_array, $this->account->HospitalID);
						unset($group_ids_array);
						$profile_data = $this->Bene_DataInfo_Model->load_owners_data_info_by_id($profile_id, $user_ids, $data_object_type);
					}
					
					if( $profile_data != FALSE )
					{
						$data_list = array($profile_data);
						$this->load->model('Bene_Utility_Model');
						$this->Bene_Utility_Model->parse_data_info_to_client($data_list, $search_object, ClientType_WebBrowser);
						$bprofile = TRUE;
						if( isset($_GET['BackURL']) ) $BackURL = urldecode($_GET['BackURL']);
						else $BackURL = "/user/my_rec_data?SearchMode=$search_mode&SearchObject=$search_object";
					}
				}
			}
			if($bprofile)
			{
				$data_object_type = Rec_Object_Data;
				if( $search_object == 'report' ) $data_object_type = Rec_Object_Report;
				$current_referee = $_SERVER['PHP_SELF'].'?'.$_SERVER["QUERY_STRING"];
				$bmanage = ( $search_type == self::Search_All_Report );
				$this->Bene_UserView_Model->load_profile_rec_data_html($profile_data, $data_object_type, $current_referee, $BackURL, $bmanage);
			}
			else $this->Bene_UserView_Model->load_search_record_entry_html($search_type);
		}
		
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function manage_user()
	{
		$this->rt_method = "manage_user";
		$this->rt_title = & $this->lang->line('title_manage_user');
		
		// check method  privilege
		$this->check_method_privilege(ACTION_MANAGE, OBJ_User, SCOPE_MY);
			
			// get manage user groups and users
		$account = $this->account;
		$privilege_list = array();
		$this->Bene_GroupPriv_Model->get_group_privileges($privilege_list, $account->GroupID, ACTION_MANAGE, OBJ_User, SCOPE_MY, 'ActionObjectType');
		$manage_group_id_names = array();
		foreach($privilege_list as $privilege) $manage_group_id_names[] = $privilege->ActionObjectType;
		unset($privilege_list);
		$manage_user_ids = array();
		$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($manage_user_ids, $manage_group_id_names, $account->HospitalID);
		$group_list = array();
		$this->load->model('Bene_Group_Model');
		$this->Bene_Group_Model->load_groups_info_by_ids($group_list, $manage_group_id_names, 'ID, Name');
		$manage_group_id_names = array();
		foreach($group_list as $group) $manage_group_id_names[$group->ID] = $group->Name;
		unset($group_list);
		
		// check manage type
		$manage_type = self::Mange_User_List;
		$ManageType = 'List';
		if( isset($_GET['ManageType']) ) $ManageType = $_GET['ManageType'];
		switch($ManageType)
		{
			case 'Profile': $manage_type = self::Mange_User_Profile; break;
			case 'Create': $manage_type = self::Mange_User_Create; break;
			case 'Edit': $manage_type = self::Mange_User_Edit; break;
			case 'Lock': $manage_type = self::Mange_User_Lock; break;
			default: break;
		}
		
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css','jquery.multiselect.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js','jquery.multiselect.min.js') );
		$this->Bene_UserView_Model->load_user_menu('menu_manage_user');
		
		$gender_list = & $this->lang->line('gender');
		$user_status_list = & $this->lang->line('user_status');
		$status_operation_list = & $this->lang->line('user_status_operation');
		
		
		$this->load->model('Bene_User_Model');
		$dgs_link_user_list = array ();
		if ($account->GroupID == UG_Aly_Admin)
		{
			$this->Bene_User_Model->get_dgs_hospital_linked_users ( $account->HospitalID, $dgs_link_user_list );
		}
		//SELECT bene_user.*, bene_group.Name AS GroupName FROM bene_user JOIN bene_group ON bene_user.GroupID = bene_group.ID JOIN bene_grouppriv ON bene_group.ID = bene_grouppriv.ActionObjectType WHERE bene_grouppriv.GroupID = 3 AND bene_grouppriv.ActionMethod = 'manage'AND bene_grouppriv.ActionObject = 'user' AND bene_grouppriv.ActionScope = 'my'
		$this->lang->load('Bene_User', $this->config->item('language'));
		$role_list					= & $this->lang->line('role_list');
		switch($manage_type)
		{
			case self::Mange_User_Profile:
				if( (! isset($_GET['ID'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$user_id = & $_GET['ID'];
				if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				if( ! in_array($user_id, $manage_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$user = $this->Bene_User_Model->load_user_info_by_id($user_id);
				if($user === FALSE) Helper_ShowErrorPage( DB_ERROR, FALSE );
				//$user->GroupName = $manage_group_id_names[$user->GroupID];
				$user->GroupName = $role_list[$user->GroupID];
				$user->HospitalName = $account->HospitalName;
				$user->GenderText = $gender_list[$user->Gender];
				$user->StatusText = $user_status_list[$user->Locked];
				$this->Bene_UserView_Model->load_profile_user_html($user, 'all');
				break;
			case self::Mange_User_Create:
				$param_list = array('HospitalID', 'GroupID', 'UserAccount', 'UserName', 'UserPwd', 'ReUserPwd', 'UserGender', 'UserAge', 'UserInfo');
				if( Helper_CheckParamExist($param_list, Req_Mode_POST) )
				{
					if( $_POST['HospitalID'] != $account->HospitalID ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! array_key_exists($_POST['GroupID'], $manage_group_id_names) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$this->load->library('form_validation');
					$this->form_validation->set_rules('UserAccount', $this->lang->line('account_label'), 'trim|required|min_length[3]|max_length[32]|alpha_dash|is_unique[Bene_User.Account]');
					$this->form_validation->set_rules('UserName', $this->lang->line('name_label'), 'trim|required|min_length[1]|max_length[21]');
					$this->form_validation->set_rules('UserPwd', $this->lang->line('pwd_label'), 'trim|required|alpha_numeric|matches[ReUserPwd]|md5');
					$this->form_validation->set_rules('ReUserPwd', $this->lang->line('re_pwd_label'), 'trim|required');
					$ftp_user_name = $_POST['UserAccount'];
					if( ($this->form_validation->run() === TRUE) and ($this->Bene_User_Model->insert_new_user() ===TRUE) )
					{	
						//创建使用FTP上传的用户对于的数据目录
						if( !empty($account->Reserve1) && ($account->Reserve1&UserFlag_FtpUpload)!=0 && $account->GroupID==UG_Opt_Admin)
						{
							$ftp_dir = trim(FTP_ROOT,'/').'/';
							if(is_dir($ftp_dir) === FALSE)
								mkdir($ftp_dir, 0777);
	                        
						    $ftp_dir .= ($ftp_user_name.'/');
							if(is_dir($ftp_dir) === FALSE)
						    	mkdir($ftp_dir, 0777);
						}
						header('Location: /user/manage_user?ManageType=List');
					}
				}
				$hospital_list = array($account->HospitalID => $account->HospitalName);
				$this->Bene_UserView_Model->load_create_user_html($hospital_list, $manage_group_id_names, $dgs_link_user_list, null, 'create');
				break;
			case self::Mange_User_Edit:
				$param_list = array('UserID', 'HospitalID', 'GroupID', 'UserAccount', 'UserName', 'UserPwd', 'ReUserPwd', 'UserGender', 'UserAge', 'UserInfo');
				if( Helper_CheckParamExist($param_list, Req_Mode_POST) )
				{
					if( $_POST['HospitalID'] != $account->HospitalID ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! array_key_exists($_POST['GroupID'], $manage_group_id_names) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$user_id = $_POST['UserID'];
					if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! in_array($user_id, $manage_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$user = $this->Bene_User_Model->load_user_info_by_id($user_id);
					if($user === FALSE) Helper_ShowErrorPage( REQ_INVALID, FALSE );
					
					$this->load->library('form_validation');
					if($user->Account != $_POST['UserAccount'])
						$this->form_validation->set_rules('UserAccount', $this->lang->line('account_label'), 'trim|required|min_length[3]|max_length[32]|is_unique[Bene_User.Account]');
					$this->form_validation->set_rules('UserName', $this->lang->line('name_label'), 'trim|required|min_length[1]|max_length[21]');
					if( ($_POST['UserPwd'] != '') or ($_POST['ReUserPwd'] != '') )
					{
						$this->form_validation->set_rules('UserPwd', $this->lang->line('pwd_label'), 'trim|required|alpha_numeric|matches[ReUserPwd]|md5');
						$this->form_validation->set_rules('ReUserPwd', $this->lang->line('re_pwd_label'), 'trim|required');
					}
					if( ($this->form_validation->run() === TRUE) and ($this->Bene_User_Model->update_user_info($_POST['UserID'], Update_Type_Edit) ===TRUE) )
						header('Location: /user/manage_user?ManageType=List');
				}
				else
				{
					if( (! isset($_GET['ID'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					$user_id = & $_GET['ID'];
					if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! in_array($user_id, $manage_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$user = $this->Bene_User_Model->load_user_info_by_id($user_id);
					if($user === FALSE) Helper_ShowErrorPage( REQ_INVALID, FALSE );
				}
				$hospital_list = array($account->HospitalID => $account->HospitalName);
				$this->Bene_UserView_Model->load_create_user_html($hospital_list, $manage_group_id_names, $dgs_link_user_list, $user, 'edit');
				break;
			case self::Mange_User_Lock:
				if( (! isset($_GET['ID'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$user_id = & $_GET['ID'];
				if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				if( ! in_array($user_id, $manage_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				
				if( (! isset($_GET['Locked'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$locked = $_GET['Locked'];
				if( (! is_numeric($locked)) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				if($locked == 0) $locked = 1;
				else $locked = 0;
				$this->Bene_User_Model->lock_user($user_id, $locked);
				header('Location: /user/manage_user?ManageType=List');
				break;
			default: // list
				$user_list = array();
				$this->load->library('Bene_Pager');
				$this->Bene_User_Model->load_users_info_by_ids($user_list, $manage_user_ids, '*', array('CreateTime' => 'DESC'), $this->bene_pager);
				foreach($user_list as $user)
				{
					$user->GenderText = $gender_list[$user->Gender];
					//$user->GroupName = $manage_group_id_names[$user->GroupID];
					$user->GroupName = $role_list[$user->GroupID];
					$user->StatusText = $user_status_list[$user->Locked];
					$user->OperationText = $status_operation_list[$user->Locked];
				}
				$this->Bene_UserView_Model->load_browser_user_html($user_list, $this->bene_pager, 'my');
				break;
		}
		
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function manage_group()
	{
		$this->rt_method = "manage_group";
		$this->rt_title = & $this->lang->line('title_manage_group');
		
		// check method  privilege
		$this->check_method_privilege(ACTION_MANAGE, OBJ_Group, SCOPE_MY);
		
        //get group_admin_list
		$account = $this->account;
		$privilege_list = array();
		$this->Bene_GroupPriv_Model->get_group_privileges($privilege_list, $account->GroupID, ACTION_MANAGE, OBJ_Group, SCOPE_MY, 'ActionObjectType');
		$manage_group_id_names = array();
		foreach($privilege_list as $privilege) $manage_group_id_names[] = $privilege->ActionObjectType;
		unset($privilege_list);
		$group_user_ids = array();
		$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($group_user_ids, $manage_group_id_names, $account->HospitalID);
		$group_list = array();
		$this->load->model('Bene_Group_Model');
		$this->Bene_Group_Model->load_groups_info_by_ids($group_list, $manage_group_id_names, 'ID, Name');
		$manage_group_id_names = array();
		foreach($group_list as $group) $manage_group_id_names[$group->ID] = $group->Name;
		unset($group_list);
	    
		
		//get aly_user_list
		$privilege_list = array();
		$this->Bene_GroupPriv_Model->get_group_privileges($privilege_list, $account->GroupID, ACTION_MANAGE, OBJ_User, SCOPE_MY, 'ActionObjectType');
		$aly_group_id_names = array();
		foreach($privilege_list as $privilege) $aly_group_id_names[] = $privilege->ActionObjectType;
		unset($privilege_list);
		$aly_user_ids = array();
		$this->Bene_User_Model->get_user_ids_in_groups_and_hospitals($aly_user_ids, $aly_group_id_names, $account->HospitalID);
		$aly_user_list=array();
		$this->load->library('Bene_Pager');
		$this->Bene_User_Model->load_users_info_by_ids($aly_user_list, $aly_user_ids, '*', array('CreateTime' => 'DESC'), $this->bene_pager);
		
		// check manage type
		$manage_type = self::Mange_User_List;
		$ManageType = 'List';
		if( isset($_GET['ManageType']) ) $ManageType = $_GET['ManageType'];
		switch($ManageType)
		{
			case 'Profile': $manage_type = self::Mange_User_Profile; break;
			case 'Create': $manage_type = self::Mange_Group_Create; break;
			case 'EditGroup': $manage_type = self::Mange_Group_Edit; break;
			case 'Edit': $manage_type = self::Mange_User_Edit; break;
			case 'GetGroup': $manage_type = self::Mange_Get_Group_List; break;
			default: break;
		}
		
		if($manage_type!=self::Mange_Get_Group_List)
		{
			$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css','jquery.multiselect.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js','jquery.multiselect.min.js') );
			$this->Bene_UserView_Model->load_user_menu('menu_manage_group');
		}
		
		$gender_list = & $this->lang->line('gender');
		$user_status_list = & $this->lang->line('user_status');
		$status_operation_list = & $this->lang->line('user_status_operation');
		$this->lang->load('Bene_User', $this->config->item('language'));
		$role_list					= & $this->lang->line('role_list');
	    switch($manage_type)
		{
			case self::Mange_User_Profile:
				if( (! isset($_GET['ID'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$user_id = & $_GET['ID'];
				if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				if( ! in_array($user_id, $group_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$user = $this->Bene_User_Model->load_user_info_by_id($user_id);
				if($user === FALSE) Helper_ShowErrorPage( DB_ERROR, FALSE );
				//$user->GroupName = $manage_group_id_names[$user->GroupID];
				$user->GroupName = $role_list[$user->GroupID];
				$user->HospitalName = $account->HospitalName;
				$user->GenderText = $gender_list[$user->Gender];
				$user->StatusText = $user_status_list[$user->Locked];
				$this->Bene_UserView_Model->load_profile_user_html($user, 'all');
				break;
			case self::Mange_Group_Create:
				$param_list = array('HospitalID', 'GroupID', 'UserAccount', 'UserName', 'UserPwd', 'ReUserPwd', 'UserGender', 'UserAge', 'UserInfo');
				if( Helper_CheckParamExist($param_list, Req_Mode_POST) )
				{
					if( $_POST['HospitalID'] != $account->HospitalID ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! array_key_exists($_POST['GroupID'], $manage_group_id_names) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$this->load->library('form_validation');
					$this->form_validation->set_rules('UserAccount', $this->lang->line('account_label'), 'trim|required|min_length[3]|max_length[32]|is_unique[Bene_User.Account]');
					$this->form_validation->set_rules('UserName', $this->lang->line('name_label'), 'trim|required|min_length[1]|max_length[21]');
					$this->form_validation->set_rules('UserPwd', $this->lang->line('pwd_label'), 'trim|required|alpha_numeric|matches[ReUserPwd]|md5');
					$this->form_validation->set_rules('ReUserPwd', $this->lang->line('re_pwd_label'), 'trim|required');
					if( ($this->form_validation->run() === TRUE) and ($this->Bene_User_Model->insert_new_user() ===TRUE) )
						header('Location: /user/manage_group?ManageType=List');
				}
				$hospital_list = array($account->HospitalID => $account->HospitalName);
				$this->Bene_UserView_Model->load_create_user_html($hospital_list, $manage_group_id_names, $dgs_link_user_list, null, 'create', TRUE);
				break;
			case self::Mange_Get_Group_List:
				$group_user_list = array();
                $group_admin_id = isset($_GET['AdminID'])?$_GET['AdminID']:'';
                $str_group = $group_admin_id;
				$this->Bene_UserGroup_Model->get_user_group_by_admin_ids($group_admin_id,$group_user_list);
				foreach ($group_user_list as $u)
				{
					$str_group = $str_group.'|'.$u->UserID;
				}
				echo $str_group;
				break;
			case self::Mange_Group_Edit:
		        //var_dump($_POST);
				$group_admin_id = isset($_GET['AdminID'])?$_GET['AdminID']:'';
                $this->Bene_UserGroup_Model->delete_user_group_by_admin_id($group_admin_id);
                foreach ($aly_user_list as $aly_user)
                {
                	if(isset($_POST[$aly_user->Account]))
                	{
                		 $aly_user_id = isset($_POST[$aly_user->Account])?$_POST[$aly_user->Account]:'';
                         if($aly_user_id==$aly_user->ID)
                			$this->Bene_UserGroup_Model->insert_new_user_group($group_admin_id,$aly_user->ID);
                	}
                }
				break;
			case self::Mange_User_Edit:
				$param_list = array('UserID', 'HospitalID', 'GroupID', 'UserAccount', 'UserName', 'UserPwd', 'ReUserPwd', 'UserGender', 'UserAge', 'UserInfo');
				if( Helper_CheckParamExist($param_list, Req_Mode_POST) )
				{
					if( $_POST['HospitalID'] != $account->HospitalID ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! array_key_exists($_POST['GroupID'], $manage_group_id_names) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$user_id = $_POST['UserID'];
					if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! in_array($user_id, $group_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$user = $this->Bene_User_Model->load_user_info_by_id($user_id);
					if($user === FALSE) Helper_ShowErrorPage( REQ_INVALID, FALSE );
					
					$this->load->library('form_validation');
					if($user->Account != $_POST['UserAccount'])
						$this->form_validation->set_rules('UserAccount', $this->lang->line('account_label'), 'trim|required|min_length[3]|max_length[32]|is_unique[Bene_User.Account]');
					$this->form_validation->set_rules('UserName', $this->lang->line('name_label'), 'trim|required|min_length[1]|max_length[21]');
					if( ($_POST['UserPwd'] != '') or ($_POST['ReUserPwd'] != '') )
					{
						$this->form_validation->set_rules('UserPwd', $this->lang->line('pwd_label'), 'trim|required|alpha_numeric|matches[ReUserPwd]|md5');
						$this->form_validation->set_rules('ReUserPwd', $this->lang->line('re_pwd_label'), 'trim|required');
					}
					if( ($this->form_validation->run() === TRUE) and ($this->Bene_User_Model->update_user_info($_POST['UserID'], Update_Type_Edit) ===TRUE) )
						header('Location: /user/manage_group?ManageType=List');
				}
				else
				{
					if( (! isset($_GET['ID'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					$user_id = & $_GET['ID'];
					if( (! is_numeric($user_id)) or ($user_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					if( ! in_array($user_id, $group_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
					
					$user = $this->Bene_User_Model->load_user_info_by_id($user_id);
					if($user === FALSE) Helper_ShowErrorPage( REQ_INVALID, FALSE );
				}
				$hospital_list = array($account->HospitalID => $account->HospitalName);
				$this->Bene_UserView_Model->load_create_user_html($hospital_list, $manage_group_id_names, $dgs_link_user_list, $user, 'edit', TRUE);
				break;
			default: // list
				$group_admin_list = array();
				$this->Bene_User_Model->load_users_info_by_ids($group_admin_list, $group_user_ids, '*', array('CreateTime' => 'DESC'), $this->bene_pager);
				foreach($group_admin_list as $user)
				{
					$user->GenderText = $gender_list[$user->Gender];
					//$user->GroupName = $manage_group_id_names[$user->GroupID];
					$user->GroupName = $role_list[$user->GroupID];
					$user->StatusText = $user_status_list[$user->Locked];
					$group_user_list = array();
					$group_list = array();
					$this->Bene_UserGroup_Model->get_user_group_by_admin_ids($user->ID,$group_user_list);
					$list = array();
					foreach ($group_user_list as $u)
					{
						$list[] = $u->UserID;
					}
					$group_list[$user->ID] = $list;
				}
				$aly_user_list=array();
				$this->Bene_User_Model->load_users_info_by_ids($aly_user_list, $aly_user_ids, '*', array('CreateTime' => 'DESC'), $this->bene_pager);
				$this->Bene_UserView_Model->load_browser_group_html($group_admin_list,$aly_user_list, $this->bene_pager, 'my');
				break;
		}
		if($manage_type!=self::Mange_Get_Group_List)
		{
			$this->load->view('footer_copyright_html');
			$this->load->view('footer_lite_html');
		}
	}
	
	public function bind_group()
	{
		$this->rt_method = "bind_group";
		$this->check_method_privilege(ACTION_LIST, OBJ_RecData, SCOPE_GROUP);
		$account = $this->account;
		
	    // check operate_type
		$operate_type = self::Operate_Bind_AlyGroup;
		$OperateType = 'BindAlyGroup';
		if( isset($_GET['OperateType']) ) $OperateType = $_GET['OperateType'];
		switch($OperateType)
		{
			case 'BindAlyGroup': $operate_type = self::Operate_Bind_AlyGroup; break;
			case 'GetAlyGroup': $operate_type = self::Operate_Get_AlyGroup; break;
			default: break;
		}
		$group_admin_id = $account->ID;
		switch($operate_type)
		{
			case self::Operate_Bind_AlyGroup:
				//UG_Aly_Group_Admin
			    $aly_group_list = array();   //分析组列表
			    $group_ids = array();
			    $this->load->model('Bene_GroupPriv_Model');
			    $this->load->model('Bene_User_Model');
				$this->Bene_GroupPriv_Model->get_group_ids_with_privilege($group_ids, ACTION_LIST, OBJ_Report, SCOPE_GROUP);
			    $this->Bene_User_Model->get_users_info_in_groups_and_hospitals($aly_group_list, $group_ids, $account->DGSHospitalID, 'ID, Name, Account');

			    $aly_user_list = array();      //分析组组员列表
			    $group_user_list = array();    //操作组组员列表
                $this->Bene_UserGroup_Model->get_user_group_by_admin_ids($group_admin_id,$group_user_list);
                

	            foreach ($group_user_list as $opt_user)
	            {  			
	                $opt_user_id = $opt_user->UserID;
	                $this->Bene_User_Binding_Model->remove_binding_user(-1,$opt_user_id);		
	            }
                
                /////////////////////////////////////////////////////////////////
                foreach ($aly_group_list as $aly_group)
                {
                	$aly_group_admin_id = $aly_group->ID;
                	$this->Bene_UserGroup_Model->get_user_group_by_admin_ids($aly_group_admin_id,$aly_user_list);
                	foreach ($aly_user_list as $aly_user)
                    {
                    	$aly_user_id = $aly_user->UserID;
                    	//var_dump($_POST);
	                    if(isset($_POST[$aly_group->Account]))
	                	{
	                		//bind opt_user to aly_user
	                		foreach ($group_user_list as $opt_user)
	                		{
	                			
	                			$opt_user_id = $opt_user->UserID;
	                			//判断是否已经绑定，如果未绑定则插入新的绑定
	                			if($this->Bene_User_Binding_Model->get_binding_user_info($aly_user_id,$opt_user_id)===FALSE)
	                			{
	                				$this->Bene_User_Binding_Model->insert_binding_user($aly_user_id,$opt_user_id);
	                			}
	                		}
	                	}
                    }                	
                }
			    break;
			case self::Operate_Get_AlyGroup:
				$group_user_list = array();
				$aly_group_list = array();
				$this->Bene_UserGroup_Model->get_user_group_by_admin_ids($group_admin_id,$group_user_list);
				$list = array();
				foreach ($group_user_list as $u)
				{
					$list[] = $u->UserID;
				}
				//如果操作组用户为空
				if(count($list)==0) $list[] = -1;
				$this->db->select('*');
			    $this->db->from(TABLE_USER_BINDING);
		        $this->db->join(TABLE_USER_GROUP, 'Bene_User_Binding.UserID_A = Bene_User_Group.UserID');
		        $this->db->where_in('UserID_O', $list);
		        $query = $this->db->get();
		        $aly_group_list = $query->result();
		        $aly_ids = array();
		        foreach ($aly_group_list as $user)
		        {
		        	$aly_ids[] = $user->GroupAdminID;
		        }
		        $str_group = '';
		        $aly_ids = array_unique($aly_ids);
		        foreach ($aly_ids as $id)
		        {
		        	$str_group = $str_group.'|'.$id;
		        }
				echo $str_group;
			    break;
			default:
		        break;
		}
	}
	
	public function manage_hospital()
	{
		$this->rt_method = "manage_hospital";
		$this->rt_title = & $this->lang->line('title_manage_hospital');
		$this->check_method_privilege(ACTION_MANAGE, OBJ_Hospital, SCOPE_ALL);
		$account = $this->account;
		
		// check manage type
		$manage_type = self::Mange_Hospital_List;
		$ManageType = 'List';
		if( isset($_GET['ManageType']) ) $ManageType = $_GET['ManageType'];
		switch($ManageType)
		{
			case 'Edit': $manage_type = self::Mange_User_Edit; break;
			case 'Show': $manage_type = self::Mange_Hospital_Show; break;
			case 'List': $manage_type = self::Mange_Hospital_List; break;
			default: break;
		}
		
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css','jquery.multiselect.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js','jquery.multiselect.min.js') );
		$this->Bene_UserView_Model->load_user_menu('menu_manage_hospital');
		
		
		$this->load->model('Bene_User_Model');
		$dgs_link_user_list = array ();
		if ($account->GroupID == UG_Aly_Admin)
		{
			$this->Bene_User_Model->get_dgs_hospital_linked_users ( $account->HospitalID, $dgs_link_user_list );
		}
		//SELECT bene_user.*, bene_group.Name AS GroupName FROM bene_user JOIN bene_group ON bene_user.GroupID = bene_group.ID JOIN bene_grouppriv ON bene_group.ID = bene_grouppriv.ActionObjectType WHERE bene_grouppriv.GroupID = 3 AND bene_grouppriv.ActionMethod = 'manage'AND bene_grouppriv.ActionObject = 'user' AND bene_grouppriv.ActionScope = 'my'
		$this->lang->load('Bene_User', $this->config->item('language'));
		switch($manage_type)
		{
			case self::Mange_Hospital_Edit:
				break;
			case self::Mange_Hospital_Show:
				if( (! isset($_GET['ID'])) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				$hospital_id = & $_GET['ID'];
				if( (! is_numeric($hospital_id)) or ($hospital_id == -1) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				//if( ! in_array($user_id, $manage_user_ids) ) Helper_ShowErrorPage(REQ_INVALID, FALSE);
				
				$flag = $this->Bene_Hospital_Model->get_hospital_flag_by_id($hospital_id);
				if($flag!==FALSE)
				{
					$temp = HospitalFlag_ShowAlyDoctor;
					$flag = $temp ^ $flag;
					$this->Bene_Hospital_Model->set_hospital_flag_by_id($hospital_id,$flag);
				}
				header('Location: /user/manage_hospital?ManageType=List');
				break;
			default: // list
				$hospital_list = array();
				$this->load->library('Bene_Pager');
				$this->Bene_Hospital_Model->get_hospital_list($hospital_list, '*', array('ID' => 'ASC'), $this->bene_pager);
				foreach ($hospital_list as $hospital) 
				{
					if(($hospital->Flag&HospitalFlag_ShowAlyDoctor) == HospitalFlag_ShowAlyDoctor)
					{
						$hospital->OperationText = & $this->lang->line('Disable_lable');
						$hospital->OperationReferee = '/user/manage_hospital?ManageType=Show&ID='.$hospital->ID;
					}
					else
					{
						$hospital->OperationText = & $this->lang->line('Enable_lable');
						$hospital->OperationReferee = '/user/manage_hospital?ManageType=Show&ID='.$hospital->ID;
					}
				}
				$this->Bene_HospitalView_Model->load_browser_user_html($hospital_list, $this->bene_pager, 'all');
				break;
		}
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function manage_action()
	{
		Helper_ShowErrorPage(REQ_INVALID, FALSE);
	}
	
	public function my_action()
	{
		$this->rt_method = "my_action";
		$this->rt_title = $this->lang->line('title_my_action');
		
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js') );
		$this->Bene_UserView_Model->load_user_menu('menu_my_action');
		
		$this->Bene_UserView_Model->load_date_group("/user/my_action");
		
		$this->load->library('Bene_Pager');
		$this->load->model('Bene_Action_Model');
		
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function my_profile()
	{
		$this->rt_method = "my_profile";
		$this->rt_title = & $this->lang->line('title_my_profile');
		
		$bpwd_valid = TRUE; $profile_type = FALSE;
		if( isset($_GET['profile_type']) ) $profile_type = $_GET['profile_type'];
		
		if( $profile_type == 'edit' )
		{
			$param_array = array('UserName', 'UserOldPwd', 'UserNewPwd', 'ReUserPwd', 'UserGender', 'UserAge', 'UserInfo');
			$bparam_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
			if( $bparam_valid )
			{
				$this->load->library('form_validation');
				$this->form_validation->set_rules('UserName', $this->lang->line('name_label'), 'trim|required|min_length[1]|max_length[21]');
				if( ($_POST['UserOldPwd'] != '') or ($_POST['UserNewPwd'] != '') or ($_POST['ReUserPwd'] != '') )
				{
					$user = $this->Bene_User_Model->load_user_info_by_id($this->account->ID, 'Pwd');
					if( $user->Pwd != md5($_POST['UserOldPwd']) ) $bpwd_valid = FALSE;
					
					$this->form_validation->set_rules('UserNewPwd', $this->lang->line('new_pwd_label'), 'trim|required|alpha_numeric|matches[ReUserPwd]|md5');
					$this->form_validation->set_rules('ReUserPwd', $this->lang->line('re_pwd_label'), 'trim|required');
				}
				if( $bpwd_valid and ($this->form_validation->run() === TRUE) and ($this->Bene_User_Model->update_user_info($this->account->ID, Update_Type_Update) ===TRUE) )
					header('Location: /user/my_profile');
			}
		}
		
		$user = $this->Bene_User_Model->load_user_info_by_id($this->account->ID);
		if($user === FALSE) Helper_ShowErrorPage( DB_ERROR, FALSE );
		$user->GroupName = & $this->account->GroupName;
		$user->HospitalName = & $this->account->HospitalName;
		
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js','jquery.ui.datepicker-zh-CN.js') );
		$this->Bene_UserView_Model->load_user_menu('menu_my_profile');
		
		if($profile_type == 'edit') $this->Bene_UserView_Model->load_edit_my_html($user, $bpwd_valid);
		else
		{
			$gender_list = & $this->lang->line('gender');
			$user->GenderText = $gender_list[$user->Gender];
			$this->Bene_UserView_Model->load_profile_user_html($user);
		}
		
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function view_online_user()
	{
		$this->rt_method = "view_online_user";
		$this->rt_title = & $this->lang->line('title_view_online_user');
		// check method  privilege
		$this->check_method_privilege(ACTION_VIEW, OBJ_Online_User, SCOPE_ALL);
		
		$this->Bene_UserView_Model->load_user_header( array(), array('yui.css','style.css','zh-cn.css','colorbox.css','ui-lightness/jquery-ui-1.9.1.custom.css'),
				array(), array('jquery.js','jquery-ui.custom.min.js','bene-min.js') );
		$this->Bene_UserView_Model->load_user_menu('menu_manage_user');
		
		$gender_list = & $this->lang->line('gender');
		$group_list = array();
		$this->load->model('Bene_Group_Model');
		$this->Bene_Group_Model->get_group_list($group_list);
		//$manage_group_id_names = array();
		//foreach($group_list as $group) $manage_group_id_names[$group->ID] = $group->Name;
		$online_user_list = array();
		$this->load->library('Bene_Pager');
		$this->load->model('Bene_Online_Model');
		$this->Bene_Online_Model->get_online_user_info_list($online_user_list,array('UpdateTime' => 'DESC'),$this->bene_pager);
	    $this->lang->load('Bene_User', $this->config->item('language'));
		$role_list					= & $this->lang->line('role_list');
		foreach($online_user_list as $user)
		{
			$user->GenderText = $gender_list[$user->Gender];
			//$user->GroupName = $manage_group_id_names[$user->GroupID];
			$user->GroupName = $role_list[$user->GroupID];
		}
		$this->Bene_UserView_Model->load_view_online_user_html($online_user_list, $this->bene_pager);
		
		$this->load->view('footer_copyright_html');
		$this->load->view('footer_lite_html');
	}
	
	public function clear_action_reocrd()
	{
		$this->rt_method = "clear_action_reocrd";
		$this->check_method_privilege(ACTION_VIEW, OBJ_Online_User, SCOPE_ALL);
		$this->load->model('Bene_Action_Model');
		$this->Bene_Action_Model->clear_action_record();
		//header("Location: /user");
		$clear_success = & $this->lang->line('clear_success_lable');
		echo $clear_success;
	}
	
	public function export_to_excel()
	{
		$this->rt_method = "export_to_excel";
		$account = $this->account;
		$group_id = $account->GroupID;		
		switch ($group_id)
		{
			case UG_Aly_Group_Admin:
				$this->check_method_privilege(ACTION_LIST, OBJ_Report, SCOPE_GROUP);
				break;
			case UG_Aly_Admin:
				$this->check_method_privilege(ACTION_LIST, OBJ_Report, SCOPE_ALL);
				break;
			case UG_Aly:
				$this->check_method_privilege(ACTION_LIST, OBJ_Report, SCOPE_MY);
				break; 
			case UG_Opt_Group_Admin:
				$this->check_method_privilege(ACTION_LIST, OBJ_RecData, SCOPE_GROUP);
				break;
			case UG_Opt_Admin:
				$this->check_method_privilege(ACTION_LIST, OBJ_RecData, SCOPE_ALL);
				break;
			case UG_Opt:
				$this->check_method_privilege(ACTION_LIST, OBJ_RecData, SCOPE_MY);
				break;
			default:
				$this->check_method_privilege(ACTION_LIST, OBJ_Report, SCOPE_GROUP);
				break;
		}
		
		
		$fileName = & $this->lang->line('search_result_lable');
		$arr_field = array('UserID','DataTypeID','DataID','PatientID','PatientName','PatientGender','PatientAge','SubmitTime','DataClinic','Status', 'DiagnosedTime','DGSHospitalID','DGSUserID','DGSResult');
        $status_list	= & $this->lang->line('data_status');
        $gender_list    = & $this->lang->line('gender');
        $this->load->model('Bene_DataType_Model');
        $this->load->model('Bene_Hospital_Model');
        $this->load->model('Bene_User_Model');
        $this->load->model('Bene_DataInfo_Model');
		$data_type_id_names = array();
		$this->Bene_DataType_Model->get_data_type_id_names($data_type_id_names);
		$hospital_names = $hospital_lists = array();
		$this->Bene_Hospital_Model->get_hospital_list($hospital_lists,'ID,Name');
		foreach ($hospital_lists as $value)
		{
			$hospital_names[$value->ID] = $value->Name;
		}
		$user_names = $user_hospitals = $user_lists = array();
		$this->Bene_User_Model->get_user_list($user_lists,'ID,HospitalID,Name');
	    foreach ($user_lists as $value)
		{
			$user_names[$value->ID] = $value->Name;
			$user_hospitals[$value->ID] = $hospital_names[$value->HospitalID];
		}
		$this->load->model('Bene_User_Model');
		$data = array();
        session_start();
		$para = $_SESSION["search_para_list"];
		session_write_close();
		$rec_list = array();
		$this->Bene_DataInfo_Model->search_data_info_list($rec_list, $para['like_array'], $para['where_array'], $para['where_in_array'], $para['order_by_array'], '*');
		$_data = array();
		$flag = $this->Bene_Hospital_Model->get_hospital_flag_by_id($account->HospitalID);
		$show_AlyDoctorInfo = FALSE;
		$replace_AlyDoctorInfo = FALSE;
		if ($flag!==FALSE)
		{
			if(($flag&HospitalFlag_ShowAlyDoctor) == HospitalFlag_ShowAlyDoctor)
			{
				$show_AlyDoctorInfo = TRUE;
			}else if(($flag&HospitalFlag_ReplaceAlyDoctor) == HospitalFlag_ReplaceAlyDoctor)
			{
				$replace_AlyDoctorInfo = TRUE;
			}
		}
		foreach ($rec_list as $key => $rec)
		{
			foreach($rec as $index => $value)
			{
			    if(in_array($index,$arr_field))
			    {
			    	if ($index=='UserID')
			    	{
			    	    $_data[$key][] = $user_hospitals[$value];
			    	    if($value==-1||$value==0) $temp_name = "";
			    		else $temp_name = $user_names[$value];
			    	    $_data[$key][] = $temp_name;
			    	}
			    	else if($index=='Status')
			    		$_data[$key][] = $status_list[$value];
			        else if ($index=='DataID')
			            $_data[$key][] = ' '.$value;
			    	else if($index=='PatientGender')
			    	    $_data[$key][] = $gender_list[$value]; 
			    	else if ($index=='DataTypeID')
			    		$_data[$key][] =$data_type_id_names[$value];
			    	else if ($index=='DGSHospitalID')
			    		$_data[$key][] =$hospital_names[$value];
			    	else if ($index=='DGSUserID')
			    	{
			    		if($value==-1||$value==0) $temp_name = "";
			    		else $temp_name = $user_names[$value];
			    		if($show_AlyDoctorInfo===TRUE)
						{
			    			$_data[$key][] =$temp_name."_".$rec_list[$key]->RepUserName;
						}else if($replace_AlyDoctorInfo===TRUE)
						{
							$_data[$key][] =$rec_list[$key]->RepUserName;
						}
						else
							$_data[$key][] =$temp_name;
			    	}
			    	else
						$_data[$key][] = $value;
			    }
			}
		}
		$arr_head = array();
		$arr_head[]				= & $this->lang->line('upload_hospital_label');
		$arr_head[]				= & $this->lang->line('acq_user_name_label');
		$arr_head[]				= & $this->lang->line('record_type_label');
		$arr_head[]				= & $this->lang->line('record_id_label');
		$arr_head[]			= & $this->lang->line('patient_id_label');
		$arr_head[]		    = & $this->lang->line('patient_name_label');
		$arr_head[]				= & $this->lang->line('patient_gender_label');
		$arr_head[]			= & $this->lang->line('patient_age_label');
		$arr_head[]				= & $this->lang->line('record_clinic_label');
		$arr_head[]				= & $this->lang->line('submit_time_label');
		$arr_head[]			= & $this->lang->line('record_status_label');
		$arr_head[]				= & $this->lang->line('dgs_time_label');
		$arr_head[]						= & $this->lang->line('dgs_hospital_label');
		if($show_AlyDoctorInfo===TRUE) $arr_head[]				= & $this->lang->line('dgs_user_info_label');
		else $arr_head[]						= & $this->lang->line('dgs_user_label');
	    $arr_head[]				= & $this->lang->line('dgs_result_label');
		$this->load->helper('Bene_Utility');
		Helper_Get_Excel($fileName, $arr_head, $_data);
	}
	
}

?>