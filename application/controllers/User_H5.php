<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_H5 extends Bene_controller{

    public function __construct(){
        parent::__construct();
        $this->load->library('session');
        $this->load->model('bene_User_H5_model');
        $this->load->model('bene_Case_H5_model');
        $this->load->model('bene_Hospital_H5_model');
        $this->load->model('bene_Message_H5_model');
    }

    /*检查是否已经登录*/
    public function checkIfLogged(){
        echo $this->checkLogStatus();
    }

    /*借用老版的代码-2017/06/13：去掉老板对于页面的调用，只返回数值给客户端三种状态：账号或密码错误（-1）、账号被锁定（-2）、登录成功（1）*/
    public function toLogin(){
        $this->rt_method = 'login';

        @$Account = @$_REQUEST['userAccount'];
        @$Pwd     = @$_REQUEST['userPwd'];

        if(empty($Account)||empty($Pwd)){
            echo -1;
            return false;
        }

        if(strlen($Account)<3 || strlen($Account) >32){
            echo -1;
            return false;
        }

        if(strlen($Pwd)!=32){
            echo -1;
            return false;
        }

        $Account = addslashes($Account);
        $Pwd = addslashes($Pwd);

        $this->load->helper('Bene_Auth');
        $this->load->model('Bene_Login_Model');

        $chek_code = $this->Bene_Login_Model->h5_login($Account,$Pwd, Rep_Failed);

        $this->account = Helper_GetAuthInfo();

        if($chek_code==Rep_OK && $this->account){
            $this->load->model('Bene_Online_Model');
            $user_id = $this->account->ID;
            $row = $this->Bene_Online_Model->get_online_user_info_by_user_id($user_id);
            if ($row===FALSE){
                $this->Bene_Online_Model->insert_new_online_user_info($user_id);
            }else{
                $this->Bene_Online_Model->update_online_user_info($user_id);
            }

            echo $this->bene_User_H5_model->toLogin($Account,$Pwd);
        }else{
            echo -1;
        }
    }

    /*获取用户的全部信息，并且缓存在服务器中*/
    public function getUserInfo(){
        if($this->checkLogStatus()){
            session_start();
            $result = $this->bene_User_H5_model->getUserInfo($_SESSION['Account']);
            echo json_encode($result);
        }else{
            echo "offline";
            return false;
        }
    }

    /*退出登录时进行相应的操作，借用老版的函数logout，改掉老版中对页面的控制*/
    public function toExit()
    {
        $this->rt_method = 'logout';

        if($this->account != FALSE)
        {
            $this->load->model('Bene_Action_Model');
            $this->load->model('Bene_Online_Model');
            $user_id = $this->account->ID;
            $this->Bene_Action_Model->insert_new_action(ACTION_LOGOUT, OBJ_System, $user_id);
            $this->Bene_Online_Model->delete_online_user($user_id);
        }

        session_start();
        $_SESSION = array();
        if(isset($_COOKIE[session_name()])){
            setcookie(session_name(),'',time()-42000,'/');
        }
        session_destroy();
    }


    /*获取不同用户所对应的医院信息列表*/
    /*采集员 + 采集管理员 + 采集组 --userInfo hospitalID所对应的dgsHospital,只有一个*/
    /*分析员 + 分析管理员 + 分析组 --userInfo hospitalID 所对应的 HospitalID, 可能有多个*/
    /*超级管理员：对应所有的医院列表*/
    /*函数：获取医院信息ID和Name*/
    public function getHospitalList(){
        if($this->checkLogStatus()){
            session_start();
            $userInfo = $_SESSION["UserInfo"];
            if(is_array($userInfo)){
                $hospitalID = $userInfo['HospitalID'];
                $groupID    = $userInfo['GroupID'];
                if($hospitalID && $groupID){
                    $result = $this->bene_Hospital_H5_model->getHospitalList($hospitalID,$groupID);
                    echo $result;
                }
            }
        }else{
            echo "offline";
            return false;
        }
    }

    /*根据时间段获取病例数据*/
    public function getCaseData(){
        if($this->checkLogStatus()){
            @$start = @$_REQUEST['start'];
            @$end   = @$_REQUEST['end'];
            if($start){
                $start = date('Y-m-d H:i:s',$start/1000);
            }

            if($end){
                $end = date('Y-m-d H:i:s',$end/1000);
            }
            session_start();
            $userInfo = $_SESSION['UserInfo'];
            $ID = $userInfo['ID'];
            $groupID = $userInfo['GroupID'];
            if($groupID==1 || $groupID==2){
                $result = $this->bene_Case_H5_model->getCaseData($ID,$groupID,$start,$end);
                echo json_encode($result);
            }else{
                return false;
            }
        }else{
            echo "offline";
            return false;
        }
    }

    /*获取某一特定病例的一生*/
    public function getCaseActions($caseID){
        if($this->checkLogStatus()){
            $result = $this->bene_Case_H5_model->getCaseActions($caseID);
            echo $result;
        }else{
            echo "offline";
            return false;
        }
    }

    /*复杂筛选*/
    /*复杂条件筛选，需要考虑用户的组别，因为不同的组别能读取的数据不一样*/
    public function searchAllRecords(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        @$userInfo       = $_SESSION["UserInfo"];
        @$userID         = $userInfo['ID'];
        @$userGroupID    = $userInfo['GroupID'];
        @$userHospitalID = $userInfo['HospitalID'];

        @$dataID         = @$_REQUEST['dataID'];
        @$dataTypeID     = @$_REQUEST['dataTypeID'];
        @$dataClinic     = @$_REQUEST['dataClinic'];
        @$dataInfo       = @$_REQUEST['dataInfo'];

        @$patientID      = @$_REQUEST['patientID'];
        @$patientName    = @$_REQUEST['patientName'];
        @$patientGender  = @$_REQUEST['patientGender'];

        @$optHospitalID  = @$_REQUEST['optHospitalID'];
        @$dgsHospitalID  = @$_REQUEST['dgsHospitalID'];

        @$dgsResult      = @$_REQUEST['dgsResult'];

        @$optUserID      = @$_REQUEST['optUserID'];
        @$dgsUserID      = @$_REQUEST['dgsUserID'];

        @$status         = @$_REQUEST['status'];

        @$start          = @$_REQUEST['start'];
        @$end            = @$_REQUEST['end'];

        if(empty($userID) || empty($userGroupID) || empty($userHospitalID)){
            return false;
        }

        $sqlCondition = " ";

        if($dataID){
            $dataID = addslashes($dataID);
            $sqlCondition = $sqlCondition."bene_datainfo.DataID like '%$dataID%' and ";
        }

        if($dataTypeID){
            $dataTypeID = addslashes($dataTypeID);
            $sqlCondition = $sqlCondition."bene_datainfo.DataTypeID='$dataTypeID' and ";
        }

        if($dataClinic){
            $dataClinic = addslashes($dataClinic);
            $sqlCondition = $sqlCondition."bene_datainfo.DataClinic like '%$dataClinic%' and ";
        }

        if($dataInfo){
            $dataInfo = addslashes($dataInfo);
            $sqlCondition = $sqlCondition."bene_datainfo.DataInfo like '%$dataInfo%' and ";
        }        

        if($patientID){
            $patientID = addslashes($patientID);
            $sqlCondition = $sqlCondition."bene_datainfo.PatientID like '%$patientID%' and ";
        }

        if($patientName){
            $patientName = addslashes($patientName);
            $sqlCondition = $sqlCondition."bene_datainfo.PatientName like '%$patientName%' and ";
        }

        if($patientGender!=""){
            $patientGender = addslashes($patientGender);
            $sqlCondition = $sqlCondition."bene_datainfo.PatientGender='$patientGender' and ";
        }

        if($optHospitalID){
            $optHospitalID = addslashes($optHospitalID);
            $sqlCondition = $sqlCondition."bene_datainfo.UserID in (select ID from bene_user where HospitalID='$optHospitalID') and ";
        }

        if($dgsHospitalID){
            $dgsHospitalID = addslashes($dgsHospitalID);
            $sqlCondition = $sqlCondition."bene_datainfo.DGSHospitalID = '$dgsHospitalID' and ";
        }

        if($dgsResult){
            $dgsResult = addslashes($dgsResult);
            $sqlCondition = $sqlCondition."bene_datainfo.DGSResult like '%$dgsResult%' and ";
        }

        if($status!=""){
            $sqlCondition = $sqlCondition." bene_datainfo.Status='$status' and ";
        }

        if($start){
            $start = date('Y-m-d 00:00:00',$start/1000);
            $sqlCondition = $sqlCondition." bene_datainfo.SubmitTime > '$start' and ";
        }

        if($end){
            $end = date('Y-m-d 23:59:59',$end/1000);
            $sqlCondition = $sqlCondition." bene_datainfo.SubmitTime < '$end' and ";
        }

        switch(intval($userGroupID)){
            case 1:
                $sqlCondition = $sqlCondition." bene_datainfo.UserID = '$userID' and ";
                break;
            case 2:
                $sqlCondition = $sqlCondition." bene_datainfo.DGSUserID = '$userID' and ";
                break;
            case 3:
                if($optUserID){
                    $sqlCondition = $sqlCondition." bene_datainfo.UserID='$optUserID' and ";
                }else{
                    $sqlCondition = $sqlCondition." bene_datainfo.UserID in (select ID from bene_user where GroupID=1 and HospitalID='$userHospitalID') and ";
                }
                break;
            case 4:
                if($dgsUserID){
                    $sqlCondition = $sqlCondition." bene_datainfo.DGSUserID='$dgsUserID' and ";
                }else{
                    $sqlCondition = $sqlCondition." bene_datainfo.DGSUserID in (select ID from bene_user where GroupID=2 and HospitalID='$userHospitalID') and ";
                }
                break;
            case 6:
                if($dgsUserID){
                    $sqlCondition = $sqlCondition." bene_datainfo.DGSUserID='$dgsUserID' and ";
                }else{
                    $sqlCondition = $sqlCondition." bene_datainfo.DGSUserID in (select UserID from bene_user_group where GroupAdminID='$userID') and ";
                };
                break;
            case 7:
                if($optUserID){
                    $sqlCondition = $sqlCondition." bene_datainfo.UserID='$optUserID' and ";
                }else{
                    $sqlCondition = $sqlCondition." bene_datainfo.UserID in (select UserID from bene_user_group where GroupAdminID='$userID') and ";
                }
                break;
            default:
                return false;
        }

        $result = $this->bene_Case_H5_model->searchAllRecord($sqlCondition);
        echo $result;
    }

    /*编辑用户档案信息*/
    public function editProfile(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        @$userInfo       = $_SESSION["UserInfo"];
        @$userAccount    = $userInfo['Account'];

        @$name    = @$_REQUEST['fullName'];        
        @$oldPwd  = @$_REQUEST['oldPwd'];
        @$newPwd  = @$_REQUEST['newPwd'];
        @$newPwd2 = @$_REQUEST['newPwd2'];
        @$gender  = @$_REQUEST['gender'];
        @$age     = @$_REQUEST['age'];

        if($name){
            $name = addslashes($name);
        } 
        if($age){
            $age = addslashes($age);
        }
        @$info    = @$_REQUEST['info'];
        if($info){
            $info = addslashes($info);
        }

        if($newPwd && !$oldPwd){
            $output['code'] = -1;
            $output['msg']  = "previous password is a must to change to a new password";
            echo json_encode($output);
        }

        if($newPwd != $newPwd2){
            $output['code'] = -2;
            $output['msg']  = "two password is not same, please check";
            echo json_encode($output);
        }

        $basicUserInfo = $this->bene_User_H5_model->getBasicUserInfo($userAccount);

        if($basicUserInfo && count($basicUserInfo)>0){
            $Pwd    = $basicUserInfo['Pwd'];
            $Name   = $basicUserInfo['Name'];
            $Gender = $basicUserInfo['Gender'];
            $Age    = $basicUserInfo['Age'];
            $Info   = $basicUserInfo['Info'];
        }else{
            $output['code'] = -3;
            $output['msg']  = "failed to get the previous password";
            echo json_encode($output);
            return false;
        }

        if($oldPwd && $oldPwd!=$Pwd){
            $output['code'] = -3;
            $output['msg']  = "wrong previous password, please check";
            echo json_encode($output);
            return false;
        }

        /*如果新字段为空白，则保留原来字段*/
        if(empty($newPwd)){ $newPwd = $Pwd; }
        if($gender==""){ $gender = $Gender; }
        if(empty($age)){ $age = $Age; }
        if(empty($name)){ $name = $Name; }
        if(empty($info)){ $info = $Info; }

        $result = $this->bene_User_H5_model->updateProfile($newPwd,$name,$gender,$age,$info,$userAccount);
        if($result['code']==1){
            $result= $this->bene_User_H5_model->getUserInfo($userAccount);
            $result['allUserList'] = "";
            echo json_encode($result);
        }
    }

    /*获取采集员的用户列表: 根据分析管理员的医院ID进行筛选*/
    public function getAllOptList(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        $userInfo   = $_SESSION["UserInfo"];
        $groupID    = $userInfo['GroupID'];
        $hospitalID = $userInfo['HospitalID'];

        if($groupID != 4 || empty($hospitalID)){
            echo "group ID must be 4 or hospitalID can not be empty";
            return false;
        }

        $result = $this->bene_User_H5_model->getAllOptList($hospitalID);
        echo $result;
    }

    /*编辑单个账号：更新单个子账号的数据，管理组才有的权限*/
    public function editAccountProfile(){

        @$id           = @$_REQUEST['ID'];
        @$account      = @$_REQUEST['Account'];
        @$name         = @$_REQUEST['Name'];
        @$newPwd       = @$_REQUEST['NewPwd'];
        @$newPwd2      = @$_REQUEST['NewPwd2'];
        @$gender       = @$_REQUEST['Gender'];
        @$age          = @$_REQUEST['Age'];
        @$lock         = @$_REQUEST['Locked'];
        @$downloadFlag = @$_REQUEST['DownloadFlag'];
        @$info         = @$_REQUEST['Info'];
        @$userBind     = @$_REQUEST['BindAccount'];        

        if($lock){
            $lock = 1;
        }else{
            $lock = 0;
        };

        if(empty($id)||empty($account)||empty($name)){
            $output['code'] = -1;
            $output['msg']  = "necessary info is not enough, please check";
            return json_encode($output);
        }

        if($newPwd != $newPwd2){
            $output['code'] = -2;
            $output['msg']  = "password should be same";
            return json_encode($output);
        }

        $id = addslashes($id);
        $account = $account?addslashes($account):$account;
        $name = $name?addslashes($name):$name;
        $age = $age?addslashes($age):$age;
        $info = $info?addslashes($info):$info;


        $result = $this->bene_User_H5_model->editAccountProfile($id,$account,$name,$newPwd,$gender,$age,$lock,$downloadFlag,$info,$userBind);
        echo $result;
    }

    /*获取管理员子账号信息*/
    public function getAccountData(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        $userInfo   = $_SESSION["UserInfo"];
        $ID         = $userInfo['ID'];
        $groupID    = $userInfo['GroupID'];
        $hospitalID = $userInfo['HospitalID'];

        if($groupID==3 || $groupID==4) {
            if (empty($hospitalID)) {
                echo "hospitalID must be provided";
                return false;
            }
            $normalID = $groupID - 2;
            $result = $this->bene_User_H5_model->getAccountData($hospitalID, $normalID);
            echo $result;
        }else if($groupID==6 || $groupID==7) {
            if (empty($ID)) {
                echo "ID is necessary";
                return false;
            }
            $result = $this->bene_User_H5_model->getGroupAccount($ID);
            echo $result;
        }else{
            echo "group ID is wrong";
            return false;
        }
    }

    /*获取组别下的子账号信息*/
    public function getGroupAccount(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        $userInfo   = $_SESSION["UserInfo"];
        $ID         = $userInfo['ID'];
        $groupID    = $userInfo['GroupID'];

        if($groupID==6 || $groupID==7) {
            if (empty($ID)) {
                echo "ID is necessary";
                return false;
            }
            $result = $this->bene_User_H5_model->getGroupAccount($ID);
            echo $result;
        }else{
            echo "group ID should be 6 or 7";
            return false;
        }
    }

    /*获取子组账号信息*/
    public function getGroupData(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        $userInfo = $_SESSION["UserInfo"];
        $groupID  = $userInfo['GroupID'];
        $hospitalID = $userInfo['HospitalID'];
        if($groupID==3 || $groupID==4){
            if(empty($hospitalID)){
                echo "hospitalID must be provided";
                return false;
            }
            $result = $this->bene_User_H5_model->getGroupData($groupID,$hospitalID);
            echo $result;
        }else if($groupID==7){
            $result = $this->bene_User_H5_model->getAlyGroupList($hospitalID);
            $result = json_decode($result,true);
            if($result['code']==1){
                $result['selectedGroup'] = $this->bene_User_H5_model->getSelectedGroupID($userInfo['ID']);
            };
            echo json_encode($result);
        }else{
            echo "group ID should be 3 or 4 or 7";
            return false;
        }
    }

    /*更新操作组绑定的分析组信息*/
    public function updateBindAlyGroup(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        @$optGroupAccountID = @$_REQUEST['optGroupAccountID'];
        @$checkedAlyGroupID = @$_REQUEST['checkedAlyGroupAccountID'];
        if(empty($optGroupAccountID)){
            return false;
        }
        $this->load->model('bene_UserGroup_H5_model');
        $result = $this->bene_UserGroup_H5_model->updateBindAlyGroup($optGroupAccountID,$checkedAlyGroupID);
        echo $result;

    }

    /*获取分析员下的绑定的操作员信息*/
    public function getAccountBind(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        $userInfo = $_SESSION["UserInfo"];
        if($userInfo['GroupID']==2){
            $result = $this->bene_User_H5_model->getSingleAccountBind($userInfo['ID']);
            echo $result;
        }else{
            echo "user group should be 2";
            return false;
        }
    }

    /*获取单个用户账号的绑定信息 : 当登录的用户组别为4或者6的时候有必要获取现有分析员所绑定的操作员信息*/
    public function getSingleAccountBind($accountID){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        if(empty($accountID)){
            echo "account ID is a must";
            return false;
        }
        session_start();
        $userInfo = $_SESSION["UserInfo"];
        if($userInfo['GroupID']==4 || $userInfo['GroupID']==6 || $userInfo['GroupID']==5){
            $result = $this->bene_User_H5_model->getSingleAccountBind($accountID);
            echo $result;
        }else{
            echo "user group should be 4 or 6 or 5";
            return false;
        }
    }

    /*根据用户组ID获取相应成员的ID列表*/
    public function getGroupUserList($ID){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        if(!$ID){
            echo "ID is a must";
            return false;
        }
        $result = $this->bene_User_H5_model->getGroupUserList($ID);
        echo $result;
    }

    /*更改单个账号的状态，只有管理员才有的权限*/
    public function toggleAccountStatus($id,$status){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        if(empty($id) || $status==""){
            echo "id and status can not be empty";
            return false;
        }

        $status = $status?0:1;

        $result = $this->bene_User_H5_model->toggleAccountStatus($id,$status);
        echo $result;
    }


    /*当是管理员时 编辑用户组 更新bene_user_group数据表*/
    public function updateGroupBind(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }

        @$adminID  = @$_REQUEST['id'];
        @$members  = @$_REQUEST['member'];

        if(empty($adminID)){
            echo 'admin ID is undefined or members is not array';
            return false;
        }

        $result = $this->bene_User_H5_model->updateGroupBind($adminID, $members);
        echo $result;
    }


    /*向数据库表bene_user中写入新用户 和 新用户组
   1.先检查数据的完整性
   2.再检查用户名是否有重复
   3.经过以上检查后写入新数据
   4.如果新增成功，则返回相同用户组GROUPID的数据集
   */
    public function addNewAccount(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }

        @$account      = @$_REQUEST['account'];
        @$pwd          = @$_REQUEST['pwd'];
        @$pwd2         = @$_REQUEST['pwd2'];
        @$hospitalID   = @$_REQUEST['hospitalID'];
        @$groupID      = @$_REQUEST['groupID'];
        @$gender       = @$_REQUEST['gender'];
        @$name         = @$_REQUEST['fullName'];
        @$age          = @$_REQUEST['age'];
        @$info         = @$_REQUEST['info'];
        @$downloadFlag = @$_REQUEST['download'];
        @$lock         = @$_REQUEST['locked']?1:0;
        @$userBind     = @$_REQUEST['userBind'];

        $createTime    = date('Y-m-d H:i:s');

        if(empty($account) || empty($pwd) || empty($pwd2) || empty($hospitalID) || empty($groupID)){
            $output['code'] = -1;
            $output['msg']  = "the key info can not be empty, please check";
            return json_encode($output);
        }

        if($pwd != $pwd2){
            $output['code'] = -2;
            $output['msg']  = "password must be same, please check";
            return json_encode($output);
        }

        if($gender==""){
            $gender = 2;
        }

        $account = addslashes($account);
        $hospitalID = addslashes($hospitalID);
        $name = addslashes($name);
        $age = addslashes($age);
        $info = addslashes($info);

        $result = $this->bene_User_H5_model->addNewAccount($hospitalID,$groupID,$account,$pwd,$name,$gender,$age,$info,$downloadFlag,$lock,$createTime,$userBind);
        echo $result;
    }

    /*检查账号是否重复 - 新增用户或者新增用户组时用*/
    public function checkAccountRepeat($account){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        $result = $this->bene_User_H5_model->checkAccountRepeat($account);
        echo $result;
    }

    /*获取所有医院的列表*/
    public function getAllHospitalList(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        $sql = "select * from bene_hospital where true";
        $result = $this->db->query($sql);

        if($result){
            $result = $result->result_array();
            if(count($result)>0){
                $output['code']  = 1;
                $output['msg']   = "get the hospital list successfully";
                $output['hospitalList']= $result;
            }else{
                $output['code'] = -1;
                $output['msg']  = "get no hospital list, please check";
            }
        }else{
            $output['code'] = -2;
            $output['msg']  = "error caused during accessing the database, please check";
        }
        echo json_encode($output);
    }


    /*获取在线用户列表 bene_online : 从bene_online 和 bene_user表中获取在线用户的UserID列表（合并）*/
    public function getOnlineUserList(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        $sql = "SELECT * FROM  bene_online LEFT JOIN bene_user ON bene_online.UserID = bene_user.ID LIMIT 0 , 1000";
        $result = $this->db->query($sql);

        if($result){
            $result = $result->result_array();
            if(count($result)>0){
                $output['code']  = 1;
                $output['msg']   = "get the online user ID list successfully";
                $output['userList']= $result;
            }else{
                $output['code'] = -1;
                $output['msg']  = "online user ID list is empty, please check";
            }
        }else{
            $output['code'] = -2;
            $output['msg']  = "error caused during accessing the database, please check";
        }
        echo json_encode($output);
    }

    /*获取医院统计图表需要的数据:医院所属类别,几家社区医院,几名分析医生,几名采集医生,相应时间段采集和上传的病例数据*/
    public function getChartData($hospitalID,$period){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        if(empty($hospitalID) || empty($period)){
            return false;
        }

        //先判断医生分类:是超级管理员, 还是分析医院,或者社区医院
        session_start();
        $userInfo = $_SESSION["UserInfo"];
        if($hospitalID == $userInfo['HospitalID']){
            $output['hospitalType'] = "super";

            $sql = "select count(ID) as dgsHospitalNum from bene_hospital where ID=DGSHospitalID";
            $result = $this->db->query($sql);
            $result = $result -> result_array();
            $output['dgsHospitalNum'] = $result[0]['dgsHospitalNum']-1;

            $sql = "select count(ID) as optHospitalNum from bene_hospital where ID!=DGSHospitalID";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['optHospitalNum'] = $result[0]['optHospitalNum'];

            $sql = "select count(ID) as analyserNum from bene_user where GroupID=2";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['analyserNum'] = $result[0]['analyserNum'];

            $sql = "select count(ID) as operatorNum from bene_user where GroupID=1";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['operatorNum'] = $result[0]['operatorNum'];
            $output['caseRecord'] = array();

            //当是超级管理员时，读取所有的病例的时间段
            $sql = "select min(SubmitTime) as submitTimeStart, max(SubmitTime) as submitTimeEnd from bene_datainfo";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $submitTimeStart = $result[0]['submitTimeStart'];
            $submitTimeEnd   = $result[0]['submitTimeEnd'];
            $datePadding = ceil((strtotime("+1 day")-strtotime($submitTimeStart))/(24*60*60));

            if($period == "day"){
                //如果周期是day，则默认读取所有（大于200天）或者 最近200天的病例数据
                $days = $datePadding>=200?200:$datePadding;
                for($i=-$days;$i<1;$i++){
                    $temp = array();
                    $d = strtotime($i." day");
                    $d = date("Y-m-d",$d);
                    $d9 = $d." 23:59:59";

                    $temp['date'] = $d;

                    $sql = "select count(ID) as collected from bene_datainfo where SubmitTime between '$d' and '$d9'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UploadedTime between '$d' and '$d9'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;
                }
            }else if($period == "week"){
                //如果周期是week，则读取至今所有记录周期的数据
                $startWeekDayStamp = strtotime("-".date("w",strtotime($submitTimeStart))." days", strtotime($submitTimeStart));
                $endWeekDayStamp = strtotime("+1 day");

                while($startWeekDayStamp<$endWeekDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startWeekDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 week",$startWeekDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startWeekDayStamp = strtotime("+1 week",$startWeekDayStamp);
                }
            }else if($period == "month"){
                //如果周期是month，则默认读取所有月份的病例数据
                $startDayStamp = strtotime("-".(date("d",strtotime($submitTimeStart))-1)." days", strtotime($submitTimeStart));
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 month",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+1 month",$startDayStamp);
                }
            }else if($period == "season"){
                //如果周期是month，则默认读取所有月份的病例数据
                $startMonth = date("m",strtotime($submitTimeStart));
                $startSeasonMonth = ceil($startMonth/3)*3-2;

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-".$startSeasonMonth."-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+3 months",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+3 months",$startDayStamp);
                }
            }else if($period == "halfYear"){
                //如果周期是halfYear，则默认读取所有月份的病例数据
                $startMonth = date("m",strtotime($submitTimeStart))<6?1:7;

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-".$startMonth."-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){

                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+6 months",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+6 months",$startDayStamp);
                }
            }else{
                //如果周期是year,则默认读取所有年份的病例数据
                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-01-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 year",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+1 year",$startDayStamp);
                }
            }

            echo json_encode($output);
            return false;
        }

        $sql = "select DGSHospitalID from bene_hospital where ID='$hospitalID' AND ID=DGSHospitalID";
        $result = $this->db->query($sql);
        $result = $result->result_array();

        if($result && count($result)==1){
            $output['hospitalType'] = "analyse";
            //是分析医院的话,则返回 社区医院数量 + 分析医生数量 + 采集医生数量
            $sql = "select count(ID) as optHospitalNum from bene_hospital where DGSHospitalID='$hospitalID' and DGSHospitalID!=ID";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['optHospitalNum'] = $result[0]['optHospitalNum'];

            $sql = "select count(ID) as analyserNum from bene_user where HospitalID='$hospitalID' and GroupID=2";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['analyserNum'] = $result[0]['analyserNum'];

            $sql = "select count(ID) as operatorNum from bene_user where HospitalID in (select ID from bene_hospital where DGSHospitalID='$hospitalID' and DGSHospitalID!=ID) and GroupID=1";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['operatorNum'] = $result[0]['operatorNum'];

            //当是分析医院时，读取分析医院下面所有病例的时间段
            $sql = "select min(SubmitTime) as submitTimeStart, max(SubmitTime) as submitTimeEnd from bene_datainfo where DGSHospitalID='$hospitalID'";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $submitTimeStart = $result[0]['submitTimeStart'];
            $submitTimeEnd   = $result[0]['submitTimeEnd'];
            $datePadding = ceil((strtotime("+1 day")-strtotime($submitTimeStart))/(24*60*60));

            if($period == "day"){
                //如果周期是day，则默认读取所有（如果总记录小于200天）或者 最近200天的病例数据
                $days = $datePadding<200?$datePadding:200;
                for($i=-$days;$i<1;$i++){
                    $temp = array();
                    $d = strtotime($i." day");
                    $d = date("Y-m-d",$d);
                    $d9 = $d." 23:59:59";

                    $temp['date'] = $d;

                    $sql = "select count(ID) as collected from bene_datainfo where DGSHospitalID='$hospitalID' and SubmitTime between '$d' and '$d9'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where DGSHospitalID='$hospitalID' and UploadedTime between '$d' and '$d9'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;
                }
            }else if($period == "week"){
                //如果周期是week，则读取至今所有记录周期的数据
                $startWeekDayStamp = strtotime("-".date("w",strtotime($submitTimeStart))." days", strtotime($submitTimeStart));
                $endWeekDayStamp = strtotime("+1 day");

                while($startWeekDayStamp<$endWeekDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startWeekDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 week",$startWeekDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where DGSHospitalID='$hospitalID' and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where DGSHospitalID='$hospitalID' and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startWeekDayStamp = strtotime("+1 week",$startWeekDayStamp);
                }
            }else if($period == "month"){
                //如果周期是month，则默认读取所有月份的病例数据
                $startDayStamp = strtotime("-".(date("d",strtotime($submitTimeStart))-1)." days", strtotime($submitTimeStart));
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 month",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where DGSHospitalID='$hospitalID' and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where DGSHospitalID='$hospitalID' and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+1 month",$startDayStamp);
                }
            }else if($period == "season"){
                //如果周期是month，则默认读取所有月份的病例数据
                $startMonth = date("m",strtotime($submitTimeStart));
                $startSeasonMonth = ceil($startMonth/3)*3-2;

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-".$startSeasonMonth."-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+3 months",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where DGSHospitalID='$hospitalID' and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where DGSHospitalID='$hospitalID' and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+3 months",$startDayStamp);
                }
            }else if($period == "halfYear"){
                //如果周期是halfYear，则默认读取所有月份的病例数据
                $startMonth = date("m",strtotime($submitTimeStart))<6?1:7;

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-".$startMonth."-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){

                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+6 months",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where DGSHospitalID='$hospitalID' and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where DGSHospitalID='$hospitalID' and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+6 months",$startDayStamp);
                }
            }else{
                //如果周期是year,则默认读取所有年份的病例数据

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-01-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 year",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where DGSHospitalID='$hospitalID' and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where DGSHospitalID='$hospitalID' and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+1 year",$startDayStamp);
                }
            }

        }else{
            $output['hospitalType'] = "operator";
            //是社区医院的话,则返回采集医生的数量
            $sql = "select count(ID) as operatorNum from bene_user where HospitalID='$hospitalID' and GroupID=1 ";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $output['operatorNum'] = $result[0]['operatorNum'];

            //当是社区医院时，读取社区医院下面所有病例的时间段
            $sql = "select min(SubmitTime) as submitTimeStart, max(SubmitTime) as submitTimeEnd from bene_datainfo where UserID in (select ID from bene_user where HospitalID ='$hospitalID' and GroupID=1)";
            $result = $this->db->query($sql);
            $result = $result->result_array();
            $submitTimeStart = $result[0]['submitTimeStart'];
            $submitTimeEnd   = $result[0]['submitTimeEnd'];
            $datePadding = ceil((strtotime("+1 day")-strtotime($submitTimeStart))/(24*60*60));

            if($period == "day"){
                //如果周期是day，则默认读取所有（如果总记录小于200天）或者 最近200天的病例数据
                $days = $datePadding<200?$datePadding:200;
                for($i=-$days;$i<1;$i++){
                    $temp = array();
                    $d = strtotime($i." day");
                    $d = date("Y-m-d",$d);
                    $d9 = $d." 23:59:59";

                    $temp['date'] = $d;

                    $sql = "select count(ID) as collected from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and SubmitTime between '$d' and '$d9'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and UploadedTime between '$d' and '$d9'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;
                }
            }else if($period == "week"){
                //如果周期是week，则读取至今所有记录周期的数据
                $startWeekDayStamp = strtotime("-".date("w",strtotime($submitTimeStart))." days", strtotime($submitTimeStart));
                $endWeekDayStamp = strtotime("+1 day");

                while($startWeekDayStamp<$endWeekDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startWeekDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 week",$startWeekDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startWeekDayStamp = strtotime("+1 week",$startWeekDayStamp);
                }
            }else if($period == "month"){
                //如果周期是month，则默认读取所有月份的病例数据
                $startDayStamp = strtotime("-".(date("d",strtotime($submitTimeStart))-1)." days", strtotime($submitTimeStart));
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 month",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+1 month",$startDayStamp);
                }
            }else if($period == "season"){
                //如果周期是month，则默认读取所有月份的病例数据
                $startMonth = date("m",strtotime($submitTimeStart));
                $startSeasonMonth = ceil($startMonth/3)*3-2;

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-".$startSeasonMonth."-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+3 months",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+3 months",$startDayStamp);
                }
            }else if($period == "halfYear"){
                //如果周期是halfYear，则默认读取所有月份的病例数据
                $startMonth = date("m",strtotime($submitTimeStart))<6?1:7;

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-".$startMonth."-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){

                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+6 months",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+6 months",$startDayStamp);
                }
            }else{
                //如果周期是year,则默认读取所有年份的病例数据

                $startDayStamp = strtotime(substr($submitTimeStart,0,4)."-01-01");
                $endDayStamp = strtotime("+1 day");

                while($startDayStamp < $endDayStamp){
                    $temp = array();

                    $w1 = date('Y-m-d',$startDayStamp);
                    $w2 = date("Y-m-d",strtotime("+1 year",$startDayStamp));

                    $temp['date'] = $w1;

                    $sql = "select count(ID) as collected from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and SubmitTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['collected'] = $result[0]['collected'];

                    $sql = "select count(ID) as uploaded from bene_datainfo where UserID in (select ID from bene_user where HospitalID='$hospitalID' and GroupID=1) and UploadedTime between '$w1' and '$w2'";
                    $result = $this->db->query($sql);
                    $result = $result->result_array();

                    $temp['uploaded'] = $result[0]['uploaded'];

                    $output['caseRecord'][] = $temp;

                    $startDayStamp = strtotime("+1 year",$startDayStamp);
                }
            }
        };

        echo json_encode($output);

    }

    /*提交留言或者反馈*/
    public function submitFeedback(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }

        @$account  = @$_REQUEST['account'];
        @$name  = @$_REQUEST['name'];
        @$phone = @$_REQUEST['phone'];
        @$email = @$_REQUEST['email'];
        @$content = @$_REQUEST['content'];

        if(empty($account) || (empty($phone) && empty($email)) || empty($content)){
            return false;
        }

        $account = addslashes($account);
        $phone = $phone?addslashes($phone):$phone;
        $email = $email?addslashes($email):$phone;
        $content = $content?addslashes($content):$phone;

        $time = date("Y-m-d H:i:s",time());
        $result = $this->bene_Message_H5_model->addNewFeedback($account,$name,$phone,$email,$content,$time);
        echo $result?1:0;
    }

    /*获取留言列表 */
    public function getMessageList(){
        if(!$this->checkLogStatus()){
            echo "offline";
            return false;
        }
        session_start();
        $account = $_SESSION['Account'];
        $result = $this->bene_Message_H5_model->getMessageListByAccount($account);
        echo json_encode($result);
    }

    /*获取*/

}