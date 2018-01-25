<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class BPNPEncrypt extends Bene_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->rt_name = 'BPNPEncrypt';
	}
	
	public function encrypt_code()
	{
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/controllers/BeneEntryInterface.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/controllers/BeneEntryInterface.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/controllers/BPNPNetCore.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/controllers/BPNPNetCore.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/controllers/User.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/controllers/User.php");
		
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/core/Bene_Controller.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/core/Bene_Controller.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/core/Bene_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/core/Bene_Model.php");
		
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/helpers/Bene_Auth_Helper.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/helpers/Bene_Auth_Helper.php");
		
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Action_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Action_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_DataInfo_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_DataInfo_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_DataType_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_DataType_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_File_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_File_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Group_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Group_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_GroupPriv_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_GroupPriv_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Hospital_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Hospital_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Log_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Log_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Login_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Login_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Menu_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Menu_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_RepealOperation_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_RepealOperation_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_TransferSession_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_TransferSession_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_User_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_User_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_UserView_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_UserView_Model.php");
		beast_encode_file("E:/Company/BPNP/Server/BPNP/application/models/Bene_Utility_Model.php", "E:/Company/BPNP/Server/BPNP_Encrypt/application/models/Bene_Utility_Model.php");
	}
}