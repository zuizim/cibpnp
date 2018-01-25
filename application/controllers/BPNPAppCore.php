<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BPNPAppCore extends Bene_Controller
{
	
	public function __construct()
	{
		parent::__construct();
		$this->rt_name = 'BPNPAppCore';
		
		// check user login
		$this->doPageIllegal();
		
		// check client agent type
		if( $this->software_client === FALSE )
			Helper_ShowErrorPage(REQ_INVALID);
	}
	
	public function get_ftp_login_info()
	{
		$this->rt_method = "get_ftp_login_info";
		
		// check method  privilege
		// $this->check_method_privilege(ACTION_UPLOAD, OBJ_RecData, '');
		
		$account = & $this->account;
		$username = $account->Account;
		$ArrPwd = array();
		$this->get_password( $username,$ArrPwd);
		$this->rep_obj->Port = FTP_PORT; 
		$this->rep_obj->Password = $ArrPwd[0];
		$this->exit_client_with_code(Rep_OK);
	    
	}
    
	private function get_password( $username,&$ArrPwd)
	{
		$ArrPwd = array();
		$TokeKey = "beneapp";
		$TokeKey = $TokeKey.$username;
		$date = new DateTime();
		$datastamp = $date->format("U");
		$datastamp = floor($datastamp/300) * 300;
		$datastampEx = $datastamp - 300;
		$date = date('Y-m-d H:i',$datastamp);
		$dateEx = date('Y-m-d H:i',$datastampEx);
		$strdate = $TokeKey.$date;
		$strdateEx = $TokeKey.$dateEx;
		$pwd = $TokeKey.md5($strdate);
		$pwdEx = $TokeKey.md5($strdateEx);
		$pwd = md5($pwd);
		$pwdEx = md5($pwdEx);
		$pwd = substr($pwd,0,8);
		$pwdEx = substr($pwdEx,0,8);
		$ArrPwd[0] = $pwd;
		$ArrPwd[1] = $pwdEx;
	}
	
	public function get_need_dgs_list()
	{
		$this->rt_method = "get_need_dgs_list";
		// check method  privilege
		$this->check_method_privilege(ACTION_DOWNLOAD, OBJ_RecData, '');
		
		// unlock data
		if( ! isset($this->db) ) $this->load->database();
		$begin_time = date('Y-m-d H:i:s', strtotime('-30 minute'));
		$this->db->query("UPDATE ".TABLE_DATAINFO." SET Locked = 0 WHERE Locked = 1 AND LockedTime < '$begin_time'");
	
		$data_type_id = DataType_PCECG;//必须为ECG
	
		$account = & $this->account;
		// check download flag
		$this->load->model('Bene_User_Model');
		$user_info = $this->Bene_User_Model->load_user_info_by_id($account->ID, 'DownloadFlag');
		if($user_info === FALSE)
			return $this->exit_client_with_code(Rep_ServerError);
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
		
		// parse to json and return to client
		$this->load->model('Bene_Utility_Model');
		$this->Bene_Utility_Model->parse_data_info_to_client($data_list, Rec_Object_Data, ClientType_Software);
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
	
    public function get_ecg_record_link()
	{
		$this->rt_method = "get_ecg_record_link";
		
		// check method  privilege
		$this->check_method_privilege(ACTION_DOWNLOAD, OBJ_RecData, '');
		
		// check ID param
		$param_array = array('ID', 'DataID', 'RandomID');
		$param_valid = Helper_CheckParamExist($param_array, Req_Mode_GET);
		if( ! $param_valid ) $this->exit_client_with_code(Rep_InvalidParam);
		
		//compete data
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
		
		if($row->DGSUserID != $account->ID)
		{
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
		}
		
		$strDataID = md5($_GET['DataID']);
        $strRandomID = md5($_GET['RandomID']);
        $strMd5 = md5($strDataID.$strRandomID);
        $strCode = substr($strMd5, -8);
        $this->load->helper('url');
		$record_url='BPNPAppCore/download_ecg_record?ID='.$_GET['ID'].'&Code='.$strCode;
		$record_link = base_url($record_url);
		$this->rep_obj->Record_Link = $record_link;
		$this->exit_client_with_code(Rep_OK);
		
	}
	
	public function download_ecg_record()
	{
		$this->rt_method = "download_ecg_record";
		
		$account = & $this->account;
		
		$param_array = array('ID','Code');
		if( Helper_CheckParamExist($param_array, Req_Mode_GET) === FALSE ) $this->exit_client_with_code(Rep_InvalidParam);
	    
		
		// check method  privilege
		$this->check_method_privilege(ACTION_DOWNLOAD, OBJ_RecData, '');
		
		$this->load->model('Bene_DataInfo_Model');
		$id = $_GET['ID'];
		$row = $this->Bene_DataInfo_Model->load_data_info($id,'ID, UserID, DataID, RandomID, DataTypeID, DGSUserID');
		if ($row===FALSE) $this->exit_client_with_code(Rep_DataNotExisted);
		//不是当前分析用户的记录->无效请求
		if($account->ID!=$row->DGSUserID || $row->DataTypeID!=DataType_PCECG)
			 $this->exit_client_with_code(Rep_InvalidReq);
		
		$strDataID = md5($row->DataID);
        $strRandomID = md5($row->RandomID);
		$strMd5 = md5($strDataID.$strRandomID);
        $strCode = substr($strMd5, -8);
        if($strCode!=$_GET['Code']) $this->exit_client_with_code(Rep_CheckCodeError);
        
        $this->load->model('Bene_User_Model');
        $user = $this->Bene_User_Model->load_user_info_by_id($row->UserID);

        $hospital = $user->HospitalID; 
        $this->load->model('Bene_File_Model');
		$data_dir = Bene_File_Model::get_data_dir($hospital, $row->DataTypeID, $row->DataID, $row->RandomID);
		
		if( ! isset($this->db) ) $this->load->database();
		$where_array = array(	'DataID' => $row->ID,
		                        'FileType' => Rec_Object_Data);
		$this->db->select('FileName');
		$query=$this->db->get_where(TABLE_FILE, $where_array);
		if($query->num_rows() <= 0) $this->exit_client_with_code(Rep_DataNotExisted);
		$fileRow= $query->row();
		$this->load->model('Bene_File_Model');
		$data_dir = trim($data_dir,'/').'/';
		$data_file_path = $data_dir.'rec_data/'.$fileRow->FileName;
	    $this->load->library('bene_download');
	    $flag = $this->bene_download->download($data_file_path, $fileRow->FileName,true); 
		//if(!$flag) 
		//	$this->exit_client_with_code(Rep_NullDataFile);
		//	else 
		//	{
		//		if( ! isset($this->db) ) $this->load->database();
		//		  $this->db->where('ID', $row->ID);
		//		  $this->db->update( TABLE_DATAINFO, array('Status' => Data_Diagnosing, 'SuplStatus' =>SuplStatus_Downloaded, 'DiagnosingTime' => date("Y-m-d H:i:s")) );
		//	}
        if( ! isset($this->db) ) $this->load->database();
		$this->db->where('ID', $id);
		$this->db->update( TABLE_DATAINFO, array('Status' => Data_Diagnosing, 'SuplStatus' =>SuplStatus_Downloaded, 'DiagnosingTime' => date("Y-m-d H:i:s")) );
	}
	
	
    public function upload_ecg_dgs_result()
	{
		$this->rt_method = "upload_ecg_dgs_result";
		
		$account = & $this->account;
		// check method  privilege
		$this->check_method_privilege(ACTION_UPLOAD, OBJ_Report, '');
		
		$param_array = array('ID', 'DataID', 'RandomID');
		if( Helper_CheckParamExist($param_array, Req_Mode_POST) === FALSE ) $this->exit_client_with_code(Rep_InvalidParam);
		
		if(isset($_POST['DGSResult'])) $dsg_result = $_POST['DGSResult'];
		else return $this->exit_client_with_code(Rep_InvalidParam);
		$obj_result=json_decode($dsg_result,true);
		if(is_null($obj_result))
		   return $this->exit_client_with_code(Rep_InvalidParam);

		$hospital = $account->HospitalID;
		$this->load->model('Bene_DataInfo_Model');
		$id = $_POST['ID'];
		$row = $this->Bene_DataInfo_Model->load_data_info($id,'ID, UserID, DataID, RandomID, DataTypeID, Status, DGSUserID');
		if ($row===FALSE) $this->exit_client_with_code(Rep_DataNotExisted);
		//ID不一致->无效请求
		if($_POST['DataID']!=$row->DataID || $_POST['RandomID']!=$row->RandomID)
			$this->exit_client_with_code(Rep_InvalidReq);
		//不是当前分析用户的记录->无效请求
		if($account->ID!=$row->DGSUserID || $row->DataTypeID!=DataType_PCECG)
			$this->exit_client_with_code(Rep_InvalidReq);
		//非诊断中状态->无效请求
		if($row->Status!=Data_Diagnosing) 
			$this->exit_client_with_code(Rep_InvalidReq);
		
		$this->load->model('Bene_User_Model');
        $user = $this->Bene_User_Model->load_user_info_by_id($row->UserID);
        $hospital = $user->HospitalID; 
		$this->load->model('Bene_File_Model');
		$data_dir = Bene_File_Model::get_data_dir($hospital, $row->DataTypeID, $row->DataID, $row->RandomID);
		if( ! isset($this->db) ) $this->load->database();
		$where_array = array(	'DataID' => $row->ID,
		                       	'FileType' => Rec_Object_Data);
		$this->db->select('FileName');
		$query=$this->db->get_where(TABLE_FILE, $where_array);
		if($query->num_rows() <= 0) $this->exit_client_with_code(Rep_DataNotExisted);
		$fileRow= $query->row();
		$this->load->model('Bene_File_Model');
		$data_dir = trim($data_dir,'/').'/';
		$data_file_path = $data_dir.'rec_data/'.$fileRow->FileName;
	    if(!is_dir($data_dir.'report')) mkdir($data_dir.'report', 0777); 
		$data_rpt_file_path = $data_dir.'report/'.$row->DataID.'.rtz';
		//unzip
		//$temp系统临时文件夹(windows下左斜杠可接受)
		$temp = sys_get_temp_dir().'/';
        //add xml
        $dxmlpath = $temp.$row->DataID.'_'.$row->RandomID.'.dxml';
        
        $this->load->helper('Bene_Utility');
		$xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8'?>
		<ECGReport/>");
		//var_dump($obj_result);
		//exit(0);
		$xmldata = $obj_result['ECGReport'];
		Helper_Arrary_To_Xml($xmldata,$xml);
		$dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $dom->save($dxmlpath);
        $file_fix = substr(md5(md5_file($dxmlpath)), -8);
        $dxml_fix_name=$row->DataID.'_'.$file_fix.'.dxml';
        $dxml_fix = $temp.$dxml_fix_name;
        $dxml_fix_path=$row->DataID.'/'.$dxml_fix_name;
        $del_pdf_file=$row->DataID.'.pdf';
        $del_xml_file=$row->DataID.'.xml';
        rename($dxmlpath,$dxml_fix);
        copy($data_file_path,$data_rpt_file_path);
        $zip = new ZipArchive;
        if ($zip->open($data_rpt_file_path) === TRUE)
        {
             $zip->addFile($dxml_fix,$dxml_fix_path);
             $zip->deleteName($del_pdf_file);
             $zip->deleteName($del_xml_file);
             $zip->close();
             unlink($dxml_fix);
        }else $this->exit_client_with_code(Rep_Failed);
    
		//insert db
		$filesize=filesize($data_rpt_file_path);
		$fimeMd5=md5_file($data_rpt_file_path);
		
		if( ! isset($this->db) ) $this->load->database();
		$data = array (
				'DataID'					=>		$row->ID,
				'FileName'				    =>		$row->DataID.'.rtz',
				'FileSize'					=>		$filesize,
				'FileMD5'					=>		$fimeMd5,
				'FileType'					=>		Rec_Object_Report,
				'CreateTime'				=>		date("Y-m-d H:i:s"),
				'UploadTime'			    =>		date("Y-m-d H:i:s"),
				'FileStatus'				=>		File_Status_Uploaded,
		);
		
		$this->db->insert(TABLE_FILE, $data);
		//update status and suplstatus
		$this->db->where('ID', $row->ID);
		$this->db->update( TABLE_DATAINFO, array('Status' => Data_Diagnosed, 'SuplStatus' =>SuplStatus_Uploaded, 'DiagnosedTime' => date("Y-m-d H:i:s")) );
	    $this->exit_client_with_code(Rep_OK);
	}
	
	private function request_post($url = '', $param = '') {
		if (empty ( $url ) || empty ( $param )) {
			return false;
		}
		$postdata = http_build_query ( $param );
		$opts = array (
				'http' => array (
						'method' => 'POST',
						'header' => array (
								"Content-type: application/x-www-form-urlencoded" 
						),
						'content' => $postdata 
				) 
		);
		$context = stream_context_create ( $opts );
		$result = file_get_contents ( $url, false, $context );
		return $result;
	}

	public function end_upload_ftp()
	{
		$this->rt_method = "end_upload_ftp";
		
		// check method  privilege
		$this->check_method_privilege(ACTION_UPLOAD, OBJ_RecData, '');
		$account = & $this->account;
		
		$param_array = array('Name','MAC', 'Md5', 'FileName', 'FileSize', 'ImportTime','RecTime');
		if( Helper_CheckParamExist($param_array, Req_Mode_POST) === FALSE ) $this->exit_client_with_code(Rep_InvalidParam);
		$RecordInfo = array(
		              'Name'					=>		$_POST['Name'],
		              'User'                    =>      $account->Account,
				  	  'FileName'				=>		$_POST['FileName'],
		              'FileSize'				=>		$_POST['FileSize'],
					  'MAC'			            =>		$_POST['MAC'],
					  'Md5'					    =>		$_POST['Md5'],
		              'ImportTime'              =>		$_POST['ImportTime'],
		              'RecTime'					=>		$_POST['RecTime'], 
		              'PID'					    =>		isset($_POST['PID'])?$_POST['PID']:'', 
		              'Gender'					=>		isset($_POST['Gender'])?$_POST['Gender']:'',
		              'Age'					    =>		isset($_POST['Age'])?$_POST['Age']:'',
					  'DOB'					    =>		isset($_POST['DOB'])?$_POST['DOB']:'',
		              'Note'					=>		isset($_POST['Note'])?$_POST['Note']:'',
		              'Custom1'                 =>		isset($_POST['Custom1'])?$_POST['Custom1']:'',
		              'Custom2'                 =>		isset($_POST['Custom2'])?$_POST['Custom2']:'',
		              'Custom3'                 =>		isset($_POST['Custom3'])?$_POST['Custom3']:'',
		              'Custom4'                 =>		isset($_POST['Custom4'])?$_POST['Custom4']:'',
					  'TimeZone'				=>		isset($_POST['TimeZone'])?$_POST['TimeZone']:'',
					  'Weight'					=>		isset($_POST['Weight'])?$_POST['Weight']:'',
					  'Height'					=>		isset($_POST['Height'])?$_POST['Height']:'',
		);
		$url = 'http://localhost:13485/ECGNotify';
		$result = $this->request_post($url,$RecordInfo);
		$obj_result=json_decode($result,true);
		if(is_null($obj_result))
		   $this->exit_client_with_code(Rep_Failed);
		$ret = $obj_result['Rep_Code']; 
		if(!is_null($obj_result['DataID']))
			$this->rep_obj->DataID = $obj_result['DataID'];
		if(!is_null($obj_result['RandomID']))
			$this->rep_obj->RandomID = $obj_result['RandomID'];
	    $this->exit_client_with_code($ret);
	}
}

?>