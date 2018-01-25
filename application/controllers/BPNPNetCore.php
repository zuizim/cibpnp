<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BPNPNetCore extends Bene_Controller
{
	const Syn_Mode_Normal		= 0;
	const Syn_Mode_Quick		= 1;
	const Syn_Mode_QuiteFast	= 2;
	
	public function __construct()
	{
		parent::__construct();
		$this->rt_name = 'BPNPNetCore';
		
		// check user login
		$this->doPageIllegal();
		
		// check client agent type
		if( $this->software_client === FALSE )
			Helper_ShowErrorPage(REQ_INVALID);
	}
	
	public function synchronize_my_data_info_list()
	{
		$this->rt_method = "synchronize_my_data_info_list";
		
		$role_type = 'operator';
		if( isset($_GET['RoleType']) ) $role_type = $_GET['RoleType'];
		
		$role_type_array = array( 'operator' => OBJ_RecData, 'doctor' => OBJ_Report );
		if( ! array_key_exists($role_type, $role_type_array) ) $role_type = 'operator';
		
		// check method  privilege
		$this->check_method_privilege(ACTION_LIST, $role_type_array[$role_type], SCOPE_MY);

		$user_id = -1; $data_type_id = -1; $reply_user_id = -1; $status = -1; $syn_mode = self::Syn_Mode_Normal;
		$period_type = 'SubmitTime'; $period_begin = ''; $period_end = ''; $sort_type = 'ASC'; $selected_fields = '*';
		
		// check synchronize mode
		if( isset($_GET['SynMode']) ) $syn_mode = intval($_GET['SynMode']);
		if( $syn_mode < self::Syn_Mode_Normal or $syn_mode > self::Syn_Mode_QuiteFast ) $syn_mode = self::Syn_Mode_Normal;
		if( $syn_mode == self::Syn_Mode_QuiteFast ) $selected_fields = 'ID, DataTypeID, DataID, RandomID, Status';
		
		// check data type
		if( isset($_GET['DataTypeID']) ) $data_type_id = $_GET['DataTypeID'];
		if( ( ! is_numeric($data_type_id)) ) $data_type_id = -1;
		
		// check period
		if( isset($_GET['PeriodBegin']) )
		{
			$period_begin = $_GET['PeriodBegin'];
			if( ! Helper_CheckDateTimeTextValid($period_begin) ) $period_begin = '';
		}
		if( isset($_GET['PeriodEnd']) )
		{
			$period_end = $_GET['PeriodEnd'];
			if( ! Helper_CheckDateTimeTextValid($period_end) ) $period_end = '';
		}
		switch($role_type)
		{
			case 'operator': $user_id = $this->account->ID; break;
			default: $period_type = 'DiagnosingTime'; $reply_user_id = $this->account->ID; break;
		}
		
		// check sort type
		if( isset($_GET['SortType']) ) $sort_type = $_GET['SortType'];
		$sort_type_list = array('ASC', 'DESC');
		if( ! in_array($sort_type, $sort_type_list) ) $sort_type = 'ASC';	
		
		// check status
		if( isset($_GET['Status']) ) $status = intval( $_GET['Status'] );
		switch($status)
		{
			case Data_Uploading:	if( $role_type == 'operator' ) $status = Data_Uploading; break;
			case Data_Uploaded:	if( $role_type == 'operator' ) $status = Data_Uploaded; break;
			case Data_Diagnosing:	$status = Data_Diagnosing; break;
			case Data_Diagnosed:	$status = Data_Diagnosed; break;
			case Data_Retrieved:	$status = Data_Retrieved; break;
			default: break;
		}
		
		//get DGSHospitalID
		//$this->load->model('Bene_User_Model');
		//$this->load->model('Bene_Hospital_Model');
		//$user = $this->Bene_User_Model->load_user_info_by_id($this->account->ID, 'HospitalID');
		//$hospital = $this->Bene_Hospital_Model->load_hospital_info_by_id($user->HospitalID, 'DGSHospitalID');
		$dgshospital_id = $this->account->DGSHospitalID;

		// check supplement status
		$supl_status = SuplStatus_NULL;
		if( isset($_GET['SuplStatus']) ) $supl_status = intval( $_GET['SuplStatus'] );
		
		$this->rep_obj->DataList = array();
		$data_list = & $this->rep_obj->DataList;
		
		$this->load->library('Bene_Pager');
		$this->load->model('Bene_DataInfo_Model');
		$_begin = '';
		$_end = '';
		Helper_Get_Period_Time($_begin, $_end);
	    if($period_end=='')$period_end = $_end;
		if($period_begin=='')$period_begin = $_begin;
		
		
		$this->Bene_DataInfo_Model->get_data_info_list($data_list, $data_type_id, $user_id, -1, $dgshospital_id, $status, $supl_status, $reply_user_id, -1, $period_type, $period_begin, $period_end, array($period_type => $sort_type), $selected_fields, $this->bene_pager );
		
		$this->rep_obj->RecTotalCount = intval($this->bene_pager->recTotal);
		$this->rep_obj->RecPerPage = intval($this->bene_pager->recPerPage);
		$this->rep_obj->PageID = intval($this->bene_pager->pageID);
		
		if( $syn_mode == self::Syn_Mode_QuiteFast ) 
		{
			foreach($data_list as $data)
			{
				$data->DataTypeID=intval($data->DataTypeID);
				$data->Status=intval($data->Status);
			}
			$this->exit_client_with_code(Rep_OK);
		}
		
		// parse to jason and return to client
		$this->load->model('Bene_Utility_Model');
		switch($role_type)
		{
			case 'operator':
			{
				if( isset($_GET['FileType']) ) $file_type = $_GET['FileType'];
				else $file_type=File_Type_Data;
				$this->Bene_Utility_Model->parse_data_info_to_client($data_list, Rec_Object_Data, ClientType_Software, $syn_mode === self::Syn_Mode_Normal, $file_type);
				break;
			}
			default:
			{
				if( isset($_GET['FileType']) ) $file_type = $_GET['FileType'];
				else $file_type=File_Type_Report;
				$this->Bene_Utility_Model->parse_data_info_to_client($data_list, Rec_Object_Report, ClientType_Software, $syn_mode === self::Syn_Mode_Normal, $file_type);
				break;
			}
		}
		$this->exit_client_with_code(Rep_OK);
	}
	
	public function acquire_need_diagnostic_list()
	{
		$this->rt_method = "acquire_need_diagnostic_list";
		// check method  privilege
		$this->check_method_privilege(ACTION_DOWNLOAD, OBJ_RecData, '');
		
		// unlock data
		if( ! isset($this->db) ) $this->load->database();
		$begin_time = date('Y-m-d H:i:s', strtotime('-30 minute'));
		$this->db->query("UPDATE ".TABLE_DATAINFO." SET Locked = 0 WHERE Locked = 1 AND LockedTime < '$begin_time'");
	
		$data_type_id = -1;
		if( isset($_GET['DataTypeID']) ) $data_type_id = $_GET['DataTypeID'];
	
		$account=$this->account;
		// check download flag
		$this->load->model('Bene_User_Model');
		$user_info = $this->Bene_User_Model->load_user_info_by_id($account->ID, 'DownloadFlag');
		if($user_info === FALSE)
			return $this->exit_client_with_code(Rep_ServerError);
		if($user_info->DownloadFlag==Manual_Mode)
		{
			if( isset($_GET['CheckCode']) )
			{
				$sessionId=session_id();
				$check_code_verify = md5($sessionId.$data_type_id);
				$check_code_verify = md5($check_code_verify);
				$check_code_verify = substr($check_code_verify ,0,8);
				$check_code = $_GET['CheckCode'];
				if($check_code != $check_code_verify)
					return  $this->exit_client_with_code(Rep_CheckCodeError);
			}
			else 
				return  $this->exit_client_with_code(Rep_InvalidParam);
		}
		// load data info.
		if($user_info->DownloadFlag==One_By_One_Mode)
		{
			$this->db->where('Status', Data_Diagnosing);
			$this->db->where('DGSUserID', $account->ID);
			$this->db->limit(1, 0);
			$this->db->select('ID');
			$query = $this->db->get(TABLE_DATAINFO);
			if( ! $query ) $this->exit_client_with_code(Rep_ServerError);
			if( $query->num_rows() >0 ) $this->exit_client_with_code(Rep_WaitDGSFinish); //some record  not diagnosed
		}
		//get DGSHospitalID
        //$this->load->model('Bene_User_Model');
		//$this->load->model('Bene_Hospital_Model');
		//$user = $this->Bene_User_Model->load_user_info_by_id($this->account->ID, 'HospitalID');
		//$hospital = $this->Bene_Hospital_Model->load_hospital_info_by_id($user->HospitalID, 'DGSHospitalID');
		$dgshospital_id = $account->DGSHospitalID;

		// get uploaded records
		$this->rep_obj->DiagnosticList = array();
		$data_list = & $this->rep_obj->DiagnosticList;
		
		$this->load->library('Bene_Pager');
		$this->bene_pager->setRecPerPage(5);
		$this->bene_pager->setPageID(1);
		
		$this->load->model('Bene_DataInfo_Model');
	    $period_begin = '';
		$period_end = '';
		Helper_Get_Period_Time($period_begin, $period_end);
		$this->Bene_DataInfo_Model->get_data_info_list($data_list, $data_type_id, -1, -1, $dgshospital_id, Data_Uploaded, SuplStatus_NULL, -1, -1, 'SubmitTime', $period_begin, $period_end, array('SubmitTime' => 'ASC'), '*',$this->bene_pager, TRUE ,TRUE);            
		
		// parse to jason and return to client
		$this->load->model('Bene_Utility_Model');
		$this->Bene_Utility_Model->parse_data_info_to_client($data_list, Rec_Object_Report, ClientType_Software);
		foreach($data_list as $data)
		{
			unset($data->AcqUserAccount);
			unset($data->AcqUserName);
			unset($data->Status);
			unset($data->DGSResult);
			unset($data->RepUserAccount);
			unset($data->RepUserName);
		}
		$this->exit_client_with_code(Rep_OK);
	}
	
	public function compete_data_diagnostic()
	{
		$this->rt_method = "compete_data_diagnostic";
		
		// check method  privilege
		$this->check_method_privilege(ACTION_DOWNLOAD, OBJ_RecData, '');
		
		// check ID param
		$param_array = array('ID', 'DataID', 'RandomID');
		$param_valid = Helper_CheckParamExist($param_array, Req_Mode_GET);
		if( ! $param_valid ) $this->exit_client_with_code(Rep_InvalidParam);
		
		$account = & $this->account;
	
		if( ! isset($this->db) ) $this->load->database();
		$this->db->where('ID', $_GET['ID']);
		$this->db->where('DataID', $_GET['DataID']);
		$this->db->where('RandomID', $_GET['RandomID']);
		$this->db->limit(1, 0);
		$this->db->select('ID, UserID, DGSUserID, Status');
		$query = $this->db->get(TABLE_DATAINFO);
		if( ! $query ) $this->exit_client_with_code(Rep_ServerError);
		if( $query->num_rows() <= 0 ) $this->exit_client_with_code(Rep_DataNotExisted); // no record exist
		$row = $query->row();
		
		if($row->DGSUserID == $account->ID) $this->exit_client_with_code(Rep_OK);
		
		// check data status
		if( ($row->Status != Data_Uploaded) or ($row->DGSUserID != -1) ) $this->exit_client_with_code(Rep_DataLocked);
		
		// now, start compete...
		$this->db->where('ID', $row->ID);
		$this->db->where('Status', Data_Uploaded);
		$this->db->where('DGSUserID', -1);
		$query = $this->db->update(TABLE_DATAINFO, array('Status' => Data_Diagnosing, 'SuplStatus' => SuplStatus_Downloading, 'DiagnosingTime' => date("Y-m-d H:i:s"), 'DGSUserID' => $this->account->ID));
		if($query === FALSE) Helper_ShowErrorPage( DB_ERROR, FALSE ); // db error
		if($this->db->affected_rows() <= 0) $this->exit_client_with_code(Rep_DataLocked); // compete failed
		
		// compete successful,  take notes for action
		$this->load->model('Bene_Action_Model');
		$this->Bene_Action_Model->insert_new_action(ACTION_DIAGNOSTIC, OBJ_RecData, $row->ID);
		$this->exit_client_with_code(Rep_OK);
	}
	
	public function get_transfer_file_list()
	{
		$this->rt_method = 'get_transfer_file_list';
	
		// check transfer key and transfer type
		$transfer_type = FALSE;
		$transfer_key = FALSE;
		if( isset($_POST['UploadKey']) or isset($_GET['UploadKey']) )
		{
			$transfer_key = isset($_POST['UploadKey']) ? $_POST['UploadKey'] : $_GET['UploadKey'];
			$transfer_type = Transfer_Type_Upload;
		}
		else if( isset($_POST['DownloadKey']) or isset($_GET['DownloadKey']) )
		{
			$transfer_key = isset($_POST['DownloadKey']) ? $_POST['DownloadKey'] : $_GET['DownloadKey'];
			$transfer_type = Transfer_Type_Download;
		}
		if( ($transfer_type === FALSE) or ($transfer_key === FALSE) ) $this->exit_client_with_code(Rep_InvalidParam);
		
		$this->load->model('Bene_TransferSession_Model');
		$transfer_mark = Bene_TransferSession_Model::check_transfer_session($transfer_key, $transfer_type);
		if($transfer_mark === FALSE) $this->exit_client_with_code(Rep_InvalidReq);
	
		$file_list = array();
		$this->load->model('Bene_DataInfo_Model');
		$this->Bene_DataInfo_Model->get_transfer_file_list($transfer_mark, $file_list);
	
		$this->rep_obj->FileList = array();
		$this->load->model('Bene_Utility_Model');
		$this->Bene_Utility_Model->parse_file_info_to_client($file_list, $this->rep_obj->FileList);
		
		$this->exit_client_with_code(Rep_OK);
	}
	
	public function begin_upload_data()
	{
		$this->rt_method = "begin_upload_data";
		$this->rep_obj->UploadKey = '';
	
		// check upload type
		if( ! isset($_POST['OpFlag']) ) $this->exit_client_with_code(Rep_InvalidParam);
		$upload_type = intval( $_POST['OpFlag'] );
		if( ($upload_type & 0x02) == 0 ) $upload_type = 'data';
		else $upload_type = 'report';
		$upload_type_array = array( 'data' => OBJ_RecData, 'report' => OBJ_Report );
	
		// check method  privilege
		$this->check_method_privilege(ACTION_UPLOAD, $upload_type_array[$upload_type]);
	
		// check disk space > 200M
		$disk_info = Helper_GetDiskVolumeInfo(DATA_ROOT);
		if( $disk_info->freeVolume < 209715200 ) $this->exit_client_with_code(Rep_DiskIsFull);
	
		$param_valid = FALSE;
		switch($upload_type)
		{
			case 'data':
				$param_array = array('DataID','RandomID','DataTypeID','PatientID','PatientName','DataAcqTime', 'DataFileList','AcqUserAccount','AcqUserName');
				$param_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
				break;
			default:
				$param_array = array('DataID','RandomID','DataTypeID','DataFileList','RepUserAccount','RepUserName');
				$param_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
				break;
		}
		if( ! $param_valid ) $this->exit_client_with_code(Rep_InvalidParam);
	
		$this->load->model('Bene_DataInfo_Model');
		switch($upload_type)
		{
			case 'data': $this->exit_client_with_code($this->Bene_DataInfo_Model->begin_new_data(Rec_Object_Data)); break;
			default: $this->exit_client_with_code($this->Bene_DataInfo_Model->begin_new_data(Rec_Object_Report)); break;
		}
	}
	
	public function upload_data_file_block()
	{
		$this->rt_method = "upload_data_file_block";
		
		// check upload key
		if( ! isset($_POST['UploadKey']) ) $this->exit_client_with_code(Rep_InvalidParam);
		$this->load->model('Bene_TransferSession_Model');
		$upload_mark = Bene_TransferSession_Model::check_transfer_session($_POST['UploadKey'], Transfer_Type_Upload);
		if($upload_mark === FALSE) $this->exit_client_with_code(Rep_InvalidReq);
	
		// check params
		$param_array = array('BlockOffset','BlockSize','BlockMD5','BlockFileName');
		$param_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
		if( (! $param_valid) or (! isset($_FILES['BlockFile'])) ) $this->exit_client_with_code(Rep_InvalidParam);
	
		$this->load->model('Bene_DataInfo_Model');
		$this->exit_client_with_code($this->Bene_DataInfo_Model->upload_data_block($upload_mark, $_POST['BlockFileName'], $_POST['BlockOffset'], $_POST['BlockSize'], $_POST['BlockMD5']));
	}
	
	public function end_upload_data()
	{
		$this->rt_method = "end_upload_data";
	
		// check upload key
		if( ! isset($_POST['UploadKey']) ) $this->exit_client_with_code(Rep_InvalidParam);
		$this->load->model('Bene_TransferSession_Model');
		$upload_mark = Bene_TransferSession_Model::check_transfer_session($_POST['UploadKey'], Transfer_Type_Upload);
		if($upload_mark === FALSE) $this->exit_client_with_code(Rep_InvalidReq);
	
		$this->load->model('Bene_DataInfo_Model');
		$rep_code = $this->Bene_DataInfo_Model->end_new_data($upload_mark);
		if($rep_code==Rep_OK) $this->post_wcf_server($upload_mark);
		if($rep_code != Rep_ServerError) Bene_TransferSession_Model::reset_transfer_session($_POST['UploadKey'], Transfer_Type_Upload);
		$this->exit_client_with_code($rep_code);
	}
	
	public function begin_download_data()
	{
		$this->rt_method = "begin_download_data";
	
		// check download type
		if( ! isset($_GET['OpFlag']) ) $this->exit_client_with_code(Rep_InvalidParam);
		$download_type = intval( $_GET['OpFlag'] );
		if( ($download_type & 0x02) == 0 ) $download_type = 'data';
		else $download_type = 'report';
		$download_type_array = array( 'data' => OBJ_RecData, 'report' => OBJ_Report );
	
		// check method  privilege
		$this->check_method_privilege(ACTION_DOWNLOAD, $download_type_array[$download_type]);
	
		$param_array = array('ID');
		if( Helper_CheckParamExist($param_array, Req_Mode_GET) === FALSE ) $this->exit_client_with_code(Rep_InvalidParam);
	
		$this->load->model('Bene_DataInfo_Model');
		switch($download_type)
		{
			case 'data': $this->exit_client_with_code($this->Bene_DataInfo_Model->begin_download_data($_GET['ID'], Rec_Object_Data)); break;
			default: $this->exit_client_with_code($this->Bene_DataInfo_Model->begin_download_data($_GET['ID'], Rec_Object_Report)); break;
		}
	}
	
	public function download_data_file_block()
	{
		$this->rt_method = "download_data_file_block";
	
		// check download key
		if( ! isset($_POST['DownloadKey']) ) $this->exit_client_with_code(Rep_InvalidParam);
		$this->load->model('Bene_TransferSession_Model');
		$download_mark = Bene_TransferSession_Model::check_transfer_session($_POST['DownloadKey'], Transfer_Type_Download);
		if($download_mark === FALSE) $this->exit_client_with_code(Rep_InvalidReq);

		// check params
		$param_array = array('DownloadOffset', 'DownloadSize', 'FileName');
		if( Helper_CheckParamExist($param_array, Req_Mode_POST) === FALSE ) $this->exit_client_with_code(Rep_InvalidParam);
	
		$this->load->model('Bene_DataInfo_Model');
		$this->Bene_DataInfo_Model->download_data_block($download_mark, $_POST['FileName'], $_POST['DownloadOffset'], $_POST['DownloadSize']);
	}
	
	public function end_download_data()
	{
		$this->rt_method = "end_download_data";
		
		// check download key
		$download_key = FALSE;
		if( isset($_POST['DownloadKey']) ) $download_key = $_POST['DownloadKey'];
		else if( isset($_GET['DownloadKey']) ) $download_key = $_GET['DownloadKey'];
		if( $download_key === FALSE ) $this->exit_client_with_code(Rep_InvalidParam);
	
		$this->load->model('Bene_TransferSession_Model');
		$download_mark = Bene_TransferSession_Model::check_transfer_session($download_key, Transfer_Type_Download);
		if($download_mark === FALSE) $this->exit_client_with_code(Rep_InvalidReq);
	
		$download_completed = FALSE;
		if( isset($_POST['DownloadCompleted']) or isset($_GET['DownloadCompleted']) ) $download_completed = TRUE;
	
		if($download_completed)
		{
			$this->load->model('Bene_Log_Model');
			$down_object = 'data';
			$is_report=($download_mark->ObjectType == Rec_Object_Report);
			if( $is_report === TRUE) $down_object = 'report';
			$info = "method { end_download_data } has been called, and file { file_type: $down_object, data_type_id: $download_mark->DataTypeID, id: $download_mark->ID } download completed.";
			$this->Bene_Log_Model->insert_new_log($info, $this->account->ID);
			
			// mark supplement status : downloaded.
			$this->db->where('ID', $download_mark->ID);
			$this->db->update(TABLE_DATAINFO, array('SuplStatus' => SuplStatus_Downloaded));
			$this->load->model('Bene_Action_Model');
			if($is_report === TRUE)
				$this->Bene_Action_Model->insert_new_action(ACTION_DOWNLOAD, OBJ_Report, $download_mark->ID);
		}
		
		Bene_TransferSession_Model::reset_transfer_session($download_key, Transfer_Type_Download);
		$this->exit_client_with_code(Rep_OK);
	}
	
	public function cancel_data()
	{
		$this->rt_method = "cancel_data";
		//check Param;
		$param_array = array('DataID','RandomID','DataTypeID');
		$param_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
		if( ! $param_valid ) $this->exit_client_with_code(Rep_InvalidParam);
		$data_id = $_POST['DataID'];
		$random_id = $_POST['RandomID'];
		$data_type_id = $_POST['DataTypeID'];
		//load model;
		$this->load->model('Bene_DataInfo_Model');
		$this->load->model('Bene_RepealOperation_Model');
		//获取数据ID;
		$data_info = $this->Bene_DataInfo_Model->load_data_info(-1,'ID, UserID',$data_type_id, $data_id, $random_id);
		if($data_info==FALSE) 
			$this->exit_client_with_code(Rep_DataNotExisted);
		else 
			$id = $data_info->ID;
		if($data_info->UserID!=$this->account->ID)
			$this->exit_client_with_code(Rep_NoPrivilege);
		$repeal_type = Bene_RepealOperation_Model::Repeal_Type_DeleteRecData;
		$bsuccess = $this->Bene_RepealOperation_Model->do_rec_data_repeal($id, $this->account->ID, $this->account->GroupID, $this->account->HospitalID, $repeal_type, TRUE);
		if($bsuccess==TRUE)
			$this->exit_client_with_code(Rep_OK);
		else 
			$this->exit_client_with_code(Rep_Failed);
	}
	
	public function query_record_info_safe()
	{
		$this->rt_method = "query_record_info_safe";
		$param_array = array('DataID','RandomID','DataTypeID','CheckCode');
		$param_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
		if( ! $param_valid ) 
			$this->exit_client_with_code(Rep_InvalidParam);
		$TokeKey = "bpnp_for_server";
		$data_id = $_POST['DataID'];
		$random_id = $_POST['RandomID'];
		$data_type_id = $_POST['DataTypeID'];
		$check_code= $_POST['CheckCode'];
		$temp=$TokeKey.md5($data_id).$random_id;
		$temp=md5($temp.$data_type_id);
		$temp=substr($temp,0,8);
		if($temp!=$check_code)
			$this->exit_client_with_code(Rep_CheckCodeError);
		//load model;
		$this->load->model('Bene_DataInfo_Model');
		//获取数据UserID,Status,SuplStatus;
		$data_info = $this->Bene_DataInfo_Model->load_data_info(-1,'ID, UserID, Status, SuplStatus,DGSUserID',$data_type_id, $data_id, $random_id);
		if($data_info==FALSE) 
			$this->exit_client_with_code(Rep_DataNotExisted);
		else 
		{
			$this->load->model('Bene_User_Model');
			$user = $this->Bene_User_Model->load_user_info_by_id($data_info->UserID, 'HospitalID');
			if($user==FALSE)
				$this->exit_client_with_code(Rep_Failed);
			$data_info->OperatorHospitalID=$user->HospitalID;
			$this->rep_obj->DataStatus = $data_info;
			$this->exit_client_with_code(Rep_OK);
		}
	}
	
	public function query_record_basic_info()
	{
		$this->rt_method = "query_record_basic_info";
		
		// check method  privilege
		$this->check_method_privilege(ACTION_UPLOAD, OBJ_RecData, '');
		//check Param;
		$param_array = array('DataID','RandomID','DataTypeID');
		$param_valid = Helper_CheckParamExist($param_array, Req_Mode_POST);
		if( ! $param_valid ) $this->exit_client_with_code(Rep_InvalidParam);
		$data_id = $_POST['DataID'];
		$random_id = $_POST['RandomID'];
		$data_type_id = $_POST['DataTypeID'];
		//load model;
		$this->load->model('Bene_DataInfo_Model');
		//获取数据UserID,Status,SuplStatus;
		$data_info = $this->Bene_DataInfo_Model->load_data_info(-1,'ID, UserID, Status, SuplStatus',$data_type_id, $data_id, $random_id);
		if($data_info==FALSE) 
			$this->exit_client_with_code(Rep_DataNotExisted);
		else 
		{
			$this->rep_obj->DataStatus = array();
			$data_status = & $this->rep_obj->DataStatus;
			$data_status["Status"] = $data_info->Status;
			$data_status["SuplStatus"] = $data_info->SuplStatus;
			if($data_info->UserID==$this->account->ID)
				$data_status["IsOwner"] = 1;
			else 
				$data_status["IsOwner"] = 0;
			$this->exit_client_with_code(Rep_OK);
		}
	}
	
    private function post_wcf_server(& $upload_mark)
	{
		if (defined('WCF_NotifyAddress')===FALSE) { 
			return;
		}
		$account = & $this->account;
		//prepare datainfo
	    $notify_type = NewDataUploaded;
		if( $upload_mark->ObjectType == Rec_Object_Report ) $notify_type = NewReportUploaded;
		$data_id = $upload_mark->DataID;
		$random_id =  $upload_mark->RandomID;
		$user_list = array();
		
		if($notify_type == NewReportUploaded)
		{
			$this->load->model('Bene_DataInfo_Model');
			$row = $this->Bene_DataInfo_Model->load_data_info($upload_mark->ID, $select_field = 'UserID',-1, $data_id, $random_id);
		    if($row!==FALSE) $user_list[] = $row->UserID;
		}else
		{
			$aly_user_list = array();
			$this->load->model('Bene_User_Binding_Model');
			$this->load->model('Bene_User_Model');
			$opt_user_id = $account->ID;
			if($this->Bene_User_Binding_Model->get_binding_aly_user_list($opt_user_id,$aly_user_list)===FALSE)
			{
				$dgshospital_id = $account->DGSHospitalID;
				$group_id = UG_Aly;
				$this->Bene_User_Model->get_user_list($aly_user_list, $select_fields = 'ID', $dgshospital_id, $group_id);
				foreach ($aly_user_list as $aly_user)
				{
					$user_list[] = intval($aly_user->ID);
				}
			}else
			{
				foreach ($aly_user_list as $aly_user)
				{
					$user_list[] = intval($aly_user->UserID_A);
				}
			}
		}
		$data_info  = array('NotifyType' => $notify_type,
			                'RecUID'     => $data_id.'_'.$random_id,
							'UserIDList'   => $user_list);
        $sub_json =  json_encode($data_info);
        $json_content = array(
         						"ContentType" => 1,
                                "Content"     => $sub_json,
        );
        $json_input = json_encode($json_content);
		$curlPost = $json_input;
		//$header = 'Content-Type: application/json';
		$header = array(
      					'Content-Type: application/json',
        );
		$ch = curl_init(); //初始化一个CURL对象
		$url = WCF_NotifyAddress;
		curl_setopt($ch, CURLOPT_URL, $url);
		//设置你所需要抓取的URL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT,1);    //1s
		//设置curl参数，要求结果是否输出到屏幕上，为true的时候是不返回到网页中
		curl_setopt($ch, CURLOPT_POST, 1);
		
		//post提交
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		
		//$res=json_decode($data,true);
		curl_close($ch);
	}
	
	private function save_json_to_file(&$str_json, $file_name = '')
	{
		if($file_name=='')
		$file_name = date('YmdHis');
		$str_rand = $this->getRandChar(4);
		$file_name = $file_name.$str_rand.'.json';
		$myfile = fopen($file_name, "w");
		fwrite($myfile, $str_json);
		fclose($myfile);
	}
	
	private function  getRandChar($length)
	{
   		$str = null;
   		$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
   		$max = strlen($strPol)-1;
   		for($i=0;$i<$length;$i++){
    	$str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
  		}
   		return $str;
   }
	
}

?>