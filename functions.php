<?php

	require 'Aws/aws-autoloader.php';
	use Aws\S3\S3Client;
	
	include_once('kyc-dashboard/include/database.php');
	include_once 'Sms.php';
	//include_once("classes/Email.class.php");
	require("smtp/dcaf/class.phpmailer.php");
	//include_once('include/classes/SaveImage.class.php');
	//define("PREFIX","ho_");
	
	//ini_set(timezone_open,"Asia/Kolkata");
	
	class Functions extends Database {
		private $groupType = 'user';
		private $userType = 'Emp';
		function writeToFile($string, $url = "undefined URL"){
			/* write to log for FLIGHT API */
			$logWriter = new LogWriter();
			$logWriter->setFileDirectory("kyc-dashboard/log/sms/");
			$logWriter->setFileName('log.txt');
			$logWriter->writeToNewFilePerDay($string, $url);
		}
		
		function loginSession($userId, $userFirstName, $userType) {
			/* DEPRECATED $_SESSION[SITE_NAME] = array(
				$this->userType."UserId" => $userId,
				$this->userType."UserFirstName" => $userFirstName,
				$this->userType."UserLastName" => $userLastName,
				$this->userType."UserType" => $this->userType
			); DEPRECATED */
			$_SESSION[SITE_NAME][$this->userType."UserId"] = $userId;
			$_SESSION[SITE_NAME][$this->userType."UserFullName"] = $userFirstName;
			//$_SESSION[SITE_NAME][$this->userType."UserLastName"] = $userLastName;
			$_SESSION[SITE_NAME][$this->userType."UserType"] = $this->userType;
			$session=session_id();
			$login_ip = $this->escape_string($this->strip_all($_SERVER['REMOTE_ADDR']));
			$now_datetime = date("Y-m-d H:i:s");
			$this->query("insert into ".PREFIX."user_login_details(user_id, login_ip,session_id,created) values('$userId', '$login_ip','$session','$now_datetime')");
			/*switch($userType){
				case:'admin'{
					break;
				}
				case:'supplier'{
					break;
				}
				case:'warehouse'{
					break;
				}
				
			}*/
		}
		function logoutSession() {
			if(isset($_SESSION[SITE_NAME])){
				if(isset($_SESSION[SITE_NAME][$this->userType."UserId"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserId"]);
				}
				if(isset($_SESSION[SITE_NAME][$this->userType."UserFullName"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserFullName"]);
				}
				/* if(isset($_SESSION[SITE_NAME][$this->userType."UserLastName"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserLastName"]);
				} */
				if(isset($_SESSION[SITE_NAME][$this->userType."UserType"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserType"]);
				}
				return true;
			} else {
				return false;
			}
		}
		function adminLogin($data, $successURL, $failURL = "index.php?failed" , $lockURL = "index.php?locked" ,$absentURL = "index.php?user-does-not-exists", $incomplete = "index.php?incomplete" ) {
			$username = $this->escape_string($this->strip_all($data['logemail']));
			$password = $this->escape_string($this->strip_all($data['logpwd']));
			$query = "select * from ".PREFIX."admin where username='".$username."' and active='Yes'";
			$result = $this->query($query);
			$ip =$_SERVER['SERVER_ADDR'];
			
			//check attempt has done in last 24 hrs
			$sqlattempts=$this->query("SELECT * FROM tata_attempts WHERE username='$username' and attempts='3' and last_modify > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
			if($this->num_rows($result) > 0) {
				
				if($this->num_rows($sqlattempts) <= 0)
				{
					
					//delete all more then 24 hrs login fail
					$query = "delete from ".PREFIX."attempts WHERE username='$username'  and last_modify < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
				
					$this->query($query);
					
					if($this->num_rows($result) == 1) { // only one unique user should be present in the system
						$row = $this->fetch($result);
						
						if(password_verify($password, $row['password'])) {
							
							$this->loginSession($row['id'], $row['full_name'], $this->userType);
							
							 //Add notification_msg
							 $sql = $this->query("SELECT * FROM tata_admin WHERE username='$username' and id=".$row['id']." and active='Yes'");
							 $sqladmin = $this->fetch($sql);
							 if(empty($sqladmin['parent']) && $sqladmin['designation'] != 'Super-admin' && $sqladmin['designation'] != 'NH' && $sqladmin['eba_rights'] != '1' && $sqladmin['designation'] != 'EBA' && $sqladmin['designation'] != 'Sales support' && $sqladmin['designation'] != 'COE Verifier'){
							 	$this->close_connection();	
								$this->logoutSession();
								return $incomplete;
								
							 }
							 $result=$this->query("select * from ".PREFIX."user_login_details where user_id='".$row['id']."' order by id desc limit 1,1");
							 $sqladmindata = $this->fetch($result);
							 //and last_modify >='".$sqladmindata['created']."'
							 $ldata = $this->query("SELECT * FROM ".PREFIX."attempts WHERE username = '$username' and uid=".$row['id']." and last_modify >='".$sqladmindata['created']."'");
							 
							 $ladata=$this->fetch($ldata);
							 $lcount = $ladata['attempts'];
							 //echo "SELECT * FROM ".PREFIX."attempts WHERE username = '$username' and uid=".$row['id']."  and last_modify >='".$sqladmindata['created']."'";exit;
							 $user_id = $sqladmin['id'];
							 $this->query("delete from ".PREFIX."user_notification WHERE user_id = ".$user_id."");
							 if(!empty($lcount))
							 { 
								 $last_login = date('d/m/Y h:i A',strtotime($sqladmindata['created']));
								 $notification_msg="Last login :$last_login ,Unsuccessful login attempts:$lcount";
								$ndate = date('Y-m-d H:i:s');
								 $this->query("insert into ".PREFIX."user_notification (user_id,notification_msg,from_id,date) values ('".$user_id."','".$notification_msg."','','$ndate')");
							 }
							 $presults=$this->query("select * from ".PREFIX."password_history where user_id='".$row['id']."' order by id desc");
							 $psqladmindata = $this->fetch($presults);
							 if(empty($psqladmindata))
							 {
								  $this->close_connection();						
								  return "change_password.php";
							 }else{
								$this->close_connection();						
								return $successURL; 
							 }
							
							//header("location: ".$successURL);
							//exit;
						} 
						else 
						{	
							
							//Check username record
							$uid = $row['id'];
							$attemptdata = $this->getUniqueAttemptById($username);
							$admindata = $this->getUniqueAdmin();
							$aresult = $this->query("select * from ".PREFIX."attempts where username='".$username."' and uid= '".$uid."' ");
							$ndate = date('Y-m-d H:i:s');
							
							if($this->num_rows($aresult) >0){
								
								$attempts = $attemptdata['attempts'];
								if($attempts == 3){
									
									$q = "update ".PREFIX."attempts set attempts='$attempts'  last_modify='$ndate' where username='$username' and  uid= '$uid' ";
									$this->query($q);
									
								}else
								{
									$attempts = $attemptdata["attempts"]+1; 
									$q = "update ".PREFIX."attempts set attempts='$attempts', last_modify='$ndate'  where username='$username' and  uid= '$uid' ";
									$this->query($q);
									if($attempts == 3){
										// == SEND EMAIL TO USER==
										include_once("block-user-ip.inc.php");
										//echo $emailMsg;
										//exit;
										$mail = new PHPMailer();
										$mail->IsSMTP();
										$mail->SMTPAuth = true;
										$mail->AddAddress($username);
										$mail->IsHTML(true);
										$mail->Subject = "Login Details | ".SITE_NAME;
										$mail->Body = $emailMsg;
										$mail->Send();
										
										
										// $emailObj = new Email();
										// $emailObj->setAddress($username);
										// //$emailObj->setAdminAddress(ADMIN_EMAIL);
										// $emailObj->setSubject("Login Details | ".SITE_NAME);
										// $emailObj->setEmailBody($emailMsg);
										// $res = $emailObj->sendEmail();
										
										$asql = $this->query("SELECT * FROM tata_admin WHERE role='super' and active='Yes'");
										$aadmin = $this->fetch($asql);
										
										// == SEND EMAIL TO ADMIN==
										include_once("block-user-admin.inc.php");
										//echo $emailMsg;
										//exit;
										$mail = new PHPMailer();
										$mail->IsSMTP();
										$mail->SMTPAuth = true;
										$mail->AddAddress($admindata['username']);
										$mail->IsHTML(true);
										$mail->Subject = "Login Details | ".SITE_NAME;
										$mail->Body = $emailMsg;
										$mail->Send();
										
										// $emailObj = new Email();
										// //$emailObj->setAddress($username);
										// $emailObj->setAddress($aadmin['username']);
										// $emailObj->setSubject("Login Details | ".SITE_NAME);
										// $emailObj->setEmailBody($emailMsg);
										// $resss = $emailObj->sendEmail();
										
									}
									
								}
								
							}else{
								$ndate = date('Y-m-d H:i:s');
								 $q = "INSERT INTO ".PREFIX."attempts (attempts,username,ip,last_modify,uid,type) values (1,'$username', '$ip','$ndate',$uid,'')";
								 $this->query($q);
							}
							
							
							$this->close_connection();
							return $failURL;
							//header("location: ".$failURL);
							//exit;
						}
					} else {
						
						$this->close_connection();
						return $failURL;
						// header("location: ".$failURL);
						// exit;
					}
				}else{
					
					$this->close_connection();
					return $lockURL;
					// header("location: ".$lockURL);
					// exit;
				}
			}else{
					$this->close_connection();
					return $absentURL;
			}
		}
		
		
		function sessionExists(){
			if($this->isUserLoggedIn()){
				return $loggedInUserDetailsArr = $this->getLoggedInUserDetails();
				// return true; // DEPRECATED
			} else {
				return false;
			}
		}
		function isUserLoggedIn(){
			if( isset($_SESSION[SITE_NAME]) && 
				isset($_SESSION[SITE_NAME][$this->userType.'UserId']) && 
				isset($_SESSION[SITE_NAME][$this->userType.'UserType']) && 
				!empty($_SESSION[SITE_NAME][$this->userType.'UserId']) &&
				$_SESSION[SITE_NAME][$this->userType.'UserType']==$this->userType){
				return true;
			} else {
				return false;
			}
		}
		function getSystemUserType() {
			return $this->userType;
		}
		function getLoggedInUserDetails(){
			$loggedInID = $this->escape_string($this->strip_all($_SESSION[SITE_NAME][$this->userType.'UserId']));
			$loggedInUserDetailsArr = $this->getUniqueUserById($loggedInID);
			return $loggedInUserDetailsArr;
		}
		function getUniqueUserById($userId) {
			$userId = $this->escape_string($this->strip_all($userId));
			$query = "select * from ".PREFIX."admin where id='".$userId."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		//newly added 22-01-18
		function getUniqueUserPermissionById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."user_permissions where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueUserPermissionByRole($role) {
			$role = $this->escape_string($this->strip_all($role));
			if($role=='COE Verifier'){
				$role = 'COE';
			}
			$query = "select * from ".PREFIX."user_permissions where role='".$role."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueSegmentPermissions($role,$segment) {
			$role = $this->escape_string($this->strip_all($role));
			$segment = $this->escape_string($this->strip_all($segment));
			if($role=='COE Verifier'){
				$role = 'COE';
			}
			$query = "select * from ".PREFIX."segment_master where role='".$role."' and segment='".$segment."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		 function UpdateUserPermissions($data){
			$id = $this->escape_string($this->strip_all($data['id']));
			$role = $this->escape_string($this->strip_all($data['role']));
			$create = $this->escape_string($this->strip_all($data['create']));
			$update = $this->escape_string($this->strip_all($data['update1']));
			$delete = $this->escape_string($this->strip_all($data['delete']));
			$export = $this->escape_string($this->strip_all($data['export']));
			$import = $this->escape_string($this->strip_all($data['import']));
			$view = $this->escape_string($this->strip_all($data['view']));
			$cso_functions = $this->escape_string($this->strip_all($data['cso_functions']));
			 if($role=='PM'){
				$qry = $this->query("UPDATE `tata_user_permissions` SET`create`='$create',`update`='$update',`delete`='$delete',`export`='$export',`import`='$import',`view`='$view',`cso_functions`='$cso_functions' WHERE role='PAM' or role='CAM'");
			} 
			$query = "UPDATE `tata_user_permissions` SET`create`='$create',`update`='$update',`delete`='$delete',`export`='$export',`import`='$import',`view`='$view',`cso_functions`='$cso_functions' WHERE id='".$id."'";
			$sql = $this->query($query);
		} 
		function UpdateSegmentMaster($data){
			$id = $this->escape_string($this->strip_all($data['id']));
			$role = $this->escape_string($this->strip_all($data['role']));
			$create = $this->escape_string($this->strip_all($data['create']));
			$update = $this->escape_string($this->strip_all($data['update1']));
			$delete = $this->escape_string($this->strip_all($data['delete']));
			$export = $this->escape_string($this->strip_all($data['export']));
			$import = $this->escape_string($this->strip_all($data['import']));
			$view = $this->escape_string($this->strip_all($data['view']));
			
			/*  if($role=='PM'){
				$qry = $this->query("UPDATE ".PREFIX."segment_master SET `create`='$create',`update`='$update',`delete`='$delete',`export`='$export',`import`='$import',`view`='$view' WHERE role='PAM' or role='CAM'");
			} */ 
			$query = "UPDATE ".PREFIX."segment_master SET `create`='$create',`update`='$update',`delete`='$delete',`export`='$export',`import`='$import',`view`='$view' WHERE id='".$id."'";
			
			$sql = $this->query($query);
		}
		function AddSegmentMaster($data){
			$role = $this->escape_string($this->strip_all($data['role']));
			$segment = $this->escape_string($this->strip_all($data['segment']));
			$create = $this->escape_string($this->strip_all($data['create']));
			$update = $this->escape_string($this->strip_all($data['update1']));
			$delete = $this->escape_string($this->strip_all($data['delete']));
			$export = $this->escape_string($this->strip_all($data['export']));
			$import = $this->escape_string($this->strip_all($data['import']));
			$view = $this->escape_string($this->strip_all($data['view']));
			$date = date("Y-m-d H:i:s");
			$query = "insert into ".PREFIX."segment_master (`role`, `segment`, `create`, `update`, `delete`, `export`, `import`, `view`, `created`) values ('".$role."','".$segment."','".$create."','".$update."','".$delete."','".$export."','".$import."','".$view."','".$date."')";
			
			$sql = $this->query($query);
			
		}
		// === LOGIN ENDS ====
		
		// === USER MANAGEMENT STARTS ===
		
		function getAllEmployee(){
			return $this->query("select * from ".PREFIX."admin where id<>1");
		}
		
		function getAllEmployeeForForm(){
			return $this->query("select * from ".PREFIX."admin where id<>1 and (designation='Relationship Manager (RM)' || designation='CAM' || designation='PAM' || designation='Partner')");
		}
		
		function getUniqueUserBydesignation($designation){
			return $result=$this->query("select * from ".PREFIX."admin where designation='".$designation."'");
			//return $this->fetch($result);
		}
		
		function getAllUserByDesignation($designation){
			//echo $designation;
			//exit;
			// if($designation=="Zoanl Head (ZH)"){
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='Sales Head (SH)'";
			// }elseif($designation=="PAM"){
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='Zonal Head (ZH)'";
			// }elseif($designation=="CAM"){
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='Zonal Head (ZH)'";
			// }elseif($designation=="Partner"){
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='PAM'";
			// }elseif($designation=="Regional Head"){
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='NH'";
			// }elseif($designation=="Relationship Manager (RM)"){
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='Regional Head'";
			// }else{
				// $sql="select * from ".PREFIX."admin where id<>1 and designation='".$designation."'";
			// }
			
			if($designation=="Zonal Head (ZH)"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='Regional Head'";
			}elseif($designation=="COE Verifier"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='Channel Partner'";
			}elseif($designation=="SSP"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='COE Verifier'";
			}elseif($designation=="Inside Sales Central"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='SSP'";
			}elseif($designation=="CAM"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='Zonal Head (ZH)'";
			}elseif($designation=="PAM"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='Zonal Head (ZH)'";
			}elseif($designation=="PM"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='Zonal Head (ZH)'";
			}elseif($designation=="Channel Partner"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation IN ('CAM', 'PAM', 'PM')";
			}elseif($designation=="Regional Head"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='NH'";
			}elseif($designation=="Relationship Manager (RM)"){
				$sql="select * from ".PREFIX."admin where id<>1 and designation='Regional Head'";
			}else{
				$sql="select * from ".PREFIX."admin where id<>1 and designation='".$designation."'";
			}
			return $this->query($sql);
		}
		
		function addUser($data) {
			$fname = $this->escape_string($this->strip_all($data['name']));
			$username = $this->escape_string($this->strip_all($data['username']));
			$password1 = $this->escape_string($this->strip_all($data['password']));
			$emp_code = $this->escape_string($this->strip_all($data['emp_code']));
			$contact_no = $this->escape_string($this->strip_all($data['contact_no']));
			$designation = $this->escape_string($this->strip_all($data['designation']));
			
			$cluster = $this->escape_string($this->strip_all($data['cluster']));
			$clusterValueArr 	= $data['cluster_value'];
			$clusterValueImp = implode(',',$data['cluster_value']);
			
			$state = $this->escape_string($this->strip_all($data['state']));
			$city = $this->escape_string($this->strip_all($data['city']));
			//$login_circle = $this->escape_string($this->strip_all($data['login_circle']));
			$region = $this->escape_string($this->strip_all($data['rights']));
			$password = password_hash($password1, PASSWORD_DEFAULT);
			$segment = $this->escape_string($this->strip_all($data['segment']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$inactive_reason = $this->escape_string($this->strip_all($data['inactive_reason']));
			if(!empty($data['cp_company_name'])){
				$cp_company_name = $this->escape_string($this->strip_all($data['cp_company_name']));
			}else{
				$cp_company_name = '';
			}
			$parent='';
			$pam='';
			$cam ='';
			$partner='';
			$rm='';
			if($designation=="Zonal Head (ZH)"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="CAM"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="Channel Partner"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="COE Verifier"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="SSP"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="Inside Sales Central"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}
			if($designation == "Sales support"){
				if($region == "HQ"){
					$this->query("insert into tata_caf_emp_state(state_id,emp_code) values('0','".$emp_code."') ");
				} else if($region == "Mumbai"){
					$this->query("insert into tata_caf_emp_state(state_id,emp_code) values('18258','".$emp_code."') ");
				} else{
					$camuserResult = $this->query("select * from  tata_state_master where region = '$region'");
					while($camuserRow = $this->fetch($camuserResult)){
						$this->query("insert into tata_caf_emp_state(state_id,emp_code) values('".$camuserRow['id']."','".$emp_code."') ");
					}
				}
			}
			
			$permissionArray = array();
			$permissions = '';
			if(isset($data['permissions'])) {
				foreach($data['permissions'] as $permission) {
					$permission = $this->escape_string($this->strip_all($permission));
					if(!empty($permission)){
						$permissionArray[] = $permission;
					}
				}
				if(count($permissionArray)>0){
					$permissions = implode(",", $permissionArray);
				}
			}
			
			$eba_right = '0';
			if($designation == 'EBA' || $designation == 'EBA LE'){
				$eba_right = '1';
			}

			$query = "insert into ".PREFIX."admin(full_name, emp_code, designation, contact_no, parent, cam, pam, partner, rm, username, password, role, permissions, state, city, user_permissions,cluster_val,cluster_value, active, inactive_reason, cp_company_name,region, eba_rights, segment) values ('".$fname."', '".$emp_code."', '".$designation."', '".$contact_no."', '".$parent."', '".$cam."', '".$pam."', '".$partner."', '".$rm."', '".$username."', '".$password."', 'sub-admin', 'kyc-update', '".$state."', '".$city."', '".$permissions."', '".$cluster."', '".$clusterValueImp."', '".$active."', '".$inactive_reason."', '".$cp_company_name."','".$region."', '".$eba_right."', '".$segment."')";
			$this->query($query);
			
				// == SEND EMAIL ==
				include_once("new-registration-employee.inc.php");
				//echo $emailMsg;
				//exit;
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true;
				$mail->AddAddress($username);
				$mail->IsHTML(true);
				$mail->Subject = "Account Details | ".SITE_NAME;
				$mail->Body = $emailMsg;
				$mail->Send();
		}
		function updateUser($data,$id){
			//print_r($data);
			//exit;
			$id 		 = $id;
			$fname 		 = $this->escape_string($this->strip_all($data['name']));
			$username 	 = $this->escape_string($this->strip_all($data['username']));
			$password1 	 = $this->escape_string($this->strip_all($data['password']));
			$emp_code 	 = $this->escape_string($this->strip_all($data['emp_code']));
			$designation = $this->escape_string($this->strip_all($data['designation']));
			$contact_no  = $this->escape_string($this->strip_all($data['contact_no']));
			$state 		 = $this->escape_string($this->strip_all($data['state']));
			$city 		 = $this->escape_string($this->strip_all($data['city']));
			$segment = $this->escape_string($this->strip_all($data['segment']));
			$cluster = $this->escape_string($this->strip_all($data['cluster']));
			$clusterValueArr 	= $data['cluster_value'];
			$clusterValueImp = implode(',',$data['cluster_value']);
			
			//$login_circle = $this->escape_string($this->strip_all($data['login_circle']));
			$region 	 = $this->escape_string($this->strip_all($data['rights']));
			$active 	 = $this->escape_string($this->strip_all($data['active']));
			$inactive_reason = $this->escape_string($this->strip_all($data['inactive_reason']));
			if(!empty($data['cp_company_name'])){
				$cp_company_name = $this->escape_string($this->strip_all($data['cp_company_name']));
			}else{
				$cp_company_name = '';
			}
			$cam='';				
			$pam='';
			$partner='';
			$rm='';
			//exit;
			if(!empty($password1)) {
				$password = password_hash($password1, PASSWORD_DEFAULT);
				$this->query("update ".PREFIX."admin set password='$password' where id='$id'");
				include_once("password-change-employee.inc.php");			
				//echo $emailMsg;exit;
				// == SEND EMAIL ==
				//echo $emailMsg;
				//exit;
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true;
				$mail->AddAddress($username);
				$mail->IsHTML(true);
				$mail->Subject = "Password Changed | ".SITE_NAME;
				$mail->Body = $emailMsg;
				$mail->Send();
				
			}
			$parent="";
			// }
			if($designation=="Zonal Head (ZH)"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="CAM"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="PM"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="Channel Partner"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="COE Verifier"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="SSP"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}elseif($designation=="Inside Sales Central"){
				$parent = $this->escape_string($this->strip_all($data['sh']));
			}
			
			//HQ Rights Code Starts. Mukunda
 			if($designation == "Sales support"){
				$selectdata = $this->query("select * from tata_caf_emp_state where emp_code = '".$emp_code."'");
				//echo "select * from tata_caf_emp_state where emp_code = '".$emp_code."'";exit;
				if($this->num_rows($selectdata) == '0'){
					if($region == "HQ"){
						$this->query("insert into tata_caf_emp_state(state_id,emp_code) values('0','".$emp_code."') ");
					} else if($region == "Mumbai"){
						$this->query("insert into tata_caf_emp_state(state_id,emp_code) values('18258','".$emp_code."') ");
					} else{
						$camuserResult = $this->query("select * from  tata_state_master where region = '$region'");
						while($camuserRow = $this->fetch($camuserResult)){
							$this->query("insert into tata_caf_emp_state(state_id,emp_code) values('".$camuserRow['id']."','".$emp_code."') ");
						}
					}
				}
			}
			//HQ Rights Code Ends.
			
			$permissionArray = array();
			$permissions = '';
			if(isset($data['permissions'])) {
				foreach($data['permissions'] as $permission) {
					$permission = $this->escape_string($this->strip_all($permission));
					if(!empty($permission)){
						$permissionArray[] = $permission;
					}
				}
				if(count($permissionArray)>0){
					$permissions = implode(",", $permissionArray);
				}
			}

			$eba_right = '0';
			if($designation == 'EBA'){
				$eba_right = '1';
			}

			$query = "update ".PREFIX."admin set full_name='$fname', username='$username', emp_code='$emp_code', designation='$designation', contact_no='$contact_no', parent='$parent', cam='$cam', pam='$pam', partner='$partner', rm='$rm', permissions='kyc-update', state='".$state."', city='".$city."', user_permissions='".$permissions."',cluster_val='".$cluster."',cluster_value='".$clusterValueImp."', active ='".$active."', inactive_reason = '".$inactive_reason."', cp_company_name = '".$cp_company_name."',region='".$region."', eba_rights = '".$eba_right."', segment='".$segment."' where id='$id'";
			$this->query($query);
			return true;
		}
		function deleteUser($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."admin where id = '$id'";
			$this->query($query);
		}
		function deleteSegmentMaster($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."segment_master where id = '$id'";
			$this->query($query);
		}
		function unblockUser($uid){
			//and last_modify > DATE_SUB(NOW(), INTERVAL 24 HOUR)
			$sqlattempts=$this->query("SELECT * FROM tata_attempts WHERE uid='$uid' and attempts='3' ");
			
			if($this->num_rows($sqlattempts) > 0)
			{
				$query = "delete from ".PREFIX."attempts WHERE uid='$uid' and attempts='3'";
			
				$this->query($query);
			}
		}
		function getUserByEmpCode($id){
			return $this->fetch($this->query("select * from ".PREFIX."admin where emp_code='".$id."'"));
		}
		
		//==================== State ====================//
		function getAllstate(){
			return $this->query("select * from ".PREFIX."state_master");
		}
		
		function getcityByStateId($id){
			return $this->query("select * from ".PREFIX."city_master where state_id='$id'");
		}
		
		function getstatebyid($id){
			return $this->fetch($this->query("select * from ".PREFIX."state_master where id='$id'"));
		}
		
		function getStateByName($statename){
			return $this->fetch($this->query("select * from ".PREFIX."state_master where state_name like '%".$statename."%'"));
		}
			
		
		function getCityNameById($id){
			return $this->fetch($this->query("select * from ".PREFIX."city_master where id='$id'"));
		}
		
		function getLogincircleByCity($city_name){
			return $this->fetch($this->query("select * from ".PREFIX."login_circle where city_name like '%".$city_name."%'"));
		}
		
		function getCustomerAssignedByEmpId($emp_id){
			return $this->query("select * from ".PREFIX."kyc_form_details where find_in_set('".$emp_id."',emp_code)");
		}
		
		function getRegistrationStatus($userDetails,$status){
			$id=$this->escape_string($this->strip_all($userDetails['id']));
			if($userDetails['role']=="super"){
				return $this->query("select * from ".PREFIX."kyc_form_details where registration_status='$status'");
			}else{
				return $this->query("select * from ".PREFIX."kyc_form_details where find_in_set(".$id.",emp_code) and registration_status='$status'");
			}
		}
		
		function getCustomerVerificationStatus($userDetails,$status=''){
			$id=$this->escape_string($this->strip_all($userDetails['id']));
			if($userDetails['role']=="super"){
				return $this->query("select * from ".PREFIX."kyc_form_details where customer_status='$status'");
			}else{
				return $this->query("select * from ".PREFIX."kyc_form_details where find_in_set(".$id.",emp_code) and customer_status='$status'");
			}
		}
		
		function getUniqueCustomerByEmail($customerEmail) {
			$customerEmail = $this->escape_string($this->strip_all($customerEmail));
			$query = "select * from ".PREFIX."admin where username='".$customerEmail."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function setCustomerPasswordResetCode($email) {
			$email = trim($this->escape_string($this->strip_all($email)));
			$customerDetails = $this->getUniqueCustomerByEmail($email);
			if($customerDetails){
				$newPassword = substr(str_shuffle("1234567890abcdefghijklmnopqrst"), 0, 8);
				$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
				//$passwordResetCode = md5(time().time()."mac".$email);

				$query = "update ".PREFIX."admin set password='".$newPasswordHash."' where id='".$customerDetails['id']."'";
				$this->query($query);

				$response = array();
				$response['updateSuccess'] = $this->affected_rows();
				$response['full_name'] = $customerDetails['full_name'];
				$response['username'] = $customerDetails['username'];
				$response['newPassword'] = $newPassword;
				//$response['passwordResetCode'] = $passwordResetCode;
				
			}else{
				$response = array();
				$response['updateSuccess'] = 0;
			}	
			return $response;
		}
		
		// === KYC FROM DETAILS  ===
		function getkycDetails(){
			return $this->query("select * from ".PREFIX."kyc_form_details");
		}
		
		function getUniqueKycDetails($id){
			return $this->fetch($this->query("select * from ".PREFIX."kyc_form_details where id='$id'"));
		}
		
		function addKycDetails($data){
			$j=0;
			$emp_code				=	implode(',',$data['emp_code']);
			$form_no				=	$this->escape_string($this->strip_all($data['form_no']));
			//$username				=	$this->escape_string($this->strip_all($data['username']));
			$logo_name				=	$this->escape_string($this->strip_all($data['logo_name']));
			if(!empty($logo_id)){
			$logo_id				=	$this->escape_string($this->strip_all($data['logo_id']));
			}else{
			$logo_id				= "0";	
			}
			$legal_entity_name				=	$this->escape_string($this->strip_all($data['legal_entity_name']));
			$cin					=	$this->escape_string($this->strip_all($data['cin']));
			$ownership_structure	=	$this->escape_string($this->strip_all($data['ownership_structure']));
			$authorized_signatory_name	=	$this->escape_string($this->strip_all($data['authorized_signatory_name']));
			$authorized_signatory_email	=	$this->escape_string($this->strip_all($data['authorized_signatory_email']));
			$authorized_signatory_mobile	=	$this->escape_string($this->strip_all($data['authorized_signatory_mobile']));
			$company_email			=	$this->escape_string($this->strip_all($data['company_email']));
			$date_of_inc			=	$this->escape_string($this->strip_all($data['date_of_inc']));
			$class					=	$this->escape_string($this->strip_all($data['class']));
			$address_of_organization=	$this->escape_string($this->strip_all($data['address_of_organization']));
			$state					=	$this->escape_string($this->strip_all($data['state']));
			$city					=	$this->escape_string($this->strip_all($data['city']));
			$pincode				=	$this->escape_string($this->strip_all($data['pincode']));
			$telephone				=	$this->escape_string($this->strip_all($data['telephone']));
			//$mobile					=	$this->escape_string($this->strip_all($data['mobile']));
			$mobile="";
			$corporate_office		=	$this->escape_string($this->strip_all($data['corporate_office']));
			$company_linkedin_page	=	$this->escape_string($this->strip_all($data['company_linkedin_page']));
			$company_website_link	=	$this->escape_string($this->strip_all($data['company_website_link']));
			$company_twitter_link	=	$this->escape_string($this->strip_all($data['company_twitter_link']));
			$industry_vertical		=	$this->escape_string($this->strip_all($data['industry_vertical']));
			$no_of_employees		=	$this->escape_string($this->strip_all($data['no_of_employees']));
			$no_of_branches			=	$this->escape_string($this->strip_all($data['no_of_branches']));
			$annual_turnover		=	$this->escape_string($this->strip_all($data['annual_turnover']));
			$avg_spends				=	$this->escape_string($this->strip_all($data['avg_spends']));
			$pan_no				=	$this->escape_string($this->strip_all($data['pan_no']));
			$branceorcorporate				=	$this->escape_string($this->strip_all($data['branceorcorporate']));
			$aotherindustryvertical="";
			$os_other="";
			if($ownership_structure=="Others"){
				$os_other			=	$this->escape_string($this->strip_all($data['os_other']));
			}
			if($industry_vertical=="Others"){
				$aotherindustryvertical			=	$this->escape_string($this->strip_all($data['aotherindustryvertical']));
			}
			
			//=================Column Count =====================//
			if(!empty($emp_code)){
				$j++;
			}
			if(!empty($form_no)){
				$j++;
			}
			if(!empty($logo_name)){
				$j++;
			}
			if(!empty($logo_id)){
				$j++;
			}
			if(!empty($legal_entity_name)){
				$j++;
			}
			if(!empty($cin)){
				$j++;
			}
			if(!empty($authorized_signatory_name)){
				$j++;
			}
			if(!empty($authorized_signatory_email)){
				$j++;
			}if(!empty($authorized_signatory_mobile)){
				$j++;
			}
			if(!empty($ownership_structure)){
				if($ownership_structure=="Others"){
					if(!empty($os_other)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($company_email)){
				$j++;
			}
			if(!empty($date_of_inc)){
				$j++;
			}
			if(!empty($class)){
				$j++;
			}
			if(!empty($address_of_organization)){
				$j++;
			}
			if(!empty($state)){
				$j++;
			}
			if(!empty($city)){
				$j++;
			}
			if(!empty($pincode)){
				$j++;
			}
			if(!empty($telephone)){
				$j++;
			}
			/* if(!empty($mobile)){
				$j++;
			} */
			if(!empty($corporate_office)){
				$j++;
			}
			if(!empty($company_linkedin_page)){
				$j++;
			}
			if(!empty($company_website_link)){
				$j++;
			}
			if(!empty($company_twitter_link)){
				$j++;
			}
			if(!empty($industry_vertical)){
				if($industry_vertical=="Others"){
					if(!empty($aotherindustryvertical)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($no_of_employees)){
				$j++;
			}
			if(!empty($no_of_branches)){
				$j++;
			}
			if(!empty($annual_turnover)){
				$j++;
			}
			if(!empty($pan_no)){
				$j++;
			}
			if(!empty($branceorcorporate)){
				$j++;
			}
			
			
			
			//=================Column Count =====================//
			
			
			//========= Authorized signatory ===============//
			$a_salutation			=	$this->escape_string($this->strip_all($data['a_salutation']));
			$a_firstname			=	$this->escape_string($this->strip_all($data['a_firstname']));
			$a_lastname				=	$this->escape_string($this->strip_all($data['a_lastname']));
			$a_email_id				=	$this->escape_string($this->strip_all($data['a_email_id']));
			$a_mobile				=	$this->escape_string($this->strip_all($data['a_mobile']));
			$a_linkedin_id			=	$this->escape_string($this->strip_all($data['a_linkedin_id']));
			$a_twitter				=	$this->escape_string($this->strip_all($data['a_twitter']));
			$a_designation			=	$this->escape_string($this->strip_all($data['a_designation']));
			$a_function				=	$this->escape_string($this->strip_all($data['a_function']));
			$a_role					=	$this->escape_string($this->strip_all($data['a_role']));
			$a_imanage_credentials	=	$this->escape_string($this->strip_all($data['a_imanage_credentials']));
			$aotherdesignation="";
			$aotherfunction	="";
			if($a_designation=="Others"){
				$aotherdesignation			=	$this->escape_string($this->strip_all($data['aotherdesignation']));
			}
			if($a_function=="Others"){
				$aotherfunction			=	$this->escape_string($this->strip_all($data['aotherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($a_salutation)){
				$j++;
			}
			if(!empty($a_firstname)){
				$j++;
			}
			if(!empty($a_lastname)){
				$j++;
			}
			if(!empty($a_email_id)){
				$j++;
			}
			if(!empty($a_mobile)){
				$j++;
			}
			if(!empty($a_linkedin_id)){
				$j++;
			}
			if(!empty($a_twitter)){
				$j++;
			}
			if(!empty($a_function)){
				if($a_function=="Others"){
					if(!empty($aotherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($a_designation)){
				if($a_designation=="Others"){
					if(!empty($aotherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($a_role)){
				$j++;
			}
			if(!empty($a_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			//====================== iManage ========================//
			$i_salutation			=	$this->escape_string($this->strip_all($data['i_salutation']));
			$i_firstname			=	$this->escape_string($this->strip_all($data['i_firstname']));
			$i_lastname				=	$this->escape_string($this->strip_all($data['i_lastname']));
			$i_email_id				=	$this->escape_string($this->strip_all($data['i_email_id']));
			$i_mobile				=	$this->escape_string($this->strip_all($data['i_mobile']));
			$i_linkedin_id			=	$this->escape_string($this->strip_all($data['i_linkedin_id']));
			$i_twitter				=	$this->escape_string($this->strip_all($data['i_twitter']));
			$i_designation			=	$this->escape_string($this->strip_all($data['i_designation']));
			$i_function				=	$this->escape_string($this->strip_all($data['i_function']));
			$i_role					=	$this->escape_string($this->strip_all($data['i_role']));
			$i_imanage_credentials	=	$this->escape_string($this->strip_all($data['i_imanage_credentials']));
			$iotherdesignation="";
			$iotherfunction="";
			if($i_designation=="Others"){
				$iotherdesignation			=	$this->escape_string($this->strip_all($data['iotherdesignation']));
			}
			if($i_function=="Others"){
				$iotherfunction			=	$this->escape_string($this->strip_all($data['iotherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($i_salutation)){
				$j++;
			}
			if(!empty($i_firstname)){
				$j++;
			}
			if(!empty($i_lastname)){
				$j++;
			}
			if(!empty($i_email_id)){
				$j++;
			}
			if(!empty($i_mobile)){
				$j++;
			}
			if(!empty($i_linkedin_id)){
				$j++;
			}
			if(!empty($i_twitter)){
				$j++;
			}
			if(!empty($i_function)){
				if($i_function=="Others"){
					if(!empty($iotherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($i_designation)){
				if($i_designation=="Others"){
					if(!empty($iotherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($i_role)){
				$j++;
			}
			if(!empty($i_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			
			
			//========================== Additional Contact 1 ==============================//
			$ad1_salutation			=	$this->escape_string($this->strip_all($data['ad1_salutation']));
			$ad1_firstname			=	$this->escape_string($this->strip_all($data['ad1_firstname']));
			$ad1_lastname			=	$this->escape_string($this->strip_all($data['ad1_lastname']));
			$ad1_email_id			=	$this->escape_string($this->strip_all($data['ad1_email_id']));
			$ad1_mobile				=	$this->escape_string($this->strip_all($data['ad1_mobile']));
			$ad1_linkedin_id		=	$this->escape_string($this->strip_all($data['ad1_linkedin_id']));
			$ad1_twitter			=	$this->escape_string($this->strip_all($data['ad1_twitter']));
			$ad1_designation		=	$this->escape_string($this->strip_all($data['ad1_designation']));
			$ad1_function			=	$this->escape_string($this->strip_all($data['ad1_function']));
			$ad1_role				=	$this->escape_string($this->strip_all($data['ad1_role']));
			$ad1_imanage_credentials=	$this->escape_string($this->strip_all($data['ad1_imanage_credentials']));
			$ad1otherdesignation="";
			$ad1otherfunction="";
			if($ad1_designation=="Others"){
				$ad1otherdesignation			=	$this->escape_string($this->strip_all($data['ad1otherdesignation']));
			}
			if($ad1_function=="Others"){
				$ad1otherfunction			=	$this->escape_string($this->strip_all($data['ad1otherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($ad1_salutation)){
				$j++;
			}
			if(!empty($ad1_firstname)){
				$j++;
			}
			if(!empty($ad1_lastname)){
				$j++;
			}
			if(!empty($ad1_email_id)){
				$j++;
			}
			if(!empty($ad1_mobile)){
				$j++;
			}
			if(!empty($ad1_linkedin_id)){
				$j++;
			}
			if(!empty($ad1_twitter)){
				$j++;
			}
			if(!empty($ad1_function)){
				if($ad1_function=="Others"){
					if(!empty($ad1otherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad1_designation)){
				if($ad1_designation=="Others"){
					if(!empty($ad1otherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad1_role)){
				$j++;
			}
			if(!empty($ad1_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			//========================== Additional Contact 2 ==============================//
			$ad2_salutation			=	$this->escape_string($this->strip_all($data['ad2_salutation']));
			$ad2_firstname			=	$this->escape_string($this->strip_all($data['ad2_firstname']));
			$ad2_lastname			=	$this->escape_string($this->strip_all($data['ad2_lastname']));
			$ad2_email_id			=	$this->escape_string($this->strip_all($data['ad2_email_id']));
			$ad2_mobile				=	$this->escape_string($this->strip_all($data['ad2_mobile']));
			$ad2_linkedin_id		=	$this->escape_string($this->strip_all($data['ad2_linkedin_id']));
			$ad2_twitter			=	$this->escape_string($this->strip_all($data['ad2_twitter']));
			$ad2_designation		=	$this->escape_string($this->strip_all($data['ad2_designation']));
			$ad2_function			=	$this->escape_string($this->strip_all($data['ad2_function']));
			$ad2_role				=	$this->escape_string($this->strip_all($data['ad2_role']));
			$ad2_imanage_credentials=	$this->escape_string($this->strip_all($data['ad2_imanage_credentials']));
			$ad2otherdesignation="";
			$ad2otherfunction="";
			if($ad2_designation=="Others"){
				$ad2otherdesignation			=	$this->escape_string($this->strip_all($data['ad2otherdesignation']));
			}
			if($ad2_function=="Others"){
				$ad2otherfunction			=	$this->escape_string($this->strip_all($data['ad2otherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($ad2_salutation)){
				$j++;
			}
			if(!empty($ad2_firstname)){
				$j++;
			}
			if(!empty($ad2_lastname)){
				$j++;
			}
			if(!empty($ad2_email_id)){
				$j++;
			}
			if(!empty($ad2_mobile)){
				$j++;
			}
			if(!empty($ad2_linkedin_id)){
				$j++;
			}
			if(!empty($ad2_twitter)){
				$j++;
			}
			if(!empty($ad2_function)){
				if($ad2_function=="Others"){
					if(!empty($ad2otherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad2_designation)){
				if($ad2_designation=="Others"){
					if(!empty($ad2otherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad2_role)){
				$j++;
			}
			if(!empty($ad2_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================69//
			
			//==== Mail Authority ====//
			/* $mail_authority			=$this->escape_string($this->strip_all($data['mail_authority']));
			$authority				=explode('-',$mail_authority);
			$mailAuthorizedSignatory="No";
			$mailiManage="No";
			$mailAdditionalContact1="No";
			$mailAdditionalContact2="No";
			if($authority[0]=="autorized signatory" && $authority[1]=="Yes"){
				$mailAuthorizedSignatory="Yes";
			}else if($authority[0]=="iManage user" && $authority[1]=="Yes"){
				$mailiManage="Yes";
			}else if($authority[0]=="aditional contact1" && $authority[1]=="Yes"){
				$mailAdditionalContact1="Yes";
			}else if($authority[0]=="additional contact2" && $authority[1]=="Yes"){
				$mailAdditionalContact2="Yes";
			} */
			$mailAuthorizedSignatory='';
			if(isset($data['authorized_mail_authority'])){
				$mailAuthorizedSignatory			=$this->escape_string($this->strip_all($data['authorized_mail_authority']));
			}
			$mailiManage='';
			if(isset($data['imanage_mail_authority'])){
				$mailiManage			=$this->escape_string($this->strip_all($data['imanage_mail_authority']));
			}
			$mailAdditionalContact1='';
			if(isset($data['ad1_mail_authority'])){
				$mailAdditionalContact1			=$this->escape_string($this->strip_all($data['ad1_mail_authority']));
			}
			$mailAdditionalContact2='';
			if(isset($data['ad2_mail_authority'])){
				$mailAdditionalContact2			=$this->escape_string($this->strip_all($data['ad2_mail_authority']));
			}
			
			if(!empty($data['authorized_mail_authority'])){
				$j++;
			}
			if(!empty($data['imanage_mail_authority'])){
				$j++;
			}
			if(!empty($data['ad1_mail_authority'])){
				$j++;
			}
			if(!empty($data['ad2_mail_authority'])){
				$j++;
			}
			
			//==== Mail Authority END====//
			$enterprise_connectivity_level='';
			if(isset($data['enterprise_connectivity_level'])){
				$enterprise_connectivity_level=$this->escape_string($this->strip_all($data['enterprise_connectivity_level']));
				
			}
			if(!empty($enterprise_connectivity_level)){
				$j++;
			}
			$mobility_soution_level='';
			if(isset($data['mobility_soution_level'])){
				$mobility_soution_level=$this->escape_string($this->strip_all($data['mobility_soution_level']));
			}
			if(!empty($mobility_soution_level)){
				$j++;
			}
			$enterpriseiot_solution_level='';
			if(isset($data['enterpriseiot_solution_level'])){
				$enterpriseiot_solution_level=$this->escape_string($this->strip_all($data['enterpriseiot_solution_level']));
			}
			if(!empty($enterpriseiot_solution_level)){
				$j++;
			}
			$marketing_solution_level='';
			if(isset($data['marketing_solution_level'])){
				$marketing_solution_level=$this->escape_string($this->strip_all($data['marketing_solution_level']));
			}
			if(!empty($marketing_solution_level)){
				$j++;
			}
			$enterprise_collaboration_level='';
			if(isset($data['enterprise_collaboration_level'])){
				$enterprise_collaboration_level=$this->escape_string($this->strip_all($data['enterprise_collaboration_level']));
			}
			if(!empty($enterprise_collaboration_level)){
				$j++;
			}
			
			$mobilityServiceProvider=array();
			$mobility_service_provider="";
			$mobilitySolutions='';
			if(isset($data['mobility_solutions']) && sizeof($data['mobility_solutions'])>0){
				$mobilitySolutions=implode(',',$data['mobility_solutions']);
				$j++;
			}
			
			if(isset($data['mobility_solutions']) && sizeof($data['mobility_solutions'])>0){
				foreach($data['mobility_solutions'] as $key => $val){
					if($val!=''){
						$mobilityServiceProvider[$val]=$this->escape_string($this->strip_all($data['mobility_service_provider'][$val]));
					}
				}
			}
			
			if(sizeof($mobilityServiceProvider)>0){
				$mobility_service_provider=json_encode($mobilityServiceProvider);
				$j++;
			}
			
			$enterprise_connectivityServiceProvider=array();
			$enterprise_connectivity_provider="";
			$enterprise_connectivity='';
			if(isset($data['enterprise_connectivity']) && sizeof($data['enterprise_connectivity'])>0){
				$enterprise_connectivity=implode(',',$data['enterprise_connectivity']);
				$j++;
			}
			if(isset($data['enterprise_connectivity']) &&  sizeof($data['enterprise_connectivity'])>0){
				foreach($data['enterprise_connectivity'] as $key => $val){
					if($val!=''){
						$enterprise_connectivityServiceProvider[$val]=$this->escape_string($this->strip_all($data['enterprise_connectivity_provider'][$val]));
					}
				}
			}
			if(sizeof($enterprise_connectivityServiceProvider)>0){
				$enterprise_connectivity_provider=json_encode($enterprise_connectivityServiceProvider);
				$j++;
			}
			
			$enterprise_collaborationServiceProvider=array();
			$enterprise_collaboration_provider="";
			$enterprise_collaboration='';
			if(isset($data['enterprise_collaboration']) && sizeof($data['enterprise_collaboration'])>0){
				$enterprise_collaboration=implode(',',$data['enterprise_collaboration']);
				$j++;
			}	
			if(isset($data['enterprise_collaboration']) && sizeof($data['enterprise_collaboration'])>0){
				foreach($data['enterprise_collaboration'] as $key => $val){
					if($val!=''){
						$enterprise_collaborationServiceProvider[$val]=$this->escape_string($this->strip_all($data['enterprise_collaboration_provider'][$val]));
					}
				}
			}
			if(sizeof($enterprise_collaborationServiceProvider)>0){
				$enterprise_collaboration_provider=json_encode($enterprise_collaborationServiceProvider);
				$j++;
			}
			
			$marketing_solutionServiceProvider=array();
			$marketing_solution_provider="";
			$marketing_solution='';
			if(isset($data['marketing_solution']) && sizeof($data['marketing_solution'])>0){
				$marketing_solution=implode(',',$data['marketing_solution']);
				$j++;
			}
			if(isset($data['marketing_solution']) && sizeof($data['marketing_solution'])>0){
				foreach($data['marketing_solution'] as $key => $val){
					if($val!=''){
						$marketing_solutionServiceProvider[$val]=$this->escape_string($this->strip_all($data['marketing_solution_provider'][$val]));
					}
				}
			}
			if(sizeof($marketing_solutionServiceProvider)>0){
				$marketing_solution_provider=json_encode($marketing_solutionServiceProvider);
				$j++;
			}
			
			$otherServiceProvider=array();
			$other_provider="";
			$other='';
			if(isset($data['other']) && sizeof($data['other'])>0){
				$other=implode(',',$data['other']);
				$j++;
			}
			if(isset($data['other']) &&  sizeof($data['other'])>0){
				foreach($data['other'] as $key => $val){
					if($val!=''){
						$otherServiceProvider[$val]=$this->escape_string($this->strip_all($data['other_provider'][$val]));
					}
				}
			}
			if(sizeof($otherServiceProvider)>0){
				$other_provider=json_encode($otherServiceProvider);
				$j++;
			}
			if($data['registration_status']=="Completed"){
				$registration_status=$this->escape_string($this->strip_all($data['registration_status']));
			}else{
				$registration_status="In process";
			}
			$sql="insert into ".PREFIX."kyc_form_details (form_no, emp_code,legal_entity_name,logo_name,logo_id,cin,company_email,date_of_inc,ownership_structure,os_other,class,pan_no,address_of_organization,state,city,pincode,mobile,telephone,branceorcorporate,authorized_signatory_name,authorized_signatory_email,authorized_signatory_mobile,corporate_office,company_linkedin_page,company_website_link,company_twitter_link,industry_vertical,aotherindustryvertical,no_of_employees,no_of_branches,annual_turnover,avg_spends,a_salutation,a_firstname,a_lastname,a_email_id,a_designation,aotherdesignation,a_mobile,a_function,aotherfunction,a_linkedin_id,a_twitter,a_role,a_imanage_credentials,a_mail_authority,i_salutation,i_firstname,i_lastname,i_email_id,i_mobile,i_linkedin_id,i_twitter,i_designation,iotherdesignation,i_function,iotherfunction,i_role,i_imanage_credentials,i_mail_authority,ad1_salutation,ad1_firstname,ad1_lastname,ad1_email_id,ad1_mobile,ad1_linkedin_id,ad1_twitter,ad1_designation,ad1otherdesignation,ad1_function,ad1otherfunction,ad1_role,ad1_imanage_credentials,ad1_mail_authority,ad2_salutation,ad2_firstname,ad2_lastname,ad2_email_id,ad2_mobile,ad2_linkedin_id,ad2_twitter,ad2_designation,ad2otherdesignation,ad2_function,ad2otherfunction,ad2_role,ad2_imanage_credentials,ad2_mail_authority,enterprise_connectivity_level,marketing_solution_level,mobility_soution_level,enterpriseiot_solution_level,enterprise_collaboration_level,mobility_solutions,mobility_service_provider,enterprise_connectivity,enterprise_connectivity_provider,enterprise_collaboration,enterprise_collaboration_provider,marketing_solution,marketing_solution_provider,other,other_provider,completedfield) 
			values 
			('$form_no', '$emp_code','$legal_entity_name','$logo_name','$logo_id','$cin','$company_email','$date_of_inc','$ownership_structure','$os_other','$class','$pan_no','$address_of_organization','$state','$city','$pincode','$mobile','$telephone','$branceorcorporate','$authorized_signatory_name','$authorized_signatory_email','$authorized_signatory_mobile','$corporate_office','$company_linkedin_page','$company_website_link','$company_twitter_link','$industry_vertical','$aotherindustryvertical','$no_of_employees','$no_of_branches','$annual_turnover','$avg_spends',
			'$a_salutation','$a_firstname','$a_lastname','$a_email_id','$a_designation','$aotherdesignation','$a_mobile','$a_function','$aotherfunction','$a_linkedin_id','$a_twitter','$a_role','$a_imanage_credentials','$mailAuthorizedSignatory',
			'$i_salutation','$i_firstname','$i_lastname','$i_email_id','$i_mobile','$i_linkedin_id','$i_twitter','$i_designation','$iotherdesignation','$i_function','$iotherfunction','$i_role','$i_imanage_credentials','$mailiManage',
			'$ad1_salutation','$ad1_firstname','$ad1_lastname','$ad1_email_id','$ad1_mobile','$ad1_linkedin_id','$ad1_twitter','$ad1_designation','$ad1otherdesignation','$ad1_function','$ad1otherfunction','$ad1_role','$ad1_imanage_credentials','$mailAdditionalContact1',
			'$ad2_salutation','$ad2_firstname','$ad2_lastname','$ad2_email_id','$ad2_mobile','$ad2_linkedin_id','$ad2_twitter','$ad2_designation','$ad2otherdesignation','$ad2_function','$ad2otherfunction','$ad2_role','$ad2_imanage_credentials','$mailAdditionalContact2','$enterprise_connectivity_level','$marketing_solution_level','$mobility_soution_level','$enterpriseiot_solution_level','$enterprise_collaboration_level','$mobilitySolutions','$mobility_service_provider','$enterprise_connectivity','$enterprise_connectivity_provider','$enterprise_collaboration','$enterprise_collaboration_provider','$marketing_solution','$marketing_solution_provider','$other','$other_provider','$j')";
			
			$this->query($sql);
			$last_id=$this->last_insert_id();
			$edited_by = '';
			$loggedInUserDetailsArr = $this->getLoggedInUserDetails();
			$edited_by = $loggedInUserDetailsArr['id'];
			$this->query("insert into ".PREFIX."kyc_completed_status(kyc_id, completed_fields, edited_by) values('".$last_id."', '".$j."', '".$edited_by."')");
			
			foreach($data['emp_code'] as $key => $val){
				if(!empty($val)){
					$this->query("insert into ".PREFIX."kmcandemp (form_id,emp_id) values ('".$last_id."','".$val."')");
				}	
			}
			
			if(sizeof($data['emp_code'])>0){
				//$q=base64_encode($id.'#'.$username);
				//$verification_link=md5($id.'#'.$username);
				//$this->query("update ".PREFIX."kyc_form_details set verification_link='$verification_link' where id='$id'");
				foreach($data['emp_code'] as $key => $val){
					$userDetails=$this->getUniqueUserById($val);
					$to=$userDetails['username'];
					$emailMsg="";
					$parent=$this->fetch($this->query("select * from ".PREFIX."admin where id='".$userDetails['parent']."'"));
					// == SEND EMAIL ==
					include_once("new-registration-assigned.inc.php");
					//echo $emailMsg;
					//exit;
					/* $emailObj = new Email();
					$emailObj->setAddress($to);
					//$emailObj->setAdminAddress(ADMIN_EMAIL);
					$emailObj->setSubject("New Registration Details | ".SITE_NAME);
					$emailObj->setEmailBody($emailMsg);
					$emailObj->sendEmail(); */
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($to);
					$mail->IsHTML(true);
					$mail->Subject = "New Registration Details | ".SITE_NAME;
					$mail->Body = $emailMsg;
					$mail->Send();
				}	
			}
			//exit;88
		}
		
		function updateKycDetails($data){
			$j=0;
			$id						=	$this->escape_string($this->strip_all($data['id']));
			$KMCDetails				=$this->getUniqueKycDetails($id);
			$newwmparray=array();
			if(isset($data['emp_code'])){
				$emp_code				=	implode(',',$data['emp_code']);
				$oldemp=explode(',',$KMCDetails['emp_code']);
				foreach($data['emp_code'] as $key=>$val){
					if(!in_array($val,$oldemp)){
						$newwmparray[]=$val;
					}
				}
				//$newemp=
			}else{
				$emp_code=$KMCDetails['emp_code'];
			}
			$form_no				=	$this->escape_string($this->strip_all($data['form_no']));
			//$username				=	$this->escape_string($this->strip_all($data['username']));
			$class					=	$this->escape_string($this->strip_all($data['class']));
			$logo_name				=	$this->escape_string($this->strip_all($data['logo_name']));
			if(!empty($logo_id)){
			$logo_id				=	$this->escape_string($this->strip_all($data['logo_id']));
			}else{
			$logo_id 				= "0";
			}
			
			$legal_entity_name		=	$this->escape_string($this->strip_all($data['legal_entity_name']));
			$cin					=	$this->escape_string($this->strip_all($data['cin']));
			$ownership_structure	=	$this->escape_string($this->strip_all($data['ownership_structure']));
			$authorized_signatory_name	=	$this->escape_string($this->strip_all($data['authorized_signatory_name']));
			$authorized_signatory_email	=	$this->escape_string($this->strip_all($data['authorized_signatory_email']));
			$authorized_signatory_mobile	=	$this->escape_string($this->strip_all($data['authorized_signatory_mobile']));
			$company_email	=	$this->escape_string($this->strip_all($data['company_email']));
			$date_of_inc	=	$this->escape_string($this->strip_all($data['date_of_inc']));
			$address_of_organization=	$this->escape_string($this->strip_all($data['address_of_organization']));
			$state					=	$this->escape_string($this->strip_all($data['state']));
			$city					=	$this->escape_string($this->strip_all($data['city']));
			$pincode				=	$this->escape_string($this->strip_all($data['pincode']));
			$telephone				=	$this->escape_string($this->strip_all($data['telephone']));
			//$mobile					=	$this->escape_string($this->strip_all($data['mobile']));
			$mobile					=	"";
			$corporate_office		=	$this->escape_string($this->strip_all($data['corporate_office']));
			$company_linkedin_page	=	$this->escape_string($this->strip_all($data['company_linkedin_page']));
			$company_website_link	=	$this->escape_string($this->strip_all($data['company_website_link']));
			$company_twitter_link	=	$this->escape_string($this->strip_all($data['company_twitter_link']));
			$industry_vertical		=	$this->escape_string($this->strip_all($data['industry_vertical']));
			$no_of_employees		=	$this->escape_string($this->strip_all($data['no_of_employees']));
			$no_of_branches			=	$this->escape_string($this->strip_all($data['no_of_branches']));
			$annual_turnover		=	$this->escape_string($this->strip_all($data['annual_turnover']));
			$avg_spends				=	$this->escape_string($this->strip_all($data['avg_spends']));
			//$registration_status	=	$this->escape_string($this->strip_all($data['registration_status']));
			if(isset($data['registration_status']) && $data['registration_status']=="Completed"){
				$registration_status=$this->escape_string($this->strip_all($data['registration_status']));
			}else{
				$registration_status="In process";
			}
			$pan_no	=	$this->escape_string($this->strip_all($data['pan_no']));
			$branceorcorporate	=	$this->escape_string($this->strip_all($data['branceorcorporate']));
			if(!empty($branceorcorporate)){
				$j++;
			}
			$aotherindustryvertical="";
			$os_other="";
			if($ownership_structure=="Others"){
				$os_other			=	$this->escape_string($this->strip_all($data['os_other']));
			}
			if($industry_vertical=="Others"){
				$aotherindustryvertical			=	$this->escape_string($this->strip_all($data['aotherindustryvertical']));
			}
			
			//=================Column Count =====================//
			if(!empty($emp_code)){
				$j++;
			}
			if(!empty($form_no)){
				$j++;
			}
			if(!empty($logo_name)){
				$j++;
			}
			if(!empty($logo_id)){
				$j++;
			}
			if(!empty($legal_entity_name)){
				$j++;
			}
			if(!empty($cin)){
				$j++;
			}
			if(!empty($authorized_signatory_name)){
				$j++;
			}
			if(!empty($authorized_signatory_email)){
				$j++;
			}if(!empty($authorized_signatory_mobile)){
				$j++;
			}
			if(!empty($ownership_structure)){
				if($ownership_structure=="Others"){
					if(!empty($os_other)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($company_email)){
				$j++;
			}
			if(!empty($date_of_inc)){
				$j++;
			}
			if(!empty($class)){
				$j++;
			}
			if(!empty($address_of_organization)){
				$j++;
			}
			if(!empty($state)){
				$j++;
			}
			if(!empty($city)){
				$j++;
			}
			if(!empty($pincode)){
				$j++;
			}
			if(!empty($telephone)){
				$j++;
			}
			/* if(!empty($mobile)){
				$j++;
			} */
			if(!empty($corporate_office)){
				$j++;
			}
			if(!empty($company_linkedin_page)){
				$j++;
			}
			if(!empty($company_website_link)){
				$j++;
			}
			if(!empty($company_twitter_link)){
				$j++;
			}
			if(!empty($industry_vertical)){
				if($industry_vertical=="Others"){
					if(!empty($aotherindustryvertical)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($no_of_employees)){
				$j++;
			}
			if(!empty($no_of_branches)){
				$j++;
			}
			if(!empty($annual_turnover)){
				$j++;
			}
			if(!empty($pan_no)){
				$j++;
			}
			if(!empty($branceorcorporate)){
				$j++;
			}
			
			
			
			//=================Column Count =====================//
			
			
				//========= Authorized signatory ===============//
			$a_salutation			=	$this->escape_string($this->strip_all($data['a_salutation']));
			$a_firstname			=	$this->escape_string($this->strip_all($data['a_firstname']));
			$a_lastname				=	$this->escape_string($this->strip_all($data['a_lastname']));
			$a_email_id				=	$this->escape_string($this->strip_all($data['a_email_id']));
			$a_mobile				=	$this->escape_string($this->strip_all($data['a_mobile']));
			//exit;
			$a_linkedin_id			=	$this->escape_string($this->strip_all($data['a_linkedin_id']));
			$a_twitter				=	$this->escape_string($this->strip_all($data['a_twitter']));
			$a_designation			=	$this->escape_string($this->strip_all($data['a_designation']));
			$a_function				=	$this->escape_string($this->strip_all($data['a_function']));
			$a_role					=	$this->escape_string($this->strip_all($data['a_role']));
			$a_imanage_credentials	=	$this->escape_string($this->strip_all($data['a_imanage_credentials']));
			$aotherdesignation="";
			$aotherfunction	="";
			if($a_designation=="Others"){
				$aotherdesignation			=	$this->escape_string($this->strip_all($data['aotherdesignation']));
			}
			if($a_function=="Others"){
				$aotherfunction			=	$this->escape_string($this->strip_all($data['aotherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($a_salutation)){
				$j++;
			}
			if(!empty($a_firstname)){
				$j++;
			}
			if(!empty($a_lastname)){
				$j++;
			}
			if(!empty($a_email_id)){
				$j++;
			}
			if(!empty($a_mobile)){
				$j++;
			}
			if(!empty($a_linkedin_id)){
				$j++;
			}
			if(!empty($a_twitter)){
				$j++;
			}
			if(!empty($a_function)){
				if($a_function=="Others"){
					if(!empty($aotherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($a_designation)){
				if($a_designation=="Others"){
					if(!empty($aotherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($a_role)){
				$j++;
			}
			if(!empty($a_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			
			//====================== iManage ========================//
			$i_salutation			=	$this->escape_string($this->strip_all($data['i_salutation']));
			$i_firstname			=	$this->escape_string($this->strip_all($data['i_firstname']));
			$i_lastname				=	$this->escape_string($this->strip_all($data['i_lastname']));
			$i_email_id				=	$this->escape_string($this->strip_all($data['i_email_id']));
			$i_mobile				=	$this->escape_string($this->strip_all($data['i_mobile']));
			$i_linkedin_id			=	$this->escape_string($this->strip_all($data['i_linkedin_id']));
			$i_twitter				=	$this->escape_string($this->strip_all($data['i_twitter']));
			$i_designation			=	$this->escape_string($this->strip_all($data['i_designation']));
			$i_function				=	$this->escape_string($this->strip_all($data['i_function']));
			$i_role					=	$this->escape_string($this->strip_all($data['i_role']));
			$i_imanage_credentials	=	$this->escape_string($this->strip_all($data['i_imanage_credentials']));
			$iotherdesignation="";
			$iotherfunction="";
			if($i_designation=="Others"){
				$iotherdesignation			=	$this->escape_string($this->strip_all($data['iotherdesignation']));
			}
			if($i_function=="Others"){
				$iotherfunction			=	$this->escape_string($this->strip_all($data['iotherfunction']));
			}
			
			
			//=============== Clumn Count ======================//
			if(!empty($i_salutation)){
				$j++;
			}
			if(!empty($i_firstname)){
				$j++;
			}
			if(!empty($i_lastname)){
				$j++;
			}
			if(!empty($i_email_id)){
				$j++;
			}
			if(!empty($i_mobile)){
				$j++;
			}
			if(!empty($i_linkedin_id)){
				$j++;
			}
			if(!empty($i_twitter)){
				$j++;
			}
			if(!empty($i_function)){
				if($i_function=="Others"){
					if(!empty($iotherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($i_designation)){
				if($i_designation=="Others"){
					if(!empty($iotherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($i_role)){
				$j++;
			}
			if(!empty($i_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			
			//========================== Additional Contact 1 ==============================//
			$ad1_salutation			=	$this->escape_string($this->strip_all($data['ad1_salutation']));
			$ad1_firstname			=	$this->escape_string($this->strip_all($data['ad1_firstname']));
			$ad1_lastname			=	$this->escape_string($this->strip_all($data['ad1_lastname']));
			$ad1_email_id			=	$this->escape_string($this->strip_all($data['ad1_email_id']));
			$ad1_mobile				=	$this->escape_string($this->strip_all($data['ad1_mobile']));
			$ad1_linkedin_id		=	$this->escape_string($this->strip_all($data['ad1_linkedin_id']));
			$ad1_twitter			=	$this->escape_string($this->strip_all($data['ad1_twitter']));
			$ad1_designation		=	$this->escape_string($this->strip_all($data['ad1_designation']));
			$ad1_function			=	$this->escape_string($this->strip_all($data['ad1_function']));
			$ad1_role				=	$this->escape_string($this->strip_all($data['ad1_role']));
			$ad1_imanage_credentials=	$this->escape_string($this->strip_all($data['ad1_imanage_credentials']));
			$ad1otherdesignation="";
			$ad1otherfunction="";
			if($ad1_designation=="Others"){
				$ad1otherdesignation			=	$this->escape_string($this->strip_all($data['ad1otherdesignation']));
			}
			if($ad1_function=="Others"){
				$ad1otherfunction			=	$this->escape_string($this->strip_all($data['ad1otherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($ad1_salutation)){
				$j++;
			}
			if(!empty($ad1_firstname)){
				$j++;
			}
			if(!empty($ad1_lastname)){
				$j++;
			}
			if(!empty($ad1_email_id)){
				$j++;
			}
			if(!empty($ad1_mobile)){
				$j++;
			}
			if(!empty($ad1_linkedin_id)){
				$j++;
			}
			if(!empty($ad1_twitter)){
				$j++;
			}
			if(!empty($ad1_function)){
				if($ad1_function=="Others"){
					if(!empty($ad1otherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad1_designation)){
				if($ad1_designation=="Others"){
					if(!empty($ad1otherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad1_role)){
				$j++;
			}
			if(!empty($ad1_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			//========================== Additional Contact 2 ==============================//
			$ad2_salutation			=	$this->escape_string($this->strip_all($data['ad2_salutation']));
			$ad2_firstname			=	$this->escape_string($this->strip_all($data['ad2_firstname']));
			$ad2_lastname			=	$this->escape_string($this->strip_all($data['ad2_lastname']));
			$ad2_email_id			=	$this->escape_string($this->strip_all($data['ad2_email_id']));
			$ad2_mobile				=	$this->escape_string($this->strip_all($data['ad2_mobile']));
			$ad2_linkedin_id		=	$this->escape_string($this->strip_all($data['ad2_linkedin_id']));
			$ad2_twitter			=	$this->escape_string($this->strip_all($data['ad2_twitter']));
			$ad2_designation		=	$this->escape_string($this->strip_all($data['ad2_designation']));
			$ad2_function			=	$this->escape_string($this->strip_all($data['ad2_function']));
			$ad2_role				=	$this->escape_string($this->strip_all($data['ad2_role']));
			$ad2_imanage_credentials=	$this->escape_string($this->strip_all($data['ad2_imanage_credentials']));
			$ad2otherdesignation="";
			$ad2otherfunction="";
			if($ad2_designation=="Others"){
				$ad2otherdesignation			=	$this->escape_string($this->strip_all($data['ad2otherdesignation']));
			}
			if($ad2_function=="Others"){
				$ad2otherfunction			=	$this->escape_string($this->strip_all($data['ad2otherfunction']));
			}
			
			//=============== Clumn Count ======================//
			if(!empty($ad2_salutation)){
				$j++;
			}
			if(!empty($ad2_firstname)){
				$j++;
			}
			if(!empty($ad2_lastname)){
				$j++;
			}
			if(!empty($ad2_email_id)){
				$j++;
			}
			if(!empty($ad2_mobile)){
				$j++;
			}
			if(!empty($ad2_linkedin_id)){
				$j++;
			}
			if(!empty($ad2_twitter)){
				$j++;
			}
			if(!empty($ad2_function)){
				if($ad2_function=="Others"){
					if(!empty($ad2otherfunction)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad2_designation)){
				if($ad2_designation=="Others"){
					if(!empty($ad2otherdesignation)){
						$j++;
					}
				}else{
					$j++;
				}
			}
			if(!empty($ad2_role)){
				$j++;
			}
			if(!empty($ad2_imanage_credentials)){
				$j++;
			}
			
			//=============== Clumn Count ======================//
			
			//==== Mail Authority ====//
			/* $mail_authority			=$this->escape_string($this->strip_all($data['mail_authority']));
			$authority				=explode('-',$mail_authority);
			$mailAuthorizedSignatory="No";
			$mailiManage="No";
			$mailAdditionalContact1="No";
			$mailAdditionalContact2="No";
			if($authority[0]=="autorized signatory" && $authority[1]=="Yes"){
				$mailAuthorizedSignatory="Yes";
			}else if($authority[0]=="iManage user" && $authority[1]=="Yes"){
				$mailiManage="Yes";
			}else if($authority[0]=="aditional contact1" && $authority[1]=="Yes"){
				$mailAdditionalContact1="Yes";
			}else if($authority[0]=="additional contact2" && $authority[1]=="Yes"){
				$mailAdditionalContact2="Yes";
			} */
			$mailAuthorizedSignatory='';
			if(isset($data['authorized_mail_authority'])){
				$mailAuthorizedSignatory			=$this->escape_string($this->strip_all($data['authorized_mail_authority']));
			}
			$mailiManage='';
			if(isset($data['imanage_mail_authority'])){
				$mailiManage			=$this->escape_string($this->strip_all($data['imanage_mail_authority']));
			}
			$mailAdditionalContact1='';
			if(isset($data['ad1_mail_authority'])){
				$mailAdditionalContact1			=$this->escape_string($this->strip_all($data['ad1_mail_authority']));
			}
			$mailAdditionalContact2='';
			if(isset($data['ad2_mail_authority'])){
				$mailAdditionalContact2			=$this->escape_string($this->strip_all($data['ad2_mail_authority']));
			}
			//exit;
			if(!empty($data['authorized_mail_authority'])){
				$j++;
			}
			if(!empty($data['imanage_mail_authority'])){
				$j++;
			}
			if(!empty($data['ad1_mail_authority'])){
				$j++;
			}
			if(!empty($data['ad2_mail_authority'])){
				$j++;
			}
			//==== Mail Authority END====//
			$enterprise_connectivity_level='';
			if(isset($data['enterprise_connectivity_level'])){
				$enterprise_connectivity_level=$this->escape_string($this->strip_all($data['enterprise_connectivity_level']));
			}
			if(!empty($enterprise_connectivity_level)){
				$j++;
			}
			$mobility_soution_level='';
			if(isset($data['mobility_soution_level'])){
				$mobility_soution_level=$this->escape_string($this->strip_all($data['mobility_soution_level']));
			}
			if(!empty($mobility_soution_level)){
				$j++;
			}
			$enterpriseiot_solution_level='';
			if(isset($data['enterpriseiot_solution_level'])){
				$enterpriseiot_solution_level=$this->escape_string($this->strip_all($data['enterpriseiot_solution_level']));
			}
			if(!empty($enterpriseiot_solution_level)){
				$j++;
			}
			$marketing_solution_level='';
			if(isset($data['marketing_solution_level'])){
				$marketing_solution_level=$this->escape_string($this->strip_all($data['marketing_solution_level']));
			}
			if(!empty($marketing_solution_level)){
				$j++;
			}
			$enterprise_collaboration_level='';
			if(isset($data['enterprise_collaboration_level'])){
				$enterprise_collaboration_level=$this->escape_string($this->strip_all($data['enterprise_collaboration_level']));
			}
			if(!empty($enterprise_collaboration_level)){
				$j++;
			}
			
			$mobilityServiceProvider=array();
			$mobility_service_provider="";
			$mobilitySolutions='';
			if(isset($data['mobility_solutions']) && sizeof($data['mobility_solutions'])>0){
				$mobilitySolutions=implode(',',$data['mobility_solutions']);
				$j++;
			}
			//print_r($data['mobility_service_provider']);
			//print_r($data['mobility_solutions']);
			//echo $data['mobility_service_provider']["'".$data['mobility_solutions'][0]."'"];
			if(isset($data['mobility_solutions']) && sizeof($data['mobility_solutions'])>0){
				foreach($data['mobility_solutions'] as $key => $val){
					if($val!=''){
						$mobilityServiceProvider[$val]=$this->escape_string($this->strip_all($data['mobility_service_provider'][$val]));
					}
				}
			}
			//print_r($mobilityServiceProvider);
			if(sizeof($mobilityServiceProvider)>0){
				$mobility_service_provider=json_encode($mobilityServiceProvider);
				$j++;
			}
			
			$enterprise_connectivityServiceProvider=array();
			$enterprise_connectivity_provider="";
			$enterprise_connectivity='';
			if(isset($data['enterprise_connectivity']) && sizeof($data['enterprise_connectivity'])>0){
				$enterprise_connectivity=implode(',',$data['enterprise_connectivity']);
				$j++;
			}
			if(isset($data['enterprise_connectivity']) && sizeof($data['enterprise_connectivity'])>0){
				foreach($data['enterprise_connectivity'] as $key => $val){
					if($val!=''){
						$enterprise_connectivityServiceProvider[$val]=$this->escape_string($this->strip_all($data['enterprise_connectivity_provider'][$val]));
					}
				}
			}
			if(sizeof($enterprise_connectivityServiceProvider)>0){
				$enterprise_connectivity_provider=json_encode($enterprise_connectivityServiceProvider);
				$j++;
			}
			
			$enterprise_collaborationServiceProvider=array();
			$enterprise_collaboration_provider="";
			$enterprise_collaboration='';
			if(isset($data['enterprise_collaboration']) && sizeof($data['enterprise_collaboration'])>0){
				$enterprise_collaboration=implode(',',$data['enterprise_collaboration']);
				$j++;
			}
			if(isset($data['enterprise_collaboration']) && sizeof($data['enterprise_collaboration'])>0){
				foreach($data['enterprise_collaboration'] as $key => $val){
					if($val!=''){
						$enterprise_collaborationServiceProvider[$val]=$this->escape_string($this->strip_all($data['enterprise_collaboration_provider'][$val]));
					}
				}
			}
			if(sizeof($enterprise_collaborationServiceProvider)>0){
				$enterprise_collaboration_provider=json_encode($enterprise_collaborationServiceProvider);
				$j++;
			}
			
			$marketing_solutionServiceProvider=array();
			$marketing_solution_provider="";
			$marketing_solution='';
			if(isset($data['marketing_solution']) &&  sizeof($data['marketing_solution'])>0){
				$marketing_solution=implode(',',$data['marketing_solution']);
				$j++;
			}
			if(isset($data['marketing_solution']) &&  sizeof($data['marketing_solution'])>0){
				foreach($data['marketing_solution'] as $key => $val){
					if($val!=''){
						$marketing_solutionServiceProvider[$val]=$this->escape_string($this->strip_all($data['marketing_solution_provider'][$val]));
					}
				}
			}
			if(isset($data['marketing_solution']) &&  sizeof($marketing_solutionServiceProvider)>0){
				$marketing_solution_provider=json_encode($marketing_solutionServiceProvider);
				$j++;
			}
			
			$otherServiceProvider=array();
			$other_provider="";
			$other='';
			if(isset($data['other']) &&  sizeof($data['other'])>0){
				$other=implode(',',$data['other']);
				$j++;
			}
			if(isset($data['other']) &&  sizeof($data['other'])>0){
				foreach($data['other'] as $key => $val){
					if($val!=''){
						$otherServiceProvider[$val]=$this->escape_string($this->strip_all($data['other_provider'][$val]));
					}
				}
			}
			if(sizeof($otherServiceProvider)>0){
				$other_provider=json_encode($otherServiceProvider);
				$j++;
			}
			//echo $j;
			//exit;
			if($this->isUserLoggedIn()){
				$loggedInUserDetailsArr = $this->getLoggedInUserDetails();
				$edited_by=$loggedInUserDetailsArr['id'];
				// return true; // DEPRECATED
			}else{
				$edited_by="";
			}
			$edited_on=date('Y-m-d H:i:s');
			
			if($KMCDetails['registration_completed_by']==0 || $KMCDetails['registration_completed_by']==''){
			
				$sql="update ".PREFIX."kyc_form_details set form_no='$form_no', emp_code='$emp_code',legal_entity_name='$legal_entity_name',logo_name='$logo_name',logo_id='$logo_id',cin='$cin',company_email='$company_email',date_of_inc='$date_of_inc',ownership_structure='$ownership_structure',os_other='$os_other',class='$class',pan_no='$pan_no',address_of_organization='$address_of_organization',authorized_signatory_name='$authorized_signatory_name',authorized_signatory_email='$authorized_signatory_email',authorized_signatory_mobile='$authorized_signatory_mobile',state='$state',city='$city',pincode='$pincode',mobile='$mobile',telephone='$telephone',branceorcorporate='$branceorcorporate',corporate_office='$corporate_office',company_linkedin_page='$company_linkedin_page',company_website_link='$company_website_link',company_twitter_link='$company_twitter_link',industry_vertical='$industry_vertical',aotherindustryvertical='$aotherindustryvertical',no_of_employees='$no_of_employees',no_of_branches='$no_of_branches',annual_turnover='$annual_turnover',avg_spends='$avg_spends',a_salutation='$a_salutation',a_firstname='$a_firstname',a_lastname='$a_lastname',a_email_id='$a_email_id',a_designation='$a_designation',aotherdesignation='$aotherdesignation',a_mobile='$a_mobile',a_function='$a_function',aotherfunction='$aotherfunction',a_linkedin_id='$a_linkedin_id',a_twitter='$a_twitter',a_role='$a_role',a_imanage_credentials='$a_imanage_credentials',a_mail_authority='$mailAuthorizedSignatory',i_salutation='$i_salutation',i_firstname='$i_firstname',i_lastname='$i_lastname',i_email_id='$i_email_id',i_designation='$i_designation',iotherdesignation='$iotherdesignation',i_mobile='$i_mobile',i_function='$i_function',iotherfunction='$iotherfunction',i_linkedin_id='$i_linkedin_id',i_twitter='$i_twitter',i_role='$i_role',i_imanage_credentials='$i_imanage_credentials',i_mail_authority='$mailiManage',ad1_salutation='$ad1_salutation',ad1_firstname='$ad1_firstname',ad1_lastname='$ad1_lastname',ad1_email_id='$ad1_email_id',ad1_designation='$ad1_designation',ad1otherdesignation='$ad1otherdesignation',ad1_mobile='$ad1_mobile',ad1_function='$ad1_function',ad1otherfunction='$ad1otherfunction',ad1_linkedin_id='$ad1_linkedin_id',ad1_twitter='$ad1_twitter',ad1_role='$ad1_role',ad1_imanage_credentials='$ad1_imanage_credentials',ad1_mail_authority='$mailAdditionalContact1',ad2_salutation='$ad2_salutation',ad2_firstname='$ad2_firstname',ad2_lastname='$ad2_lastname',ad2_email_id='$ad2_email_id',ad2_designation='$ad2_designation',ad2otherdesignation='$ad2otherdesignation',ad2_mobile='$ad2_mobile',ad2_function='$ad2_function',ad2otherfunction='$ad2otherfunction',ad2_linkedin_id='$ad2_linkedin_id',ad2_twitter='$ad2_twitter',ad2_role='$ad2_role',ad2_imanage_credentials='$ad2_imanage_credentials',ad2_mail_authority='$mailAdditionalContact2',enterprise_connectivity_level='$enterprise_connectivity_level',marketing_solution_level='$marketing_solution_level',mobility_soution_level='$mobility_soution_level',enterpriseiot_solution_level='$enterpriseiot_solution_level',enterprise_collaboration_level='$enterprise_collaboration_level',mobility_solutions='$mobilitySolutions',mobility_service_provider='$mobility_service_provider',enterprise_connectivity='$enterprise_connectivity',enterprise_connectivity_provider='$enterprise_connectivity_provider',enterprise_collaboration='$enterprise_collaboration',enterprise_collaboration_provider='$enterprise_collaboration_provider',marketing_solution='$marketing_solution',marketing_solution_provider='$marketing_solution_provider',other='$other',other_provider='$other_provider',registration_status='$registration_status',edited_by='$edited_by',edited_on='$edited_on',completedfield='$j' where id='$id'";
				//exit;
				$this->query($sql);
				$loggedInUserDetailsArr = $this->getLoggedInUserDetails();
				$edited_by = $loggedInUserDetailsArr['id'];
				$this->query("insert into ".PREFIX."kyc_completed_status(kyc_id, completed_fields, edited_by) values('".$id."', '".$j."', '".$edited_by."')");
				if(isset($data['emp_code'])){
					$this->query("delete from ".PREFIX."kmcandemp where form_id='".$id."'");
					foreach($data['emp_code'] as $key => $val){
						if(!empty($val)){
							$this->query("insert into ".PREFIX."kmcandemp (form_id,emp_id) values ('".$id."','".$val."')");
						}
					}
				}
				
				//==================== new emp assigned mail ========================//
				if(sizeof($newwmparray)>0){
					foreach($newwmparray as $key => $val){
					$userDetails=$this->getUniqueUserById($val);
					$to=$userDetails['username'];
					$emailMsg="";
					$parent=$this->fetch($this->query("select * from ".PREFIX."admin where id='".$userDetails['parent']."'"));
					// == SEND EMAIL ==
					include_once("new-registration-assigned.inc.php");
					//echo $emailMsg;
					//exit;
					/* $emailObj = new Email();
					$emailObj->setAddress($to);
					//$emailObj->setAdminAddress(ADMIN_EMAIL);
					$emailObj->setSubject("New Registration Details | ".SITE_NAME);
					$emailObj->setEmailBody($emailMsg);
					$emailObj->sendEmail(); */
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($to);
					$mail->IsHTML(true);
					$mail->Subject = "New Registration Details | ".SITE_NAME;
					$mail->Body = $emailMsg;
					$mail->Send();
					//echo $emailMsg;
				}	
				}
				
				//==================== new emp assigned mail end========================//
				
				
				
				// ============= registration process completed mail ==============//
				if($KMCDetails['registration_status']=="In process" && $registration_status=="Completed"){
					$completed_date=date('Y-m-d H:i:s');
					$this->query("update ".PREFIX."kyc_form_details set registration_completed_by='".$edited_by."',status_completed_date='".$completed_date."' where id='".$id."'");
					if($mailAuthorizedSignatory=="Yes"){
						$to=$a_email_id;
						$mailname=$a_firstname.' '.$a_lastname;
					}elseif($mailiManage=="Yes"){
						$to=$i_email_id;
						$mailname=$i_firstname.' '.$i_lastname;
					}elseif($mailAdditionalContact1=="Yes"){
						$to=$ad1_email_id;
						$mailname=$ad1_firstname.' '.$ad1_lastname;
					}elseif($mailAdditionalContact2=="Yes"){
						$to=$ad2_email_id;
						$mailname=$ad2_firstname.' '.$ad2_lastname;
					}else{
						$to=$loggedInUserDetailsArr['username'];
					}
					$q=base64_encode($id.'#'.$mailname);
					$verification_link=md5($id.'#'.$mailname);
					$this->query("update ".PREFIX."kyc_form_details set verification_link='$verification_link' where id='".$id."'");
					
						// == SEND EMAIL FOR CUSTOMER== //
					include_once("new-registration-customer.inc.php");
						
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($to);
					$mail->AddCC($loggedInUserDetailsArr['username']);
					$mail->IsHTML(true);
					$mail->Subject = "KnowMyCustomer - We seek your approval";
					$mail->Body = $emailMsg;
					$mail->Send();
					//echo $emailMsg;
					// == SEND EMAIL FOR CUSTOMER END == //
					
					// == SEND EMAIL FOR EMPLOYEE FOR FORM REMOVE FROM PENDING WORK == //
					$empdetails=$this->query("select * from ".PREFIX."kmcandemp where form_id='$id'");
						include_once("remove-from-pending.inc.php");
					// == SEND EMAIL FOR EMPLOYEE FOR FORM REMOVE FROM PENDING WORK END == //
					
					
				}
				//exit;
			}
		}
		
		function deleteKMCForm($id){
			$this->query("delete from ".PREFIX."kyc_form_details where id='".$id."'");
			$this->query("delete from ".PREFIX."kmcandemp where form_id='".$id."'");
		}
		
		function getuserlastLogin($userDetails){
			$sql="select * from ".PREFIX."user_login_details where user_id='".$userDetails['id']."' order by id desc limit 1,1";
			$result=$this->query($sql);
			return $this->fetch($result);
		}
		
		function getCityByName($cityname){
				return $this->fetch($this->query("select * from ".PREFIX."city_master where city_name like '%".$cityname."%'"));
			}
		// === KYC FROM DETAILS END ===
		
		function getFormcountOfRegistrationstatusByEmpAndParent($userDetails,$status=''){
			if($status=="Completed"){
				 $regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and registration_status='".$status."' and registration_completed_by in (".$user.")";
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and registration_status='".$status."'";
			}
			return $regcom=$this->query($regcsql);
		}
		
		//double hierarchy means cluster=>CAM,PAM
		
		function getFormcountOfRegistrationstatusByEmpBydesignation($des='',$userDetails,$status=''){
			//if($userDetails['designation']=="Cluster Head (CH)"){
				if($status=="Completed"){
					 $regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$userDetails['id']."' and designation='$des') and status='Active' and registration_status='$status' and registration_completed_by in (select id from ".PREFIX."admin where parent='".$userDetails['id']."' and designation='$des') )";
				}else{
					$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$userDetails['id']."' and designation='$des') ) and status='Active' and registration_status='$status'";
				}
				//exit;
			//}
			return $regcom=$this->query($regcsql);
		}
		
		function getFormcountOfCustomerstatusByEmpBydesignation($des='',$userDetails,$status=''){
			//if($userDetails['designation']=="Cluster Head (CH)"){
				if($status=="Yes"){
					$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$userDetails['id']."' and designation='$des') and status='Active' and customer_status='".$status."' and registration_completed_by in (select id from ".PREFIX."admin where parent='".$userDetails['id']."' and designation='$des') )";
				}else{
					$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$userDetails['id']."' and designation='$des') ) and status='Active' and customer_status='".$status."'";
				}
				
				
			//}
			return $regcom=$this->query($regcsql);
		}
		
		//double hierarchy means cluster=>CAM,PAM END


		//double hierarchy means cluster=>PAM=>Parner
		
		function getFormcountOfRegistrationstatusByParent($des='',$userDetails,$status=''){
			//echo $status;
			if($status=="Completed"){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$userDetails['id']."')  and designation='$des') and status='Active' and registration_status='".$status."' and registration_completed_by in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$userDetails['id']."')  and designation='$des') )";
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$userDetails['id']."')  and designation='$des') ) and status='Active' and registration_status='$status'";
			
			}
			return $regcom=$this->query($regcsql);
		}
		
		function getFormcountOfCustomerstatusByParent($des='',$userDetails,$status=''){
			if($status=="Completed"){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$userDetails['id']."')  and designation='$des') and status='Active' and customer_status='".$status."' and registration_completed_by in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$userDetails['id']."')  and designation='$des') )";
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$userDetails['id']."')  and designation='$des') ) and status='Active' and customer_status='$status'";
			
			}
			return $regcom=$this->query($regcsql);
		}
		//double hierarchy means cluster=>PAM=>Parner END
		function getFormcountOfRegistrationstatusByEmp($user,$status=''){
			if($status=="Completed"){
				 $regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and registration_status='".$status."' and status='Active' and registration_completed_by in (".$user.")";
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and registration_status='".$status."' and status='Active'";
			}
			return $regcom=$this->query($regcsql);
		}
		
		
		
		
		function getFormcountOfCustomerstatusByEmp($user,$status=''){
			if($status=="Yes"){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and customer_status='".$status."' and status='Active' and registration_completed_by in (".$user.")";
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and status='Active' and customer_status='".$status."'";
			}	
			
			return $regcom=$this->query($regcsql);
		}
		
		//=============== Assigned ==================//
		function getFormcountOfAssignedByEmpBydesignation($des,$user){
			$query="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."' and designation='".$des."') group by form_id) and status='Active'";
			return $this->query($query);
		}
		
		function getFormcountOfAssignedByEmpParentBydesignation($des,$user){
			$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active'";
			return $this->query($regcsql);
		}
		
		function getCustomerAssignedByMultipleEmpId($user){
			//echo $user;
			$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user['id'].") group by form_id) and status='Active'";
			return $regcom=$this->query($regcsql);
		}
		
		function getCustomerAssignedByMultipleEmpIds($user){
			//echo $user;
			$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and status='Active'";
			return $regcom=$this->query($regcsql);
		}
		
		//Double hierarchya for total assigned count
		function getCustomerAssignedByParentMultipleEmpId($user,$des=''){
			//echo $des;
			if($des!=''){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active'";
				//exit;
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') and status='Active')";
			}
			return $regcom=$this->query($regcsql);
		}
		
		function getFormcountOfRegistrationstatusByParentId($user,$des='',$status=''){
			
			if($des!=''){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active' and registration_status='$status'";
				//exit;
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') ) and status='Active'";
			}
			return $regcom=$this->query($regcsql);
		}
		
		function getFormcountOfCustomerstatusByParentId($user,$des='',$status=''){
			if($des!=''){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active' and customer_status='$status'";
				//exit;
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') ) and status='Active'";
			}
			return $regcom=$this->query($regcsql);
		}
		
		//===============Notification Area ===================//
			function addNotification($data,$userDetails){
				$user_id=$this->escape_string($this->strip_all($data['user_id']));
				$notification_msg=$this->escape_string($this->strip_all($data['notification_msg']));
				include "LogWriter.php";
				$roleUserResult=$this->query("select * from ".PREFIX."admin where id='".$user_id."'");
				$roleUserRow=$this->fetch($roleUserResult);
				$to=$roleUserRow['contact_no'];
				$Message=$notification_msg;
				$Msg=urlencode($Message);
				
				$msgUrl="http://182.71.161.20:8080/EnterpriseUi/pushasynchAction/Nzg%3D.html?username=".USERNAME."&password=".PASSWORD."&from=".SMSFROM."&to=91".$to."&text=".$Msg;
				$this->writeToFile($Message,$msgUrl);
				//$func->writeToFile($Message,$msgUrl);
				$response=file_get_contents($msgUrl);
				$this->writeToFile($response,$msgUrl);
				$query="insert into ".PREFIX."user_notification (user_id,notification_msg,from_id,date) values ('".$user_id."','".$notification_msg."','".$userDetails['id']."',now())";
				$this->query($query);
				
			}
			
			function getUniqueNotificationById($id){
				$result=$this->query("select * from ".PREFIX."user_notification where id='".$id."'");
				return $this->fetch($result);
			}
			
			function updateNotification($data,$userDetails){
				$id=$this->escape_string($this->strip_all($data['id']));
				$notification_msg=$this->escape_string($this->strip_all($data['notification_msg']));
				$user_id=$this->escape_string($this->strip_all($data['user_id']));
				include "LogWriter.php";
				$roleUserResult=$this->query("select * from ".PREFIX."admin where id='".$user_id."'");
				$roleUserRow=$this->fetch($roleUserResult);
				$to=$roleUserRow['contact_no'];
				$Message=$notification_msg;
				$Msg=urlencode($Message);
				
				$msgUrl="http://182.71.161.20:8080/EnterpriseUi/pushasynchAction/Nzg%3D.html?username=".USERNAME."&password=".PASSWORD."&from=".SMSFROM."&to=91".$to."&text=".$Msg;
				$this->writeToFile($Message,$msgUrl);
				//$func->writeToFile($Message,$msgUrl);
				$response=file_get_contents($msgUrl);
				$this->writeToFile($response,$msgUrl);
				
				$query="update ".PREFIX."user_notification set notification_msg='".$notification_msg."',date=now() where user_id='".$user_id."' and id='".$id."'";
				$this->query($query);
			}
			
			function deleteNotification($id){
				$this->query("delete from ".PREFIX."user_notification where id='".$id."'");
			}
			
			function getUserNotitfication($userDetails){
				//echo "select u.*,rn.* from ".PREFIX."user_notification u,".PREFIX."role_notification rn where (u.user_id='".$userDetails['id']."' or (find_in_set('".$userDetails['id']."',rn.user_id) and role='".$userDetails['designation']."')) order by u.date desc,rn.date desc";
				return $this->query("select * from ".PREFIX."user_notification where user_id='".$userDetails['id']."' order by date desc");
			}
			
			function getUserNotitficationunreadCount($userDetails){
				return $this->query("select * from ".PREFIX."user_notification where user_id='".$userDetails['id']."' and read_status='0' order by date desc");
			}
			
			function deletedatabase($id){
				return $this->query("delete from ".PREFIX."database_backup where id='".$id."'");
			}
			
			function addroleNotification($data,$user){
				//$role=trim($this->escape_string($this->strip_all($data['role'])));
				$role=implode(',',$data['role']);
				$notification_msg=trim($this->escape_string($this->strip_all($data['notification_msg'])));
				$userArray=array();
				$roleu="";
				$this->query("insert into ".PREFIX."role_notification (role,notification_msg,date) values ('".$role."','".$notification_msg."',now())");
				$last_id=$this->last_insert_id();
				include "LogWriter.php";
				foreach($data['role'] as $key=>$val){
					//echo "select * from ".PREFIX."admin where designation='".$val."'";
					$roleUserResult=$this->query("select * from ".PREFIX."admin where designation='".$val."'");
					while($roleUserRow=$this->fetch($roleUserResult)){
						//$userArray[]=$roleUserRow['id'];
						$to=$roleUserRow['contact_no'];
						$Message=$notification_msg;
						$Msg=urlencode($Message);
						
						//$msgUrl="http://182.71.161.20:8080/EnterpriseUi/pushasynchAction/Nzg%3D.html?username=".USERNAME."&password=".PASSWORD."&from=".SMSFROM."&to=91".$to."&text=".$Msg;
						$msgUrl="http://www.mgage.solutions/SendSMS/sendmsg.php?uname=".USERNAME."&pass=".PASSWORD."&send=".SMSFROM."&dest=91".$to."&msg=".$Msg;
						$this->writeToFile($Message,$msgUrl);
						//$func->writeToFile($Message,$msgUrl);
						$response=file_get_contents($msgUrl);
						$this->writeToFile($response,$msgUrl);
						//exit;
						$this->query("insert into ".PREFIX."user_notification (user_id,unique_id,notification_msg,from_id,date) values ('".$roleUserRow['id']."','".$last_id."','".$notification_msg."','".$user['id']."',now())");
					}
					
				}
				//exit;
				/* if(sizeof($userArray)>0){
					$roleu=implode(',',$userArray);
				} */
				
				
			}
			
			
			function getUniqueRoleNotificationById($id){
				return $this->fetch($this->query("select * from ".PREFIX."role_notification where id='".$id."'"));
			}
			
			function updateRoleNotification($data,$userdetails){
				$id=trim($this->escape_string($this->strip_all($data['id'])));
				//$role=trim($this->escape_string($this->strip_all($data['role'])));
				$role=implode(',',$data['role']);
				$notification_msg=trim($this->escape_string($this->strip_all($data['notification_msg'])));
				$userArray=array();
				$roleu="";
				$this->query("update ".PREFIX."role_notification set role='".$role."',notification_msg='".$notification_msg."',date=now() where id='".$id."'");
				$this->query("delete from ".PREFIX."user_notification where unique_id='".$id."'");
				include "LogWriter.php";
				foreach($data['role'] as $key=>$val){
					$roleUserResult=$this->query("select * from ".PREFIX."admin where designation='".$val."'");
					while($roleUserRow=$this->fetch($roleUserResult)){
						//$userArray[]=$roleUserRow['id'];
						//$this->query("update ".PREFIX."user_notification set notification_msg='".$notification_msg."',date=now() where unique_id='".$id."'");
						$to=$roleUserRow['contact_no'];
						$Message=$notification_msg;
						$Msg=urlencode($Message);
						
						//$msgUrl="http://182.71.161.20:8080/EnterpriseUi/pushasynchAction/Nzg%3D.html?username=".USERNAME."&password=".PASSWORD."&from=".SMSFROM."&to=91".$to."&text=".$Msg;
						$msgUrl="http://www.mgage.solutions/SendSMS/sendmsg.php?uname=".USERNAME."&pass=".PASSWORD."&send=".SMSFROM."&dest=91".$to."&msg=".$Msg;
						$this->writeToFile($Message,$msgUrl);
						//$func->writeToFile($Message,$msgUrl);
						$response=file_get_contents($msgUrl);
						$this->writeToFile($response,$msgUrl);
						$this->query("insert into ".PREFIX."user_notification (user_id,unique_id,notification_msg,from_id,date) values ('".$roleUserRow['id']."','".$id."','".$notification_msg."','".$user['id']."',now())");
					}
					/* if(sizeof($userArray)>0){
						$roleu=implode(',',$userArray);
					} */
				}
				
				
			}
			
			function deleteRoleNotification($id){
				$this->query("delete from ".PREFIX."role_notification where id='".$id."'");
			}
		//===============Notification Area End ===================//
		
		
		function getTotalFieldScore($loggedInUserDetailsArr){
			$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id='".$loggedInUserDetailsArr['id']."' group by form_id) and status='Active'";
			$totalResult=$this->query($totalquery);
			$totalforms=$this->num_rows($totalResult);
			$query="select sum(completedfield) as sumr from tata_kyc_form_details where id in (select form_id from tata_kmcandemp where emp_id='".$loggedInUserDetailsArr['id']."' group by form_id) and status='Active'";
			$sumoffiledsResult=$this->query($query);
			$sumoffiledsFetch=$this->fetch($sumoffiledsResult);
			//$completedquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id='".$loggedInUserDetailsArr['id']."' group by form_id) and registration_status='Completed'  and status='Active'";
			/* $parent=$this->fetch($this->query("select * from ".PREFIX."admin where id='".$loggedInUserDetailsArr['parent']."'"));
			if($parent){
				$parentfullname=$parent['full_name'];
				$parentDesignation=$parent['designation'];
			}else{
				$parentfullname="Tata Docomo Business Services";
				$parentDesignation="";
			} */
			//$completedResult=$this->query($completedquery);
			$i=0;
			$j=0;
			$totalfields=$totalforms*92;
			$notemptyfield=$sumoffiledsFetch['sumr'];
			/* while($completedRow=$this->fetch($totalResult)){
				if(!empty($completedRow['completedfield'])){
					$notemptyfield=$notemptyfield+$completedRow['completedfield'];
				}
			} */
			
			//$completedforms=$this->num_rows($completedResult);
			if($totalforms!=0 && $notemptyfield!=0){
				return $completedpercent=round(($notemptyfield/$totalfields)*100);
			}else{
				return $completedpercent='0';
			}
		}
		
		
		function getTotalFieldScoreByParent($user,$des=''){
			if($des!=''){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active'";
				//exit;
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') and status='Active')";
			}
			 //$regcom=$this->query($regcsql);
			 $totalResult=$this->query($regcsql);
			 $totalforms=$this->num_rows($totalResult);
			 $totalfields=$totalforms*92;
			$notemptyfield=0;
			while($completedRow=$this->fetch($totalResult)){
				if(!empty($completedRow['completedfield'])){
					$notemptyfield=$notemptyfield+$completedRow['completedfield'];
				}
			}
			
			//$completedforms=$this->num_rows($completedResult);
			if($totalforms!=0 && $notemptyfield!=0){
				return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
			}else{
				return $completedpercent='0';
			}
			 
		}
		
		function getTotalFieldScoreByParent_new($user,$des=''){
		
			$regcsql="select count(*) as num from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active'";
			
			$regcsql_sum="select sum(completedfield) as completedfield from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active'";
			//exit;
			
			 //$regcom=$this->query($regcsql);
			 $totalResult=$this->query($regcsql);
			 $totalResult1 =$this->fetch($totalResult);
			 $totalforms=$totalResult1['num'];
			 $totalfields=$totalforms*92;
			$notemptyfield= $regcsql_sum;
			
			//$completedforms=$this->num_rows($completedResult);
			if($totalforms!=0 && $notemptyfield!=0){
				return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
			}else{
				return $completedpercent='0';
			}
			 
		}
		
		function getTotalFieldScoreByMultipleIds($user){
			$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (".$user.") group by form_id) and status='Active'";
			/* return $regcom=$this->query($regcsql);
			if($des!=''){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent='".$user['id']."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') group by form_id ) and status='Active'";
				//exit;
			}else{
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent='".$user['id']."')  and designation='$des') and status='Active')";
			} */
			 //$regcom=$this->query($regcsql);
			 $totalResult=$this->query($regcsql);
			 $totalforms=$this->num_rows($totalResult);
			 $totalfields=$totalforms*92;
			$notemptyfield=0;
			while($completedRow=$this->fetch($totalResult)){
				if(!empty($completedRow['completedfield'])){
					$notemptyfield=$notemptyfield+$completedRow['completedfield'];
				}
			}
			
			//$completedforms=$this->num_rows($completedResult);
			if($totalforms!=0 && $notemptyfield!=0){
				return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
			}else{
				return $completedpercent='0';
			}
			 
		}
		
		function getTotalFieldScoreForAdmin($loggedInUserDetailsArr){
			$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id='".$loggedInUserDetailsArr['id']."' group by form_id) and status='Active'";
			$totalResult=$this->query($totalquery);
			$totalforms=$this->num_rows($totalResult);
			
			$query="select sum(completedfield) as sumr from tata_kyc_form_details where id in (select form_id from tata_kmcandemp where emp_id='".$loggedInUserDetailsArr['id']."' group by form_id) and status='Active'";
			$sumoffiledsResult=$this->query($query);
			$sumoffiledsFetch=$this->fetch($sumoffiledsResult);
			//$completedquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id='".$loggedInUserDetailsArr['id']."' group by form_id) and registration_status='Completed'  and status='Active'";
			/* $parent=$this->fetch($this->query("select * from ".PREFIX."admin where id='".$loggedInUserDetailsArr['parent']."'"));
			if($parent){
				$parentfullname=$parent['full_name'];
				$parentDesignation=$parent['designation'];
			}else{
				$parentfullname="Tata Docomo Business Services";
				$parentDesignation="";
			} */
			//$completedResult=$this->query($completedquery);
			$i=0;
			$j=0;
			$totalfields=$totalforms*25;
			$notemptyfield=$sumoffiledsFetch['sumr'];
			/* while($completedRow=$this->fetch($totalResult)){
				if(!empty($completedRow['completedfield'])){
					$notemptyfield=$notemptyfield+$completedRow['completedfield'];
				}
			} */
			
			//$completedforms=$this->num_rows($completedResult);
			if($totalforms!=0 && $notemptyfield!=0){
				return $completedpercent=round(($notemptyfield/$totalfields)*100);
			}else{
				return $completedpercent='0';
			}
		}
		
		function getScoreByRoleWise($role){
			if($role=="Cluster Head (CH)"){
				$regcsql="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where designation='".$role."')) or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where designation='".$role."'))  and designation='Partner') group by form_id ) and status='Active'";
				//exit;
				$totalResult=$this->query($regcsql);
				$totalforms=$this->num_rows($totalResult);
				$totalfields=$totalforms*92;
				$notemptyfield=0;
				while($completedRow=$this->fetch($totalResult)){
					if(!empty($completedRow['completedfield'])){
						$notemptyfield=$notemptyfield+$completedRow['completedfield'];
					}
				}
				
				//$completedforms=$this->num_rows($completedResult);
				if($totalforms!=0 && $notemptyfield!=0){
					return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
				}else{
					return $completedpercent='0';
				}
			}elseif($role=="CAM"){
				//$scoreResult=$func->getTotalFieldScore($row);
				$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where designation='".$role."') group by form_id) and status='Active'";
				$totalResult=$this->query($totalquery);
				$totalforms=$this->num_rows($totalResult);
				$totalfields=$totalforms*92;
				$notemptyfield=0;
				while($completedRow=$this->fetch($totalResult)){
					if(!empty($completedRow['completedfield'])){
						$notemptyfield=$notemptyfield+$completedRow['completedfield'];
					}
				}
				
				//$completedforms=$this->num_rows($completedResult);
				if($totalforms!=0 && $notemptyfield!=0){
					return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
				}else{
					return $completedpercent='0';
				}
			}elseif($role=="PAM"){
				$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where designation='".$role."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where designation='".$role."')))";
				$totalResult=$this->query($totalquery);
				$totalforms=$this->num_rows($totalResult);
				$totalfields=$totalforms*92;
				$notemptyfield=0;
				while($completedRow=$this->fetch($totalResult)){
					if(!empty($completedRow['completedfield'])){
						$notemptyfield=$notemptyfield+$completedRow['completedfield'];
					}
				}
				
				//$completedforms=$this->num_rows($completedResult);
				if($totalforms!=0 && $notemptyfield!=0){
					return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
				}else{
					return $completedpercent='0';
				}
			}elseif($role=="Regional Head"){
				$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where designation='".$role."') or emp_id in (select id from ".PREFIX."admin where parent in (select id from ".PREFIX."admin where designation='".$role."')))";
				$totalResult=$this->query($totalquery);
				$totalforms=$this->num_rows($totalResult);
				$totalfields=$totalforms*92;
				$notemptyfield=0;
				while($completedRow=$this->fetch($totalResult)){
					if(!empty($completedRow['completedfield'])){
						$notemptyfield=$notemptyfield+$completedRow['completedfield'];
					}
				}
				//echo $notemptyfield;
				//$completedforms=$this->num_rows($completedResult);
				if($totalforms!=0 && $notemptyfield!=0){
					return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
				}else{
					return $completedpercent='0';
				}
			}elseif($role=='Partner'){
				$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where designation='".$role."') group by form_id) and status='Active'";
				$totalResult=$this->query($totalquery);
				$totalforms=$this->num_rows($totalResult);
				$totalfields=$totalforms*92;
				$notemptyfield=0;
				while($completedRow=$this->fetch($totalResult)){
					if(!empty($completedRow['completedfield'])){
						$notemptyfield=$notemptyfield+$completedRow['completedfield'];
					}
				}
				
				//$completedforms=$this->num_rows($completedResult);
				if($totalforms!=0 && $notemptyfield!=0){
					return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
				}else{
					return $completedpercent='0';
				}
			}elseif($role=='Relationship Manager (RM)'){
				$totalquery="select * from ".PREFIX."kyc_form_details where id in (select form_id from ".PREFIX."kmcandemp where emp_id in (select id from ".PREFIX."admin where designation='".$role."') group by form_id) and status='Active'";
				$totalResult=$this->query($totalquery);
				$totalforms=$this->num_rows($totalResult);
				$totalfields=$totalforms*92;
				$notemptyfield=0;
				while($completedRow=$this->fetch($totalResult)){
					if(!empty($completedRow['completedfield'])){
						$notemptyfield=$notemptyfield+$completedRow['completedfield'];
					}
				}
				
				//$completedforms=$this->num_rows($completedResult);
				if($totalforms!=0 && $notemptyfield!=0){
					return $completedpercent=round(($notemptyfield/$totalfields)*100)."%";
				}else{
					return $completedpercent='0';
				}
			}
			
		}

		/* ===CAF FORM STARTS=== */
		function getVariant($product) {
			$product = trim($this->escape_string($this->strip_all($product)));
			$query = "select DISTINCT(variant) from ".PREFIX."caf_product_master where product='".$product."' and variant<>''";
			return $this->query($query);
		}
		function getSubVariant($product,$variant) {
			$product = trim($this->escape_string($this->strip_all($product)));
			$variant = trim($this->escape_string($this->strip_all($variant)));
			$query = "select DISTINCT(sub_variant) from ".PREFIX."caf_product_master where product='".$product."' and variant='".$variant."' and sub_variant<>''";
			return $this->query($query);
		}
		function generateCafNo($state,$city) {
			$stateDetail = $this->getstatebyid($state);
			$cityDetail = $this->getCityNameById($city);
			$state_code = $stateDetail['state_code'];
			$city_zone = $cityDetail['zone'];
			if($state=='1646') {
				
				if($city=='18258' || $city== '18277' || $city== '18278' || $city== '18122'|| $city== '18169' || $city== '18279' || $city== '18387' ) {
					$state_code = 'Mumbai';
				}else{
					$state_code = 'ROM';
				}
			} else if($state=='1662') {
				if($city_zone=='East') {
					$state_code = 'UPE';
				} else if($city_zone=='West') {
					$state_code = 'UPW';
				} else {
					$state_code = 'DNCR';
				}
			}
			
			if($state_code == "Mumbai"){ 
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else if($state_code == "PUN"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else if($state_code == "APR"){
			    $cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");		
			}else if($state_code == "ROM"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else if($state_code == "GJ"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else if($state_code == "TN"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else if($state_code == "HAR"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else{
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by id DESC LIMIT 0,1");
			}
			
			/* if($state_code == "KA"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else */ 
			/* if($state_code == "KA"){
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else if($state_code == "Mumbai"){ 
				$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by caf_no DESC LIMIT 0,1");
			}else{ */
				//$cafNoRS = $this->query("select caf_no  from ".PREFIX."caf_details where caf_no LIKE '".$state_code."-%' order by id DESC LIMIT 0,1");
			//}
			if($this->num_rows($cafNoRS)>0) {
				$cafNoDetail = $this->fetch($cafNoRS);
				$lastCaf = $cafNoDetail['caf_no'];
				//$series = str_replace($state_code.'-', '', $lastCaf);
				$series = preg_replace('/[^0-9]/', '', $lastCaf);
				$newSeries = $series + 1;
			} else {
				$newSeries = $stateDetail['series_start'];
				if($state_code=='MUM') {
					$newSeries = '8959900';
				} else if($state=='1662' and $city_zone=='East') {
					$newSeries = '8701610563901';
				} else if($state=='1662' and $city_zone=='West') {
					$newSeries = '8710101066401';
				}
			}
			$caf_no = $state_code.'-'.$newSeries;
			//Mukunda Check CAF no exits or not.
			$confirm = $this->query("select * from ".PREFIX."caf_details WHERE caf_no='".$caf_no."'");
			$confirmrs = $this->num_rows($confirm);
			if($confirmrs > 0){
				$caf_no1 = $this->generateCafNo($state,$city);
			}else {
				return $caf_no;
			}
		}
		
		function generateCafNo_new($state_code,$state) {
			$stateDetail = $this->getstatebyid($state);
			//$state_code = $stateDetail['state_code'];
			if($state_code == "Mumbai"){
				$sc = "MUM";
			}else{
				$sc = $state_code;
			}
			$cafNoRS = $this->query("select caf_no from ".PREFIX."caf_details where caf_no LIKE '".$sc."-%' order by id DESC LIMIT 0,1");
			
			//$cafNoRS = $this->query("select caf_no from ".PREFIX."caf_details where caf_no LIKE '".$state_code."%' order by id DESC LIMIT 0,1");
			if($this->num_rows($cafNoRS)>0) {
				
				$cafNoDetail = $this->fetch($cafNoRS);
				$lastCaf = $cafNoDetail['caf_no'];
				//$series = str_replace($state_code.'-', '', $lastCaf); 
				$series = preg_replace('/[^0-9]/', '', $lastCaf);
				$newSeries = $series + 1;
				
			} else {
				
				$newSeries = $stateDetail['series_start'];
				if($newSeries=='0') {
					$newSeries = '1';
				} 
			}
			$caf_no = $state_code.'-'.$newSeries;
			
			return $caf_no;
		}
		function addCAFForm($data,$file) {
			$company_name = trim($this->escape_string($this->strip_all($data['company_name'])));
			
			if(!empty($cin)){
				$cin = trim($this->escape_string($this->strip_all($data['cin'])));
			}else{
				$cin = "0";
			}
			
			$pan = trim($this->escape_string($this->strip_all($data['pan'])));
			if(!empty($logo_id)){
			$logo_id = trim($this->escape_string($this->strip_all($data['logo_id'])));
			}else{
			$logo_id = "0";
			}
			$osp = trim($this->escape_string($this->strip_all($data['osp'])));
			$title = trim($this->escape_string($this->strip_all($data['title'])));
			$sez_certificate_no = trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			$nature_business = trim($this->escape_string($this->strip_all($data['nature_business'])));
			$aotherindustryvertical = trim($this->escape_string($this->strip_all($data['aotherindustryvertical'])));
			$no_of_employees = trim($this->escape_string($this->strip_all($data['no_of_employees'])));
			$no_of_branches = trim($this->escape_string($this->strip_all($data['no_of_branches'])));
			$turnover = trim($this->escape_string($this->strip_all($data['turnover'])));
			$salutation = trim($this->escape_string($this->strip_all($data['salutation'])));
			$name = trim($this->escape_string($this->strip_all($data['name'])));
			$designation = trim($this->escape_string($this->strip_all($data['designation'])));
			$anotherdesignation = trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			$email = trim($this->escape_string($this->strip_all($data['email'])));
			$mobile = trim($this->escape_string($this->strip_all($data['mobile'])));
			$telephone = trim($this->escape_string($this->strip_all($data['telephone'])));
			$aadhar_no = trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			$same_contact_person = trim($this->escape_string($this->strip_all($data['same_contact_person'])));

			$po_given = trim($this->escape_string($this->strip_all($data['po_given'])));
			if(!isset($data['po_given']) || empty($data['po_given'])){
				$po_given = '';
			}
			//echo $po_given;exit;
			if($data['same_contact_person']=='Yes') {
				$contact_person = $this->escape_string($this->strip_all($name));
				$contact_person_designation = $this->escape_string($this->strip_all($designation));
				$contact_person_email = $this->escape_string($this->strip_all($email));
				$contact_person_mobile = $this->escape_string($this->strip_all($mobile));
			} else {
				$contact_person = trim($this->escape_string($this->strip_all($data['contact_person'])));
				$contact_person_designation = trim($this->escape_string($this->strip_all($data['contact_person_designation'])));
				$contact_person_email = trim($this->escape_string($this->strip_all($data['contact_person_email'])));
				$contact_person_mobile = trim($this->escape_string($this->strip_all($data['contact_person_mobile'])));
			}
			
			if(!empty($data['address'])){$address = trim($this->escape_string($this->strip_all($data['address'])));}else{$address ="";}
			if(!empty($data['gst_no'])){$gst_no = trim($this->escape_string($this->strip_all($data['gst_no'])));}else{$gst_no ="";}
			if(!empty($data['state'])){$state = trim($this->escape_string($this->strip_all($data['state'])));}else{$state ="0";}
			if(!empty($data['city'])){$city = trim($this->escape_string($this->strip_all($data['city'])));}else{$city ="0";}
			if(!empty($data['pincode'])){$pincode = trim($this->escape_string($this->strip_all($data['pincode'])));}else{$pincode ="0";}
			if(!empty($data['alternate_gst_no'])){$alternate_gst_no = trim($this->escape_string($this->strip_all($data['alternate_gst_no'])));}else{$alternate_gst_no ="0";}
			$alternate_bdlg_no = trim($this->escape_string($this->strip_all($data['alternate_bdlg_no'])));
			$alternate_bdlg_name = trim($this->escape_string($this->strip_all($data['alternate_bdlg_name'])));
			$alternate_floor = trim($this->escape_string($this->strip_all($data['alternate_floor'])));
			$alternate_street_name = trim($this->escape_string($this->strip_all($data['alternate_street_name'])));
			$alternate_area = trim($this->escape_string($this->strip_all($data['alternate_area'])));
			$alternate_landmark = trim($this->escape_string($this->strip_all($data['alternate_landmark'])));
			$alternate_state = trim($this->escape_string($this->strip_all($data['alternate_state'])));
			$alternate_city = trim($this->escape_string($this->strip_all($data['alternate_city'])));
			$alternate_pincode = trim($this->escape_string($this->strip_all($data['alternate_pincode'])));
			//$alternate_multiple_installation_address = trim($this->escape_string($this->strip_all($data['alternate_multiple_installation_address'])));
			$same_billing_address = trim($this->escape_string($this->strip_all($data['same_billing_address'])));
			$billing_state = "";
			$billing_city = "";
			$billing_pincode = "";
			if($data['same_billing_address']=='Yes') {
				$billing_gst_no = $this->escape_string($this->strip_all($alternate_gst_no));
				$billing_bdlg_no = $this->escape_string($this->strip_all($alternate_bdlg_no));
				$billing_bdlg_name = $this->escape_string($this->strip_all($alternate_bdlg_name));
				$billing_floor = $this->escape_string($this->strip_all($alternate_floor));
				$billing_street_name = $this->escape_string($this->strip_all($alternate_street_name));
				$billing_area = $this->escape_string($this->strip_all($alternate_area));
				$billing_landmark = $this->escape_string($this->strip_all($alternate_landmark));
				$billing_state = $this->escape_string($this->strip_all($alternate_state));
				$billing_city = $this->escape_string($this->strip_all($alternate_city));
				$billing_pincode = $this->escape_string($this->strip_all($alternate_pincode));
			} else {
				$billing_gst_no = trim($this->escape_string($this->strip_all($data['billing_gst_no'])));
				$billing_bdlg_no = trim($this->escape_string($this->strip_all($data['billing_bdlg_no'])));
				$billing_bdlg_name = trim($this->escape_string($this->strip_all($data['billing_bdlg_name'])));
				$billing_floor = trim($this->escape_string($this->strip_all($data['billing_floor'])));
				$billing_street_name = trim($this->escape_string($this->strip_all($data['billing_street_name'])));
				$billing_area = trim($this->escape_string($this->strip_all($data['billing_area'])));
				$billing_landmark = trim($this->escape_string($this->strip_all($data['billing_landmark'])));
				$billing_state = trim($this->escape_string($this->strip_all($data['billing_state'])));
				$billing_city = trim($this->escape_string($this->strip_all($data['billing_city'])));
				$billing_pincode = trim($this->escape_string($this->strip_all($data['billing_pincode'])));
			}
			$loggedInUserDetailsArr = $this->sessionExists();
			if(!empty($alternate_state) && !empty($alternate_city) && !empty($loggedInUserDetailsArr['login_circle'])){
				//$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
				//$caf_no = $this->generateCafNo_new($loggedInUserDetailsArr['login_circle'],$alternate_state);
				$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
				
			}else if(!empty($alternate_state) && !empty($alternate_city)){
				
				$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
			}
			else{
				$caf_no = 0;
			}
			
			$accepttermscondition = $this->escape_string($data['accepttermscondition']);
			if($accepttermscondition=='accepttermscondition') {
				$customer_form_sent='Yes';
			} else {
				$customer_form_sent='No';
			}

			$loggedInUserDetailsArr = $this->sessionExists();
			$emp_id = trim($this->escape_string($this->strip_all($loggedInUserDetailsArr['id'])));
			$segment = trim($this->escape_string($this->strip_all($loggedInUserDetailsArr['segment'])));
			
			//$uploadDir = 'caf-uploads/';
			if(!empty($file['image_name']['name'])) {
				/* $file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				move_uploaded_file($file['image_name']['tmp_name'],$uploadDir.$image_name);  */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$image_name, fopen($file['image_name']['tmp_name'], 'rb'), 'public-read');
			}
			
			if(!empty($file['alternate_multiple_installation_address']['name'])) {
				//echo "here";exit;
				/* $file_name = strtolower( pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_EXTENSION));
				$alternate_multiple_installation_address = time().'01.'.$ext;
				move_uploaded_file($file['alternate_multiple_installation_address']['tmp_name'],$uploadDir.$alternate_multiple_installation_address); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_EXTENSION));
				$alternate_multiple_installation_address = time().'01.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$alternate_multiple_installation_address, fopen($file['alternate_multiple_installation_address']['tmp_name'], 'rb'), 'public-read');
			}
	
			/*by dhanashree*/
				$random_no 		= str_shuffle('1234567890-');
				$random_no		= substr($random_no,0,5);
				$random_letters = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
				$random_letters	= substr($random_letters,0,5);
				$random_str 	= str_shuffle($random_letters.$random_no);
				
				$unique_id = $this->generate_no($random_str,'caf_details','unique_id');
			
			if(!empty($data['dealer_detail_code'])){
				$dealer_detail_code = trim($this->escape_string($this->strip_all($data['dealer_detail_code'])));
			}else{
				$dealer_detail_code = '';
			}
			if(!empty($data['dealer_detail_name'])){
				$dealer_detail_name = trim($this->escape_string($this->strip_all($data['dealer_detail_name'])));
			}else{
				$dealer_detail_name = '';
			}
			$sales_support_email = trim($this->escape_string($this->strip_all($data['sales_support_email'])));
			$additional_customer_email = trim($this->escape_string($this->strip_all($data['additional_customer_email'])));
			$fos_code = trim($this->escape_string($this->strip_all($data['fos_code'])));
			
			$brm_email_id = trim($this->escape_string($this->strip_all($data['brm_email_id'])));
			$remark = trim($this->escape_string($this->strip_all($data['remark'])));
			
			if(!empty($data['lbs_scheme'])){$lbs_scheme = trim($this->escape_string($this->strip_all($data['lbs_scheme'])));}else{$lbs_scheme ="";}
			
			$query = "insert into ".PREFIX."caf_details(brm_email_id,sales_support_email,additional_customer_email,dealer_detail_code, fos_code, dealer_detail_name,unique_id,emp_id,segment,caf_no, cin, company_name, po_given, pan, logo_id, osp, title, sez_certificate_no, nature_business, aotherindustryvertical, no_of_employees, no_of_branches, turnover, salutation, name, designation, anotherdesignation, email, mobile, telephone, aadhar_no, same_contact_person, contact_person, contact_person_designation, contact_person_email, contact_person_mobile, address, gst_no, state, city, pincode, image_name, alternate_gst_no, alternate_bdlg_no, alternate_bdlg_name, alternate_floor, alternate_street_name, alternate_area, alternate_landmark, alternate_state, alternate_city, alternate_pincode, alternate_multiple_installation_address, same_billing_address, billing_gst_no, billing_bdlg_no, billing_bdlg_name, billing_floor, billing_street_name, billing_area, billing_landmark, billing_state, billing_city, billing_pincode, customer_form_sent, caf_status,form_flag, remarks, epos_id) values ('".$brm_email_id."','".$sales_support_email."','".$additional_customer_email."','".$dealer_detail_code."','".$fos_code."','".$dealer_detail_name."','".$unique_id."','".$emp_id."', '".$segment."', '".$caf_no."', '".$cin."', '".$company_name."', '".$po_given."', '".$pan."', '".$logo_id."', '".$osp."', '".$title."', '".$sez_certificate_no."', '".$nature_business."', '".$aotherindustryvertical."', '".$no_of_employees."', '".$no_of_branches."', '".$turnover."', '".$salutation."', '".$name."', '".$designation."', '".$anotherdesignation."', '".$email."', '".$mobile."', '".$telephone."', '".$aadhar_no."', '".$same_contact_person."', '".$contact_person."', '".$contact_person_designation."', '".$contact_person_email."', '".$contact_person_mobile."', '".$address."', '".$gst_no."', '".$state."', '".$city."', '".$pincode."', '".$image_name."', '".$alternate_gst_no."', '".$alternate_bdlg_no."', '".$alternate_bdlg_name."', '".$alternate_floor."', '".$alternate_street_name."', '".$alternate_area."', '".$alternate_landmark."', ".$alternate_state.", ".$alternate_city.", '".$alternate_pincode."', '".$alternate_multiple_installation_address."', '".$same_billing_address."', '".$billing_gst_no."', '".$billing_bdlg_no."', '".$billing_bdlg_name."', '".$billing_floor."', '".$billing_street_name."', '".$billing_area."', '".$billing_landmark."', ".$billing_state.", ".$billing_city.", '".$billing_pincode."', '".$customer_form_sent."', 'Pending with Customer','DCAF_portal', '".$remark."', '".$lbs_scheme."')";
			$sql = $this->query($query);

			$caf_id = $this->last_insert_id();
			
			if(!empty($remark)){			
			$remarkQuery = "insert into ".PREFIX."caf_remarks (caf_id, user_id, remarks)values ('".$caf_id."','".$emp_id."','".$remark."')";
			$remark_result = $this->query($remarkQuery);
			}

			// PRODUCT DETAILS
			$product = trim($this->escape_string($this->strip_all($data['product'])));
			$variant = trim($this->escape_string($this->strip_all($data['variant'])));
			$sub_variant = trim($this->escape_string($this->strip_all($data['sub_variant'])));
			$category = trim($this->escape_string($this->strip_all($data['category'])));
			$no_del = trim($this->escape_string($this->strip_all($data['no_del'])));
			$no_did = trim($this->escape_string($this->strip_all($data['no_did'])));
			$no_channel = trim($this->escape_string($this->strip_all($data['no_channel'])));
			$no_drop_locations = trim($this->escape_string($this->strip_all($data['no_drop_locations'])));
			$mobile_no = trim($this->escape_string($this->strip_all($data['mobile_no'])));
			$del_no = trim($this->escape_string($this->strip_all($data['del_no'])));
			$pilot_no = trim($this->escape_string($this->strip_all($data['pilot_no'])));
			$imsi_no = trim($this->escape_string($this->strip_all($data['imsi_no'])));
			$did_range = trim($this->escape_string($this->strip_all($data['did_range'])));
			$did_range_to = trim($this->escape_string($this->strip_all($data['did_range_to'])));
			//dhanashree*/
			if(!empty($product) && $product == "Internet Leased Line"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['ill_text_bandwidth'])));
			}else if(!empty($product) && $product == "Smart VPN"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['mpls_text_bandwidth'])));
			}else if(!empty($product) && $product == "SmartOffice"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['sill_text_bandwidth'])));
			}else if(!empty($product) && $product == "Enterprise Broadband"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['broadband_bandwidth'])));
			}else{
				$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			}
			$arc = trim($this->escape_string($this->strip_all($data['arc'])));
			$arc_type = trim($this->escape_string($this->strip_all($data['arc_type'])));
			$monthly_rental = trim($this->escape_string($this->strip_all($data['monthly_rental'])));
			$nrc = trim($this->escape_string($this->strip_all($data['nrc'])));
			/*DHANASHREE*/
			if($data['bill_plan_opted']){
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));
			}else{
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted_select'])));	
			}
			$lockin_period = trim($this->escape_string($this->strip_all($data['lockin_period'])));
			$security_deposit = trim($this->escape_string($this->strip_all($data['security_deposit'])));
			$activation_fee = trim($this->escape_string($this->strip_all($data['activation_fee'])));
			$traif_available = trim($this->escape_string($this->strip_all($data['traif_available'])));
			if($traif_available == 'Manual Input'){
				$trai_id = trim($this->escape_string($this->strip_all($data['trai_id'])));
			}else if($traif_available == 'NA'){
				$trai_id = 'NA';
			}else{
				$trai_id = 'Not Registered';
			}
			
			//done by dhanashree
			$rack_type = trim($this->escape_string($this->strip_all($data['rack_type'])));
			$sales_type = trim($this->escape_string($this->strip_all($data['sales_type'])));			
			$billing_frequency = trim($this->escape_string($this->strip_all($data['billing_frequency'])));
			//Done By Dhanashree
			//$billing_type = trim($this->escape_string($this->strip_all($data['billing_type'])));
			if($data['billing_type']){
				$billing_type = trim($this->escape_string($this->strip_all($data['billing_type'])));
			}else{
				$billing_type = "Arrears";
			}
			$bill_mode = trim($this->escape_string($this->strip_all($data['bill_mode'])));
			$cug_id = trim($this->escape_string($this->strip_all($data['cug_id'])));

			if(!empty($file['drop_location_sheet']['name'])) {
				/* $file_name = strtolower( pathinfo($file['drop_location_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				move_uploaded_file($file['drop_location_sheet']['tmp_name'],$uploadDir.$drop_location_sheet); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$drop_location_sheet, fopen($file['drop_location_sheet']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['del_sheet']['name'])) {
				/* $file_name = strtolower( pathinfo($file['del_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['del_sheet']['name'], PATHINFO_EXTENSION));
				$del_sheet = time().'2.'.$ext;
				move_uploaded_file($file['del_sheet']['tmp_name'],$uploadDir.$del_sheet); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['del_sheet']['name'], PATHINFO_EXTENSION));
				$del_sheet = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$del_sheet, fopen($file['del_sheet']['tmp_name'], 'rb'), 'public-read');
			}

			$query = "insert into ".PREFIX."caf_product_details(caf_id, product, variant, sub_variant, category, no_del, del_sheet, no_did, no_channel, no_drop_locations, drop_location_sheet, mobile_no, del_no, pilot_no, imsi_no, did_range, did_range_to, bandwidth, arc, arc_type, monthly_rental, nrc, bill_plan_opted, lockin_period, security_deposit, activation_fee,trai_available, trai_id, billing_frequency, billing_type, bill_mode, cug_id, rack_type, sales_type) values ('".$caf_id."', '".$product."', '".$variant."', '".$sub_variant."', '".$category."', '".$no_del."', '".$del_sheet."', '".$no_did."', '".$no_channel."', '".$no_drop_locations."', '".$drop_location_sheet."', '".$mobile_no."', '".$del_no."', '".$pilot_no."', '".$imsi_no."', '".$did_range."', '".$did_range_to."', '".$bandwidth."', '".$arc."', '".$arc_type."', '".$monthly_rental."', '".$nrc."', '".$bill_plan_opted."', '".$lockin_period."', '".$security_deposit."', '".$activation_fee."', '".$traif_available."', '".$trai_id."', '".$billing_frequency."', '".$billing_type."', '".$bill_mode."', '".$cug_id."', '".$rack_type."', '".$sales_type."')";
			$this->query($query);
			// PRODUCT DETAILS

			// DOCUMENT DETAILS
			$registration_document_type = trim($this->escape_string($this->strip_all($data['registration_document_type'])));
			$registration_document_type_other = trim($this->escape_string($this->strip_all($data['registration_document_type_other'])));
			$registration_document_no = trim($this->escape_string($this->strip_all($data['registration_document_no'])));
			$registration_place_issue = trim($this->escape_string($this->strip_all($data['registration_place_issue'])));
			$registration_issuing_authority = trim($this->escape_string($this->strip_all($data['registration_issuing_authority'])));
			$registration_issuing_date = trim($this->escape_string($this->strip_all($data['registration_issuing_date'])));
			$registration_expiry_date = trim($this->escape_string($this->strip_all($data['registration_expiry_date'])));
			$reg_caf_number = trim($this->escape_string($this->strip_all($data['reg-caf-number'])));
			
			$address_document_type = trim($this->escape_string($this->strip_all($data['address_document_type'])));
			$address_document_type_other = trim($this->escape_string($this->strip_all($data['address_document_type_other'])));
			$address_document_no = trim($this->escape_string($this->strip_all($data['address_document_no'])));
			$address_place_issue = trim($this->escape_string($this->strip_all($data['address_place_issue'])));
			$address_issuing_authority = trim($this->escape_string($this->strip_all($data['address_issuing_authority'])));
			$address_issuing_date = trim($this->escape_string($this->strip_all($data['address_issuing_date'])));
			$address_expiry_date = trim($this->escape_string($this->strip_all($data['address_expiry_date'])));
			$add_caf_number = trim($this->escape_string($this->strip_all($data['add-caf-number'])));
			
			$identity_document_type = trim($this->escape_string($this->strip_all($data['identity_document_type'])));
			$identity_document_type_other = trim($this->escape_string($this->strip_all($data['identity_document_type_other'])));
			$identity_document_no = trim($this->escape_string($this->strip_all($data['identity_document_no'])));
			$identity_place_issue = trim($this->escape_string($this->strip_all($data['identity_place_issue'])));
			$identity_issuing_authority = trim($this->escape_string($this->strip_all($data['identity_issuing_authority'])));
			$identity_issuing_date = trim($this->escape_string($this->strip_all($data['identity_issuing_date'])));
			$identity_expiry_date = trim($this->escape_string($this->strip_all($data['identity_expiry_date'])));
			$iden_caf_number = trim($this->escape_string($this->strip_all($data['iden-caf-number'])));
			
			$authorisation_document_type = trim($this->escape_string($this->strip_all($data['authorisation_document_type'])));
			$authorisation_document_type_other = trim($this->escape_string($this->strip_all($data['authorisation_document_type_other'])));
			$authorisation_document_no = trim($this->escape_string($this->strip_all($data['authorisation_document_no'])));
			$authorisation_place_issue = trim($this->escape_string($this->strip_all($data['authorisation_place_issue'])));
			$authorisation_issuing_authority = trim($this->escape_string($this->strip_all($data['authorisation_issuing_authority'])));
			$authorisation_issuing_date = trim($this->escape_string($this->strip_all($data['authorisation_issuing_date'])));
			$authorisation_expiry_date = trim($this->escape_string($this->strip_all($data['authorisation_expiry_date'])));
			$auth_caf_number = trim($this->escape_string($this->strip_all($data['auth-caf-number'])));
			
			/* new for installation dhgnashree */
			$installation_document_type = trim($this->escape_string($this->strip_all($data['installation_document_type'])));
			$installation_document_type_other = trim($this->escape_string($this->strip_all($data['installation_document_type_other'])));
			$installation_document_no = trim($this->escape_string($this->strip_all($data['installation_document_no'])));
			$installation_place_issue = trim($this->escape_string($this->strip_all($data['installation_place_issue'])));
			$installation_issuing_authority = trim($this->escape_string($this->strip_all($data['installation_issuing_authority'])));
			$installation_issuing_date = trim($this->escape_string($this->strip_all($data['installation_issuing_date'])));
			$installation_expiry_date = trim($this->escape_string($this->strip_all($data['installation_expiry_date'])));
			$install_caf_number = trim($this->escape_string($this->strip_all($data['install-caf-number'])));

			/* if(!empty($file['registration_document']['name'])) {
				$file_name = strtolower( pathinfo($file['registration_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['registration_document']['name'], PATHINFO_EXTENSION));
				$registration_document = time().'21.'.$ext;
				move_uploaded_file($file['registration_document']['tmp_name'],$uploadDir.$registration_document);
			}

			if(!empty($file['address_document']['name'])) {
				$file_name = strtolower( pathinfo($file['address_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['address_document']['name'], PATHINFO_EXTENSION));
				$address_document = time().'22.'.$ext;
				move_uploaded_file($file['address_document']['tmp_name'],$uploadDir.$address_document);
			}

			if(!empty($file['identity_document']['name'])) {
				$file_name = strtolower( pathinfo($file['identity_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['identity_document']['name'], PATHINFO_EXTENSION));
				$identity_document = time().'23.'.$ext;
				move_uploaded_file($file['identity_document']['tmp_name'],$uploadDir.$identity_document);
			}

			if(!empty($file['authorisation_document']['name'])) {
				$file_name = strtolower( pathinfo($file['authorisation_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['authorisation_document']['name'], PATHINFO_EXTENSION));
				$authorisation_document = time().'24.'.$ext;
				move_uploaded_file($file['authorisation_document']['tmp_name'],$uploadDir.$authorisation_document);
			} */

			if(!empty($file['tm_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tm_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				move_uploaded_file($file['tm_download']['tmp_name'],$uploadDir.$tm_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tm_download, fopen($file['tm_download']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['trai_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['trai_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				move_uploaded_file($file['trai_form']['tmp_name'],$uploadDir.$trai_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$trai_form, fopen($file['trai_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['dd_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['dd_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				move_uploaded_file($file['dd_form']['tmp_name'],$uploadDir.$dd_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$dd_form, fopen($file['dd_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['osp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['osp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				move_uploaded_file($file['osp_form']['tmp_name'],$uploadDir.$osp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$osp_form, fopen($file['osp_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['sez_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['sez_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				move_uploaded_file($file['sez_form']['tmp_name'],$uploadDir.$sez_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$sez_form, fopen($file['sez_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['bulk_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['bulk_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				move_uploaded_file($file['bulk_form']['tmp_name'],$uploadDir.$bulk_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$bulk_form, fopen($file['bulk_form']['tmp_name'], 'rb'), 'public-read');
			}
			// done by dhanashree*/
			if(!empty($file['billing_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['billing_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				move_uploaded_file($file['billing_form']['tmp_name'],$uploadDir.$billing_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$billing_form, fopen($file['billing_form']['tmp_name'], 'rb'), 'public-read');
			}
			
			if(!empty($file['gst_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['gst_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				move_uploaded_file($file['gst_form']['tmp_name'],$uploadDir.$gst_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$gst_form, fopen($file['gst_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['all_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['all_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				move_uploaded_file($file['all_form']['tmp_name'],$uploadDir.$all_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$all_form, fopen($file['all_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['logical_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['logical_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				move_uploaded_file($file['logical_form']['tmp_name'],$uploadDir.$logical_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$logical_form, fopen($file['logical_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['stc_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['stc_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				move_uploaded_file($file['stc_form']['tmp_name'],$uploadDir.$stc_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$stc_form, fopen($file['stc_form']['tmp_name'], 'rb'), 'public-read');
			}
			
			if(!empty($file['tef_download']['name'])) {
				//echo $file['tef_download']['name'];
				/* $file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download']['tmp_name'], 'rb'), 'public-read');
				
			}
			if(!empty($file['tef_download1']['name'])) {
				//echo $file['tef_download1']['name'];
				/* $file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download1']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download1']['tmp_name'], 'rb'), 'public-read');
				
			}

			
			$query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, registration_document_no, registration_place_issue, registration_issuing_authority, registration_issuing_date, registration_expiry_date, registration_document,reg_caf_number, address_document_type, address_document_type_other, address_document_no, address_place_issue, address_issuing_authority, address_issuing_date, address_expiry_date, address_document,add_caf_number, identity_document_type, identity_document_type_other, identity_document_no, identity_place_issue, identity_issuing_authority, identity_issuing_date, identity_expiry_date, identity_document,identity_caf_number, authorisation_document_type, authorisation_document_type_other, authorisation_document_no, authorisation_place_issue, authorisation_issuing_authority, authorisation_issuing_date, authorisation_expiry_date, auth_caf_number, installation_document_type, installation_document_type_other, installation_document_no, installation_issuing_authority, installation_issuing_date, installation_expiry_date, install_caf_number, authorisation_document, tef_download, tm_download, trai_form, dd_form, osp_form, sez_form, bulk_form,billing_form,gst_form, all_form, logical_form, stc_form) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$registration_document_no."', '".$registration_place_issue."', '".$registration_issuing_authority."', '".$registration_issuing_date."', '".$registration_expiry_date."', '".$registration_document."', '".$reg_caf_number."', '".$address_document_type."', '".$address_document_type_other."', '".$address_document_no."', '".$address_place_issue."', '".$address_issuing_authority."', '".$address_issuing_date."', '".$address_expiry_date."', '".$address_document."', '".$add_caf_number."', '".$identity_document_type."', '".$identity_document_type_other."', '".$identity_document_no."', '".$identity_place_issue."', '".$identity_issuing_authority."', '".$identity_issuing_date."', '".$identity_expiry_date."', '".$identity_document."', '".$iden_caf_number."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$authorisation_document_no."', '".$authorisation_place_issue."', '".$authorisation_issuing_authority."', '".$authorisation_issuing_date."', '".$authorisation_expiry_date."', '".$auth_caf_number."','".$installation_document_type."', '".$installation_document_type_other."', '".$installation_document_no."', '".$installation_issuing_authority."', '".$installation_issuing_date."', '".$installation_expiry_date."', '".$install_caf_number."', '".$authorisation_document."', '".$tef_download."', '".$tm_download."', '".$trai_form."', '".$dd_form."', '".$osp_form."', '".$sez_form."', '".$bulk_form."', '".$billing_form."', '".$gst_form."', '".$all_form."', '".$logical_form."', '".$stc_form."')";
			$this->query($query);

			$c=30;
			foreach($file['registration_document']['name'] as $key=>$value) {
				if(!empty($file['registration_document']['name'][$key])) {
					$registration_document = '';
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'][$key], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_registration_document(caf_id, document) values ('$caf_id', '$registration_document')");
				}
			}
			foreach($file['address_document']['name'] as $key=>$value) {
				if(!empty($file['address_document']['name'][$key])) {
					$address_document = '';
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'][$key], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_address_document(caf_id, document) values ('$caf_id', '$address_document')");
				}
			}
			foreach($file['identity_document']['name'] as $key=>$value) {
				if(!empty($file['identity_document']['name'][$key])) {
					$identity_document = '';
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'][$key], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_identity_document(caf_id, document) values ('$caf_id', '$identity_document')");
				}
			}
			foreach($file['authorisation_document']['name'] as $key=>$value) {
				if(!empty($file['authorisation_document']['name'][$key])) {
					$authorisation_document = '';
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'][$key], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_authorisation_document(caf_id, document) values ('$caf_id', '$authorisation_document')");
				}
			}
			
			//new by dhanashree*/
			foreach($file['installation_document']['name'] as $key=>$value) {
				if(!empty($file['installation_document']['name'][$key])) {
					$installation_document = '';
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'][$key], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_installation_document(caf_id, document) values ('$caf_id', '$installation_document')");
				}
			}
			
			foreach($file['other_form']['name'] as $key=>$value) {
				if(!empty($file['other_form']['name'][$key])) {
					$other_form = '';
					if(!empty($file['other_form']['name'][$key])) {
						$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'][$key], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_other_document(caf_id, document) values ('$caf_id', '$other_form')");
				}
			}
			
			// DOCUMENT DETAILS

			// OTHER DETAILS
			$mobile_connection1 = trim($this->escape_string($this->strip_all($data['mobile_connection1'])));
			$mobile_connection2 = trim($this->escape_string($this->strip_all($data['mobile_connection2'])));
			$mobile_connection3 = trim($this->escape_string($this->strip_all($data['mobile_connection3'])));
			$mobile_connection4 = trim($this->escape_string($this->strip_all($data['mobile_connection4'])));
			$no_connection1 = trim($this->escape_string($this->strip_all($data['no_connection1'])));
			$no_connection2 = trim($this->escape_string($this->strip_all($data['no_connection2'])));
			$no_connection3 = trim($this->escape_string($this->strip_all($data['no_connection3'])));
			$no_connection4 = trim($this->escape_string($this->strip_all($data['no_connection4'])));
			$mobile_connection_total = trim($this->escape_string($this->strip_all($data['mobile_connection_total'])));
			$is_mnp = trim($this->escape_string($this->strip_all($data['is_mnp'])));
			$upc_code = trim($this->escape_string($this->strip_all($data['upc_code'])));
			$upc_code_date = trim($this->escape_string($this->strip_all($data['upc_code_date'])));
			$existing_operator = trim($this->escape_string($this->strip_all($data['existing_operator'])));
			$porting_imsi_no = trim($this->escape_string($this->strip_all($data['porting_imsi_no'])));
			$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'])));
			/* $payment_type = trim($this->escape_string($this->strip_all($data['payment_type']))); */
			// $payment_type = implode(',', $data['payment_type']);
			// if($payment_mode != 'Cash') {
			foreach($data['bank_name'] as $key=>$value) {
				if(!empty($data['payment_type'][$key])) {
					$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'][$key])));
					$payment_type = trim($this->escape_string($this->strip_all($data['payment_type'][$key])));
					$bank_name = trim($this->escape_string($this->strip_all($data['bank_name'][$key])));
					$bank_acc_no = trim($this->escape_string($this->strip_all($data['bank_acc_no'][$key])));
					$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
					$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
					$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
					$this->query("insert into ".PREFIX."caf_payment_details(caf_id, payment_mode, payment_type, bank_name, bank_acc_no, branch_address, transactional_details, transaction_amount) values ('".$caf_id."', '".$payment_mode."', '".$payment_type."', '".$bank_name."', '".$bank_acc_no."', '".$branch_address."', '".$transactional_details."', '".$transaction_amount."')");
				}
			}
			// }
			$grand_amount = trim($this->escape_string($this->strip_all($data['grand_amount'])));

			if($is_mnp=='Yes' and !empty($file['mnp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['mnp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				move_uploaded_file($file['mnp_form']['tmp_name'],$uploadDir.$mnp_form); */
										
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$mnp_form, fopen($file['mnp_form']['tmp_name'], 'rb'), 'public-read');
			}

			$query = "insert into ".PREFIX."caf_other_details(caf_id, mobile_connection1, mobile_connection2, mobile_connection3, mobile_connection4, no_connection1, no_connection2, no_connection3, no_connection4, mobile_connection_total, is_mnp, upc_code, upc_code_date, existing_operator, porting_imsi_no, mnp_form, grand_amount) values('".$caf_id."', '".$mobile_connection1."', '".$mobile_connection2."', '".$mobile_connection3."', '".$mobile_connection4."', '".$no_connection1."', '".$no_connection2."', '".$no_connection3."', '".$no_connection4."', '".$mobile_connection_total."', '".$is_mnp."', '".$upc_code."', '".$upc_code_date."', '".$existing_operator."', '".$porting_imsi_no."', '".$mnp_form."', '".$grand_amount."')";
			$this->query($query);
			// OTHER DETAILS

			// Service Enrollment
			$tar_id = trim($this->escape_string($this->strip_all($data['tar_id'])));
			$market_segment = trim($this->escape_string($this->strip_all($data['market_segment'])));
			
			$dealer_code = trim($this->escape_string($this->strip_all($data['dealer_code'])));
			
			$brm_code = trim($this->escape_string($this->strip_all($data['brm_code'])));
			$po_upload_date = date("Y-m-d H:i:s");
			
			if(!empty($file['po_upload']['name'])) {
				/* $file_name = strtolower( pathinfo($file['po_upload']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				move_uploaded_file($file['po_upload']['tmp_name'],$uploadDir.$po_upload); */
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$po_upload, fopen($file['po_upload']['tmp_name'], 'rb'), 'public-read');
				//print_r($upload);exit;
				//echo "Uploaded";exit;
			}
			
			
			
			if(!empty($file['other_approval_docs']['name'])) {
				/* $file_name = strtolower( pathinfo($file['other_approval_docs']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['other_approval_docs']['name'], PATHINFO_EXTENSION));
				$other_approval_docs = time().'4.'.$ext;
				move_uploaded_file($file['other_approval_docs']['tmp_name'],$uploadDir.$other_approval_docs); */
										
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['other_approval_docs']['name'], PATHINFO_EXTENSION));
				$other_approval_docs = time().'4.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$other_approval_docs, fopen($file['other_approval_docs']['tmp_name'], 'rb'), 'public-read');
			}
			
			$this->query("update ".PREFIX."caf_details set tar_id='".$tar_id."', market_segment='".$market_segment."', dealer_code='".$dealer_code."', brm_code='".$brm_code."', po_upload='".$po_upload."', po_upload_date='".$po_upload_date."', other_approval_docs='".$other_approval_docs."' where id='".$caf_id."'");
			foreach($data['caf_no'] as $key=>$value) {
				if(!empty($data['caf_no'][$key])) {
					$caf_no = trim($this->escape_string($this->strip_all($data['caf_no'][$key])));
					$ref_doc = trim($this->escape_string($this->strip_all($data['ref_doc'][$key])));
					$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
					$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
					$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
					$this->query("insert into ".PREFIX."caf_existing_details(caf_id, caf_no, ref_doc) values ('".$caf_id."', '".$caf_no."', '".$ref_doc."')");
				}
			}
			
			if($product=='SmartOffice') {
				if($variant == 'Internet Leased Line'){
					$ill_connection_type = trim($this->escape_string($this->strip_all($data['sm_ill_connection_type'])));
					$ill_del_no = trim($this->escape_string($this->strip_all($data['sm_ill_del_no'])));
					$ill_billing_cycle = trim($this->escape_string($this->strip_all($data['sm_ill_billing_cycle'])));
					$ill_exit_policy = trim($this->escape_string($this->strip_all($data['sm_ill_exit_policy'])));
					$ill_pm_email = trim($this->escape_string($this->strip_all($data['sm_ill_pm_email'])));
					$ill_super_account = trim($this->escape_string($this->strip_all($data['sm_ill_super_account'])));
					$ill_addon_account = trim($this->escape_string($this->strip_all($data['sm_ill_addon_account'])));
					$ill_circuit_id = trim($this->escape_string($this->strip_all($data['sm_ill_circuit_id'])));
					$ill_fan_no = trim($this->escape_string($this->strip_all($data['sm_ill_fan_no'])));
					$ill_srf_no = trim($this->escape_string($this->strip_all($data['sm_ill_srf_no'])));
					$ill_bandwidth = trim($this->escape_string($this->strip_all($data['sm_ill_bandwidth'])));
					$ill_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['sm_ill_bandwidth_ratio'])));

					$this->query("insert into ".PREFIX."caf_ill_details(caf_id, ill_connection_type, ill_del_no, ill_billing_cycle, ill_exit_policy, ill_pm_email, ill_super_account, ill_addon_account, ill_circuit_id, ill_fan_no, ill_srf_no, ill_bandwidth, ill_bandwidth_ratio) values('".$caf_id."', '".$ill_connection_type."', '".$ill_del_no."', '".$ill_billing_cycle."', '".$ill_exit_policy."', '".$ill_pm_email."', '".$ill_super_account."', '".$ill_addon_account."', '".$ill_circuit_id."', '".$ill_fan_no."', '".$ill_srf_no."', '".$ill_bandwidth."', '".$ill_bandwidth_ratio."')");
				
				}else if($variant == 'SIP Trunk'){
					
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sm_sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sm_sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sm_sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sm_sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sm_sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sm_sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sm_sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sm_sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sm_sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sm_sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sm_sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sm_sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sm_sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sm_sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sm_sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sm_sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sm_sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sm_sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sm_sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sm_sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sm_sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sm_sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sm_sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sm_sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sm_sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sm_sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sm_sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sm_sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sm_sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sm_sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sm_sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				
				}else if($variant=='Audio Conferencing') {
					$audio_conf_pgi_landline_no = trim($this->escape_string($this->strip_all($data['sm_audio_conf_pgi_landline_no'])));
					$sm_audio_conf_del_number = trim($this->escape_string($this->strip_all($data['sm_audio_conf_del_number'])));
					$this->query("insert into ".PREFIX."caf_audio_conf_details(caf_id, audio_conf_pgi_landline_no, audio_conf_del_no) values('".$caf_id."', '".$audio_conf_pgi_landline_no."', '".$sm_audio_conf_del_number."')");
				}
			}
			if($product=='Internet Leased Line') {
				$ill_connection_type = trim($this->escape_string($this->strip_all($data['ill_connection_type'])));
				$ill_del_no = trim($this->escape_string($this->strip_all($data['ill_del_no'])));
				$ill_billing_cycle = trim($this->escape_string($this->strip_all($data['ill_billing_cycle'])));
				$ill_exit_policy = trim($this->escape_string($this->strip_all($data['ill_exit_policy'])));
				$ill_pm_email = trim($this->escape_string($this->strip_all($data['ill_pm_email'])));
				$ill_super_account = trim($this->escape_string($this->strip_all($data['ill_super_account'])));
				$ill_addon_account = trim($this->escape_string($this->strip_all($data['ill_addon_account'])));
				$ill_circuit_id = trim($this->escape_string($this->strip_all($data['ill_circuit_id'])));
				$ill_fan_no = trim($this->escape_string($this->strip_all($data['ill_fan_no'])));
				$ill_srf_no = trim($this->escape_string($this->strip_all($data['ill_srf_no'])));
				$ill_bandwidth = trim($this->escape_string($this->strip_all($data['ill_bandwidth'])));
				$ill_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['ill_bandwidth_ratio'])));

				$this->query("insert into ".PREFIX."caf_ill_details(caf_id, ill_connection_type, ill_del_no, ill_billing_cycle, ill_exit_policy, ill_pm_email, ill_super_account, ill_addon_account, ill_circuit_id, ill_fan_no, ill_srf_no, ill_bandwidth, ill_bandwidth_ratio) values('".$caf_id."', '".$ill_connection_type."', '".$ill_del_no."', '".$ill_billing_cycle."', '".$ill_exit_policy."', '".$ill_pm_email."', '".$ill_super_account."', '".$ill_addon_account."', '".$ill_circuit_id."', '".$ill_fan_no."', '".$ill_srf_no."', '".$ill_bandwidth."', '".$ill_bandwidth_ratio."')");
			}
			else if($product=='Smart VPN') {
				if($variant=='MPLS Standard' || $variant=='MPLS Managed') {
					$mpls_connection_type = trim($this->escape_string($this->strip_all($data['mpls_connection_type'])));
					$mpls_del_no = trim($this->escape_string($this->strip_all($data['mpls_del_no'])));
					$mpls_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_billing_cycle'])));
					$mpls_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_exit_policy'])));
					$mpls_pm_email = trim($this->escape_string($this->strip_all($data['mpls_pm_email'])));
					$mpls_super_account = trim($this->escape_string($this->strip_all($data['mpls_super_account'])));
					$mpls_addon_account = trim($this->escape_string($this->strip_all($data['mpls_addon_account'])));
					$mpls_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_circuit_id'])));
					$mpls_fan_no = trim($this->escape_string($this->strip_all($data['mpls_fan_no'])));
					$mpls_srf_no = trim($this->escape_string($this->strip_all($data['mpls_srf_no'])));
					$mpls_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_bandwidth'])));
					$mpls_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['mpls_bandwidth_ratio'])));

					$this->query("insert into ".PREFIX."caf_mpls_details(caf_id, mpls_connection_type, mpls_del_no, mpls_billing_cycle, mpls_exit_policy, mpls_pm_email, mpls_super_account, mpls_addon_account, mpls_circuit_id, mpls_fan_no, mpls_srf_no, mpls_bandwidth, mpls_bandwidth_ratio) values('".$caf_id."', '".$mpls_connection_type."', '".$mpls_del_no."', '".$mpls_billing_cycle."', '".$mpls_exit_policy."', '".$mpls_pm_email."', '".$mpls_super_account."', '".$mpls_addon_account."', '".$mpls_circuit_id."', '".$mpls_fan_no."', '".$mpls_srf_no."', '".$mpls_bandwidth."', '".$mpls_bandwidth_ratio."')");
				}
				else if($variant=='Xpress VPN') {
					$mpls_express_connection_type = trim($this->escape_string($this->strip_all($data['mpls_express_connection_type'])));
					$mpls_express_del_no = trim($this->escape_string($this->strip_all($data['mpls_express_del_no'])));
					$mpls_express_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_express_exit_policy'])));
					$mpls_express_pm_email = trim($this->escape_string($this->strip_all($data['mpls_express_pm_email'])));
					$mpls_express_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_express_billing_cycle'])));
					$mpls_express_parent_account = trim($this->escape_string($this->strip_all($data['mpls_express_parent_account'])));
					$mpls_express_addon_account = trim($this->escape_string($this->strip_all($data['mpls_express_addon_account'])));
					$mpls_express_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_express_circuit_id'])));
					$mpls_express_client_del_creation = trim($this->escape_string($this->strip_all($data['mpls_express_client_del_creation'])));
					$mpls_express_apn_name = trim($this->escape_string($this->strip_all($data['mpls_express_apn_name'])));
					$mpls_express_dummy_del = trim($this->escape_string($this->strip_all($data['mpls_express_dummy_del'])));
					$mpls_express_user_id = trim($this->escape_string($this->strip_all($data['mpls_express_user_id'])));
					$mpls_express_password = trim($this->escape_string($this->strip_all($data['mpls_express_password'])));
					$mpls_express_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_express_bandwidth'])));
					$mpls_express_internet_blocking = trim($this->escape_string($this->strip_all($data['mpls_express_internet_blocking'])));
					$mpls_express_client_id_charges = trim($this->escape_string($this->strip_all($data['mpls_express_client_id_charges'])));
					$mpls_express_customer_apn = trim($this->escape_string($this->strip_all($data['mpls_express_customer_apn'])));
					$mpls_express_reserved_id = trim($this->escape_string($this->strip_all($data['mpls_express_reserved_id'])));
					$mpls_express_empower_id = trim($this->escape_string($this->strip_all($data['mpls_express_empower_id'])));
					$mpls_express_handset_id = trim($this->escape_string($this->strip_all($data['mpls_express_handset_id'])));
					$mpls_express_network_email = trim($this->escape_string($this->strip_all($data['mpls_express_network_email'])));

					if(!empty($file['mpls_express_apn_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_express_apn_excel = time().'5.'.$ext;
						move_uploaded_file($file['mpls_express_apn_excel']['tmp_name'],$uploadDir.$mpls_express_apn_excel); */
																		
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_express_apn_excel = time().'5.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_express_apn_excel, fopen($file['mpls_express_apn_excel']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_mpls_express_details(caf_id, mpls_express_connection_type, mpls_express_del_no, mpls_express_exit_policy, mpls_express_pm_email, mpls_express_billing_cycle, mpls_express_parent_account, mpls_express_addon_account, mpls_express_apn_name, mpls_express_user_id, mpls_express_password, mpls_express_bandwidth, mpls_express_internet_blocking, mpls_express_client_id_charges, mpls_express_customer_apn, mpls_express_apn_excel, mpls_express_reserved_id, mpls_express_empower_id, mpls_express_handset_id, mpls_express_network_email) values('".$caf_id."', '".$mpls_express_connection_type."', '".$mpls_express_del_no."', '".$mpls_express_exit_policy."', '".$mpls_express_pm_email."', '".$mpls_express_billing_cycle."', '".$mpls_express_parent_account."', '".$mpls_express_addon_account."', '".$mpls_express_apn_name."', '".$mpls_express_user_id."', '".$mpls_express_password."', '".$mpls_express_bandwidth."', '".$mpls_express_internet_blocking."', '".$mpls_express_client_id_charges."', '".$mpls_express_customer_apn."', '".$mpls_express_apn_excel."', '".$mpls_express_reserved_id."', '".$mpls_express_empower_id."', '".$mpls_express_handset_id."', '".$mpls_express_network_email."')");
				}
				else if($variant=='Road warrior') {
					$mpls_rw_connection_type = trim($this->escape_string($this->strip_all($data['mpls_rw_connection_type'])));
					$mpls_rw_del_no = trim($this->escape_string($this->strip_all($data['mpls_rw_del_no'])));
					$mpls_rw_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_rw_billing_cycle'])));
					$mpls_rw_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_rw_exit_policy'])));
					$mpls_rw_pw_email = trim($this->escape_string($this->strip_all($data['mpls_rw_pw_email'])));
					$mpls_rw_parent_account = trim($this->escape_string($this->strip_all($data['mpls_rw_parent_account'])));
					$mpls_rw_addon_account = trim($this->escape_string($this->strip_all($data['mpls_rw_addon_account'])));
					$mpls_rw_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_rw_circuit_id'])));
					$mpls_rw_apn_name = trim($this->escape_string($this->strip_all($data['mpls_rw_apn_name'])));
					$mpls_rw_del_creation = trim($this->escape_string($this->strip_all($data['mpls_rw_del_creation'])));
					$mpls_rw_dummy_del = trim($this->escape_string($this->strip_all($data['mpls_rw_dummy_del'])));
					$mpls_rw_user_id = trim($this->escape_string($this->strip_all($data['mpls_rw_user_id'])));
					$mpls_rw_password = trim($this->escape_string($this->strip_all($data['mpls_rw_password'])));
					$mpls_rw_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_rw_bandwidth'])));
					$mpls_rw_internet_blocking = trim($this->escape_string($this->strip_all($data['mpls_rw_internet_blocking'])));
					$mpls_rw_client_id_charges = trim($this->escape_string($this->strip_all($data['mpls_rw_client_id_charges'])));
					$mpls_rw_customer_apn = trim($this->escape_string($this->strip_all($data['mpls_rw_customer_apn'])));
					$mpls_rw_calling_level = trim($this->escape_string($this->strip_all($data['mpls_rw_calling_level'])));

					if(!empty($file['mpls_rw_apn_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_rw_apn_excel = time().'5.'.$ext;
						move_uploaded_file($file['mpls_rw_apn_excel']['tmp_name'],$uploadDir.$mpls_rw_apn_excel); */
																		
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_rw_apn_excel = time().'5.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_rw_apn_excel, fopen($file['mpls_rw_apn_excel']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_mpls_rw_details(caf_id, mpls_rw_connection_type, mpls_rw_del_no, mpls_rw_exit_policy, mpls_rw_pw_email, mpls_rw_billing_cycle, mpls_rw_parent_account, mpls_rw_addon_account, mpls_rw_circuit_id, mpls_rw_apn_name, mpls_rw_del_creation, mpls_rw_dummy_del,  mpls_rw_user_id, mpls_rw_password, mpls_rw_bandwidth, mpls_rw_internet_blocking, mpls_rw_client_id_charges, mpls_rw_customer_apn, mpls_rw_apn_excel, mpls_rw_calling_level) values('".$caf_id."', '".$mpls_rw_connection_type."', '".$mpls_rw_del_no."', '".$mpls_rw_exit_policy."', '".$mpls_rw_pw_email."', '".$mpls_rw_billing_cycle."', '".$mpls_rw_parent_account."', '".$mpls_rw_addon_account."', '".$mpls_rw_circuit_id."', '".$mpls_rw_apn_name."', '".$mpls_rw_del_creation."', '".$mpls_rw_dummy_del."', '".$mpls_rw_user_id."', '".$mpls_rw_password."', '".$mpls_rw_bandwidth."', '".$mpls_rw_internet_blocking."', '".$mpls_rw_client_id_charges."', '".$mpls_rw_customer_apn."', '".$mpls_rw_apn_excel."', '".$mpls_rw_calling_level."')");
				}
			}
			else if($product=='Leased Line') {
				if($variant=='Standard' || $variant=='Premium') {
					if($sub_variant=='DLC') {
						$dlc_connection_type = trim($this->escape_string($this->strip_all($data['dlc_connection_type'])));
						$dlc_del_no = trim($this->escape_string($this->strip_all($data['dlc_del_no'])));
						$del_billing_cycle = trim($this->escape_string($this->strip_all($data['del_billing_cycle'])));
						$del_exit_policy = trim($this->escape_string($this->strip_all($data['del_exit_policy'])));
						$dlc_pm_email = trim($this->escape_string($this->strip_all($data['dlc_pm_email'])));
						$dlc_parent_account = trim($this->escape_string($this->strip_all($data['dlc_parent_account'])));
						$dlc_addon_account = trim($this->escape_string($this->strip_all($data['dlc_addon_account'])));
						$dlc_circuit_id = trim($this->escape_string($this->strip_all($data['dlc_circuit_id'])));
						$dlc_fan_no = trim($this->escape_string($this->strip_all($data['dlc_fan_no'])));
						$dlc_srf_no = trim($this->escape_string($this->strip_all($data['dlc_srf_no'])));
						$dlc_bandwidth = trim($this->escape_string($this->strip_all($data['dlc_bandwidth'])));
						$dlc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['dlc_bandwidth_ratio'])));

						$this->query("insert into ".PREFIX."caf_dlc_details(caf_id, dlc_connection_type, dlc_del_no, del_billing_cycle, del_exit_policy, dlc_pm_email, dlc_parent_account, dlc_addon_account, dlc_circuit_id, dlc_fan_no, dlc_srf_no, dlc_bandwidth, dlc_bandwidth_ratio) values('".$caf_id."', '".$dlc_connection_type."', '".$dlc_del_no."', '".$del_billing_cycle."', '".$del_exit_policy."', '".$dlc_pm_email."', '".$dlc_parent_account."', '".$dlc_addon_account."', '".$dlc_circuit_id."', '".$dlc_fan_no."', '".$dlc_srf_no."', '".$dlc_bandwidth."', '".$dlc_bandwidth_ratio."')");
					}
					if($sub_variant=='NPLC') {
						$nplc_connection_type = trim($this->escape_string($this->strip_all($data['nplc_connection_type'])));
						$nplc_del_no = trim($this->escape_string($this->strip_all($data['nplc_del_no'])));
						$nplc_billing_cycle = trim($this->escape_string($this->strip_all($data['nplc_billing_cycle'])));
						$nplc_exit_policy = trim($this->escape_string($this->strip_all($data['nplc_exit_policy'])));
						$nplc_pm_email = trim($this->escape_string($this->strip_all($data['nplc_pm_email'])));
						$nplc_parent_account = trim($this->escape_string($this->strip_all($data['nplc_parent_account'])));
						$nplc_addon_account = trim($this->escape_string($this->strip_all($data['nplc_addon_account'])));
						$nplc_circuit_id = trim($this->escape_string($this->strip_all($data['nplc_circuit_id'])));
						$nplc_fan_no = trim($this->escape_string($this->strip_all($data['nplc_fan_no'])));
						$nplc_srf_no = trim($this->escape_string($this->strip_all($data['nplc_srf_no'])));
						$nplc_bandwidth = trim($this->escape_string($this->strip_all($data['nplc_bandwidth'])));
						$nplc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['nplc_bandwidth_ratio'])));

						$this->query("insert into ".PREFIX."caf_nplc_details(caf_id, nplc_connection_type, nplc_del_no, nplc_billing_cycle, nplc_exit_policy, nplc_pm_email, nplc_parent_account, nplc_addon_account, nplc_circuit_id, nplc_fan_no, nplc_srf_no, nplc_bandwidth, nplc_bandwidth_ratio) values('".$caf_id."', '".$nplc_connection_type."', '".$nplc_del_no."', '".$nplc_billing_cycle."', '".$nplc_exit_policy."', '".$nplc_pm_email."', '".$nplc_parent_account."', '".$nplc_addon_account."', '".$nplc_circuit_id."', '".$nplc_fan_no."', '".$nplc_srf_no."', '".$nplc_bandwidth."', '".$nplc_bandwidth_ratio."')");
					}
				}
				if($variant=='Platinum' || $variant=='Ultra LoLa') {
					$nplc_connection_type = trim($this->escape_string($this->strip_all($data['nplc_connection_type'])));
					$nplc_del_no = trim($this->escape_string($this->strip_all($data['nplc_del_no'])));
					$nplc_billing_cycle = trim($this->escape_string($this->strip_all($data['nplc_billing_cycle'])));
					$nplc_exit_policy = trim($this->escape_string($this->strip_all($data['nplc_exit_policy'])));
					$nplc_pm_email = trim($this->escape_string($this->strip_all($data['nplc_pm_email'])));
					$nplc_parent_account = trim($this->escape_string($this->strip_all($data['nplc_parent_account'])));
					$nplc_addon_account = trim($this->escape_string($this->strip_all($data['nplc_addon_account'])));
					$nplc_circuit_id = trim($this->escape_string($this->strip_all($data['nplc_circuit_id'])));
					$nplc_fan_no = trim($this->escape_string($this->strip_all($data['nplc_fan_no'])));
					$nplc_srf_no = trim($this->escape_string($this->strip_all($data['nplc_srf_no'])));
					$nplc_bandwidth = trim($this->escape_string($this->strip_all($data['nplc_bandwidth'])));
					$nplc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['nplc_bandwidth_ratio'])));

					$this->query("insert into ".PREFIX."caf_nplc_details(caf_id, nplc_connection_type, nplc_del_no, nplc_billing_cycle, nplc_exit_policy, nplc_pm_email, nplc_parent_account, nplc_addon_account, nplc_circuit_id, nplc_fan_no, nplc_srf_no, nplc_bandwidth, nplc_bandwidth_ratio) values('".$caf_id."', '".$nplc_connection_type."', '".$nplc_del_no."', '".$nplc_billing_cycle."', '".$nplc_exit_policy."', '".$nplc_pm_email."', '".$nplc_parent_account."', '".$nplc_addon_account."', '".$nplc_circuit_id."', '".$nplc_fan_no."', '".$nplc_srf_no."', '".$nplc_bandwidth."', '".$nplc_bandwidth_ratio."')");
				}
			}
			else if($product=='L2 Multicast Solution') {
				$l2mc_connection_type = trim($this->escape_string($this->strip_all($data['l2mc_connection_type'])));
				$l2mc_del_no = trim($this->escape_string($this->strip_all($data['l2mc_del_no'])));
				$l2mc_billing_cycle = trim($this->escape_string($this->strip_all($data['l2mc_billing_cycle'])));
				$l2mc_exit_policy = trim($this->escape_string($this->strip_all($data['l2mc_exit_policy'])));
				$l2mc_pm_email = trim($this->escape_string($this->strip_all($data['l2mc_pm_email'])));
				$l2mc_parent_account = trim($this->escape_string($this->strip_all($data['l2mc_parent_account'])));
				$l2mc_addon_account = trim($this->escape_string($this->strip_all($data['l2mc_addon_account'])));
				$l2mc_circuit_id = trim($this->escape_string($this->strip_all($data['l2mc_circuit_id'])));
				$l2mc_fan_no = trim($this->escape_string($this->strip_all($data['l2mc_fan_no'])));
				$l2mc_srf_no = trim($this->escape_string($this->strip_all($data['l2mc_srf_no'])));
				$l2mc_bandwidth = trim($this->escape_string($this->strip_all($data['l2mc_bandwidth'])));
				$l2mc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['l2mc_bandwidth_ratio'])));

				$this->query("insert into ".PREFIX."caf_l2mc_details(caf_id, l2mc_connection_type, l2mc_del_no, l2mc_billing_cycle, l2mc_exit_policy, l2mc_pm_email, l2mc_parent_account, l2mc_addon_account, l2mc_circuit_id, l2mc_fan_no, l2mc_srf_no, l2mc_bandwidth, l2mc_bandwidth_ratio) values('".$caf_id."', '".$l2mc_connection_type."', '".$l2mc_del_no."', '".$l2mc_billing_cycle."', '".$l2mc_exit_policy."', '".$l2mc_pm_email."', '".$l2mc_parent_account."', '".$l2mc_addon_account."', '".$l2mc_circuit_id."', '".$l2mc_fan_no."', '".$l2mc_srf_no."', '".$l2mc_bandwidth."', '".$l2mc_bandwidth_ratio."')");
			}
			else if($product=='Photon') {
				if($variant=='Photon Dongle') {
					$photon_dongal_connection_type = trim($this->escape_string($this->strip_all($data['photon_dongal_connection_type'])));
					$photon_dongal_del_no = trim($this->escape_string($this->strip_all($data['photon_dongal_del_no'])));
					$photon_dongal_rid = trim($this->escape_string($this->strip_all($data['photon_dongal_rid'])));
					$photon_dongal_pm_email = trim($this->escape_string($this->strip_all($data['photon_dongal_pm_email'])));
					$photon_dongal_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_dongal_billing_cycle'])));
					$photon_dongal_parent_account = trim($this->escape_string($this->strip_all($data['photon_dongal_parent_account'])));
					$photon_dongal_addon_account = trim($this->escape_string($this->strip_all($data['photon_dongal_addon_account'])));
					$photon_dongal_handset_id = trim($this->escape_string($this->strip_all($data['photon_dongal_handset_id'])));

					$this->query("insert into ".PREFIX."caf_photon_dongal_details(caf_id, photon_dongal_connection_type, photon_dongal_del_no, photon_dongal_rid, photon_dongal_pm_email, photon_dongal_billing_cycle, photon_dongal_parent_account, photon_dongal_addon_account, photon_dongal_handset_id) values('".$caf_id."', '".$photon_dongal_connection_type."', '".$photon_dongal_del_no."', '".$photon_dongal_rid."', '".$photon_dongal_pm_email."', '".$photon_dongal_billing_cycle."', '".$photon_dongal_parent_account."', '".$photon_dongal_addon_account."', '".$photon_dongal_handset_id."')");
				}
				else if($variant=='Photon Dongle Wifi') {
					$photon_wifi_connection_type = trim($this->escape_string($this->strip_all($data['photon_wifi_connection_type'])));
					$photon_wifi_del_no = trim($this->escape_string($this->strip_all($data['photon_wifi_del_no'])));
					$photon_wifi_rid = trim($this->escape_string($this->strip_all($data['photon_wifi_rid'])));
					$photon_wifi_pm_email = trim($this->escape_string($this->strip_all($data['photon_wifi_pm_email'])));
					$photon_wifi_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_wifi_billing_cycle'])));
					$photon_wifi_parent_account = trim($this->escape_string($this->strip_all($data['photon_wifi_parent_account'])));
					$photon_wifi_addon_account = trim($this->escape_string($this->strip_all($data['photon_wifi_addon_account'])));
					$photon_wifi_handset_id = trim($this->escape_string($this->strip_all($data['photon_wifi_handset_id'])));

					$this->query("insert into ".PREFIX."caf_photon_wifi_details(caf_id, photon_wifi_connection_type, photon_wifi_del_no, photon_wifi_rid, photon_wifi_pm_email, photon_wifi_billing_cycle, photon_wifi_parent_account, photon_wifi_addon_account, photon_wifi_handset_id) values('".$caf_id."', '".$photon_wifi_connection_type."', '".$photon_wifi_del_no."', '".$photon_wifi_rid."', '".$photon_wifi_pm_email."', '".$photon_wifi_billing_cycle."', '".$photon_wifi_parent_account."', '".$photon_wifi_addon_account."', '".$photon_wifi_handset_id."')");
				}
				else if($variant=='Photon Mifi') {
					$photon_mifi_existing_caf = trim($this->escape_string($this->strip_all($data['photon_mifi_existing_caf'])));
					$photon_mifi_proof_company_id = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_company_id'])));
					$photon_mifi_proof_authorization = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_authorization'])));
					$photon_mifi_proof_address = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_address'])));
					$photon_mifi_other_docs = trim($this->escape_string($this->strip_all($data['photon_mifi_other_docs'])));
					$photon_mifi_govt_id_proof = trim($this->escape_string($this->strip_all($data['photon_mifi_govt_id_proof'])));
					$photon_mifi_connection_type = trim($this->escape_string($this->strip_all($data['photon_mifi_connection_type'])));
					$photon_mifi_del_no = trim($this->escape_string($this->strip_all($data['photon_mifi_del_no'])));
					$photon_mifi_rid = trim($this->escape_string($this->strip_all($data['photon_mifi_rid'])));
					$photon_mifi_pm_email = trim($this->escape_string($this->strip_all($data['photon_mifi_pm_email'])));
					$photon_mifi_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_mifi_billing_cycle'])));
					$photon_mifi_parent_account = trim($this->escape_string($this->strip_all($data['photon_mifi_parent_account'])));
					$photon_mifi_addon_account = trim($this->escape_string($this->strip_all($data['photon_mifi_addon_account'])));
					$photon_mifi_handset_id = trim($this->escape_string($this->strip_all($data['photon_mifi_handset_id'])));

					if(!empty($file['photon_mifi_approval']['name'])) {
						/* $file_name = strtolower( pathinfo($file['photon_mifi_approval']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['photon_mifi_approval']['name'], PATHINFO_EXTENSION));
						$photon_mifi_approval = time().'7.'.$ext;
						move_uploaded_file($file['photon_mifi_approval']['tmp_name'],$uploadDir.$photon_mifi_approval); */
																		
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['photon_mifi_approval']['name'], PATHINFO_EXTENSION));
						$photon_mifi_approval = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$photon_mifi_approval, fopen($file['photon_mifi_approval']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_photon_mifi_details(caf_id, photon_mifi_existing_caf, photon_mifi_proof_company_id, photon_mifi_proof_authorization, photon_mifi_proof_address, photon_mifi_other_docs, photon_mifi_govt_id_proof, photon_mifi_approval, photon_mifi_connection_type, photon_mifi_del_no, photon_mifi_rid, photon_mifi_pm_email, photon_mifi_billing_cycle, photon_mifi_parent_account, photon_mifi_addon_account, photon_mifi_handset_id) values('".$caf_id."', '".$photon_mifi_existing_caf."', '".$photon_mifi_proof_company_id."', '".$photon_mifi_proof_authorization."', '".$photon_mifi_proof_address."', '".$photon_mifi_other_docs."', '".$photon_mifi_govt_id_proof."', '".$photon_mifi_approval."', '".$photon_mifi_connection_type."', '".$photon_mifi_del_no."', '".$photon_mifi_rid."', '".$photon_mifi_pm_email."', '".$photon_mifi_billing_cycle."', '".$photon_mifi_parent_account."', '".$photon_mifi_addon_account."', '".$photon_mifi_handset_id."')");
				}
			}
			else if($product=='PRI') {
					$pri_cug_type = trim($this->escape_string($this->strip_all($data['pri_cug_type'])));
					$pri_connection_type = trim($this->escape_string($this->strip_all($data['pri_connection_type'])));
					$pri_billing_cycle = trim($this->escape_string($this->strip_all($data['pri_billing_cycle'])));
					$pri_pm_email = trim($this->escape_string($this->strip_all($data['pri_pm_email'])));
					$pri_parent_account = trim($this->escape_string($this->strip_all($data['pri_parent_account'])));
					$pri_addon_account = trim($this->escape_string($this->strip_all($data['pri_addon_account'])));
					$pri_rid = trim($this->escape_string($this->strip_all($data['pri_rid'])));
					$pri_del_no = trim($this->escape_string($this->strip_all($data['pri_del_no'])));
					$pri_wepbax_config = trim($this->escape_string($this->strip_all($data['pri_wepbax_config'])));
					$pri_service_type_wireline = trim($this->escape_string($this->strip_all($data['pri_service_type_wireline'])));
					$pri_pilot_no = trim($this->escape_string($this->strip_all($data['pri_pilot_no'])));
					$pri_did_count = trim($this->escape_string($this->strip_all($data['pri_did_count'])));
					$pri_channel_count = trim($this->escape_string($this->strip_all($data['pri_channel_count'])));
					$pri_switch_name = trim($this->escape_string($this->strip_all($data['pri_switch_name'])));
					$pri_dial_code = trim($this->escape_string($this->strip_all($data['pri_dial_code'])));
					$pri_zone_id = trim($this->escape_string($this->strip_all($data['pri_zone_id'])));
					$pri_msgn_node = trim($this->escape_string($this->strip_all($data['pri_msgn_node'])));
					$pri_d_channel = trim($this->escape_string($this->strip_all($data['pri_d_channel'])));
					$pri_sponsered = trim($this->escape_string($this->strip_all($data['pri_sponsered'])));
					$pri_epabx_procured = trim($this->escape_string($this->strip_all($data['pri_epabx_procured'])));
					$pri_cost_epabx = trim($this->escape_string($this->strip_all($data['pri_cost_epabx'])));
					$pri_penalty_matrix = trim($this->escape_string($this->strip_all($data['pri_penalty_matrix'])));
					$pri_contract_period = trim($this->escape_string($this->strip_all($data['pri_contract_period'])));
					$pri_cost_pri_card = trim($this->escape_string($this->strip_all($data['pri_cost_pri_card'])));
					$pri_vendor_name = trim($this->escape_string($this->strip_all($data['pri_vendor_name'])));
					$pri_ebabx_make = trim($this->escape_string($this->strip_all($data['pri_ebabx_make'])));
					$pri_mis_entry = trim($this->escape_string($this->strip_all($data['pri_mis_entry'])));
					$pri_calling_level = trim($this->escape_string($this->strip_all($data['pri_calling_level'])));
					$pri_hosted_ivr = trim($this->escape_string($this->strip_all($data['pri_hosted_ivr'])));
					$pri_hivr_no = trim($this->escape_string($this->strip_all($data['pri_hivr_no'])));
					$pri_type = trim($this->escape_string($this->strip_all($data['pri_type'])));

					$this->query("insert into ".PREFIX."caf_pri_details(caf_id, pri_cug_type, pri_del_no, pri_billing_cycle, pri_pm_email, pri_connection_type, pri_parent_account, pri_wepbax_config, pri_rid, pri_addon_account, pri_service_type_wireline, pri_pilot_no, pri_did_count, pri_switch_name, pri_dial_code, pri_zone_id, pri_msgn_node, pri_d_channel, pri_channel_count, pri_sponsered, pri_epabx_procured, pri_cost_epabx, pri_penalty_matrix, pri_contract_period, pri_cost_pri_card, pri_vendor_name, pri_ebabx_make, pri_mis_entry, pri_calling_level, pri_hosted_ivr, pri_hivr_no, pri_type) values('".$caf_id."', '".$pri_cug_type."', '".$pri_del_no."', '".$pri_billing_cycle."', '".$pri_pm_email."', '".$pri_connection_type."', '".$pri_parent_account."', '".$pri_wepbax_config."', '".$pri_rid."', '".$pri_addon_account."', '".$pri_service_type_wireline."', '".$pri_pilot_no."', '".$pri_did_count."', '".$pri_switch_name."', '".$pri_dial_code."', '".$pri_zone_id."', '".$pri_msgn_node."', '".$pri_d_channel."', '".$pri_channel_count."', '".$pri_sponsered."', '".$pri_epabx_procured."', '".$pri_cost_epabx."', '".$pri_penalty_matrix."', '".$pri_contract_period."', '".$pri_cost_pri_card."', '".$pri_vendor_name."', '".$pri_ebabx_make."', '".$pri_mis_entry."', '".$pri_calling_level."', '".$pri_hosted_ivr."', '".$pri_hivr_no."', '".$pri_type."')");
				}
			else if($product=='SIP Trunk') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
			else if($product=='Hosted OBD') {
				$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
				$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
				$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
				$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
				$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
				$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
				$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
				$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
				$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
				$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
				$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
				$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
				$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
				$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
				$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
				$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
				$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
				$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
				$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
				$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
				$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
				$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
				$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
				$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
				$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
				$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
				$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
				$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
				$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
				$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
				$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

				$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
			}
			else if($product=='Standard Centrex') {
					$standard_centrex_group_type = trim($this->escape_string($this->strip_all($data['standard_centrex_group_type'])));
					$standard_centrex_bgid = trim($this->escape_string($this->strip_all($data['standard_centrex_bgid'])));
					$standard_centrex_idp_id = trim($this->escape_string($this->strip_all($data['standard_centrex_idp_id'])));
					$standard_centrex_switch_name = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_name'])));
					$standard_centrex_dail_code = trim($this->escape_string($this->strip_all($data['standard_centrex_dail_code'])));
					$standard_centrex_zone = trim($this->escape_string($this->strip_all($data['standard_centrex_zone'])));
					$standard_centrex_zte_pnr = trim($this->escape_string($this->strip_all($data['standard_centrex_zte_pnr'])));
					$standard_centrex_switch_type = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_type'])));
					$standard_centrex_switch_details = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_details'])));
					$standard_centrex_zone_id = trim($this->escape_string($this->strip_all($data['standard_centrex_zone_id'])));
					$standard_centrex_calling_level = trim($this->escape_string($this->strip_all($data['standard_centrex_calling_level'])));

					$this->query("insert into ".PREFIX."caf_standard_centrex_details(caf_id, standard_centrex_group_type, standard_centrex_bgid, standard_centrex_idp_id, standard_centrex_dail_code, standard_centrex_switch_name, standard_centrex_zone, standard_centrex_zte_pnr, standard_centrex_switch_type, standard_centrex_switch_details, standard_centrex_zone_id, standard_centrex_calling_level) values('".$caf_id."', '".$standard_centrex_group_type."', '".$standard_centrex_bgid."', '".$standard_centrex_idp_id."', '".$standard_centrex_dail_code."', '".$standard_centrex_switch_name."', '".$standard_centrex_zone."', '".$standard_centrex_zte_pnr."', '".$standard_centrex_switch_type."', '".$standard_centrex_switch_details."', '".$standard_centrex_zone_id."', '".$standard_centrex_calling_level."')");
				}
			else if($product=='IP Centrex') {
					$ip_centrex_group_type = trim($this->escape_string($this->strip_all($data['ip_centrex_group_type'])));
					$ip_centrex_bgid = trim($this->escape_string($this->strip_all($data['ip_centrex_bgid'])));
					$ip_centrex_idp_id = trim($this->escape_string($this->strip_all($data['ip_centrex_idp_id'])));
					$ip_centrex_switch_name = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_name'])));
					$ip_centrex_zone_code = trim($this->escape_string($this->strip_all($data['ip_centrex_zone_code'])));
					$ip_centrex_dail_code = trim($this->escape_string($this->strip_all($data['ip_centrex_dail_code'])));
					$ip_centrex_zte_pnr = trim($this->escape_string($this->strip_all($data['ip_centrex_zte_pnr'])));
					$ip_centrex_switch_type = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_type'])));
					$ip_centrex_switch_details = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_details'])));
					$ip_centrex_zone_id = trim($this->escape_string($this->strip_all($data['ip_centrex_zone_id'])));
					$ip_centrex_calling_level = trim($this->escape_string($this->strip_all($data['ip_centrex_calling_level'])));
					$ip_centrex_cug_type = trim($this->escape_string($this->strip_all($data['ip_centrex_cug_type'])));
					$ip_centrex_del_no = trim($this->escape_string($this->strip_all($data['ip_centrex_del_no'])));
					$ip_centrex_billing_cycle = trim($this->escape_string($this->strip_all($data['ip_centrex_billing_cycle'])));
					$ip_centrex_pm_email = trim($this->escape_string($this->strip_all($data['ip_centrex_pm_email'])));
					$ip_centrex_connection_type = trim($this->escape_string($this->strip_all($data['ip_centrex_connection_type'])));
					$ip_centrex_parent_account = trim($this->escape_string($this->strip_all($data['ip_centrex_parent_account'])));
					$ip_centrex_handset_id = trim($this->escape_string($this->strip_all($data['ip_centrex_handset_id'])));
					$ip_centrex_addon_account = trim($this->escape_string($this->strip_all($data['ip_centrex_addon_account'])));
					$ip_centrex_ip_address1 = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_address1'])));
					$ip_centrex_ip_address2 = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_address2'])));
					$ip_centrex_ip_mask = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_mask'])));
					$ip_centrex_vlan_tag = trim($this->escape_string($this->strip_all($data['ip_centrex_vlan_tag'])));
					$ip_centrex_vlan_id = trim($this->escape_string($this->strip_all($data['ip_centrex_vlan_id'])));
					$ip_centrex_dealer_contact = trim($this->escape_string($this->strip_all($data['ip_centrex_dealer_contact'])));
					$ip_centrex_je_email = trim($this->escape_string($this->strip_all($data['ip_centrex_je_email'])));
					$ip_centrex_zte_ctxgrpnr = trim($this->escape_string($this->strip_all($data['ip_centrex_zte_ctxgrpnr'])));
					$ip_centrex_type = trim($this->escape_string($this->strip_all($data['ip_centrex_type'])));
					$ip_centrex_customer_type = trim($this->escape_string($this->strip_all($data['ip_centrex_customer_type'])));
					$ip_centrex_customer_owned_equipment = trim($this->escape_string($this->strip_all($data['ip_centrex_customer_owned_equipment'])));
					$ip_centrex_operator_type = trim($this->escape_string($this->strip_all($data['ip_centrex_operator_type'])));

					if(!empty($file['ip_centrex_ip_address_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_EXTENSION));
						$ip_centrex_ip_address_excel = time().'7.'.$ext;
						move_uploaded_file($file['ip_centrex_ip_address_excel']['tmp_name'],$uploadDir.$ip_centrex_ip_address_excel); */
																		
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_EXTENSION));
						$ip_centrex_ip_address_excel = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$ip_centrex_ip_address_excel, fopen($file['ip_centrex_ip_address_excel']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_ip_centrex_details(caf_id, ip_centrex_group_type, ip_centrex_bgid, ip_centrex_idp_id, ip_centrex_switch_name, ip_centrex_zone_code, ip_centrex_dail_code, ip_centrex_zte_pnr, ip_centrex_switch_type, ip_centrex_switch_details, ip_centrex_zone_id, ip_centrex_calling_level, ip_centrex_cug_type, ip_centrex_del_no, ip_centrex_billing_cycle, ip_centrex_pm_email, ip_centrex_connection_type, ip_centrex_parent_account, ip_centrex_handset_id, ip_centrex_addon_account, ip_centrex_ip_address1, ip_centrex_ip_address2, ip_centrex_ip_address3, ip_centrex_ip_address4, ip_centrex_ip_address_excel, ip_centrex_ip_mask, ip_centrex_vlan_tag, ip_centrex_vlan_id, ip_centrex_dealer_contact, ip_centrex_je_email, ip_centrex_zte_ctxgrpnr, ip_centrex_type, ip_centrex_customer_type, ip_centrex_customer_owned_equipment, ip_centrex_operator_type) values('".$caf_id."', '".$ip_centrex_group_type."', '".$ip_centrex_bgid."', '".$ip_centrex_idp_id."', '".$ip_centrex_switch_name."', '".$ip_centrex_zone_code."', '".$ip_centrex_dail_code."', '".$ip_centrex_zte_pnr."', '".$ip_centrex_switch_type."', '".$ip_centrex_switch_details."', '".$ip_centrex_zone_id."', '".$ip_centrex_calling_level."', '".$ip_centrex_cug_type."', '".$ip_centrex_del_no."', '".$ip_centrex_billing_cycle."', '".$ip_centrex_pm_email."', '".$ip_centrex_connection_type."', '".$ip_centrex_parent_account."', '".$ip_centrex_handset_id."', '".$ip_centrex_addon_account."', '".$ip_centrex_ip_address1."', '".$ip_centrex_ip_address2."', '".$ip_centrex_ip_address3."', '".$ip_centrex_ip_address4."', '".$ip_centrex_ip_address_excel."', '".$ip_centrex_ip_mask."', '".$ip_centrex_vlan_tag."', '".$ip_centrex_vlan_id."', '".$ip_centrex_dealer_contact."', '".$ip_centrex_je_email."', '".$ip_centrex_zte_ctxgrpnr."', '".$ip_centrex_type."', '".$ip_centrex_customer_type."', '".$ip_centrex_customer_owned_equipment."', '".$ip_centrex_operator_type."')");
				}
			else if($product=='Standard Wireline') {
					$standard_wrln_existing_caf = trim($this->escape_string($this->strip_all($data['standard_wrln_existing_caf'])));
					$standard_wrln_proof_company_id = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_company_id'])));
					$standard_wrln_proof_authorization = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_authorization'])));
					$standard_wrln_proof_address = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_address'])));
					$standard_wrln_other_docs = trim($this->escape_string($this->strip_all($data['standard_wrln_other_docs'])));
					$standard_wrln_govt_id_proof = trim($this->escape_string($this->strip_all($data['standard_wrln_govt_id_proof'])));
					$standard_wrln_cug_type = trim($this->escape_string($this->strip_all($data['standard_wrln_cug_type'])));
					$standard_wrln_cug_no = trim($this->escape_string($this->strip_all($data['standard_wrln_cug_no'])));
					$standard_wrln_billing_cycle = trim($this->escape_string($this->strip_all($data['standard_wrln_billing_cycle'])));
					$standard_wrln_pm_email = trim($this->escape_string($this->strip_all($data['standard_wrln_pm_email'])));
					$standard_wrln_connection_type = trim($this->escape_string($this->strip_all($data['standard_wrln_connection_type'])));
					$standard_wrln_parent_account = trim($this->escape_string($this->strip_all($data['standard_wrln_parent_account'])));
					$standard_wrln_trai_id = trim($this->escape_string($this->strip_all($data['standard_wrln_trai_id'])));
					$standard_wrln_handset_id = trim($this->escape_string($this->strip_all($data['standard_wrln_handset_id'])));
					$standard_wrln_addon_account = trim($this->escape_string($this->strip_all($data['standard_wrln_addon_account'])));
					$standard_wrln_service_type = trim($this->escape_string($this->strip_all($data['standard_wrln_service_type'])));
					$standard_wrln_del_no = trim($this->escape_string($this->strip_all($data['standard_wrln_del_no'])));
					$standard_wrln_operator_type = trim($this->escape_string($this->strip_all($data['standard_wrln_operator_type'])));
					$standard_wrln_operation_id = trim($this->escape_string($this->strip_all($data['standard_wrln_operation_id'])));
					$standard_wrln_customer_type = trim($this->escape_string($this->strip_all($data['standard_wrln_customer_type'])));
					$standard_wrln_ip_type = trim($this->escape_string($this->strip_all($data['standard_wrln_ip_type'])));
					$standard_wrln_dslam_id = trim($this->escape_string($this->strip_all($data['standard_wrln_dslam_id'])));
					$standard_wrln_static_ip_discount = trim($this->escape_string($this->strip_all($data['standard_wrln_static_ip_discount'])));
					$standard_wrln_calling_level = trim($this->escape_string($this->strip_all($data['standard_wrln_calling_level'])));

					if(!empty($file['standard_wrln_approval']['name'])) {
						/* $file_name = strtolower( pathinfo($file['standard_wrln_approval']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['standard_wrln_approval']['name'], PATHINFO_EXTENSION));
						$standard_wrln_approval = time().'7.'.$ext;
						move_uploaded_file($file['standard_wrln_approval']['tmp_name'],$uploadDir.$standard_wrln_approval); */
																		
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['standard_wrln_approval']['name'], PATHINFO_EXTENSION));
						$standard_wrln_approval = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$standard_wrln_approval, fopen($file['standard_wrln_approval']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_standard_wrln_details(caf_id, standard_wrln_existing_caf, standard_wrln_proof_company_id, standard_wrln_proof_authorization, standard_wrln_proof_address, standard_wrln_other_docs, standard_wrln_govt_id_proof, standard_wrln_approval, standard_wrln_cug_type, standard_wrln_cug_no, standard_wrln_billing_cycle, standard_wrln_pm_email, standard_wrln_connection_type, standard_wrln_parent_account, standard_wrln_trai_id, standard_wrln_handset_id, standard_wrln_addon_account, standard_wrln_service_type, standard_wrln_del_no, standard_wrln_operator_type, standard_wrln_operation_id, standard_wrln_customer_type, standard_wrln_ip_type, standard_wrln_dslam_id, standard_wrln_static_ip_discount, standard_wrln_calling_level) values('".$caf_id."', '".$standard_wrln_existing_caf."', '".$standard_wrln_proof_company_id."', '".$standard_wrln_proof_authorization."', '".$standard_wrln_proof_address."', '".$standard_wrln_other_docs."', '".$standard_wrln_govt_id_proof."', '".$standard_wrln_approval."', '".$standard_wrln_cug_type."', '".$standard_wrln_cug_no."', '".$standard_wrln_billing_cycle."', '".$standard_wrln_pm_email."', '".$standard_wrln_connection_type."', '".$standard_wrln_parent_account."', '".$standard_wrln_trai_id."', '".$standard_wrln_handset_id."', '".$standard_wrln_addon_account."', '".$standard_wrln_service_type."', '".$standard_wrln_del_no."', '".$standard_wrln_operator_type."', '".$standard_wrln_operation_id."', '".$standard_wrln_customer_type."', '".$standard_wrln_ip_type."', '".$standard_wrln_dslam_id."', '".$standard_wrln_static_ip_discount."', '".$standard_wrln_calling_level."')");
				}
			else if($product=='WEPABX') {
					$wepbax_cug_type = trim($this->escape_string($this->strip_all($data['wepbax_cug_type'])));
					$wepbax_connection_type = trim($this->escape_string($this->strip_all($data['wepbax_connection_type'])));
					$wepbax_billing_cycle = trim($this->escape_string($this->strip_all($data['wepbax_billing_cycle'])));
					$wepbax_pm_email = trim($this->escape_string($this->strip_all($data['wepbax_pm_email'])));
					$wepbax_parent_account = trim($this->escape_string($this->strip_all($data['wepbax_parent_account'])));
					$wepbax_addon_account = trim($this->escape_string($this->strip_all($data['wepbax_addon_account'])));
					$wepbax_del_no = trim($this->escape_string($this->strip_all($data['wepbax_del_no'])));
					$wepbax_rid = trim($this->escape_string($this->strip_all($data['wepbax_rid'])));
					$wepbax_config = trim($this->escape_string($this->strip_all($data['wepbax_config'])));
					$wepbax_calling_level = trim($this->escape_string($this->strip_all($data['wepbax_calling_level'])));
					$wepbax_cug_no = trim($this->escape_string($this->strip_all($data['wepbax_cug_no'])));
					$wepbax_handset_id = trim($this->escape_string($this->strip_all($data['wepbax_handset_id'])));

					$this->query("insert into ".PREFIX."caf_wepbax_details(caf_id, wepbax_cug_type, wepbax_billing_cycle, wepbax_pm_email, wepbax_connection_type, wepbax_del_no, wepbax_rid, wepbax_parent_account, wepbax_addon_account, wepbax_config, wepbax_calling_level, wepbax_cug_no, wepbax_handset_id) values('".$caf_id."', '".$wepbax_cug_type."', '".$wepbax_billing_cycle."', '".$wepbax_pm_email."', '".$wepbax_connection_type."', '".$wepbax_del_no."', '".$wepbax_rid."', '".$wepbax_parent_account."', '".$wepbax_addon_account."', '".$wepbax_config."', '".$wepbax_calling_level."', '".$wepbax_cug_no."', '".$wepbax_handset_id."')");
				
			}
			else if($product=='IBS') {
				$ibs_existing_caf = trim($this->escape_string($this->strip_all($data['ibs_existing_caf'])));
				$ibs_proof_company_id = trim($this->escape_string($this->strip_all($data['ibs_proof_company_id'])));
				$ibs_proof_authorization = trim($this->escape_string($this->strip_all($data['ibs_proof_authorization'])));
				$ibs_proof_address = trim($this->escape_string($this->strip_all($data['ibs_proof_address'])));
				$ibs_other_docs = trim($this->escape_string($this->strip_all($data['ibs_other_docs'])));
				$ibs_govt_id_proof = trim($this->escape_string($this->strip_all($data['ibs_govt_id_proof'])));
				$ibs_connection_type = trim($this->escape_string($this->strip_all($data['ibs_connection_type'])));
				$ibs_billing_cycle = trim($this->escape_string($this->strip_all($data['ibs_billing_cycle'])));
				$ibs_pm_email = trim($this->escape_string($this->strip_all($data['ibs_pm_email'])));
				$ibs_del_no = trim($this->escape_string($this->strip_all($data['ibs_del_no'])));
				$ibs_reserved_id = trim($this->escape_string($this->strip_all($data['ibs_reserved_id'])));
				$ibs_addon_account = trim($this->escape_string($this->strip_all($data['ibs_addon_account'])));
				$ibs_parent_account = trim($this->escape_string($this->strip_all($data['ibs_parent_account'])));
				$ibs_type = trim($this->escape_string($this->strip_all($data['ibs_type'])));
				$ibs_provision_on = trim($this->escape_string($this->strip_all($data['ibs_provision_on'])));
				$ibs_present = trim($this->escape_string($this->strip_all($data['ibs_present'])));
				$ibs_mapped_on = trim($this->escape_string($this->strip_all($data['ibs_mapped_on'])));
				$ibs_calling_level = trim($this->escape_string($this->strip_all($data['ibs_calling_level'])));

				if(!empty($file['ibs_approval']['name'])) {
					/* $file_name = strtolower( pathinfo($file['ibs_approval']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['ibs_approval']['name'], PATHINFO_EXTENSION));
					$ibs_approval = time().'7.'.$ext;
					move_uploaded_file($file['ibs_approval']['tmp_name'],$uploadDir.$ibs_approval); */
																	
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['ibs_approval']['name'], PATHINFO_EXTENSION));
					$ibs_approval = time().'7.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$ibs_approval, fopen($file['ibs_approval']['tmp_name'], 'rb'), 'public-read');
				}

				$this->query("insert into ".PREFIX."caf_ibs_details(caf_id, ibs_existing_caf, ibs_proof_company_id, ibs_proof_authorization, ibs_proof_address, ibs_other_docs, ibs_govt_id_proof, ibs_approval, ibs_connection_type, ibs_billing_cycle, ibs_pm_email, ibs_del_no, ibs_reserved_id, ibs_addon_account, ibs_parent_account, ibs_type, ibs_provision_on, ibs_present, ibs_mapped_on, ibs_calling_level) values('".$caf_id."', '".$ibs_existing_caf."', '".$ibs_proof_company_id."', '".$ibs_proof_authorization."', '".$ibs_proof_address."', '".$ibs_other_docs."', '".$ibs_govt_id_proof."', '".$ibs_approval."', '".$ibs_connection_type."', '".$ibs_billing_cycle."', '".$ibs_pm_email."', '".$ibs_del_no."', '".$ibs_reserved_id."', '".$ibs_addon_account."', '".$ibs_parent_account."', '".$ibs_type."', '".$ibs_provision_on."', '".$ibs_present."', '".$ibs_mapped_on."', '".$ibs_calling_level."')");
			}
			else if($product=='Walky') {
				$walky_sns = trim($this->escape_string($this->strip_all($data['walky_sns'])));
				$walky_type = trim($this->escape_string($this->strip_all($data['walky_type'])));
				$walky_cug_type = trim($this->escape_string($this->strip_all($data['walky_cug_type'])));
				$walky_cug_no = trim($this->escape_string($this->strip_all($data['walky_cug_no'])));
				$walky_billing_cycle = trim($this->escape_string($this->strip_all($data['walky_billing_cycle'])));
				$walky_pm_email = trim($this->escape_string($this->strip_all($data['walky_pm_email'])));
				$walky_connection_type = trim($this->escape_string($this->strip_all($data['walky_connection_type'])));
				$walky_del_no = trim($this->escape_string($this->strip_all($data['walky_del_no'])));
				$walky_rid = trim($this->escape_string($this->strip_all($data['walky_rid'])));
				$walky_parent_account = trim($this->escape_string($this->strip_all($data['walky_parent_account'])));
				$walky_handset_id = trim($this->escape_string($this->strip_all($data['walky_handset_id'])));
				$walky_addon_account = trim($this->escape_string($this->strip_all($data['walky_addon_account'])));
				$walky_trai_id = trim($this->escape_string($this->strip_all($data['walky_trai_id'])));
				$walky_calling_level = trim($this->escape_string($this->strip_all($data['walky_calling_level'])));

				$this->query("insert into ".PREFIX."caf_walky_details(caf_id, walky_sns, walky_type, walky_cug_type, walky_cug_no, walky_billing_cycle, walky_pm_email, walky_connection_type, walky_del_no, walky_rid, walky_parent_account, walky_handset_id, walky_addon_account, walky_trai_id, walky_calling_level) values('".$caf_id."', '".$walky_sns."', '".$walky_type."', '".$walky_cug_type."', '".$walky_cug_no."', '".$walky_billing_cycle."', '".$walky_pm_email."', '".$walky_connection_type."', '".$walky_del_no."', '".$walky_rid."', '".$walky_parent_account."', '".$walky_handset_id."', '".$walky_addon_account."', '".$walky_trai_id."', '".$walky_calling_level."')");
			}
			else if($product=='Mobile') {
				$mobile_sns = trim($this->escape_string($this->strip_all($data['mobile_sns'])));
				$mobile_cug_type = trim($this->escape_string($this->strip_all($data['mobile_cug_type'])));
				$mobile_cug_no = trim($this->escape_string($this->strip_all($data['mobile_cug_no'])));
				$mobile_billing_cycle = trim($this->escape_string($this->strip_all($data['mobile_billing_cycle'])));
				$mobile_pm_email = trim($this->escape_string($this->strip_all($data['mobile_pm_email'])));
				$mobile_connection_type = trim($this->escape_string($this->strip_all($data['mobile_connection_type'])));
				$mobile_del_no = trim($this->escape_string($this->strip_all($data['mobile_del_no'])));
				$mobile_reserved_id = trim($this->escape_string($this->strip_all($data['mobile_reserved_id'])));
				$mobile_parent_account = trim($this->escape_string($this->strip_all($data['mobile_parent_account'])));
				$mobile_handset_id = trim($this->escape_string($this->strip_all($data['mobile_handset_id'])));
				$mobile_addon_account = trim($this->escape_string($this->strip_all($data['mobile_addon_account'])));
				$mobile_addon_account2 = trim($this->escape_string($this->strip_all($data['mobile_addon_account2'])));
				$mobile_addon_account3 = trim($this->escape_string($this->strip_all($data['mobile_addon_account3'])));
				$mobile_spm = trim($this->escape_string($this->strip_all($data['mobile_spm'])));
				$mobile_spn_scheme = trim($this->escape_string($this->strip_all($data['mobile_spn_scheme'])));
				$mobile_calling_level = trim($this->escape_string($this->strip_all($data['mobile_calling_level'])));

				$this->query("insert into ".PREFIX."caf_mobile_details(caf_id, mobile_sns, mobile_cug_type, mobile_cug_no, mobile_billing_cycle, mobile_pm_email, mobile_connection_type, mobile_del_no, mobile_reserved_id, mobile_parent_account, mobile_handset_id, mobile_addon_account, mobile_addon_account2, mobile_addon_account3, mobile_spm, mobile_spn_scheme, mobile_calling_level) values('".$caf_id."', '".$mobile_sns."', '".$mobile_cug_type."', '".$mobile_cug_no."', '".$mobile_billing_cycle."', '".$mobile_pm_email."', '".$mobile_connection_type."', '".$mobile_del_no."', '".$mobile_reserved_id."', '".$mobile_parent_account."', '".$mobile_handset_id."', '".$mobile_addon_account."', '".$mobile_addon_account2."', '".$mobile_addon_account3."', '".$mobile_spm."', '".$mobile_spn_scheme."', '".$mobile_calling_level."')");
			}
			else if($product=='Fleet Management' || $product=='School Bus Tracking' || $product=='Asset Management' || $product=='Workforce Management' || $product=='LaaS') {
				$lbs_connection_type = trim($this->escape_string($this->strip_all($data['lbs_connection_type'])));
				$lbs_del_no = trim($this->escape_string($this->strip_all($data['lbs_del_no'])));
				$lbs_rid = trim($this->escape_string($this->strip_all($data['lbs_rid'])));
				$lbs_pm_email = trim($this->escape_string($this->strip_all($data['lbs_pm_email'])));
				$lbs_billing_cycle = trim($this->escape_string($this->strip_all($data['lbs_billing_cycle'])));
				$lbs_parent_account = trim($this->escape_string($this->strip_all($data['lbs_parent_account'])));
				$lbs_addon_account = trim($this->escape_string($this->strip_all($data['lbs_addon_account'])));
				$lbs_handset_id = trim($this->escape_string($this->strip_all($data['lbs_handset_id'])));
				$lbs_type = trim($this->escape_string($this->strip_all($data['lbs_type'])));
				$lbs_vehicle_no = trim($this->escape_string($this->strip_all($data['lbs_vehicle_no'])));
				$lbs_imei_no = trim($this->escape_string($this->strip_all($data['lbs_imei_no'])));
				$lbs_vendor_type = trim($this->escape_string($this->strip_all($data['lbs_vendor_type'])));
				$lbs_product_type = trim($this->escape_string($this->strip_all($data['lbs_product_type'])));

				$this->query("insert into ".PREFIX."caf_lbs_details(caf_id, lbs_connection_type, lbs_del_no, lbs_rid, lbs_pm_email, lbs_billing_cycle, lbs_parent_account, lbs_addon_account, lbs_handset_id, lbs_type, lbs_vehicle_no, lbs_imei_no, lbs_vendor_type, lbs_product_type) values('".$caf_id."', '".$lbs_connection_type."', '".$lbs_del_no."', '".$lbs_rid."', '".$lbs_pm_email."', '".$lbs_billing_cycle."', '".$lbs_parent_account."', '".$lbs_addon_account."', '".$lbs_handset_id."', '".$lbs_type."', '".$lbs_vehicle_no."', '".$lbs_imei_no."', '".$lbs_vendor_type."', '".$lbs_product_type."')");
			}
			else if($product=='M2M Sim') {
				if($variant=='M2M Standard') {
					if($subvariant=='Vehicle Tracking' || $subvariant=='Others') {
						$lbs_connection_type = trim($this->escape_string($this->strip_all($data['lbs_connection_type'])));
						$lbs_del_no = trim($this->escape_string($this->strip_all($data['lbs_del_no'])));
						$lbs_rid = trim($this->escape_string($this->strip_all($data['lbs_rid'])));
						$lbs_pm_email = trim($this->escape_string($this->strip_all($data['lbs_pm_email'])));
						$lbs_billing_cycle = trim($this->escape_string($this->strip_all($data['lbs_billing_cycle'])));
						$lbs_parent_account = trim($this->escape_string($this->strip_all($data['lbs_parent_account'])));
						$lbs_addon_account = trim($this->escape_string($this->strip_all($data['lbs_addon_account'])));
						$lbs_handset_id = trim($this->escape_string($this->strip_all($data['lbs_handset_id'])));
						$lbs_type = trim($this->escape_string($this->strip_all($data['lbs_type'])));
						$lbs_vehicle_no = trim($this->escape_string($this->strip_all($data['lbs_vehicle_no'])));
						$lbs_imei_no = trim($this->escape_string($this->strip_all($data['lbs_imei_no'])));
						$lbs_vendor_type = trim($this->escape_string($this->strip_all($data['lbs_vendor_type'])));
						$lbs_product_type = trim($this->escape_string($this->strip_all($data['lbs_product_type'])));

						$this->query("insert into ".PREFIX."caf_lbs_details(caf_id, lbs_connection_type, lbs_del_no, lbs_rid, lbs_pm_email, lbs_billing_cycle, lbs_parent_account, lbs_addon_account, lbs_handset_id, lbs_type, lbs_vehicle_no, lbs_imei_no, lbs_vendor_type, lbs_product_type) values('".$caf_id."', '".$lbs_connection_type."', '".$lbs_del_no."', '".$lbs_rid."', '".$lbs_pm_email."', '".$lbs_billing_cycle."', '".$lbs_parent_account."', '".$lbs_addon_account."', '".$lbs_handset_id."', '".$lbs_type."', '".$lbs_vehicle_no."', '".$lbs_imei_no."', '".$lbs_vendor_type."', '".$lbs_product_type."')");
					}
				}
			}
			else if($product=='Toll Free Services' || $product=='Call Register Services') {
				$crs_connection_type = trim($this->escape_string($this->strip_all($data['crs_connection_type'])));
				$crs_ibs = trim($this->escape_string($this->strip_all($data['crs_ibs'])));
				$crs_provision_on = trim($this->escape_string($this->strip_all($data['crs_provision_on'])));
				$crs_present = trim($this->escape_string($this->strip_all($data['crs_present'])));
				$crs_mapped_on = trim($this->escape_string($this->strip_all($data['crs_mapped_on'])));
				$crs_calling_level = trim($this->escape_string($this->strip_all($data['crs_calling_level'])));
				$crs_billing_cycle = trim($this->escape_string($this->strip_all($data['crs_billing_cycle'])));
				$crs_pm_email = trim($this->escape_string($this->strip_all($data['crs_pm_email'])));
				$crs_parent_account = trim($this->escape_string($this->strip_all($data['crs_parent_account'])));
				$crs_addon_account = trim($this->escape_string($this->strip_all($data['crs_addon_account'])));
				$crs_rid = trim($this->escape_string($this->strip_all($data['crs_rid'])));
				$crs_del_no = trim($this->escape_string($this->strip_all($data['crs_del_no'])));

				$this->query("insert into ".PREFIX."caf_crs_details(caf_id, crs_connection_type, crs_ibs, crs_provision_on, crs_present, crs_mapped_on, crs_calling_level, crs_billing_cycle, crs_pm_email, crs_parent_account, crs_addon_account, crs_rid, crs_del_no) values('".$caf_id."', '".$crs_connection_type."', '".$crs_ibs."', '".$crs_provision_on."', '".$crs_present."', '".$crs_mapped_on."', '".$crs_calling_level."', '".$crs_billing_cycle."', '".$crs_pm_email."', '".$crs_parent_account."', '".$crs_addon_account."', '".$crs_rid."', '".$crs_del_no."')");
			}
			else if($product=='HIVR' || $product=='Webconnect') {
				$toll_free_cug_type = trim($this->escape_string($this->strip_all($data['toll_free_cug_type'])));
				$toll_free_del_no = trim($this->escape_string($this->strip_all($data['toll_free_del_no'])));
				$toll_free_billing_cycle = trim($this->escape_string($this->strip_all($data['toll_free_billing_cycle'])));
				$toll_free_pm_email = trim($this->escape_string($this->strip_all($data['toll_free_pm_email'])));
				$toll_free_connection_type = trim($this->escape_string($this->strip_all($data['toll_free_connection_type'])));
				$toll_free_parent_account = trim($this->escape_string($this->strip_all($data['toll_free_parent_account'])));
				$toll_free_rid = trim($this->escape_string($this->strip_all($data['toll_free_rid'])));
				$toll_free_wepbax_config = trim($this->escape_string($this->strip_all($data['toll_free_wepbax_config'])));
				$toll_free_addon_account = trim($this->escape_string($this->strip_all($data['toll_free_addon_account'])));
				$toll_free_service_type_wireline = trim($this->escape_string($this->strip_all($data['toll_free_service_type_wireline'])));
				$toll_free_pilot_no = trim($this->escape_string($this->strip_all($data['toll_free_pilot_no'])));
				$toll_free_did_count = trim($this->escape_string($this->strip_all($data['toll_free_did_count'])));
				$toll_free_switch_name = trim($this->escape_string($this->strip_all($data['toll_free_switch_name'])));
				$toll_free_dial_code = trim($this->escape_string($this->strip_all($data['toll_free_dial_code'])));
				$toll_free_zone_id = trim($this->escape_string($this->strip_all($data['toll_free_zone_id'])));
				$toll_free_msgn_node = trim($this->escape_string($this->strip_all($data['toll_free_msgn_node'])));
				$toll_free_d_channel = trim($this->escape_string($this->strip_all($data['toll_free_d_channel'])));
				$toll_free_channel_count = trim($this->escape_string($this->strip_all($data['toll_free_channel_count'])));
				$toll_free_sponsered_pri = trim($this->escape_string($this->strip_all($data['toll_free_sponsered_pri'])));
				$toll_free_epabx_procured = trim($this->escape_string($this->strip_all($data['toll_free_epabx_procured'])));
				$toll_free_cost_epabx = trim($this->escape_string($this->strip_all($data['toll_free_cost_epabx'])));
				$toll_free_penalty_matrix = trim($this->escape_string($this->strip_all($data['toll_free_penalty_matrix'])));
				$toll_free_contract_period_pri = trim($this->escape_string($this->strip_all($data['toll_free_contract_period_pri'])));
				$toll_free_cost_pri_card = trim($this->escape_string($this->strip_all($data['toll_free_cost_pri_card'])));
				$toll_free_vendor_name = trim($this->escape_string($this->strip_all($data['toll_free_vendor_name'])));
				$toll_free_ebabx_make = trim($this->escape_string($this->strip_all($data['toll_free_ebabx_make'])));
				$toll_free_mis_entry = trim($this->escape_string($this->strip_all($data['toll_free_mis_entry'])));
				$toll_free_calling_level = trim($this->escape_string($this->strip_all($data['toll_free_calling_level'])));
				$toll_free_call_per_day = trim($this->escape_string($this->strip_all($data['toll_free_call_per_day'])));
				$toll_free_call_duration = trim($this->escape_string($this->strip_all($data['toll_free_call_duration'])));
				$toll_free_call_concurrency = trim($this->escape_string($this->strip_all($data['toll_free_call_concurrency'])));
				$toll_free_call_unit = trim($this->escape_string($this->strip_all($data['toll_free_call_unit'])));
				$toll_free_recording_required = trim($this->escape_string($this->strip_all($data['toll_free_recording_required'])));
				$toll_free_ct_required = trim($this->escape_string($this->strip_all($data['toll_free_ct_required'])));
				$toll_free_acd_required = trim($this->escape_string($this->strip_all($data['toll_free_acd_required'])));
				$toll_free_prompt_recording_required = trim($this->escape_string($this->strip_all($data['toll_free_prompt_recording_required'])));
				$toll_free_languages = trim($this->escape_string($this->strip_all($data['toll_free_languages'])));
				$toll_free_routing_required = trim($this->escape_string($this->strip_all($data['toll_free_routing_required'])));
				$toll_free_crm_integration_required = trim($this->escape_string($this->strip_all($data['toll_free_crm_integration_required'])));
				$toll_free_ivr_level = trim($this->escape_string($this->strip_all($data['toll_free_ivr_level'])));
				$toll_free_avg_hold_time_ivr = trim($this->escape_string($this->strip_all($data['toll_free_avg_hold_time_ivr'])));

				$this->query("insert into ".PREFIX."caf_toll_free_details(caf_id, toll_free_cug_type, toll_free_del_no, toll_free_billing_cycle, toll_free_pm_email, toll_free_connection_type, toll_free_parent_account, toll_free_rid, toll_free_wepbax_config, toll_free_addon_account, toll_free_service_type_wireline, toll_free_pilot_no, toll_free_did_count, toll_free_channel_count, toll_free_switch_name, toll_free_dial_code, toll_free_zone_id, toll_free_msgn_node, toll_free_d_channel, toll_free_sponsered_pri, toll_free_epabx_procured, toll_free_cost_epabx, toll_free_penalty_matrix, toll_free_contract_period_pri, toll_free_cost_pri_card, toll_free_vendor_name, toll_free_ebabx_make, toll_free_mis_entry, toll_free_calling_level, toll_free_call_per_day, toll_free_call_duration, toll_free_call_concurrency, toll_free_call_unit, toll_free_recording_required, toll_free_ct_required, toll_free_acd_required, toll_free_prompt_recording_required, toll_free_languages, toll_free_routing_required, toll_free_crm_integration_required, toll_free_ivr_level, toll_free_avg_hold_time_ivr) values('".$caf_id."', '".$toll_free_cug_type."', '".$toll_free_del_no."', '".$toll_free_billing_cycle."', '".$toll_free_pm_email."', '".$toll_free_connection_type."', '".$toll_free_parent_account."', '".$toll_free_rid."', '".$toll_free_wepbax_config."', '".$toll_free_addon_account."', '".$toll_free_service_type_wireline."', '".$toll_free_pilot_no."', '".$toll_free_did_count."', '".$toll_free_channel_count."', '".$toll_free_switch_name."', '".$toll_free_dial_code."', '".$toll_free_zone_id."', '".$toll_free_msgn_node."', '".$toll_free_d_channel."', '".$toll_free_sponsered_pri."', '".$toll_free_epabx_procured."', '".$toll_free_cost_epabx."', '".$toll_free_penalty_matrix."', '".$toll_free_contract_period_pri."', '".$toll_free_cost_pri_card."', '".$toll_free_vendor_name."', '".$toll_free_ebabx_make."', '".$toll_free_mis_entry."', '".$toll_free_calling_level."', '".$toll_free_call_per_day."', '".$toll_free_call_duration."', '".$toll_free_call_concurrency."', '".$toll_free_call_unit."', '".$toll_free_recording_required."', '".$toll_free_ct_required."', '".$toll_free_acd_required."', '".$toll_free_prompt_recording_required."', '".$toll_free_languages."', '".$toll_free_routing_required."', '".$toll_free_crm_integration_required."', '".$toll_free_ivr_level."', '".$toll_free_avg_hold_time_ivr."')");
			}
			else if($product=='SMS Solutions') {
				$sms_connection_type = trim($this->escape_string($this->strip_all($data['sms_connection_type'])));
				$sms_del_no = trim($this->escape_string($this->strip_all($data['sms_del_no'])));
				$sms_reserved_id = trim($this->escape_string($this->strip_all($data['sms_reserved_id'])));
				$sms_pm_email = trim($this->escape_string($this->strip_all($data['sms_pm_email'])));
				$sms_billing_cycle = trim($this->escape_string($this->strip_all($data['sms_billing_cycle'])));
				$sms_parent_account = trim($this->escape_string($this->strip_all($data['sms_parent_account'])));
				$sms_addon_account = trim($this->escape_string($this->strip_all($data['sms_addon_account'])));
				$sms_handset_id = trim($this->escape_string($this->strip_all($data['sms_handset_id'])));
				$sms_type = trim($this->escape_string($this->strip_all($data['sms_type'])));
				$sms_te_type = trim($this->escape_string($this->strip_all($data['sms_te_type'])));
				$sms_trai_id = trim($this->escape_string($this->strip_all($data['sms_trai_id'])));
				$sms_transactional_sender_id = trim($this->escape_string($this->strip_all($data['sms_transactional_sender_id'])));
				$sms_promotional_sender_id = trim($this->escape_string($this->strip_all($data['sms_promotional_sender_id'])));
				$sms_ip_address1 = trim($this->escape_string($this->strip_all($data['sms_ip_address1'])));
				$sms_ip_address2 = trim($this->escape_string($this->strip_all($data['sms_ip_address2'])));
				$sms_pull_url = trim($this->escape_string($this->strip_all($data['sms_pull_url'])));
				$sms_push_type = trim($this->escape_string($this->strip_all($data['sms_push_type'])));
				$sms_customer_server_location = trim($this->escape_string($this->strip_all($data['sms_customer_server_location'])));
				$sms_additional_maas_details = trim($this->escape_string($this->strip_all($data['sms_additional_maas_details'])));
				$sms_connectivity = trim($this->escape_string($this->strip_all($data['sms_connectivity'])));
				$sms_web_based_gui = trim($this->escape_string($this->strip_all($data['sms_web_based_gui'])));
				$sms_api_integration = trim($this->escape_string($this->strip_all($data['sms_api_integration'])));
				$sms_standard_reports = trim($this->escape_string($this->strip_all($data['sms_standard_reports'])));
				$sms_customization = trim($this->escape_string($this->strip_all($data['sms_customization'])));
				$sms_calling_level = trim($this->escape_string($this->strip_all($data['sms_calling_level'])));

				if(!empty($file['sms_ip_upload']['name'])) {
					/* $file_name = strtolower( pathinfo($file['sms_ip_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['sms_ip_upload']['name'], PATHINFO_EXTENSION));
					$sms_ip_upload = time().'7.'.$ext;
					move_uploaded_file($file['sms_ip_upload']['tmp_name'],$uploadDir.$sms_ip_upload); */
																	
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['sms_ip_upload']['name'], PATHINFO_EXTENSION));
					$sms_ip_upload = time().'7.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$sms_ip_upload, fopen($file['sms_ip_upload']['tmp_name'], 'rb'), 'public-read');
				}
				$this->query("insert into ".PREFIX."caf_sms_details(caf_id, sms_connection_type, sms_del_no, sms_reserved_id, sms_pm_email, sms_billing_cycle, sms_parent_account, sms_addon_account, sms_trai_id, sms_type, sms_te_type, sms_transactional_sender_id, sms_promotional_sender_id, sms_ip_address1, sms_ip_address2, sms_ip_upload, sms_pull_url, sms_push_type, sms_customer_server_location, sms_additional_maas_details, sms_connectivity, sms_web_based_gui, sms_api_integration, sms_standard_reports, sms_customization, sms_calling_level) values('".$caf_id."', '".$sms_connection_type."', '".$sms_del_no."', '".$sms_reserved_id."', '".$sms_pm_email."', '".$sms_billing_cycle."', '".$sms_parent_account."', '".$sms_addon_account."', '".$sms_trai_id."', '".$sms_type."', '".$sms_te_type."', '".$sms_transactional_sender_id."', '".$sms_promotional_sender_id."', '".$sms_ip_address1."', '".$sms_ip_address2."', '".$sms_ip_upload."', '".$sms_pull_url."', '".$sms_push_type."', '".$sms_customer_server_location."', '".$sms_additional_maas_details."', '".$sms_connectivity."', '".$sms_web_based_gui."', '".$sms_api_integration."', '".$sms_standard_reports."', '".$sms_customization."', '".$sms_calling_level."')");
			}
			else if($product=='SNS Solution') {
				$sns_present = trim($this->escape_string($this->strip_all($data['sns_present'])));
				$sns_type = trim($this->escape_string($this->strip_all($data['sns_type'])));
				$sns_calling_level = trim($this->escape_string($this->strip_all($data['sns_calling_level'])));
				$sns_switch_name = trim($this->escape_string($this->strip_all($data['sns_switch_name'])));
				$sns_dial_code = trim($this->escape_string($this->strip_all($data['sns_dial_code'])));
				$sns_zone = trim($this->escape_string($this->strip_all($data['sns_zone'])));
				$sns_zte_pnr = trim($this->escape_string($this->strip_all($data['sns_zte_pnr'])));
				$sns_msgn_node = trim($this->escape_string($this->strip_all($data['sns_msgn_node'])));

				$this->query("insert into ".PREFIX."caf_sns_details(caf_id, sns_present, sns_type, sns_calling_level, sns_switch_name, sns_dial_code, sns_zone, sns_zte_pnr, sns_msgn_node) values('".$caf_id."', '".$sns_present."', '".$sns_type."', '".$sns_calling_level."', '".$sns_switch_name."', '".$sns_dial_code."', '".$sns_zone."', '".$sns_zte_pnr."', '".$sns_msgn_node."')");
			}
			else if($product=='Tele Marketing- 140') {
				if($variant=='SIP') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
				else if($variant=='PRI') {
					$pri_cug_type = trim($this->escape_string($this->strip_all($data['pri_cug_type'])));
					$pri_connection_type = trim($this->escape_string($this->strip_all($data['pri_connection_type'])));
					$pri_billing_cycle = trim($this->escape_string($this->strip_all($data['pri_billing_cycle'])));
					$pri_pm_email = trim($this->escape_string($this->strip_all($data['pri_pm_email'])));
					$pri_parent_account = trim($this->escape_string($this->strip_all($data['pri_parent_account'])));
					$pri_addon_account = trim($this->escape_string($this->strip_all($data['pri_addon_account'])));
					$pri_rid = trim($this->escape_string($this->strip_all($data['pri_rid'])));
					$pri_del_no = trim($this->escape_string($this->strip_all($data['pri_del_no'])));
					$pri_wepbax_config = trim($this->escape_string($this->strip_all($data['pri_wepbax_config'])));
					$pri_service_type_wireline = trim($this->escape_string($this->strip_all($data['pri_service_type_wireline'])));
					$pri_pilot_no = trim($this->escape_string($this->strip_all($data['pri_pilot_no'])));
					$pri_did_count = trim($this->escape_string($this->strip_all($data['pri_did_count'])));
					$pri_channel_count = trim($this->escape_string($this->strip_all($data['pri_channel_count'])));
					$pri_switch_name = trim($this->escape_string($this->strip_all($data['pri_switch_name'])));
					$pri_dial_code = trim($this->escape_string($this->strip_all($data['pri_dial_code'])));
					$pri_zone_id = trim($this->escape_string($this->strip_all($data['pri_zone_id'])));
					$pri_msgn_node = trim($this->escape_string($this->strip_all($data['pri_msgn_node'])));
					$pri_d_channel = trim($this->escape_string($this->strip_all($data['pri_d_channel'])));
					$pri_sponsered = trim($this->escape_string($this->strip_all($data['pri_sponsered'])));
					$pri_epabx_procured = trim($this->escape_string($this->strip_all($data['pri_epabx_procured'])));
					$pri_cost_epabx = trim($this->escape_string($this->strip_all($data['pri_cost_epabx'])));
					$pri_penalty_matrix = trim($this->escape_string($this->strip_all($data['pri_penalty_matrix'])));
					$pri_contract_period = trim($this->escape_string($this->strip_all($data['pri_contract_period'])));
					$pri_cost_pri_card = trim($this->escape_string($this->strip_all($data['pri_cost_pri_card'])));
					$pri_vendor_name = trim($this->escape_string($this->strip_all($data['pri_vendor_name'])));
					$pri_ebabx_make = trim($this->escape_string($this->strip_all($data['pri_ebabx_make'])));
					$pri_mis_entry = trim($this->escape_string($this->strip_all($data['pri_mis_entry'])));
					$pri_calling_level = trim($this->escape_string($this->strip_all($data['pri_calling_level'])));
					$pri_hosted_ivr = trim($this->escape_string($this->strip_all($data['pri_hosted_ivr'])));
					$pri_hivr_no = trim($this->escape_string($this->strip_all($data['pri_hivr_no'])));
					$pri_type = trim($this->escape_string($this->strip_all($data['pri_type'])));

					$this->query("insert into ".PREFIX."caf_pri_details(caf_id, pri_cug_type, pri_del_no, pri_billing_cycle, pri_pm_email, pri_connection_type, pri_parent_account, pri_wepbax_config, pri_rid, pri_addon_account, pri_service_type_wireline, pri_pilot_no, pri_did_count, pri_switch_name, pri_dial_code, pri_zone_id, pri_msgn_node, pri_d_channel, pri_channel_count, pri_sponsered, pri_epabx_procured, pri_cost_epabx, pri_penalty_matrix, pri_contract_period, pri_cost_pri_card, pri_vendor_name, pri_ebabx_make, pri_mis_entry, pri_calling_level, pri_hosted_ivr, pri_hivr_no, pri_type) values('".$caf_id."', '".$pri_cug_type."', '".$pri_del_no."', '".$pri_billing_cycle."', '".$pri_pm_email."', '".$pri_connection_type."', '".$pri_parent_account."', '".$pri_wepbax_config."', '".$pri_rid."', '".$pri_addon_account."', '".$pri_service_type_wireline."', '".$pri_pilot_no."', '".$pri_did_count."', '".$pri_switch_name."', '".$pri_dial_code."', '".$pri_zone_id."', '".$pri_msgn_node."', '".$pri_d_channel."', '".$pri_channel_count."', '".$pri_sponsered."', '".$pri_epabx_procured."', '".$pri_cost_epabx."', '".$pri_penalty_matrix."', '".$pri_contract_period."', '".$pri_cost_pri_card."', '".$pri_vendor_name."', '".$pri_ebabx_make."', '".$pri_mis_entry."', '".$pri_calling_level."', '".$pri_hosted_ivr."', '".$pri_hivr_no."', '".$pri_type."')");
				}
			}
			else if($product=='Hosted OBD') {
				$hosted_obd_ivr = trim($this->escape_string($this->strip_all($data['hosted_obd_ivr'])));
				$hosted_obd_hivr_no = trim($this->escape_string($this->strip_all($data['hosted_obd_hivr_no'])));
				$hosted_obd_switch_type = trim($this->escape_string($this->strip_all($data['hosted_obd_switch_type'])));
				$hosted_obd_switch_details = trim($this->escape_string($this->strip_all($data['hosted_obd_switch_details'])));
				$hosted_obd_zone_id = trim($this->escape_string($this->strip_all($data['hosted_obd_zone_id'])));
				$hosted_obd_type = trim($this->escape_string($this->strip_all($data['hosted_obd_type'])));
				$hosted_obd_billing_cycle = trim($this->escape_string($this->strip_all($data['hosted_obd_billing_cycle'])));
				$hosted_obd_pm_email = trim($this->escape_string($this->strip_all($data['hosted_obd_pm_email'])));
				$hosted_obd_calling_level = trim($this->escape_string($this->strip_all($data['hosted_obd_calling_level'])));
				$hosted_obd_connection_type = trim($this->escape_string($this->strip_all($data['hosted_obd_connection_type'])));
				$hosted_obd_del_no = trim($this->escape_string($this->strip_all($data['hosted_obd_del_no'])));
				$hosted_obd_reserved_id = trim($this->escape_string($this->strip_all($data['hosted_obd_reserved_id'])));

				$this->query("insert into ".PREFIX."caf_hosted_obd_details(caf_id, hosted_obd_ivr, hosted_obd_hivr_no, hosted_obd_switch_type, hosted_obd_switch_details, hosted_obd_zone_id, hosted_obd_type, hosted_obd_billing_cycle, hosted_obd_pm_email, hosted_obd_connection_type, hosted_obd_del_no, hosted_obd_reserved_id, hosted_obd_parent_account, hosted_obd_addon_account, hosted_obd_calling_level) values('".$caf_id."', '".$hosted_obd_ivr."', '".$hosted_obd_hivr_no."', '".$hosted_obd_switch_type."', '".$hosted_obd_switch_details."', '".$hosted_obd_zone_id."', '".$hosted_obd_type."', '".$hosted_obd_billing_cycle."', '".$hosted_obd_pm_email."', '".$hosted_obd_connection_type."', '".$hosted_obd_del_no."', '".$hosted_obd_reserved_id."', '".$hosted_obd_parent_account."', '".$hosted_obd_addon_account."', '".$hosted_obd_calling_level."')");
			}
			else if($product=='Conferencing') {
				if($variant=='Audio Conferencing' || $variant=='Web Conferencing') {
					$audio_conf_pgi_landline_no = trim($this->escape_string($this->strip_all($data['audio_conf_pgi_landline_no'])));
					$audio_conf_del_number = trim($this->escape_string($this->strip_all($data['audio_conf_del_number'])));
					$this->query("insert into ".PREFIX."caf_audio_conf_details(caf_id, audio_conf_pgi_landline_no,audio_conf_del_no) values('".$caf_id."', '".$audio_conf_pgi_landline_no."', '".$audio_conf_del_number."')");
				}
			}
			
			// Service Enrollment
			
				$responseArr = array();
				$responseArr['caf_id'] = $caf_id;
				$responseArr['customer_form_sent'] = $customer_form_sent;
				$responseArr['email'] = $email;
				
				$emp_data = $this->getUniqueCafDetailsById($caf_id);
			if(isset($data['submit'])) 
			{	
				$loggedInUserDetailsArr = $this->sessionExists();
				$verification_link=md5($caf_id.'#'.$name);
				$unique_id = $emp_data['unique_id'];
				$this->query("update ".PREFIX."caf_details set verification_link='$verification_link' where id='".$caf_id."'");
				
				include_once("test_email.php");
				include_once("caf-verification-mail.inc.php");

				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true;
				$mail->AddAddress($loggedInUserDetailsArr['username']);
				//$mail->AddCC($loggedInUserDetailsArr['username']);
				if(!empty($sales_support_email)) { $mail->AddCC($sales_support_email); }
				if(!empty($additional_customer_email)) { $mail->AddCC($additional_customer_email); }
				
				$mail->IsHTML(true);
				$mail->Subject = "Thank you for choosing Tata Tele Business Services. Company Name:".$emp_data['company_name']." and CAF no ".$caf_no."";
				$mail->Body = $emailMsg;
				$mail->Send();
				$mail->SmtpClose();
				//echo $emailMsg;
				
				include_once("caf-verification-mail-sales.inc.php");
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true;
				$mail->AddAddress($loggedInUserDetailsArr['username']);
				$mail->IsHTML(true);
				//$mail->Subject = "CAF Status of ".$company_name;
				$mail->Subject = "Company Name:".$emp_data['company_name']." and CAF no ".$caf_no." received and has been forwarded for verification ";
				$mail->Body = $emailMsg;
				$mail->Send();
				//echo $emailMsg;
				
				$msg = "Your CAF $caf_no is currently under verification. For any queries contact 18002661800 Click http://knowmycustomer.in/send-otp-caf.php?q=$verification_link";
				$this->callsms($mobile,$msg,'Dcaf_Portal');
				
			}
			
			return $responseArr;
		}

		function updateCAFForm($data,$file) {
			
			//echo $file['fan_nmber_upload']['name'];exit;
			//echo $data['bill_plan_opted'];exit;
			$id = trim($this->escape_string($this->strip_all($data['uid'])));
			$olddata = $this->getUniqueCafDetailsById($id);
			
			//$id = empty($data['uid']) ? $olddata['id'] :  trim($this->escape_string($this->strip_all($data['uid']))) ;
			
			$company_name = empty($data['company_name']) ? $olddata['company_name'] : trim($this->escape_string($this->strip_all($data['company_name'])));
			
			//$company_name = trim($this->escape_string($this->strip_all($data['company_name'])));
			
			if(!empty($cin)){
				$cin = trim($this->escape_string($this->strip_all($data['cin'])));
			}else{
				$cin = "0";
			}
			
			$pan = empty($data['pan']) ? $olddata['pan'] : trim($this->escape_string($this->strip_all($data['pan'])));
			
			//$pan = trim($this->escape_string($this->strip_all($data['pan'])));
			
			if(!empty($logo_id)){
			$logo_id = trim($this->escape_string($this->strip_all($data['logo_id'])));
			}else{
			$logo_id = "0";	
			}
			
			$name = empty($data['name']) ? $olddata['name'] : trim($this->escape_string($this->strip_all($data['name'])));
			
			$osp = empty($data['osp']) ? $olddata['osp'] : trim($this->escape_string($this->strip_all($data['osp'])));
			//$osp = trim($this->escape_string($this->strip_all($data['osp'])));
			
			$title = empty($data['title']) ? $olddata['title'] : trim($this->escape_string($this->strip_all($data['title'])));
			//$title = trim($this->escape_string($this->strip_all($data['title'])));

			$sez_certificate_no = empty($data['sez_certificate_no']) ? $olddata['sez_certificate_no'] : trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			//$sez_certificate_no = trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			
			$nature_business = empty($data['nature_business']) ? $olddata['nature_business'] : trim($this->escape_string($this->strip_all($data['nature_business'])));
			//$nature_business = trim($this->escape_string($this->strip_all($data['nature_business'])));
			
			$aotherindustryvertical = empty($data['aotherindustryvertical']) ? $olddata['aotherindustryvertical'] : trim($this->escape_string($this->strip_all($data['aotherindustryvertical'])));
			//$aotherindustryvertical = trim($this->escape_string($this->strip_all($data['aotherindustryvertical'])));
			
			$no_of_employees = trim($this->escape_string($this->strip_all($data['no_of_employees'])));
			$no_of_branches = trim($this->escape_string($this->strip_all($data['no_of_branches'])));
			$turnover = trim($this->escape_string($this->strip_all($data['turnover'])));

			$salutation = empty($data['salutation']) ? $olddata['salutation'] : trim($this->escape_string($this->strip_all($data['salutation'])));
			//$salutation = trim($this->escape_string($this->strip_all($data['salutation'])));
			
			$designation = empty($data['designation']) ? $olddata['designation'] : trim($this->escape_string($this->strip_all($data['designation'])));
			//$designation = trim($this->escape_string($this->strip_all($data['designation'])));
			
			$anotherdesignation = empty($data['anotherdesignation']) ? $olddata['anotherdesignation'] : trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			//$anotherdesignation = trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			
			$email = empty($data['email']) ? $olddata['email'] : trim($this->escape_string($this->strip_all($data['email'])));
			//$email = trim($this->escape_string($this->strip_all($data['email'])));
			
			$mobile = empty($data['mobile']) ? $olddata['mobile'] : trim($this->escape_string($this->strip_all($data['mobile'])));
			//$mobile = trim($this->escape_string($this->strip_all($data['mobile'])));
			
			$telephone = empty($data['telephone']) ? $olddata['telephone'] : trim($this->escape_string($this->strip_all($data['telephone'])));
			//$telephone = trim($this->escape_string($this->strip_all($data['telephone'])));
			
			$aadhar_no = empty($data['aadhar_no']) ? $olddata['aadhar_no'] : trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			//$aadhar_no = trim($this->escape_string($this->strip_all($data['aadhar_no'])));

			$po_given = trim($this->escape_string($this->strip_all($data['po_given'])));
			if(empty($po_given)){
				$po_given = '';
			}
			
			//$title = empty($data['title']) ? $olddata['title'] : trim($this->escape_string($this->strip_all($data['title'])));
			$same_contact_person = trim($this->escape_string($this->strip_all($data['same_contact_person'])));
			if($data['same_contact_person']=='Yes') {
				$contact_person = $this->escape_string($this->strip_all($name));
				$contact_person_designation = $this->escape_string($this->strip_all($designation));
				$contact_person_email = $this->escape_string($this->strip_all($email));
				$contact_person_mobile = $this->escape_string($this->strip_all($mobile));
			} else {
				$contact_person = trim($this->escape_string($this->strip_all($data['contact_person'])));
				$contact_person_designation = trim($this->escape_string($this->strip_all($data['contact_person_designation'])));
				$contact_person_email = trim($this->escape_string($this->strip_all($data['contact_person_email'])));
				$contact_person_mobile = trim($this->escape_string($this->strip_all($data['contact_person_mobile'])));
			}
			if(!empty($data['address'])){$address = trim($this->escape_string($this->strip_all($data['address'])));}else{$address ="";}
			if(!empty($data['gst_no'])){$gst_no = trim($this->escape_string($this->strip_all($data['gst_no'])));}else{$gst_no ="";}
			if(!empty($data['state'])){$state = trim($this->escape_string($this->strip_all($data['state'])));}else{$state ="0";}
			if(!empty($data['city'])){$city = trim($this->escape_string($this->strip_all($data['city'])));}else{$city ="0";}
			if(!empty($data['pincode'])){$pincode = trim($this->escape_string($this->strip_all($data['pincode'])));}else{$pincode ="0";}
			if(!empty($data['alternate_gst_no'])){$alternate_gst_no = trim($this->escape_string($this->strip_all($data['alternate_gst_no'])));}else{$alternate_gst_no ="0";}
			
			$alternate_bdlg_no = trim($this->escape_string($this->strip_all($data['alternate_bdlg_no'])));
			$alternate_bdlg_name = trim($this->escape_string($this->strip_all($data['alternate_bdlg_name'])));
			$alternate_floor = trim($this->escape_string($this->strip_all($data['alternate_floor'])));
			$alternate_street_name = trim($this->escape_string($this->strip_all($data['alternate_street_name'])));
			$alternate_area = trim($this->escape_string($this->strip_all($data['alternate_area'])));
			$alternate_landmark = trim($this->escape_string($this->strip_all($data['alternate_landmark'])));
			$alternate_state = trim($this->escape_string($this->strip_all($data['alternate_state'])));
			$alternate_city = trim($this->escape_string($this->strip_all($data['alternate_city'])));
			$alternate_pincode = trim($this->escape_string($this->strip_all($data['alternate_pincode'])));
			//$alternate_multiple_installation_address = trim($this->escape_string($this->strip_all($data['alternate_multiple_installation_address'])));
			
			$same_billing_address = trim($this->escape_string($this->strip_all($data['same_billing_address'])));
			if($data['same_billing_address']=='Yes') {
				$billing_gst_no = $this->escape_string($this->strip_all($alternate_gst_no));
				$billing_bdlg_no = $this->escape_string($this->strip_all($alternate_bdlg_no));
				$billing_bdlg_name = $this->escape_string($this->strip_all($alternate_bdlg_name));
				$billing_floor = $this->escape_string($this->strip_all($alternate_floor));
				$billing_street_name = $this->escape_string($this->strip_all($alternate_street_name));
				$billing_area = $this->escape_string($this->strip_all($alternate_area));
				$billing_landmark = $this->escape_string($this->strip_all($alternate_landmark));
				$billing_state = $this->escape_string($this->strip_all($alternate_state));
				$billing_city = $this->escape_string($this->strip_all($alternate_city));
				$billing_pincode = $this->escape_string($this->strip_all($alternate_pincode));
			} else {
				$billing_gst_no = trim($this->escape_string($this->strip_all($data['billing_gst_no'])));
				$billing_bdlg_no = trim($this->escape_string($this->strip_all($data['billing_bdlg_no'])));
				$billing_bdlg_name = trim($this->escape_string($this->strip_all($data['billing_bdlg_name'])));
				$billing_floor = trim($this->escape_string($this->strip_all($data['billing_floor'])));
				$billing_street_name = trim($this->escape_string($this->strip_all($data['billing_street_name'])));
				$billing_area = trim($this->escape_string($this->strip_all($data['billing_area'])));
				$billing_landmark = trim($this->escape_string($this->strip_all($data['billing_landmark'])));
				$billing_state = trim($this->escape_string($this->strip_all($data['billing_state'])));
				$billing_city = trim($this->escape_string($this->strip_all($data['billing_city'])));
				$billing_pincode = trim($this->escape_string($this->strip_all($data['billing_pincode'])));
			}
			/* $uploadDir = 'caf-uploads/';
			if(!empty($file['image_name']['name'])) {
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				move_uploaded_file($file['image_name']['tmp_name'],$uploadDir.$image_name);
				$this->query("update ".PREFIX."caf_details set image_name='".$image_name."' where id='".$id."'");
			} */
			if(!empty($file['image_name']['name'])) {
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$image_name, fopen($file['image_name']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_details set image_name='".$image_name."' where id='".$id."'");
			}
			
			/* if(!empty($file['alternate_multiple_installation_address']['name'])) {
				//echo "here";exit;
				$file_name = strtolower( pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_EXTENSION));
				$alternate_multiple_installation_address = time().'01.'.$ext;
				move_uploaded_file($file['alternate_multiple_installation_address']['tmp_name'],$uploadDir.$alternate_multiple_installation_address);
				$this->query("update ".PREFIX."caf_details set alternate_multiple_installation_address='".$alternate_multiple_installation_address."' where id='".$id."'");
			} */
			if(!empty($file['alternate_multiple_installation_address']['name'])) {
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_EXTENSION));
				$alternate_multiple_installation_address = time().'01.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$alternate_multiple_installation_address, fopen($file['alternate_multiple_installation_address']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_details set alternate_multiple_installation_address='".$alternate_multiple_installation_address."' where id='".$id."'");
			}
			//update status
			$emp_data = $this->getUniqueCafDetailsById($id);
			/* if($emp_data['caf_status'] == "OE rejected"  && !isset($data['save_update'])){
				$this->query("update ".PREFIX."caf_details set caf_status='Pending with Customer' where id='".$id."'");
			}
			else if($emp_data['caf_status'] == "Pending with Sales" && !isset($data['save_update'])){
				$this->query("update ".PREFIX."caf_details set caf_status='Pending with OE' where id='".$id."'");
			} */
			//Caf Number generation
			if(!empty($alternate_state) && !empty($alternate_city) && $emp_data['caf_no'] == "0"){
				
				$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
			}else{
				$caf_no = $emp_data['caf_no'];
			}
			
			$detailselect = $this->query("select id from tata_caf_details WHERE caf_no='".$caf_no."'");
			$detailfetch = $this->fetch($detailselect);
			$new_id= $detailfetch['id'];
			
			$prodselect = $this->query("select * from tata_caf_product_details WHERE caf_id='".$new_id."'");
			$prodfetch = $this->fetch($prodselect);
			$product = $prodfetch['product'];
			$prodvariant = $prodfetch['variant'];
			
			 //new with EBA Bucket starts
			if($product == 'Internet Leased Line' || $product == 'Smart VPN' || $product == 'Leased Line' || $product == 'L2 Multicast Solution' || $product == 'Dark Fiber' || $prodvariant == 'Internet Leased Line'){
				if($emp_data['caf_status'] == "OE rejected" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with Customer' where id='".$id."'");
				}
				//newly added OE TO SALES & Vice Versa
				else if($emp_data['caf_status'] == "Pending with Sales" && $emp_data['eba_approval_status'] == "Approved" && $emp_data['approval_status'] == "Disapproved" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with OE' where id='".$id."'");
				}
				else if($emp_data['caf_status'] == "Pending with Sales" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with EBA' where id='".$id."'");
				}
				//newly added OE TO CUSTOMER & Vice Versa
				else if($emp_data['caf_status'] == "Pending with Customer" && $emp_data['eba_approval_status'] == "Approved" && $emp_data['approval_status'] == "Disapproved" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with OE' where id='".$id."'");
				}
				else if($emp_data['caf_status'] == "Pending with EBA" && $emp_data['eba_approval_status'] == "Approved" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with OE' where id='".$id."'");
				}
				//newly added EBA TO SALES & Vice Versa
				else if($emp_data['caf_status'] == "Pending for SRF correction" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with EBA' where id='".$id."'");
					$this->query("update ".PREFIX."caf_eba_rejection_data set eba_re_submission_datetime='".date('Y-m-d H:i:s')."' where id='".$emp_data['eba_rejection_id']."'");
				}
				//newly added EBA TO Customer & Vice Versa
				else if($emp_data['caf_status'] == "Pending with Customer for SRF correction" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with EBA' where id='".$id."'");
					$this->query("update ".PREFIX."caf_eba_rejection_data set eba_re_submission_datetime='".date('Y-m-d H:i:s')."' where id='".$emp_data['eba_rejection_id']."'");
					
				}
			}else{
				if($emp_data['caf_status'] == "OE rejected" && !isset($data['save_update'])){
				$this->query("update ".PREFIX."caf_details set caf_status='Pending with Customer' where id='".$id."'");
				}
				else if($emp_data['caf_status'] == "Pending with Sales" && !isset($data['save_update'])){
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with OE' where id='".$id."'");
				}
			}
			//new with EBA bucket ends
			
			
			if($emp_data['caf_status'] == "Pending with Sales" && $emp_data['form_flag'] == "DCAF_link" && $emp_data['created'] > "2018-05-07 12:33:32" && is_null($emp_data['linkcaf_submission_date']) == true){
				$this->query("update ".PREFIX."caf_details set linkcaf_submission_date='".date('Y-m-d H:i:s')."' where id='".$id."'");
			}
			
			if(isset($emp_data['rejection_id']) && $emp_data['caf_status'] == "Pending with Sales" && !isset($data['save_update'])){
				$this->query("update ".PREFIX."caf_rejection_data set order_re_submission_datetime='".date('Y-m-d H:i:s')."' where id='".$emp_data['rejection_id']."'");
				$this->query("update ".PREFIX."caf_details set rejection_id = '0' where id='".$id."'");
			}
			
			// else if($emp_data['caf_status'] == "Pending with Customer"){
				// $this->query("update ".PREFIX."caf_details set caf_status='Re Sub-Pending with COE' where id='".$id."'");
			// }
			
			if(!empty($data['dealer_detail_code'])){
				$dealer_detail_code = trim($this->escape_string($this->strip_all($data['dealer_detail_code'])));
			}else{
				$dealer_detail_code = '';
			}
			if(!empty($data['dealer_detail_name'])){
				$dealer_detail_name = trim($this->escape_string($this->strip_all($data['dealer_detail_name'])));
			}else{
				$dealer_detail_name = '';
			}
			$sales_support_email = trim($this->escape_string($this->strip_all($data['sales_support_email'])));
			$additional_customer_email = trim($this->escape_string($this->strip_all($data['additional_customer_email'])));
			$brm_email_id = trim($this->escape_string($this->strip_all($data['brm_email_id'])));
			$fos_code = trim($this->escape_string($this->strip_all($data['fos_code'])));
			
			$remark = trim($this->escape_string($this->strip_all($data['remark'])));
			$lbs_scheme = trim($this->escape_string($this->strip_all($data['lbs_scheme'])));
			
			$query = "update ".PREFIX."caf_details set caf_no='".$caf_no."', po_given = '".$po_given."', brm_email_id ='".$brm_email_id."',  sales_support_email ='".$sales_support_email."', additional_customer_email ='".$additional_customer_email."', dealer_detail_code ='".$dealer_detail_code."', fos_code ='".$fos_code."', dealer_detail_name='".$dealer_detail_name."', cin='".$cin."', company_name='".$company_name."', pan='".$pan."', logo_id='".$logo_id."', osp='".$osp."', title='".$title."', sez_certificate_no='".$sez_certificate_no."', nature_business='".$nature_business."', aotherindustryvertical='".$aotherindustryvertical."', no_of_employees='".$no_of_employees."', no_of_branches='".$no_of_branches."', turnover='".$turnover."', salutation='".$salutation."', name='".$name."', designation='".$designation."', anotherdesignation='".$anotherdesignation."', email='".$email."', mobile='".$mobile."', telephone='".$telephone."', aadhar_no='".$aadhar_no."', same_contact_person='".$same_contact_person."', contact_person='".$contact_person."', contact_person_designation='".$contact_person_designation."', contact_person_email='".$contact_person_email."', contact_person_mobile='".$contact_person_mobile."', address='".$address."', gst_no='".$gst_no."', state='".$state."', city='".$city."', pincode='".$pincode."', alternate_gst_no='".$alternate_gst_no."', alternate_bdlg_no='".$alternate_bdlg_no."', alternate_bdlg_name='".$alternate_bdlg_name."', alternate_floor='".$alternate_floor."', alternate_street_name='".$alternate_street_name."', alternate_area='".$alternate_area."', alternate_landmark='".$alternate_landmark."', alternate_state='".$alternate_state."', alternate_city='".$alternate_city."', alternate_pincode='".$alternate_pincode."', same_billing_address='".$same_billing_address."', billing_gst_no='".$billing_gst_no."', billing_bdlg_no='".$billing_bdlg_no."', billing_bdlg_name='".$billing_bdlg_name."', billing_floor='".$billing_floor."', billing_street_name='".$billing_street_name."', billing_area='".$billing_area."', billing_landmark='".$billing_landmark."', billing_state='".$billing_state."', billing_city='".$billing_city."', billing_pincode='".$billing_pincode."', remarks='".$remark."', epos_id = '".$lbs_scheme."' where id='".$id."'";
			$sql = $this->query($query);

			$caf_id = $id;
			
			if(!empty($remark)){
				$emp_data  = $this->fetch($this->query("select * from tata_caf_details where id='".$caf_id."'"));
				$remark_data  = $this->fetch($this->query("select * from ".PREFIX."caf_remarks where caf_id='".$caf_id."' order by id DESC limit 1"));
				if($remark_data['remarks'] != $emp_data['remarks']){					
					$loggedInUserDetailsArr = $this->getLoggedInUserDetails();	
					$emp_id = trim($this->escape_string($this->strip_all($loggedInUserDetailsArr['id'])));
					$remarkQuery = "insert into ".PREFIX."caf_remarks (caf_id, user_id, remarks) values ('".$caf_id."','".$emp_id."','".$remark."')";
					$remarkresult = $this->query($remarkQuery);
				}
			}

			// PRODUCT DETAILS
			$product = trim($this->escape_string($this->strip_all($data['product'])));
			$variant = trim($this->escape_string($this->strip_all($data['variant'])));
			$sub_variant = trim($this->escape_string($this->strip_all($data['sub_variant'])));
			$category = trim($this->escape_string($this->strip_all($data['category'])));
			$no_del = trim($this->escape_string($this->strip_all($data['no_del'])));
			$no_did = trim($this->escape_string($this->strip_all($data['no_did'])));
			$no_channel = trim($this->escape_string($this->strip_all($data['no_channel'])));
			$no_drop_locations = trim($this->escape_string($this->strip_all($data['no_drop_locations'])));
			$mobile_no = trim($this->escape_string($this->strip_all($data['mobile_no'])));
			$del_no = trim($this->escape_string($this->strip_all($data['del_no'])));
			$pilot_no = trim($this->escape_string($this->strip_all($data['pilot_no'])));
			$imsi_no = trim($this->escape_string($this->strip_all($data['imsi_no'])));
			$did_range = trim($this->escape_string($this->strip_all($data['did_range'])));
			$did_range_to = trim($this->escape_string($this->strip_all($data['did_range_to'])));
			//$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			
			if(!empty($product) && $product == "Internet Leased Line"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['ill_text_bandwidth'])));
			}else if(!empty($product) && $product == "Smart VPN"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['mpls_text_bandwidth'])));
			}else if(!empty($product) && $product == "SmartOffice"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['sill_text_bandwidth'])));
			}else if(!empty($product) && $product == "Enterprise Broadband"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['broadband_bandwidth'])));
			}else{
				$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			}
			
			$arc = empty($bandwidth) ? $prodfetch['bandwidth'] : trim($this->escape_string($this->strip_all($bandwidth)));
			
			$arc = empty($data['arc']) ? $prodfetch['arc'] : trim($this->escape_string($this->strip_all($data['arc'])));
			
			//$arc = trim($this->escape_string($this->strip_all($data['arc']))); 
			$arc_type = trim($this->escape_string($this->strip_all($data['arc_type'])));
			$monthly_rental = trim($this->escape_string($this->strip_all($data['monthly_rental'])));
			$nrc = trim($this->escape_string($this->strip_all($data['nrc'])));
			//$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));
			// echo $data['bill_plan_opted'];
			// echo $data['bill_plan_opted_select'];
			// exit;
			if(!empty($data['bill_plan_opted_select']) && $data['bill_plan_opted_select'] != 'Other'){
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted_select'])));
			}else{
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));	
			}
			// echo $bill_plan_opted;
			// exit;
			$lockin_period = trim($this->escape_string($this->strip_all($data['lockin_period'])));
			$security_deposit = trim($this->escape_string($this->strip_all($data['security_deposit'])));
			$activation_fee = trim($this->escape_string($this->strip_all($data['activation_fee'])));
			$traif_available = trim($this->escape_string($this->strip_all($data['traif_available'])));
			if($traif_available == 'Manual Input'){
				$trai_id = trim($this->escape_string($this->strip_all($data['trai_id'])));
			}else if($traif_available == 'NA'){
				$trai_id = 'NA';
			}else{
				$trai_id = 'Not Registered';
			}
			//done by dhanashree
			$rack_type = trim($this->escape_string($this->strip_all($data['rack_type'])));
			$sales_type = trim($this->escape_string($this->strip_all($data['sales_type'])));
			$billing_frequency = trim($this->escape_string($this->strip_all($data['billing_frequency'])));
			//Done by Dhanashree
			//$billing_type = trim($this->escape_string($this->strip_all($data['billing_type'])));
			if($data['billing_type']){
				$billing_type = trim($this->escape_string($this->strip_all($data['billing_type'])));
			}else{
				$billing_type = "Arrears";
			}
			$bill_mode = trim($this->escape_string($this->strip_all($data['bill_mode'])));
			$cug_id = trim($this->escape_string($this->strip_all($data['cug_id'])));

			$results =$this->query("select * from tata_caf_product_details where caf_id = '".$caf_id."'");
			if($this->num_rows($results) > 0){
			$query = "update ".PREFIX."caf_product_details set product='".$product."', variant='".$variant."', sub_variant='".$sub_variant."', category='".$category."', no_del='".$no_del."', no_did='".$no_did."', no_channel='".$no_channel."', no_drop_locations='".$no_drop_locations."', mobile_no='".$mobile_no."', del_no='".$del_no."', pilot_no='".$pilot_no."', imsi_no='".$imsi_no."', did_range='".$did_range."', did_range_to='".$did_range_to."', bandwidth='".$bandwidth."', arc='".$arc."', arc_type='".$arc_type."', monthly_rental='".$monthly_rental."', nrc='".$nrc."', bill_plan_opted='".$bill_plan_opted."', lockin_period='".$lockin_period."', security_deposit='".$security_deposit."', activation_fee='".$activation_fee."', trai_id='".$trai_id."', billing_frequency='".$billing_frequency."', billing_type='".$billing_type."', bill_mode='".$bill_mode."', cug_id='".$cug_id."', rack_type='".$rack_type."', sales_type='".$sales_type."', trai_available = '".$traif_available."' where caf_id='".$caf_id."'";
			$this->query($query);
			}
			
			else{
			$query = "insert into ".PREFIX."caf_product_details (caf_id, product, variant, sub_variant, category, no_del, no_did, no_channel, no_drop_locations, mobile_no, del_no, pilot_no, imsi_no, did_range, did_range_to, bandwidth, arc, arc_type, monthly_rental, nrc, bill_plan_opted, lockin_period, security_deposit, activation_fee, trai_available, trai_id, billing_frequency, billing_type, bill_mode, cug_id, rack_type, sales_type) values('".$caf_id."','".$product."','".$variant."','".$sub_variant."','".$category."','".$no_del."','".$no_did."','".$no_channel."','".$no_drop_locations."','".$mobile_no."','".$del_no."','".$pilot_no."','".$imsi_no."','".$did_range."','".$did_range_to."','".$bandwidth."','".$arc."','".$arc_type."','".$monthly_rental."','".$nrc."','".$bill_plan_opted."','".$lockin_period."','".$security_deposit."','".$activation_fee."', '".$traif_available."', '".$trai_id."','".$billing_frequency."','".$billing_type."','".$bill_mode."','".$cug_id."','".$rack_type."','".$sales_type."')";
			$this->query($query);
			}
			
			/* if(!empty($file['del_sheet']['name'])) {
				$file_name = strtolower( pathinfo($file['del_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['del_sheet']['name'], PATHINFO_EXTENSION));
				$del_sheet = time().'2.'.$ext;
				move_uploaded_file($file['del_sheet']['tmp_name'],$uploadDir.$del_sheet);
				$this->query("update ".PREFIX."caf_product_details set del_sheet='".$del_sheet."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['del_sheet']['name'])) {
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['del_sheet']['name'], PATHINFO_EXTENSION));
				$del_sheet = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$del_sheet, fopen($file['del_sheet']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_product_details set del_sheet='".$del_sheet."' where caf_id='".$caf_id."'");
			}
			// PRODUCT DETAILS

			// DOCUMENT DETAILS
			$registration_document_type = trim($this->escape_string($this->strip_all($data['registration_document_type'])));
			$registration_document_type_other = trim($this->escape_string($this->strip_all($data['registration_document_type_other'])));
			 $registration_document_no = trim($this->escape_string($this->strip_all($data['registration_document_no'])));
			$registration_place_issue = trim($this->escape_string($this->strip_all($data['registration_place_issue'])));
			$registration_issuing_authority = trim($this->escape_string($this->strip_all($data['registration_issuing_authority'])));
			$registration_issuing_date = trim($this->escape_string($this->strip_all($data['registration_issuing_date'])));
			$registration_expiry_date = trim($this->escape_string($this->strip_all($data['registration_expiry_date'])));
			$reg_caf_number = trim($this->escape_string($this->strip_all($data['reg-caf-number'])));
			$address_document_type = trim($this->escape_string($this->strip_all($data['address_document_type'])));
			$address_document_type_other = trim($this->escape_string($this->strip_all($data['address_document_type_other'])));
			$address_document_no = trim($this->escape_string($this->strip_all($data['address_document_no'])));
			$address_place_issue = trim($this->escape_string($this->strip_all($data['address_place_issue'])));
			$address_issuing_authority = trim($this->escape_string($this->strip_all($data['address_issuing_authority'])));
			$address_issuing_date = trim($this->escape_string($this->strip_all($data['address_issuing_date'])));
			$address_expiry_date = trim($this->escape_string($this->strip_all($data['address_expiry_date'])));
			$add_caf_number = trim($this->escape_string($this->strip_all($data['add-caf-number'])));
			$identity_document_type = trim($this->escape_string($this->strip_all($data['identity_document_type'])));
			$identity_document_type_other = trim($this->escape_string($this->strip_all($data['identity_document_type_other'])));
			$identity_document_no = trim($this->escape_string($this->strip_all($data['identity_document_no'])));
			$identity_place_issue = trim($this->escape_string($this->strip_all($data['identity_place_issue'])));
			$identity_issuing_authority = trim($this->escape_string($this->strip_all($data['identity_issuing_authority'])));
			$identity_issuing_date = trim($this->escape_string($this->strip_all($data['identity_issuing_date'])));
			$identity_expiry_date = trim($this->escape_string($this->strip_all($data['identity_expiry_date'])));
			$iden_caf_number = trim($this->escape_string($this->strip_all($data['iden-caf-number'])));
			$authorisation_document_type = trim($this->escape_string($this->strip_all($data['authorisation_document_type'])));
			$authorisation_document_type_other = trim($this->escape_string($this->strip_all($data['authorisation_document_type_other'])));
			$authorisation_document_no = trim($this->escape_string($this->strip_all($data['authorisation_document_no'])));
			$authorisation_place_issue = trim($this->escape_string($this->strip_all($data['authorisation_place_issue'])));
			$authorisation_issuing_authority = trim($this->escape_string($this->strip_all($data['authorisation_issuing_authority'])));
			$authorisation_issuing_date = trim($this->escape_string($this->strip_all($data['authorisation_issuing_date'])));
			$authorisation_expiry_date = trim($this->escape_string($this->strip_all($data['authorisation_expiry_date'])));
			$auth_caf_number = trim($this->escape_string($this->strip_all($data['auth-caf-number'])));
			
			//new
			$installation_document_type = trim($this->escape_string($this->strip_all($data['installation_document_type'])));
			$installation_document_type_other = trim($this->escape_string($this->strip_all($data['installation_document_type_other'])));
			$installation_document_no = trim($this->escape_string($this->strip_all($data['installation_document_no'])));
			$installation_place_issue = trim($this->escape_string($this->strip_all($data['installation_place_issue'])));
			$installation_issuing_authority = trim($this->escape_string($this->strip_all($data['installation_issuing_authority'])));
			$installation_issuing_date = trim($this->escape_string($this->strip_all($data['installation_issuing_date'])));
			$installation_expiry_date = trim($this->escape_string($this->strip_all($data['installation_expiry_date'])));
			$install_caf_number = trim($this->escape_string($this->strip_all($data['install-caf-number'])));
			
			$tef_download = trim($this->escape_string($this->strip_all($data['tef_download'])));
			
			$documentData_sql = $this->query("select * from ".PREFIX."caf_document_details where caf_id='".$caf_id."'");
			
			if($this->num_rows($documentData_sql) > 0) {
			
			//new by dhanashree*/
			$query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', registration_document_no='".$registration_document_no."', registration_place_issue='".$registration_place_issue."', registration_issuing_authority='".$registration_issuing_authority."', registration_issuing_date='".$registration_issuing_date."', registration_expiry_date='".$registration_expiry_date."',reg_caf_number='".$reg_caf_number."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', address_document_no='".$address_document_no."', address_place_issue='".$address_place_issue."', address_issuing_authority='".$address_issuing_authority."', address_issuing_date='".$address_issuing_date."', address_expiry_date='".$address_expiry_date."',add_caf_number='".$add_caf_number."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', identity_document_no='".$identity_document_no."', identity_place_issue='".$identity_place_issue."', identity_issuing_authority='".$identity_issuing_authority."', identity_issuing_date='".$identity_issuing_date."', identity_expiry_date='".$identity_expiry_date."',identity_caf_number='".$iden_caf_number."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."', authorisation_document_no='".$authorisation_document_no."', authorisation_place_issue='".$authorisation_place_issue."', authorisation_issuing_authority='".$authorisation_issuing_authority."', authorisation_issuing_date='".$authorisation_issuing_date."', authorisation_expiry_date='".$authorisation_expiry_date."',auth_caf_number='".$auth_caf_number."', installation_document_type='".$installation_document_type."', installation_document_type_other='".$installation_document_type_other."', installation_document_no='".$installation_document_no."',installation_issuing_authority='".$installation_issuing_authority."', installation_place_issue='".$installation_place_issue."', installation_issuing_date='".$installation_issuing_date."', installation_expiry_date='".$installation_expiry_date."',install_caf_number='".$install_caf_number."' where caf_id='".$caf_id."'";
			$this->query($query);
			
			}else{
			
			$query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, registration_document_no, registration_place_issue, registration_issuing_authority, registration_issuing_date, registration_expiry_date, registration_document, reg_caf_number, address_document_type, address_document_type_other, address_document_no, address_place_issue, address_issuing_authority, address_issuing_date, address_expiry_date, address_document, add_caf_number, identity_document_type, identity_document_type_other, identity_document_no, identity_place_issue, identity_issuing_authority, identity_issuing_date, identity_expiry_date, identity_document, identity_caf_number, authorisation_document_type, authorisation_document_type_other, authorisation_document_no, authorisation_place_issue, authorisation_issuing_authority, authorisation_issuing_date, authorisation_expiry_date, auth_caf_number, installation_document_type, installation_document_type_other, installation_document_no, installation_issuing_authority, installation_place_issue, installation_issuing_date, installation_expiry_date, install_caf_number) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$registration_document_no."', '".$registration_place_issue."', '".$registration_issuing_authority."', '".$registration_issuing_date."', '".$registration_expiry_date."', '".$registration_document."', '".$reg_caf_number."', '".$address_document_type."', '".$address_document_type_other."', '".$address_document_no."', '".$address_place_issue."', '".$address_issuing_authority."', '".$address_issuing_date."', '".$address_expiry_date."', '".$address_document."', '".$add_caf_number."', '".$identity_document_type."', '".$identity_document_type_other."', '".$identity_document_no."', '".$identity_place_issue."', '".$identity_issuing_authority."', '".$identity_issuing_date."', '".$identity_expiry_date."', '".$identity_document."', '".$iden_caf_number."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$authorisation_document_no."', '".$authorisation_place_issue."', '".$authorisation_issuing_authority."', '".$authorisation_issuing_date."', '".$authorisation_expiry_date."', '".$auth_caf_number."','".$installation_document_type."', '".$installation_document_type_other."', '".$installation_document_no."', '".$installation_issuing_authority."', '".$installation_place_issue."','".$installation_issuing_date."', '".$installation_expiry_date."', '".$install_caf_number."')";
			$this->query($query);
				
			}
			
			//echo $query;exit;
			/* if(!empty($file['registration_document']['name'])) {
				$file_name = strtolower( pathinfo($file['registration_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['registration_document']['name'], PATHINFO_EXTENSION));
				$registration_document = time().'21.'.$ext;
				move_uploaded_file($file['registration_document']['tmp_name'],$uploadDir.$registration_document);
				$this->query("update ".PREFIX."caf_document_details set registration_document='".$registration_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['address_document']['name'])) {
				$file_name = strtolower( pathinfo($file['address_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['address_document']['name'], PATHINFO_EXTENSION));
				$address_document = time().'22.'.$ext;
				move_uploaded_file($file['address_document']['tmp_name'],$uploadDir.$address_document);
				$this->query("update ".PREFIX."caf_document_details set address_document='".$address_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['identity_document']['name'])) {
				$file_name = strtolower( pathinfo($file['identity_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['identity_document']['name'], PATHINFO_EXTENSION));
				$identity_document = time().'23.'.$ext;
				move_uploaded_file($file['identity_document']['tmp_name'],$uploadDir.$identity_document);
				$this->query("update ".PREFIX."caf_document_details set identity_document='".$identity_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['authorisation_document']['name'])) {
				$file_name = strtolower( pathinfo($file['authorisation_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['authorisation_document']['name'], PATHINFO_EXTENSION));
				$authorisation_document = time().'24.'.$ext;
				move_uploaded_file($file['authorisation_document']['tmp_name'],$uploadDir.$authorisation_document);
				$this->query("update ".PREFIX."caf_document_details set authorisation_document='".$authorisation_document."' where caf_id='".$caf_id."'");
			} */
			$currentdatetime = date("Y-m-d H:i:s");
			//print_r ($file['tef_download']['name']);exit;
			/* if(!empty($file['tef_download']['name'])) {
				$file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download);
				$this->query("update ".PREFIX."caf_document_details set tef_download='".$tef_download."',tef_download_date='".$currentdatetime."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['tef_download']['name'])) {				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tef_download='".$tef_download."',tef_download_date='".$currentdatetime."' where caf_id='".$caf_id."'");
				
			}
			if(!empty($file['tef_download1']['name'])) {
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download1']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download1']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tef_download='".$tef_download."',tef_download_date='".$currentdatetime."' where caf_id='".$caf_id."'");
				
			}

			/* if(!empty($file['tm_download']['name'])) {
				$file_name = strtolower( pathinfo($file['tm_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				move_uploaded_file($file['tm_download']['tmp_name'],$uploadDir.$tm_download);
				$this->query("update ".PREFIX."caf_document_details set tm_download='".$tm_download."',tm_download_date ='".$currentdatetime."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['tm_download']['name'])) {
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tm_download, fopen($file['tm_download']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tm_download='".$tm_download."',tm_download_date ='".$currentdatetime."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['trai_form']['name'])) {
				$file_name = strtolower( pathinfo($file['trai_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				move_uploaded_file($file['trai_form']['tmp_name'],$uploadDir.$trai_form);
				$this->query("update ".PREFIX."caf_document_details set trai_form='".$trai_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['trai_form']['name'])) {				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$trai_form, fopen($file['trai_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set trai_form='".$trai_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['dd_form']['name'])) {
				$file_name = strtolower( pathinfo($file['dd_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				move_uploaded_file($file['dd_form']['tmp_name'],$uploadDir.$dd_form);
				$this->query("update ".PREFIX."caf_document_details set dd_form='".$dd_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['dd_form']['name'])) {				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$dd_form, fopen($file['dd_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set dd_form='".$dd_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['osp_form']['name'])) {
				$file_name = strtolower( pathinfo($file['osp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				move_uploaded_file($file['osp_form']['tmp_name'],$uploadDir.$osp_form);
				$this->query("update ".PREFIX."caf_document_details set osp_form='".$osp_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['osp_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$osp_form, fopen($file['osp_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set osp_form='".$osp_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['sez_form']['name'])) {
				$file_name = strtolower( pathinfo($file['sez_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				move_uploaded_file($file['sez_form']['tmp_name'],$uploadDir.$sez_form);
				$this->query("update ".PREFIX."caf_document_details set sez_form='".$sez_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['sez_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$sez_form, fopen($file['sez_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set sez_form='".$sez_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['bulk_form']['name'])) {
				$file_name = strtolower( pathinfo($file['bulk_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				move_uploaded_file($file['bulk_form']['tmp_name'],$uploadDir.$bulk_form);
				$this->query("update ".PREFIX."caf_document_details set bulk_form='".$bulk_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['bulk_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$bulk_form, fopen($file['bulk_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set bulk_form='".$bulk_form."' where caf_id='".$caf_id."'");
			}
			//done by dhanashree*/
			/* if(!empty($file['billing_form']['name'])) {
				$file_name = strtolower( pathinfo($file['billing_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				move_uploaded_file($file['billing_form']['tmp_name'],$uploadDir.$billing_form);
				$this->query("update ".PREFIX."caf_document_details set billing_form='".$billing_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['billing_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$billing_form, fopen($file['billing_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set billing_form='".$billing_form."' where caf_id='".$caf_id."'");
			}
			/* if(!empty($file['gst_form']['name'])) {
				$file_name = strtolower( pathinfo($file['gst_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				move_uploaded_file($file['gst_form']['tmp_name'],$uploadDir.$gst_form);
				$this->query("update ".PREFIX."caf_document_details set gst_form='".$gst_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['gst_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$gst_form, fopen($file['gst_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set gst_form='".$gst_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['all_form']['name'])) {
				$file_name = strtolower( pathinfo($file['all_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				move_uploaded_file($file['all_form']['tmp_name'],$uploadDir.$all_form);
				$this->query("update ".PREFIX."caf_document_details set all_form='".$all_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['all_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$all_form, fopen($file['all_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set all_form='".$all_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['logical_form']['name'])) {
				$file_name = strtolower( pathinfo($file['logical_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				move_uploaded_file($file['logical_form']['tmp_name'],$uploadDir.$logical_form);
				$this->query("update ".PREFIX."caf_document_details set logical_form='".$logical_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['logical_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$logical_form, fopen($file['logical_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set logical_form='".$logical_form."' where caf_id='".$caf_id."'");
			}

			/* if(!empty($file['stc_form']['name'])) {
				$file_name = strtolower( pathinfo($file['stc_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				move_uploaded_file($file['stc_form']['tmp_name'],$uploadDir.$stc_form);
				$this->query("update ".PREFIX."caf_document_details set stc_form='".$stc_form."' where caf_id='".$caf_id."'");
			} */
			if(!empty($file['stc_form']['name'])) {
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$stc_form, fopen($file['stc_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set stc_form='".$stc_form."' where caf_id='".$caf_id."'");
			}

			// $query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', registration_document_no='".$registration_document_no."', registration_place_issue='".$registration_place_issue."', registration_issuing_authority='".$registration_issuing_authority."', registration_issuing_date='".$registration_issuing_date."', registration_expiry_date='".$registration_expiry_date."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', address_document_no='".$address_document_no."', address_place_issue='".$address_place_issue."', address_issuing_authority='".$address_issuing_authority."', address_issuing_date='".$address_issuing_date."', address_expiry_date='".$address_expiry_date."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', identity_document_no='".$identity_document_no."', identity_place_issue='".$identity_place_issue."', identity_issuing_authority='".$identity_issuing_authority."', identity_issuing_date='".$identity_issuing_date."', identity_expiry_date='".$identity_expiry_date."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."', authorisation_document_no='".$authorisation_document_no."', authorisation_place_issue='".$authorisation_place_issue."', authorisation_issuing_authority='".$authorisation_issuing_authority."', authorisation_issuing_date='".$authorisation_issuing_date."', authorisation_expiry_date='".$authorisation_expiry_date."' where caf_id='".$caf_id."'";
			
			
			$c=30;

			$documentResultRS = $this->getCAFRegistrationDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['registration_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_registration_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['registration_document']['name'] as $key=>$value) {
				if(!empty($data['registration_id'][$key])) {
					$registration_id = $this->escape_string($this->strip_all($data['registration_id'][$key]));
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'][$key], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_registration_document set document='".$registration_document."' where id='".$registration_id."'");
					}
				} else {
					$registration_document = '';
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'][$key], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_registration_document(caf_id, document) values ('$caf_id', '$registration_document')");
				}
			}

			$documentResultRS = $this->getCAFAddressDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['address_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_address_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['address_document']['name'] as $key=>$value) {
				if(!empty($data['address_id'][$key])) {
					$address_id = $this->escape_string($this->strip_all($data['address_id'][$key]));
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'][$key], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_address_document set document='".$address_document."' where id='".$address_id."'");
					}
				} else {
					$address_document = '';
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'][$key], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_address_document(caf_id, document) values ('$caf_id', '$address_document')");
				}
			}

			$documentResultRS = $this->getCAFIdentityDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['identity_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_identity_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['identity_document']['name'] as $key=>$value) {
				if(!empty($data['identity_id'][$key])) {
					$identity_id = $this->escape_string($this->strip_all($data['identity_id'][$key]));
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'][$key], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_identity_document set document='".$identity_document."' where id='".$identity_id."'");
					}
				} else {
					$identity_document = '';
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'][$key], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_identity_document(caf_id, document) values ('$caf_id', '$identity_document')");
				}
			}

			$documentResultRS = $this->getCAFAuthDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['authorisation_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_authorisation_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['authorisation_document']['name'] as $key=>$value) {
				if(!empty($data['authorisation_id'][$key])) {
					$authorisation_id = $this->escape_string($this->strip_all($data['authorisation_id'][$key]));
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'][$key], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_authorisation_document set document='".$authorisation_document."' where id='".$authorisation_id."'");
					}
				} else {
					$authorisation_document = '';
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'][$key], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_authorisation_document(caf_id, document) values ('$caf_id', '$authorisation_document')");
				}
			}
			
			//new
			$documentResultRS = $this->getCAFInstallationDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['installation_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_installation_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['installation_document']['name'] as $key=>$value) {
				if(!empty($data['installation_id'][$key])) {
					$installation_id = $this->escape_string($this->strip_all($data['installation_id'][$key]));
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'][$key], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_installation_document set document='".$installation_document."' where id='".$installation_id."'");
					}
				} else {
					$installation_document = '';
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'][$key], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_installation_document(caf_id, document) values ('$caf_id', '$installation_document')");
				}
			}
			//for other
			$documentResultRS = $this->getCAFOtherDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['other_form_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_other_document where id='".$documentResult['id']."' and doc_approved!='1' ");
				}
			}
			foreach($file['other_form']['name'] as $key=>$value) {
				if(!empty($data['other_form_id'][$key])) {
					$other_form_id = $this->escape_string($this->strip_all($data['other_form_id'][$key]));
					if(!empty($file['other_form']['name'][$key])) {
						$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'][$key], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_other_document set document='".$other_form."' where id='".$other_form_id."'");
					}
				} else {
					$other_form = '';
					if(!empty($file['other_form']['name'][$key])) {
						$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'][$key], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_other_document(caf_id, document) values ('$caf_id', '$other_form')");
				}
			}


			// DOCUMENT DETAILS

			// OTHER DETAILS
			$mobile_connection1 = trim($this->escape_string($this->strip_all($data['mobile_connection1'])));
			$mobile_connection2 = trim($this->escape_string($this->strip_all($data['mobile_connection2'])));
			$mobile_connection3 = trim($this->escape_string($this->strip_all($data['mobile_connection3'])));
			$mobile_connection4 = trim($this->escape_string($this->strip_all($data['mobile_connection4'])));
			$no_connection1 = trim($this->escape_string($this->strip_all($data['no_connection1'])));
			$no_connection2 = trim($this->escape_string($this->strip_all($data['no_connection2'])));
			$no_connection3 = trim($this->escape_string($this->strip_all($data['no_connection3'])));
			$no_connection4 = trim($this->escape_string($this->strip_all($data['no_connection4'])));
			$mobile_connection_total = trim($this->escape_string($this->strip_all($data['mobile_connection_total'])));
			$is_mnp = trim($this->escape_string($this->strip_all($data['is_mnp'])));
			$upc_code = trim($this->escape_string($this->strip_all($data['upc_code'])));
			$upc_code_date = trim($this->escape_string($this->strip_all($data['upc_code_date'])));
			$existing_operator = trim($this->escape_string($this->strip_all($data['existing_operator'])));
			$porting_imsi_no = trim($this->escape_string($this->strip_all($data['porting_imsi_no'])));
			/* $payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'])));
			$payment_type = trim($this->escape_string($this->strip_all($data['payment_type']))); */
			// if($payment_mode != 'Cash') {
				$paymentDetailsRS = $this->getCAFPaymentDetailsByCAFId($caf_id);
				while($paymentDetails = $this->fetch($paymentDetailsRS)){
					if(!in_array($paymentDetails['id'],$data['payment_id'])){
						$this->query("delete from ".PREFIX."caf_payment_details where id='".$paymentDetails['id']."'");
					}
				}
				foreach($data['payment_type'] as $key=>$value) {
					$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'][$key])));
					$payment_type = trim($this->escape_string($this->strip_all($data['payment_type'][$key])));
					$bank_name = trim($this->escape_string($this->strip_all($data['bank_name'][$key])));
					$bank_acc_no = trim($this->escape_string($this->strip_all($data['bank_acc_no'][$key])));
					$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
					$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
					$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
					if(!empty($data['payment_id'][$key])) {
						$payment_id = $this->escape_string($this->strip_all($data['payment_id'][$key]));
						$this->query("update ".PREFIX."caf_payment_details set payment_mode='".$payment_mode."', payment_type='".$payment_type."', bank_name='".$bank_name."', bank_acc_no='".$bank_acc_no."', branch_address='".$branch_address."', transactional_details='".$transactional_details."', transaction_amount='".$transaction_amount."' where id='".$payment_id."'");
					} else {
						$this->query("insert into ".PREFIX."caf_payment_details(caf_id, payment_mode, payment_type, bank_name, bank_acc_no, branch_address, transactional_details, transaction_amount) values ('".$caf_id."', '".$payment_mode."', '".$payment_type."', '".$bank_name."', '".$bank_acc_no."', '".$branch_address."', '".$transactional_details."', '".$transaction_amount."')");
					}
				}
			// }
			$grand_amount = trim($this->escape_string($this->strip_all($data['grand_amount'])));

			if($is_mnp=='Yes' and !empty($file['mnp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['mnp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				move_uploaded_file($file['mnp_form']['tmp_name'],$uploadDir.$mnp_form); */
										
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$mnp_form, fopen($file['mnp_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_other_details set mnp_form='".$mnp_form."' where caf_id='".$caf_id."'");
			}

			$query = "update ".PREFIX."caf_other_details set mobile_connection1='".$mobile_connection1."', mobile_connection2='".$mobile_connection2."', mobile_connection3='".$mobile_connection3."', mobile_connection4='".$mobile_connection4."', no_connection1='".$no_connection1."', no_connection2='".$no_connection2."', no_connection3='".$no_connection3."', no_connection4='".$no_connection4."', mobile_connection_total='".$mobile_connection_total."', is_mnp='".$is_mnp."', upc_code='".$upc_code."', upc_code_date='".$upc_code_date."', existing_operator='".$existing_operator."', porting_imsi_no='".$porting_imsi_no."', grand_amount='".$grand_amount."' where caf_id='".$caf_id."'";
			$this->query($query);
			// OTHER DETAILS

			// Service Enrollment
			$this->query("delete from ".PREFIX."caf_ill_details	where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mpls_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mpls_express_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mpls_rw_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_dlc_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_nplc_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_l2mc_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_photon_dongal_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_photon_wifi_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_photon_mifi_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_pri_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_sip_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_standard_centrex_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_ip_centrex_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_standard_wrln_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_wepbax_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_ibs_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_walky_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mobile_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_lbs_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_crs_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_toll_free_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_sms_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_sns_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_hosted_obd_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_audio_conf_details where caf_id='$caf_id'");

			// Service Enrollment
			$tar_id = trim($this->escape_string($this->strip_all($data['tar_id'])));
			
			$market_segment = trim($this->escape_string($this->strip_all($data['market_segment'])));
			$dealer_code = trim($this->escape_string($this->strip_all($data['dealer_code'])));
			$brm_code = trim($this->escape_string($this->strip_all($data['brm_code'])));
			$po_upload_date = date("Y-m-d H:i:s");
			if(!empty($file['po_upload']['name'])) {
				/* $file_name = strtolower( pathinfo($file['po_upload']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				move_uploaded_file($file['po_upload']['tmp_name'],$uploadDir.$po_upload); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$po_upload, fopen($file['po_upload']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_details set po_upload='".$po_upload."', po_upload_date='".$po_upload_date."' where id='".$caf_id."'");
			}
			if(!empty($file['other_approval_docs']['name'])) {
				/* $file_name = strtolower( pathinfo($file['other_approval_docs']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['other_approval_docs']['name'], PATHINFO_EXTENSION));
				$other_approval_docs = time().'4.'.$ext;
				move_uploaded_file($file['other_approval_docs']['tmp_name'],$uploadDir.$other_approval_docs); */
										
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['other_approval_docs']['name'], PATHINFO_EXTENSION));
				$other_approval_docs = time().'4.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$other_approval_docs, fopen($file['other_approval_docs']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_details set other_approval_docs='".$other_approval_docs."' where id='".$caf_id."'");
			}
			
			$this->query("update ".PREFIX."caf_details set tar_id='".$tar_id."', market_segment='".$market_segment."', dealer_code='".$dealer_code."', brm_code='".$brm_code."' where id='".$caf_id."'");
			
			$cafExistingSQL = $this->getCAFExistingDetailsByCAFId($caf_id);
			while($cafExisting = $this->fetch($cafExistingSQL)){
				if(!in_array($cafExisting['id'],$data['existing_id'])){
					$this->query("delete from ".PREFIX."caf_existing_details where id='".$cafExisting['id']."'");
				}
			}
			foreach($data['caf_no'] as $key=>$value) {
				if(!empty($data['caf_no'][$key])) {
					$caf_no = trim($this->escape_string($this->strip_all($data['caf_no'][$key])));
					$ref_doc = trim($this->escape_string($this->strip_all($data['ref_doc'][$key])));
					if(!empty($data['existing_id'][$key])) {
						$existing_id = $this->escape_string($this->strip_all($data['existing_id'][$key]));
						$this->query("update ".PREFIX."caf_existing_details set caf_no='".$caf_no."', ref_doc='".$ref_doc."' where id='".$existing_id."'");
					} else {
						$this->query("insert into ".PREFIX."caf_existing_details(caf_id, caf_no, ref_doc) values ('".$caf_id."', '".$caf_no."', '".$ref_doc."')");
					}
				}
			}
			
			if($product=='SmartOffice') {
				if($variant == 'Internet Leased Line'){
					$ill_connection_type = trim($this->escape_string($this->strip_all($data['sm_ill_connection_type'])));
					$ill_del_no = trim($this->escape_string($this->strip_all($data['sm_ill_del_no'])));
					$ill_billing_cycle = trim($this->escape_string($this->strip_all($data['sm_ill_billing_cycle'])));
					$ill_exit_policy = trim($this->escape_string($this->strip_all($data['sm_ill_exit_policy'])));
					$ill_pm_email = trim($this->escape_string($this->strip_all($data['sm_ill_pm_email'])));
					$ill_super_account = trim($this->escape_string($this->strip_all($data['sm_ill_super_account'])));
					$ill_addon_account = trim($this->escape_string($this->strip_all($data['sm_ill_addon_account'])));
					$ill_circuit_id = trim($this->escape_string($this->strip_all($data['sm_ill_circuit_id'])));
					$ill_fan_no = trim($this->escape_string($this->strip_all($data['sm_ill_fan_no'])));
					$ill_srf_no = trim($this->escape_string($this->strip_all($data['sm_ill_srf_no'])));
					$ill_bandwidth = trim($this->escape_string($this->strip_all($data['sm_ill_bandwidth'])));
					$ill_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['sm_ill_bandwidth_ratio'])));
					//echo $file['fan_nmber_upload']['name'];exit;
					if(!empty($file['sm_ill_fan_nmber_upload']['name'])) {
						//echo "here";exit;
						/* $file_name = strtolower( pathinfo($file['sm_ill_fan_nmber_upload']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['sm_ill_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$sm_ill_fan_nmber_upload = time().'1.'.$ext;
						move_uploaded_file($file['sm_ill_fan_nmber_upload']['tmp_name'],$uploadDir.$sm_ill_fan_nmber_upload); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['sm_ill_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$sm_ill_fan_nmber_upload = time().'1.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$sm_ill_fan_nmber_upload, fopen($file['sm_ill_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$sm_ill_fan_nmber_upload."' where caf_id='".$caf_id."'");
					}


					$this->query("insert into ".PREFIX."caf_ill_details(caf_id, ill_connection_type, ill_del_no, ill_billing_cycle, ill_exit_policy, ill_pm_email, ill_super_account, ill_addon_account, ill_circuit_id, ill_fan_no, ill_srf_no, ill_bandwidth, ill_bandwidth_ratio) values('".$caf_id."', '".$ill_connection_type."', '".$ill_del_no."', '".$ill_billing_cycle."', '".$ill_exit_policy."', '".$ill_pm_email."', '".$ill_super_account."', '".$ill_addon_account."', '".$ill_circuit_id."', '".$ill_fan_no."', '".$ill_srf_no."', '".$ill_bandwidth."', '".$ill_bandwidth_ratio."')");
				
				}else if($variant == 'SIP Trunk'){
					
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sm_sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sm_sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sm_sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sm_sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sm_sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sm_sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sm_sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sm_sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sm_sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sm_sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sm_sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sm_sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sm_sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sm_sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sm_sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sm_sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sm_sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sm_sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sm_sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sm_sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sm_sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sm_sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sm_sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sm_sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sm_sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sm_sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sm_sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sm_sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sm_sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sm_sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sm_sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
					
				
				}else if($variant=='Audio Conferencing') {
					$audio_conf_pgi_landline_no = trim($this->escape_string($this->strip_all($data['sm_audio_conf_pgi_landline_no'])));
					$sm_audio_conf_del_number = trim($this->escape_string($this->strip_all($data['sm_audio_conf_del_number'])));
					$this->query("insert into ".PREFIX."caf_audio_conf_details(caf_id, audio_conf_pgi_landline_no, audio_conf_del_no) values('".$caf_id."', '".$audio_conf_pgi_landline_no."', '".$sm_audio_conf_del_number."')");
				}
			}
			if($product=='Internet Leased Line') {
				$ill_connection_type = trim($this->escape_string($this->strip_all($data['ill_connection_type'])));
				$ill_del_no = trim($this->escape_string($this->strip_all($data['ill_del_no'])));
				$ill_billing_cycle = trim($this->escape_string($this->strip_all($data['ill_billing_cycle'])));
				$ill_exit_policy = trim($this->escape_string($this->strip_all($data['ill_exit_policy'])));
				$ill_pm_email = trim($this->escape_string($this->strip_all($data['ill_pm_email'])));
				$ill_super_account = trim($this->escape_string($this->strip_all($data['ill_super_account'])));
				$ill_addon_account = trim($this->escape_string($this->strip_all($data['ill_addon_account'])));
				$ill_circuit_id = trim($this->escape_string($this->strip_all($data['ill_circuit_id'])));
				$ill_fan_no = trim($this->escape_string($this->strip_all($data['ill_fan_no'])));
				$ill_srf_no = trim($this->escape_string($this->strip_all($data['ill_srf_no'])));
				$ill_bandwidth = trim($this->escape_string($this->strip_all($data['ill_bandwidth'])));
				$ill_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['ill_bandwidth_ratio'])));
				
				//echo $file['fan_nmber_upload']['name'];exit;
				if(!empty($file['ill_fan_nmber_upload']['name'])) {
					//echo "here";exit;
					/* $file_name = strtolower( pathinfo($file['ill_fan_nmber_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['ill_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$ill_fan_nmber_upload = time().'1.'.$ext;
					move_uploaded_file($file['ill_fan_nmber_upload']['tmp_name'],$uploadDir.$ill_fan_nmber_upload); */
											
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['ill_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$ill_fan_nmber_upload = time().'1.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$ill_fan_nmber_upload, fopen($file['ill_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
					$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$ill_fan_nmber_upload."' where caf_id='".$caf_id."'");
				}

				$this->query("insert into ".PREFIX."caf_ill_details(caf_id, ill_connection_type, ill_del_no, ill_billing_cycle, ill_exit_policy, ill_pm_email, ill_super_account, ill_addon_account, ill_circuit_id, ill_fan_no, ill_srf_no, ill_bandwidth, ill_bandwidth_ratio) values('".$caf_id."', '".$ill_connection_type."', '".$ill_del_no."', '".$ill_billing_cycle."', '".$ill_exit_policy."', '".$ill_pm_email."', '".$ill_super_account."', '".$ill_addon_account."', '".$ill_circuit_id."', '".$ill_fan_no."', '".$ill_srf_no."', '".$ill_bandwidth."', '".$ill_bandwidth_ratio."')");
			}
			else if($product=='Smart VPN') {
				if($variant=='MPLS Standard' || $variant=='MPLS Managed' || $variant=='Secure Connect' || $variant=='Internet VPN-On Site' || $varaint == 'Internet VPN-On the Move') {
					$mpls_connection_type = trim($this->escape_string($this->strip_all($data['mpls_connection_type'])));
					$mpls_del_no = trim($this->escape_string($this->strip_all($data['mpls_del_no'])));
					$mpls_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_billing_cycle'])));
					$mpls_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_exit_policy'])));
					$mpls_pm_email = trim($this->escape_string($this->strip_all($data['mpls_pm_email'])));
					$mpls_super_account = trim($this->escape_string($this->strip_all($data['mpls_super_account'])));
					$mpls_addon_account = trim($this->escape_string($this->strip_all($data['mpls_addon_account'])));
					$mpls_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_circuit_id'])));
					$mpls_fan_no = trim($this->escape_string($this->strip_all($data['mpls_fan_no'])));
					$mpls_srf_no = trim($this->escape_string($this->strip_all($data['mpls_srf_no'])));
					$mpls_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_bandwidth'])));
					$mpls_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['mpls_bandwidth_ratio'])));
					//echo $file['fan_nmber_upload']['name'];exit;
					if(!empty($file['mpls_fan_nmber_upload']['name'])) {
						//echo "here";exit;
						/* $file_name = strtolower( pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$mpls_fan_nmber_upload = time().'1.'.$ext;
						move_uploaded_file($file['mpls_fan_nmber_upload']['tmp_name'],$uploadDir.$mpls_fan_nmber_upload); */						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$mpls_fan_nmber_upload = time().'1.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_fan_nmber_upload, fopen($file['mpls_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$mpls_fan_nmber_upload."' where caf_id='".$caf_id."'");
					}

					$this->query("insert into ".PREFIX."caf_mpls_details(caf_id, mpls_connection_type, mpls_del_no, mpls_billing_cycle, mpls_exit_policy, mpls_pm_email, mpls_super_account, mpls_addon_account, mpls_circuit_id, mpls_fan_no, mpls_srf_no, mpls_bandwidth, mpls_bandwidth_ratio) values('".$caf_id."', '".$mpls_connection_type."', '".$mpls_del_no."', '".$mpls_billing_cycle."', '".$mpls_exit_policy."', '".$mpls_pm_email."', '".$mpls_super_account."', '".$mpls_addon_account."', '".$mpls_circuit_id."', '".$mpls_fan_no."', '".$mpls_srf_no."', '".$mpls_bandwidth."', '".$mpls_bandwidth_ratio."')");
				}
				else if($variant=='Xpress VPN') {
					$mpls_express_connection_type = trim($this->escape_string($this->strip_all($data['mpls_express_connection_type'])));
					$mpls_express_del_no = trim($this->escape_string($this->strip_all($data['mpls_express_del_no'])));
					$mpls_express_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_express_exit_policy'])));
					$mpls_express_pm_email = trim($this->escape_string($this->strip_all($data['mpls_express_pm_email'])));
					$mpls_express_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_express_billing_cycle'])));
					$mpls_express_parent_account = trim($this->escape_string($this->strip_all($data['mpls_express_parent_account'])));
					$mpls_express_addon_account = trim($this->escape_string($this->strip_all($data['mpls_express_addon_account'])));
					$mpls_express_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_express_circuit_id'])));
					$mpls_express_client_del_creation = trim($this->escape_string($this->strip_all($data['mpls_express_client_del_creation'])));
					$mpls_express_apn_name = trim($this->escape_string($this->strip_all($data['mpls_express_apn_name'])));
					$mpls_express_dummy_del = trim($this->escape_string($this->strip_all($data['mpls_express_dummy_del'])));
					$mpls_express_user_id = trim($this->escape_string($this->strip_all($data['mpls_express_user_id'])));
					$mpls_express_password = trim($this->escape_string($this->strip_all($data['mpls_express_password'])));
					$mpls_express_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_express_bandwidth'])));
					$mpls_express_internet_blocking = trim($this->escape_string($this->strip_all($data['mpls_express_internet_blocking'])));
					$mpls_express_client_id_charges = trim($this->escape_string($this->strip_all($data['mpls_express_client_id_charges'])));
					$mpls_express_customer_apn = trim($this->escape_string($this->strip_all($data['mpls_express_customer_apn'])));
					$mpls_express_reserved_id = trim($this->escape_string($this->strip_all($data['mpls_express_reserved_id'])));
					$mpls_express_empower_id = trim($this->escape_string($this->strip_all($data['mpls_express_empower_id'])));
					$mpls_express_handset_id = trim($this->escape_string($this->strip_all($data['mpls_express_handset_id'])));
					$mpls_express_network_email = trim($this->escape_string($this->strip_all($data['mpls_express_network_email'])));

					if(!empty($file['mpls_express_apn_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_express_apn_excel = time().'5.'.$ext;
						move_uploaded_file($file['mpls_express_apn_excel']['tmp_name'],$uploadDir.$mpls_express_apn_excel); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_express_apn_excel = time().'5.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_express_apn_excel, fopen($file['mpls_express_apn_excel']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_mpls_express_details(caf_id, mpls_express_connection_type, mpls_express_del_no, mpls_express_exit_policy, mpls_express_pm_email, mpls_express_billing_cycle, mpls_express_parent_account, mpls_express_addon_account, mpls_express_apn_name, mpls_express_user_id, mpls_express_password, mpls_express_bandwidth, mpls_express_internet_blocking, mpls_express_client_id_charges, mpls_express_customer_apn, mpls_express_apn_excel, mpls_express_reserved_id, mpls_express_empower_id, mpls_express_handset_id, mpls_express_network_email) values('".$caf_id."', '".$mpls_express_connection_type."', '".$mpls_express_del_no."', '".$mpls_express_exit_policy."', '".$mpls_express_pm_email."', '".$mpls_express_billing_cycle."', '".$mpls_express_parent_account."', '".$mpls_express_addon_account."', '".$mpls_express_apn_name."', '".$mpls_express_user_id."', '".$mpls_express_password."', '".$mpls_express_bandwidth."', '".$mpls_express_internet_blocking."', '".$mpls_express_client_id_charges."', '".$mpls_express_customer_apn."', '".$mpls_express_apn_excel."', '".$mpls_express_reserved_id."', '".$mpls_express_empower_id."', '".$mpls_express_handset_id."', '".$mpls_express_network_email."')");
				}
				else if($variant=='Road warrior') {
					$mpls_rw_connection_type = trim($this->escape_string($this->strip_all($data['mpls_rw_connection_type'])));
					$mpls_rw_del_no = trim($this->escape_string($this->strip_all($data['mpls_rw_del_no'])));
					$mpls_rw_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_rw_billing_cycle'])));
					$mpls_rw_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_rw_exit_policy'])));
					$mpls_rw_pw_email = trim($this->escape_string($this->strip_all($data['mpls_rw_pw_email'])));
					$mpls_rw_parent_account = trim($this->escape_string($this->strip_all($data['mpls_rw_parent_account'])));
					$mpls_rw_addon_account = trim($this->escape_string($this->strip_all($data['mpls_rw_addon_account'])));
					$mpls_rw_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_rw_circuit_id'])));
					$mpls_rw_apn_name = trim($this->escape_string($this->strip_all($data['mpls_rw_apn_name'])));
					$mpls_rw_del_creation = trim($this->escape_string($this->strip_all($data['mpls_rw_del_creation'])));
					$mpls_rw_dummy_del = trim($this->escape_string($this->strip_all($data['mpls_rw_dummy_del'])));
					$mpls_rw_user_id = trim($this->escape_string($this->strip_all($data['mpls_rw_user_id'])));
					$mpls_rw_password = trim($this->escape_string($this->strip_all($data['mpls_rw_password'])));
					$mpls_rw_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_rw_bandwidth'])));
					$mpls_rw_internet_blocking = trim($this->escape_string($this->strip_all($data['mpls_rw_internet_blocking'])));
					$mpls_rw_client_id_charges = trim($this->escape_string($this->strip_all($data['mpls_rw_client_id_charges'])));
					$mpls_rw_customer_apn = trim($this->escape_string($this->strip_all($data['mpls_rw_customer_apn'])));
					$mpls_rw_calling_level = trim($this->escape_string($this->strip_all($data['mpls_rw_calling_level'])));

					if(!empty($file['mpls_rw_apn_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_rw_apn_excel = time().'5.'.$ext;
						move_uploaded_file($file['mpls_rw_apn_excel']['tmp_name'],$uploadDir.$mpls_rw_apn_excel); */
												
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_rw_apn_excel = time().'5.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_rw_apn_excel, fopen($file['mpls_rw_apn_excel']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_mpls_rw_details(caf_id, mpls_rw_connection_type, mpls_rw_del_no, mpls_rw_exit_policy, mpls_rw_pw_email, mpls_rw_billing_cycle, mpls_rw_parent_account, mpls_rw_addon_account, mpls_rw_circuit_id, mpls_rw_apn_name, mpls_rw_del_creation, mpls_rw_dummy_del,  mpls_rw_user_id, mpls_rw_password, mpls_rw_bandwidth, mpls_rw_internet_blocking, mpls_rw_client_id_charges, mpls_rw_customer_apn, mpls_rw_apn_excel, mpls_rw_calling_level) values('".$caf_id."', '".$mpls_rw_connection_type."', '".$mpls_rw_del_no."', '".$mpls_rw_exit_policy."', '".$mpls_rw_pw_email."', '".$mpls_rw_billing_cycle."', '".$mpls_rw_parent_account."', '".$mpls_rw_addon_account."', '".$mpls_rw_circuit_id."', '".$mpls_rw_apn_name."', '".$mpls_rw_del_creation."', '".$mpls_rw_dummy_del."', '".$mpls_rw_user_id."', '".$mpls_rw_password."', '".$mpls_rw_bandwidth."', '".$mpls_rw_internet_blocking."', '".$mpls_rw_client_id_charges."', '".$mpls_rw_customer_apn."', '".$mpls_rw_apn_excel."', '".$mpls_rw_calling_level."')");
				
				}else{
					//done by dhanashree 31/10/2018
						$mpls_pm_email = trim($this->escape_string($this->strip_all($data['mpls_pm_email'])));
						$mpls_super_account = trim($this->escape_string($this->strip_all($data['mpls_super_account'])));
						$mpls_fan_no = trim($this->escape_string($this->strip_all($data['mpls_fan_no'])));

						if(!empty($file['mpls_fan_nmber_upload']['name'])) {
							//echo "here";exit;
							/* $file_name = strtolower( pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_FILENAME));
							$ext = strtolower(pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$mpls_fan_nmber_upload = time().'1.'.$ext;
							move_uploaded_file($file['mpls_fan_nmber_upload']['tmp_name'],$uploadDir.$mpls_fan_nmber_upload); */						
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$ext = strtolower(pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$mpls_fan_nmber_upload = time().'1.'.$ext;
							$upload = $s3->upload($bucket, $mediaDir.$mpls_fan_nmber_upload, fopen($file['mpls_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$mpls_fan_nmber_upload."' where caf_id='".$caf_id."'");
						}
						
					$this->query("insert into ".PREFIX."caf_mpls_details(caf_id,  mpls_pm_email, mpls_super_account, mpls_fan_no) values('".$caf_id."', '".$mpls_pm_email."', '".$mpls_super_account."', '".$mpls_fan_no."')");	
				}
			}
			else if($product=='Leased Line') {
				if($variant=='Standard' || $variant=='Premium') {
					if($sub_variant=='DLC') {
						$dlc_connection_type = trim($this->escape_string($this->strip_all($data['dlc_connection_type'])));
						$dlc_del_no = trim($this->escape_string($this->strip_all($data['dlc_del_no'])));
						$del_billing_cycle = trim($this->escape_string($this->strip_all($data['del_billing_cycle'])));
						$del_exit_policy = trim($this->escape_string($this->strip_all($data['del_exit_policy'])));
						$dlc_pm_email = trim($this->escape_string($this->strip_all($data['dlc_pm_email'])));
						$dlc_parent_account = trim($this->escape_string($this->strip_all($data['dlc_parent_account'])));
						$dlc_addon_account = trim($this->escape_string($this->strip_all($data['dlc_addon_account'])));
						$dlc_circuit_id = trim($this->escape_string($this->strip_all($data['dlc_circuit_id'])));
						$dlc_fan_no = trim($this->escape_string($this->strip_all($data['dlc_fan_no'])));
						$dlc_srf_no = trim($this->escape_string($this->strip_all($data['dlc_srf_no'])));
						$dlc_bandwidth = trim($this->escape_string($this->strip_all($data['dlc_bandwidth'])));
						$dlc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['dlc_bandwidth_ratio'])));
						
						//echo $file['fan_nmber_upload']['name'];exit;
						if(!empty($file['dlc_fan_nmber_upload']['name'])) {
							//echo "here";exit;
							/* $file_name = strtolower( pathinfo($file['dlc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
							$ext = strtolower(pathinfo($file['dlc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$dlc_fan_nmber_upload = time().'1.'.$ext;
							move_uploaded_file($file['dlc_fan_nmber_upload']['tmp_name'],$uploadDir.$dlc_fan_nmber_upload); */	
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$ext = strtolower(pathinfo($file['dlc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$dlc_fan_nmber_upload = time().'1.'.$ext;
							$upload = $s3->upload($bucket, $mediaDir.$dlc_fan_nmber_upload, fopen($file['dlc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$dlc_fan_nmber_upload."' where caf_id='".$caf_id."'");
						}

						$this->query("insert into ".PREFIX."caf_dlc_details(caf_id, dlc_connection_type, dlc_del_no, del_billing_cycle, del_exit_policy, dlc_pm_email, dlc_parent_account, dlc_addon_account, dlc_circuit_id, dlc_fan_no, dlc_srf_no, dlc_bandwidth, dlc_bandwidth_ratio) values('".$caf_id."', '".$dlc_connection_type."', '".$dlc_del_no."', '".$del_billing_cycle."', '".$del_exit_policy."', '".$dlc_pm_email."', '".$dlc_parent_account."', '".$dlc_addon_account."', '".$dlc_circuit_id."', '".$dlc_fan_no."', '".$dlc_srf_no."', '".$dlc_bandwidth."', '".$dlc_bandwidth_ratio."')");
					}
					if($sub_variant=='NPLC') {
						$nplc_connection_type = trim($this->escape_string($this->strip_all($data['nplc_connection_type'])));
						$nplc_del_no = trim($this->escape_string($this->strip_all($data['nplc_del_no'])));
						$nplc_billing_cycle = trim($this->escape_string($this->strip_all($data['nplc_billing_cycle'])));
						$nplc_exit_policy = trim($this->escape_string($this->strip_all($data['nplc_exit_policy'])));
						$nplc_pm_email = trim($this->escape_string($this->strip_all($data['nplc_pm_email'])));
						$nplc_parent_account = trim($this->escape_string($this->strip_all($data['nplc_parent_account'])));
						$nplc_addon_account = trim($this->escape_string($this->strip_all($data['nplc_addon_account'])));
						$nplc_circuit_id = trim($this->escape_string($this->strip_all($data['nplc_circuit_id'])));
						$nplc_fan_no = trim($this->escape_string($this->strip_all($data['nplc_fan_no'])));
						$nplc_srf_no = trim($this->escape_string($this->strip_all($data['nplc_srf_no'])));
						$nplc_bandwidth = trim($this->escape_string($this->strip_all($data['nplc_bandwidth'])));
						$nplc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['nplc_bandwidth_ratio'])));
						
						//echo $file['fan_nmber_upload']['name'];exit;
						if(!empty($file['nplc_fan_nmber_upload']['name'])) {
							//echo "here";exit;
							/* $file_name = strtolower( pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
							$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$nplc_fan_nmber_upload = time().'1.'.$ext;
							move_uploaded_file($file['nplc_fan_nmber_upload']['tmp_name'],$uploadDir.$nplc_fan_nmber_upload); */
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$nplc_fan_nmber_upload = time().'1.'.$ext;
							$upload = $s3->upload($bucket, $mediaDir.$nplc_fan_nmber_upload, fopen($file['nplc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$nplc_fan_nmber_upload."' where caf_id='".$caf_id."'");
						}

						$this->query("insert into ".PREFIX."caf_nplc_details(caf_id, nplc_connection_type, nplc_del_no, nplc_billing_cycle, nplc_exit_policy, nplc_pm_email, nplc_parent_account, nplc_addon_account, nplc_circuit_id, nplc_fan_no, nplc_srf_no, nplc_bandwidth, nplc_bandwidth_ratio) values('".$caf_id."', '".$nplc_connection_type."', '".$nplc_del_no."', '".$nplc_billing_cycle."', '".$nplc_exit_policy."', '".$nplc_pm_email."', '".$nplc_parent_account."', '".$nplc_addon_account."', '".$nplc_circuit_id."', '".$nplc_fan_no."', '".$nplc_srf_no."', '".$nplc_bandwidth."', '".$nplc_bandwidth_ratio."')");
					}
				}
				if($variant=='Platinum' || $variant=='Ultra LoLa') {
					$nplc_connection_type = trim($this->escape_string($this->strip_all($data['nplc_connection_type'])));
					$nplc_del_no = trim($this->escape_string($this->strip_all($data['nplc_del_no'])));
					$nplc_billing_cycle = trim($this->escape_string($this->strip_all($data['nplc_billing_cycle'])));
					$nplc_exit_policy = trim($this->escape_string($this->strip_all($data['nplc_exit_policy'])));
					$nplc_pm_email = trim($this->escape_string($this->strip_all($data['nplc_pm_email'])));
					$nplc_parent_account = trim($this->escape_string($this->strip_all($data['nplc_parent_account'])));
					$nplc_addon_account = trim($this->escape_string($this->strip_all($data['nplc_addon_account'])));
					$nplc_circuit_id = trim($this->escape_string($this->strip_all($data['nplc_circuit_id'])));
					$nplc_fan_no = trim($this->escape_string($this->strip_all($data['nplc_fan_no'])));
					$nplc_srf_no = trim($this->escape_string($this->strip_all($data['nplc_srf_no'])));
					$nplc_bandwidth = trim($this->escape_string($this->strip_all($data['nplc_bandwidth'])));
					$nplc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['nplc_bandwidth_ratio'])));
					
					//echo $file['fan_nmber_upload']['name'];exit;
					if(!empty($file['nplc_fan_nmber_upload']['name'])) {
						//echo "here";exit;
						/* $file_name = strtolower( pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$nplc_fan_nmber_upload = time().'1.'.$ext;
						move_uploaded_file($file['nplc_fan_nmber_upload']['tmp_name'],$uploadDir.$nplc_fan_nmber_upload); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$nplc_fan_nmber_upload = time().'1.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$nplc_fan_nmber_upload, fopen($file['nplc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$nplc_fan_nmber_upload."' where caf_id='".$caf_id."'");
					}


					$this->query("insert into ".PREFIX."caf_nplc_details(caf_id, nplc_connection_type, nplc_del_no, nplc_billing_cycle, nplc_exit_policy, nplc_pm_email, nplc_parent_account, nplc_addon_account, nplc_circuit_id, nplc_fan_no, nplc_srf_no, nplc_bandwidth, nplc_bandwidth_ratio) values('".$caf_id."', '".$nplc_connection_type."', '".$nplc_del_no."', '".$nplc_billing_cycle."', '".$nplc_exit_policy."', '".$nplc_pm_email."', '".$nplc_parent_account."', '".$nplc_addon_account."', '".$nplc_circuit_id."', '".$nplc_fan_no."', '".$nplc_srf_no."', '".$nplc_bandwidth."', '".$nplc_bandwidth_ratio."')");
				}
			}
			else if($product=='L2 Multicast Solution') {
				$l2mc_connection_type = trim($this->escape_string($this->strip_all($data['l2mc_connection_type'])));
				$l2mc_del_no = trim($this->escape_string($this->strip_all($data['l2mc_del_no'])));
				$l2mc_billing_cycle = trim($this->escape_string($this->strip_all($data['l2mc_billing_cycle'])));
				$l2mc_exit_policy = trim($this->escape_string($this->strip_all($data['l2mc_exit_policy'])));
				$l2mc_pm_email = trim($this->escape_string($this->strip_all($data['l2mc_pm_email'])));
				$l2mc_parent_account = trim($this->escape_string($this->strip_all($data['l2mc_parent_account'])));
				$l2mc_addon_account = trim($this->escape_string($this->strip_all($data['l2mc_addon_account'])));
				$l2mc_circuit_id = trim($this->escape_string($this->strip_all($data['l2mc_circuit_id'])));
				$l2mc_fan_no = trim($this->escape_string($this->strip_all($data['l2mc_fan_no'])));
				$l2mc_srf_no = trim($this->escape_string($this->strip_all($data['l2mc_srf_no'])));
				$l2mc_bandwidth = trim($this->escape_string($this->strip_all($data['l2mc_bandwidth'])));
				$l2mc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['l2mc_bandwidth_ratio'])));
				
				//echo $file['fan_nmber_upload']['name'];exit;
				if(!empty($file['l2mc_fan_nmber_upload']['name'])) {
					//echo "here";exit;
					/* $file_name = strtolower( pathinfo($file['l2mc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['l2mc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$l2mc_fan_nmber_upload = time().'1.'.$ext;
					move_uploaded_file($file['l2mc_fan_nmber_upload']['tmp_name'],$uploadDir.$l2mc_fan_nmber_upload); */
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['l2mc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$l2mc_fan_nmber_upload = time().'1.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$l2mc_fan_nmber_upload, fopen($file['l2mc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
					$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$l2mc_fan_nmber_upload."' where caf_id='".$caf_id."'");
				}

				$this->query("insert into ".PREFIX."caf_l2mc_details(caf_id, l2mc_connection_type, l2mc_del_no, l2mc_billing_cycle, l2mc_exit_policy, l2mc_pm_email, l2mc_parent_account, l2mc_addon_account, l2mc_circuit_id, l2mc_fan_no, l2mc_srf_no, l2mc_bandwidth, l2mc_bandwidth_ratio) values('".$caf_id."', '".$l2mc_connection_type."', '".$l2mc_del_no."', '".$l2mc_billing_cycle."', '".$l2mc_exit_policy."', '".$l2mc_pm_email."', '".$l2mc_parent_account."', '".$l2mc_addon_account."', '".$l2mc_circuit_id."', '".$l2mc_fan_no."', '".$l2mc_srf_no."', '".$l2mc_bandwidth."', '".$l2mc_bandwidth_ratio."')");
			}
			else if($product=='Photon') {
				if($variant=='Photon Dongle') {
					$photon_dongal_connection_type = trim($this->escape_string($this->strip_all($data['photon_dongal_connection_type'])));
					$photon_dongal_del_no = trim($this->escape_string($this->strip_all($data['photon_dongal_del_no'])));
					$photon_dongal_rid = trim($this->escape_string($this->strip_all($data['photon_dongal_rid'])));
					$photon_dongal_pm_email = trim($this->escape_string($this->strip_all($data['photon_dongal_pm_email'])));
					$photon_dongal_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_dongal_billing_cycle'])));
					$photon_dongal_parent_account = trim($this->escape_string($this->strip_all($data['photon_dongal_parent_account'])));
					$photon_dongal_addon_account = trim($this->escape_string($this->strip_all($data['photon_dongal_addon_account'])));
					$photon_dongal_handset_id = trim($this->escape_string($this->strip_all($data['photon_dongal_handset_id'])));

					$this->query("insert into ".PREFIX."caf_photon_dongal_details(caf_id, photon_dongal_connection_type, photon_dongal_del_no, photon_dongal_rid, photon_dongal_pm_email, photon_dongal_billing_cycle, photon_dongal_parent_account, photon_dongal_addon_account, photon_dongal_handset_id) values('".$caf_id."', '".$photon_dongal_connection_type."', '".$photon_dongal_del_no."', '".$photon_dongal_rid."', '".$photon_dongal_pm_email."', '".$photon_dongal_billing_cycle."', '".$photon_dongal_parent_account."', '".$photon_dongal_addon_account."', '".$photon_dongal_handset_id."')");
				}
				else if($variant=='Photon Dongle Wifi') {
					$photon_wifi_connection_type = trim($this->escape_string($this->strip_all($data['photon_wifi_connection_type'])));
					$photon_wifi_del_no = trim($this->escape_string($this->strip_all($data['photon_wifi_del_no'])));
					$photon_wifi_rid = trim($this->escape_string($this->strip_all($data['photon_wifi_rid'])));
					$photon_wifi_pm_email = trim($this->escape_string($this->strip_all($data['photon_wifi_pm_email'])));
					$photon_wifi_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_wifi_billing_cycle'])));
					$photon_wifi_parent_account = trim($this->escape_string($this->strip_all($data['photon_wifi_parent_account'])));
					$photon_wifi_addon_account = trim($this->escape_string($this->strip_all($data['photon_wifi_addon_account'])));
					$photon_wifi_handset_id = trim($this->escape_string($this->strip_all($data['photon_wifi_handset_id'])));

					$this->query("insert into ".PREFIX."caf_photon_wifi_details(caf_id, photon_wifi_connection_type, photon_wifi_del_no, photon_wifi_rid, photon_wifi_pm_email, photon_wifi_billing_cycle, photon_wifi_parent_account, photon_wifi_addon_account, photon_wifi_handset_id) values('".$caf_id."', '".$photon_wifi_connection_type."', '".$photon_wifi_del_no."', '".$photon_wifi_rid."', '".$photon_wifi_pm_email."', '".$photon_wifi_billing_cycle."', '".$photon_wifi_parent_account."', '".$photon_wifi_addon_account."', '".$photon_wifi_handset_id."')");
				}
				else if($variant=='Photon Mifi') {
					$photon_mifi_existing_caf = trim($this->escape_string($this->strip_all($data['photon_mifi_existing_caf'])));
					$photon_mifi_proof_company_id = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_company_id'])));
					$photon_mifi_proof_authorization = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_authorization'])));
					$photon_mifi_proof_address = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_address'])));
					$photon_mifi_other_docs = trim($this->escape_string($this->strip_all($data['photon_mifi_other_docs'])));
					$photon_mifi_govt_id_proof = trim($this->escape_string($this->strip_all($data['photon_mifi_govt_id_proof'])));
					$photon_mifi_connection_type = trim($this->escape_string($this->strip_all($data['photon_mifi_connection_type'])));
					$photon_mifi_del_no = trim($this->escape_string($this->strip_all($data['photon_mifi_del_no'])));
					$photon_mifi_rid = trim($this->escape_string($this->strip_all($data['photon_mifi_rid'])));
					$photon_mifi_pm_email = trim($this->escape_string($this->strip_all($data['photon_mifi_pm_email'])));
					$photon_mifi_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_mifi_billing_cycle'])));
					$photon_mifi_parent_account = trim($this->escape_string($this->strip_all($data['photon_mifi_parent_account'])));
					$photon_mifi_addon_account = trim($this->escape_string($this->strip_all($data['photon_mifi_addon_account'])));
					$photon_mifi_handset_id = trim($this->escape_string($this->strip_all($data['photon_mifi_handset_id'])));

					if(!empty($file['photon_mifi_approval']['name'])) {
						/* $file_name = strtolower( pathinfo($file['photon_mifi_approval']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['photon_mifi_approval']['name'], PATHINFO_EXTENSION));
						$photon_mifi_approval = time().'7.'.$ext;
						move_uploaded_file($file['photon_mifi_approval']['tmp_name'],$uploadDir.$photon_mifi_approval); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['photon_mifi_approval']['name'], PATHINFO_EXTENSION));
						$photon_mifi_approval = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$photon_mifi_approval, fopen($file['photon_mifi_approval']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_photon_mifi_details(caf_id, photon_mifi_existing_caf, photon_mifi_proof_company_id, photon_mifi_proof_authorization, photon_mifi_proof_address, photon_mifi_other_docs, photon_mifi_govt_id_proof, photon_mifi_approval, photon_mifi_connection_type, photon_mifi_del_no, photon_mifi_rid, photon_mifi_pm_email, photon_mifi_billing_cycle, photon_mifi_parent_account, photon_mifi_addon_account, photon_mifi_handset_id) values('".$caf_id."', '".$photon_mifi_existing_caf."', '".$photon_mifi_proof_company_id."', '".$photon_mifi_proof_authorization."', '".$photon_mifi_proof_address."', '".$photon_mifi_other_docs."', '".$photon_mifi_govt_id_proof."', '".$photon_mifi_approval."', '".$photon_mifi_connection_type."', '".$photon_mifi_del_no."', '".$photon_mifi_rid."', '".$photon_mifi_pm_email."', '".$photon_mifi_billing_cycle."', '".$photon_mifi_parent_account."', '".$photon_mifi_addon_account."', '".$photon_mifi_handset_id."')");
				}
			}
			else if($product=='PRI') {
					$pri_cug_type = trim($this->escape_string($this->strip_all($data['pri_cug_type'])));
					$pri_connection_type = trim($this->escape_string($this->strip_all($data['pri_connection_type'])));
					$pri_billing_cycle = trim($this->escape_string($this->strip_all($data['pri_billing_cycle'])));
					$pri_pm_email = trim($this->escape_string($this->strip_all($data['pri_pm_email'])));
					$pri_parent_account = trim($this->escape_string($this->strip_all($data['pri_parent_account'])));
					$pri_addon_account = trim($this->escape_string($this->strip_all($data['pri_addon_account'])));
					$pri_rid = trim($this->escape_string($this->strip_all($data['pri_rid'])));
					$pri_del_no = trim($this->escape_string($this->strip_all($data['pri_del_no'])));
					$pri_wepbax_config = trim($this->escape_string($this->strip_all($data['pri_wepbax_config'])));
					$pri_service_type_wireline = trim($this->escape_string($this->strip_all($data['pri_service_type_wireline'])));
					$pri_pilot_no = trim($this->escape_string($this->strip_all($data['pri_pilot_no'])));
					$pri_did_count = trim($this->escape_string($this->strip_all($data['pri_did_count'])));
					$pri_channel_count = trim($this->escape_string($this->strip_all($data['pri_channel_count'])));
					$pri_switch_name = trim($this->escape_string($this->strip_all($data['pri_switch_name'])));
					$pri_dial_code = trim($this->escape_string($this->strip_all($data['pri_dial_code'])));
					$pri_zone_id = trim($this->escape_string($this->strip_all($data['pri_zone_id'])));
					$pri_msgn_node = trim($this->escape_string($this->strip_all($data['pri_msgn_node'])));
					$pri_d_channel = trim($this->escape_string($this->strip_all($data['pri_d_channel'])));
					$pri_sponsered = trim($this->escape_string($this->strip_all($data['pri_sponsered'])));
					$pri_epabx_procured = trim($this->escape_string($this->strip_all($data['pri_epabx_procured'])));
					$pri_cost_epabx = trim($this->escape_string($this->strip_all($data['pri_cost_epabx'])));
					$pri_penalty_matrix = trim($this->escape_string($this->strip_all($data['pri_penalty_matrix'])));
					$pri_contract_period = trim($this->escape_string($this->strip_all($data['pri_contract_period'])));
					$pri_cost_pri_card = trim($this->escape_string($this->strip_all($data['pri_cost_pri_card'])));
					$pri_vendor_name = trim($this->escape_string($this->strip_all($data['pri_vendor_name'])));
					$pri_ebabx_make = trim($this->escape_string($this->strip_all($data['pri_ebabx_make'])));
					$pri_mis_entry = trim($this->escape_string($this->strip_all($data['pri_mis_entry'])));
					$pri_calling_level = trim($this->escape_string($this->strip_all($data['pri_calling_level'])));
					$pri_hosted_ivr = trim($this->escape_string($this->strip_all($data['pri_hosted_ivr'])));
					$pri_hivr_no = trim($this->escape_string($this->strip_all($data['pri_hivr_no'])));
					$pri_type = trim($this->escape_string($this->strip_all($data['pri_type'])));

					$this->query("insert into ".PREFIX."caf_pri_details(caf_id, pri_cug_type, pri_del_no, pri_billing_cycle, pri_pm_email, pri_connection_type, pri_parent_account, pri_wepbax_config, pri_rid, pri_addon_account, pri_service_type_wireline, pri_pilot_no, pri_did_count, pri_switch_name, pri_dial_code, pri_zone_id, pri_msgn_node, pri_d_channel, pri_channel_count, pri_sponsered, pri_epabx_procured, pri_cost_epabx, pri_penalty_matrix, pri_contract_period, pri_cost_pri_card, pri_vendor_name, pri_ebabx_make, pri_mis_entry, pri_calling_level, pri_hosted_ivr, pri_hivr_no, pri_type) values('".$caf_id."', '".$pri_cug_type."', '".$pri_del_no."', '".$pri_billing_cycle."', '".$pri_pm_email."', '".$pri_connection_type."', '".$pri_parent_account."', '".$pri_wepbax_config."', '".$pri_rid."', '".$pri_addon_account."', '".$pri_service_type_wireline."', '".$pri_pilot_no."', '".$pri_did_count."', '".$pri_switch_name."', '".$pri_dial_code."', '".$pri_zone_id."', '".$pri_msgn_node."', '".$pri_d_channel."', '".$pri_channel_count."', '".$pri_sponsered."', '".$pri_epabx_procured."', '".$pri_cost_epabx."', '".$pri_penalty_matrix."', '".$pri_contract_period."', '".$pri_cost_pri_card."', '".$pri_vendor_name."', '".$pri_ebabx_make."', '".$pri_mis_entry."', '".$pri_calling_level."', '".$pri_hosted_ivr."', '".$pri_hivr_no."', '".$pri_type."')");
				}
			else if($product=='SIP Trunk') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
			else if($product=='Hosted OBD') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
			else if($product=='Standard Centrex') {
					$standard_centrex_group_type = trim($this->escape_string($this->strip_all($data['standard_centrex_group_type'])));
					$standard_centrex_bgid = trim($this->escape_string($this->strip_all($data['standard_centrex_bgid'])));
					$standard_centrex_idp_id = trim($this->escape_string($this->strip_all($data['standard_centrex_idp_id'])));
					$standard_centrex_switch_name = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_name'])));
					$standard_centrex_dail_code = trim($this->escape_string($this->strip_all($data['standard_centrex_dail_code'])));
					$standard_centrex_zone = trim($this->escape_string($this->strip_all($data['standard_centrex_zone'])));
					$standard_centrex_zte_pnr = trim($this->escape_string($this->strip_all($data['standard_centrex_zte_pnr'])));
					$standard_centrex_switch_type = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_type'])));
					$standard_centrex_switch_details = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_details'])));
					$standard_centrex_zone_id = trim($this->escape_string($this->strip_all($data['standard_centrex_zone_id'])));
					$standard_centrex_calling_level = trim($this->escape_string($this->strip_all($data['standard_centrex_calling_level'])));

					$this->query("insert into ".PREFIX."caf_standard_centrex_details(caf_id, standard_centrex_group_type, standard_centrex_bgid, standard_centrex_idp_id, standard_centrex_dail_code, standard_centrex_switch_name, standard_centrex_zone, standard_centrex_zte_pnr, standard_centrex_switch_type, standard_centrex_switch_details, standard_centrex_zone_id, standard_centrex_calling_level) values('".$caf_id."', '".$standard_centrex_group_type."', '".$standard_centrex_bgid."', '".$standard_centrex_idp_id."', '".$standard_centrex_dail_code."', '".$standard_centrex_switch_name."', '".$standard_centrex_zone."', '".$standard_centrex_zte_pnr."', '".$standard_centrex_switch_type."', '".$standard_centrex_switch_details."', '".$standard_centrex_zone_id."', '".$standard_centrex_calling_level."')");
				}
			else if($product=='IP Centrex') {
					$ip_centrex_group_type = trim($this->escape_string($this->strip_all($data['ip_centrex_group_type'])));
					$ip_centrex_bgid = trim($this->escape_string($this->strip_all($data['ip_centrex_bgid'])));
					$ip_centrex_idp_id = trim($this->escape_string($this->strip_all($data['ip_centrex_idp_id'])));
					$ip_centrex_switch_name = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_name'])));
					$ip_centrex_zone_code = trim($this->escape_string($this->strip_all($data['ip_centrex_zone_code'])));
					$ip_centrex_dail_code = trim($this->escape_string($this->strip_all($data['ip_centrex_dail_code'])));
					$ip_centrex_zte_pnr = trim($this->escape_string($this->strip_all($data['ip_centrex_zte_pnr'])));
					$ip_centrex_switch_type = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_type'])));
					$ip_centrex_switch_details = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_details'])));
					$ip_centrex_zone_id = trim($this->escape_string($this->strip_all($data['ip_centrex_zone_id'])));
					$ip_centrex_calling_level = trim($this->escape_string($this->strip_all($data['ip_centrex_calling_level'])));
					$ip_centrex_cug_type = trim($this->escape_string($this->strip_all($data['ip_centrex_cug_type'])));
					$ip_centrex_del_no = trim($this->escape_string($this->strip_all($data['ip_centrex_del_no'])));
					$ip_centrex_billing_cycle = trim($this->escape_string($this->strip_all($data['ip_centrex_billing_cycle'])));
					$ip_centrex_pm_email = trim($this->escape_string($this->strip_all($data['ip_centrex_pm_email'])));
					$ip_centrex_connection_type = trim($this->escape_string($this->strip_all($data['ip_centrex_connection_type'])));
					$ip_centrex_parent_account = trim($this->escape_string($this->strip_all($data['ip_centrex_parent_account'])));
					$ip_centrex_handset_id = trim($this->escape_string($this->strip_all($data['ip_centrex_handset_id'])));
					$ip_centrex_addon_account = trim($this->escape_string($this->strip_all($data['ip_centrex_addon_account'])));
					$ip_centrex_ip_address1 = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_address1'])));
					$ip_centrex_ip_address2 = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_address2'])));
					$ip_centrex_ip_mask = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_mask'])));
					$ip_centrex_vlan_tag = trim($this->escape_string($this->strip_all($data['ip_centrex_vlan_tag'])));
					$ip_centrex_vlan_id = trim($this->escape_string($this->strip_all($data['ip_centrex_vlan_id'])));
					$ip_centrex_dealer_contact = trim($this->escape_string($this->strip_all($data['ip_centrex_dealer_contact'])));
					$ip_centrex_je_email = trim($this->escape_string($this->strip_all($data['ip_centrex_je_email'])));
					$ip_centrex_zte_ctxgrpnr = trim($this->escape_string($this->strip_all($data['ip_centrex_zte_ctxgrpnr'])));
					$ip_centrex_type = trim($this->escape_string($this->strip_all($data['ip_centrex_type'])));
					$ip_centrex_customer_type = trim($this->escape_string($this->strip_all($data['ip_centrex_customer_type'])));
					$ip_centrex_customer_owned_equipment = trim($this->escape_string($this->strip_all($data['ip_centrex_customer_owned_equipment'])));
					$ip_centrex_operator_type = trim($this->escape_string($this->strip_all($data['ip_centrex_operator_type'])));

					if(!empty($file['ip_centrex_ip_address_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_EXTENSION));
						$ip_centrex_ip_address_excel = time().'7.'.$ext;
						move_uploaded_file($file['ip_centrex_ip_address_excel']['tmp_name'],$uploadDir.$ip_centrex_ip_address_excel); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_EXTENSION));
						$ip_centrex_ip_address_excel = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$ip_centrex_ip_address_excel, fopen($file['ip_centrex_ip_address_excel']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_ip_centrex_details(caf_id, ip_centrex_group_type, ip_centrex_bgid, ip_centrex_idp_id, ip_centrex_switch_name, ip_centrex_zone_code, ip_centrex_dail_code, ip_centrex_zte_pnr, ip_centrex_switch_type, ip_centrex_switch_details, ip_centrex_zone_id, ip_centrex_calling_level, ip_centrex_cug_type, ip_centrex_del_no, ip_centrex_billing_cycle, ip_centrex_pm_email, ip_centrex_connection_type, ip_centrex_parent_account, ip_centrex_handset_id, ip_centrex_addon_account, ip_centrex_ip_address1, ip_centrex_ip_address2, ip_centrex_ip_address3, ip_centrex_ip_address4, ip_centrex_ip_address_excel, ip_centrex_ip_mask, ip_centrex_vlan_tag, ip_centrex_vlan_id, ip_centrex_dealer_contact, ip_centrex_je_email, ip_centrex_zte_ctxgrpnr, ip_centrex_type, ip_centrex_customer_type, ip_centrex_customer_owned_equipment, ip_centrex_operator_type) values('".$caf_id."', '".$ip_centrex_group_type."', '".$ip_centrex_bgid."', '".$ip_centrex_idp_id."', '".$ip_centrex_switch_name."', '".$ip_centrex_zone_code."', '".$ip_centrex_dail_code."', '".$ip_centrex_zte_pnr."', '".$ip_centrex_switch_type."', '".$ip_centrex_switch_details."', '".$ip_centrex_zone_id."', '".$ip_centrex_calling_level."', '".$ip_centrex_cug_type."', '".$ip_centrex_del_no."', '".$ip_centrex_billing_cycle."', '".$ip_centrex_pm_email."', '".$ip_centrex_connection_type."', '".$ip_centrex_parent_account."', '".$ip_centrex_handset_id."', '".$ip_centrex_addon_account."', '".$ip_centrex_ip_address1."', '".$ip_centrex_ip_address2."', '".$ip_centrex_ip_address3."', '".$ip_centrex_ip_address4."', '".$ip_centrex_ip_address_excel."', '".$ip_centrex_ip_mask."', '".$ip_centrex_vlan_tag."', '".$ip_centrex_vlan_id."', '".$ip_centrex_dealer_contact."', '".$ip_centrex_je_email."', '".$ip_centrex_zte_ctxgrpnr."', '".$ip_centrex_type."', '".$ip_centrex_customer_type."', '".$ip_centrex_customer_owned_equipment."', '".$ip_centrex_operator_type."')");
				}
			else if($product=='Standard Wireline') {
					$standard_wrln_existing_caf = trim($this->escape_string($this->strip_all($data['standard_wrln_existing_caf'])));
					$standard_wrln_proof_company_id = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_company_id'])));
					$standard_wrln_proof_authorization = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_authorization'])));
					$standard_wrln_proof_address = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_address'])));
					$standard_wrln_other_docs = trim($this->escape_string($this->strip_all($data['standard_wrln_other_docs'])));
					$standard_wrln_govt_id_proof = trim($this->escape_string($this->strip_all($data['standard_wrln_govt_id_proof'])));
					$standard_wrln_cug_type = trim($this->escape_string($this->strip_all($data['standard_wrln_cug_type'])));
					$standard_wrln_cug_no = trim($this->escape_string($this->strip_all($data['standard_wrln_cug_no'])));
					$standard_wrln_billing_cycle = trim($this->escape_string($this->strip_all($data['standard_wrln_billing_cycle'])));
					$standard_wrln_pm_email = trim($this->escape_string($this->strip_all($data['standard_wrln_pm_email'])));
					$standard_wrln_connection_type = trim($this->escape_string($this->strip_all($data['standard_wrln_connection_type'])));
					$standard_wrln_parent_account = trim($this->escape_string($this->strip_all($data['standard_wrln_parent_account'])));
					$standard_wrln_trai_id = trim($this->escape_string($this->strip_all($data['standard_wrln_trai_id'])));
					$standard_wrln_handset_id = trim($this->escape_string($this->strip_all($data['standard_wrln_handset_id'])));
					$standard_wrln_addon_account = trim($this->escape_string($this->strip_all($data['standard_wrln_addon_account'])));
					$standard_wrln_service_type = trim($this->escape_string($this->strip_all($data['standard_wrln_service_type'])));
					$standard_wrln_del_no = trim($this->escape_string($this->strip_all($data['standard_wrln_del_no'])));
					$standard_wrln_operator_type = trim($this->escape_string($this->strip_all($data['standard_wrln_operator_type'])));
					$standard_wrln_operation_id = trim($this->escape_string($this->strip_all($data['standard_wrln_operation_id'])));
					$standard_wrln_customer_type = trim($this->escape_string($this->strip_all($data['standard_wrln_customer_type'])));
					$standard_wrln_ip_type = trim($this->escape_string($this->strip_all($data['standard_wrln_ip_type'])));
					$standard_wrln_dslam_id = trim($this->escape_string($this->strip_all($data['standard_wrln_dslam_id'])));
					$standard_wrln_static_ip_discount = trim($this->escape_string($this->strip_all($data['standard_wrln_static_ip_discount'])));
					$standard_wrln_calling_level = trim($this->escape_string($this->strip_all($data['standard_wrln_calling_level'])));

					if(!empty($file['standard_wrln_approval']['name'])) {
						/* $file_name = strtolower( pathinfo($file['standard_wrln_approval']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['standard_wrln_approval']['name'], PATHINFO_EXTENSION));
						$standard_wrln_approval = time().'7.'.$ext;
						move_uploaded_file($file['standard_wrln_approval']['tmp_name'],$uploadDir.$standard_wrln_approval); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['standard_wrln_approval']['name'], PATHINFO_EXTENSION));
						$standard_wrln_approval = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$standard_wrln_approval, fopen($file['standard_wrln_approval']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_standard_wrln_details(caf_id, standard_wrln_existing_caf, standard_wrln_proof_company_id, standard_wrln_proof_authorization, standard_wrln_proof_address, standard_wrln_other_docs, standard_wrln_govt_id_proof, standard_wrln_approval, standard_wrln_cug_type, standard_wrln_cug_no, standard_wrln_billing_cycle, standard_wrln_pm_email, standard_wrln_connection_type, standard_wrln_parent_account, standard_wrln_trai_id, standard_wrln_handset_id, standard_wrln_addon_account, standard_wrln_service_type, standard_wrln_del_no, standard_wrln_operator_type, standard_wrln_operation_id, standard_wrln_customer_type, standard_wrln_ip_type, standard_wrln_dslam_id, standard_wrln_static_ip_discount, standard_wrln_calling_level) values('".$caf_id."', '".$standard_wrln_existing_caf."', '".$standard_wrln_proof_company_id."', '".$standard_wrln_proof_authorization."', '".$standard_wrln_proof_address."', '".$standard_wrln_other_docs."', '".$standard_wrln_govt_id_proof."', '".$standard_wrln_approval."', '".$standard_wrln_cug_type."', '".$standard_wrln_cug_no."', '".$standard_wrln_billing_cycle."', '".$standard_wrln_pm_email."', '".$standard_wrln_connection_type."', '".$standard_wrln_parent_account."', '".$standard_wrln_trai_id."', '".$standard_wrln_handset_id."', '".$standard_wrln_addon_account."', '".$standard_wrln_service_type."', '".$standard_wrln_del_no."', '".$standard_wrln_operator_type."', '".$standard_wrln_operation_id."', '".$standard_wrln_customer_type."', '".$standard_wrln_ip_type."', '".$standard_wrln_dslam_id."', '".$standard_wrln_static_ip_discount."', '".$standard_wrln_calling_level."')");
				}
			else if($product=='WEPABX') {
					$wepbax_cug_type = trim($this->escape_string($this->strip_all($data['wepbax_cug_type'])));
					$wepbax_connection_type = trim($this->escape_string($this->strip_all($data['wepbax_connection_type'])));
					$wepbax_billing_cycle = trim($this->escape_string($this->strip_all($data['wepbax_billing_cycle'])));
					$wepbax_pm_email = trim($this->escape_string($this->strip_all($data['wepbax_pm_email'])));
					$wepbax_parent_account = trim($this->escape_string($this->strip_all($data['wepbax_parent_account'])));
					$wepbax_addon_account = trim($this->escape_string($this->strip_all($data['wepbax_addon_account'])));
					$wepbax_del_no = trim($this->escape_string($this->strip_all($data['wepbax_del_no'])));
					$wepbax_rid = trim($this->escape_string($this->strip_all($data['wepbax_rid'])));
					$wepbax_config = trim($this->escape_string($this->strip_all($data['wepbax_config'])));
					$wepbax_calling_level = trim($this->escape_string($this->strip_all($data['wepbax_calling_level'])));
					$wepbax_cug_no = trim($this->escape_string($this->strip_all($data['wepbax_cug_no'])));
					$wepbax_handset_id = trim($this->escape_string($this->strip_all($data['wepbax_handset_id'])));

					$this->query("insert into ".PREFIX."caf_wepbax_details(caf_id, wepbax_cug_type, wepbax_billing_cycle, wepbax_pm_email, wepbax_connection_type, wepbax_del_no, wepbax_rid, wepbax_parent_account, wepbax_addon_account, wepbax_config, wepbax_calling_level, wepbax_cug_no, wepbax_handset_id) values('".$caf_id."', '".$wepbax_cug_type."', '".$wepbax_billing_cycle."', '".$wepbax_pm_email."', '".$wepbax_connection_type."', '".$wepbax_del_no."', '".$wepbax_rid."', '".$wepbax_parent_account."', '".$wepbax_addon_account."', '".$wepbax_config."', '".$wepbax_calling_level."', '".$wepbax_cug_no."', '".$wepbax_handset_id."')");
				
			}
			else if($product=='IBS') {
				$ibs_existing_caf = trim($this->escape_string($this->strip_all($data['ibs_existing_caf'])));
				$ibs_proof_company_id = trim($this->escape_string($this->strip_all($data['ibs_proof_company_id'])));
				$ibs_proof_authorization = trim($this->escape_string($this->strip_all($data['ibs_proof_authorization'])));
				$ibs_proof_address = trim($this->escape_string($this->strip_all($data['ibs_proof_address'])));
				$ibs_other_docs = trim($this->escape_string($this->strip_all($data['ibs_other_docs'])));
				$ibs_govt_id_proof = trim($this->escape_string($this->strip_all($data['ibs_govt_id_proof'])));
				$ibs_connection_type = trim($this->escape_string($this->strip_all($data['ibs_connection_type'])));
				$ibs_billing_cycle = trim($this->escape_string($this->strip_all($data['ibs_billing_cycle'])));
				$ibs_pm_email = trim($this->escape_string($this->strip_all($data['ibs_pm_email'])));
				$ibs_del_no = trim($this->escape_string($this->strip_all($data['ibs_del_no'])));
				$ibs_reserved_id = trim($this->escape_string($this->strip_all($data['ibs_reserved_id'])));
				$ibs_addon_account = trim($this->escape_string($this->strip_all($data['ibs_addon_account'])));
				$ibs_parent_account = trim($this->escape_string($this->strip_all($data['ibs_parent_account'])));
				$ibs_type = trim($this->escape_string($this->strip_all($data['ibs_type'])));
				$ibs_provision_on = trim($this->escape_string($this->strip_all($data['ibs_provision_on'])));
				$ibs_present = trim($this->escape_string($this->strip_all($data['ibs_present'])));
				$ibs_mapped_on = trim($this->escape_string($this->strip_all($data['ibs_mapped_on'])));
				$ibs_calling_level = trim($this->escape_string($this->strip_all($data['ibs_calling_level'])));

				if(!empty($file['ibs_approval']['name'])) {
					/* $file_name = strtolower( pathinfo($file['ibs_approval']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['ibs_approval']['name'], PATHINFO_EXTENSION));
					$ibs_approval = time().'7.'.$ext;
					move_uploaded_file($file['ibs_approval']['tmp_name'],$uploadDir.$ibs_approval); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['ibs_approval']['name'], PATHINFO_EXTENSION));
					$ibs_approval = time().'7.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$ibs_approval, fopen($file['ibs_approval']['tmp_name'], 'rb'), 'public-read');
				}

				$this->query("insert into ".PREFIX."caf_ibs_details(caf_id, ibs_existing_caf, ibs_proof_company_id, ibs_proof_authorization, ibs_proof_address, ibs_other_docs, ibs_govt_id_proof, ibs_approval, ibs_connection_type, ibs_billing_cycle, ibs_pm_email, ibs_del_no, ibs_reserved_id, ibs_addon_account, ibs_parent_account, ibs_type, ibs_provision_on, ibs_present, ibs_mapped_on, ibs_calling_level) values('".$caf_id."', '".$ibs_existing_caf."', '".$ibs_proof_company_id."', '".$ibs_proof_authorization."', '".$ibs_proof_address."', '".$ibs_other_docs."', '".$ibs_govt_id_proof."', '".$ibs_approval."', '".$ibs_connection_type."', '".$ibs_billing_cycle."', '".$ibs_pm_email."', '".$ibs_del_no."', '".$ibs_reserved_id."', '".$ibs_addon_account."', '".$ibs_parent_account."', '".$ibs_type."', '".$ibs_provision_on."', '".$ibs_present."', '".$ibs_mapped_on."', '".$ibs_calling_level."')");
			}
			else if($product=='Walky') {
				$walky_sns = trim($this->escape_string($this->strip_all($data['walky_sns'])));
				$walky_type = trim($this->escape_string($this->strip_all($data['walky_type'])));
				$walky_cug_type = trim($this->escape_string($this->strip_all($data['walky_cug_type'])));
				$walky_cug_no = trim($this->escape_string($this->strip_all($data['walky_cug_no'])));
				$walky_billing_cycle = trim($this->escape_string($this->strip_all($data['walky_billing_cycle'])));
				$walky_pm_email = trim($this->escape_string($this->strip_all($data['walky_pm_email'])));
				$walky_connection_type = trim($this->escape_string($this->strip_all($data['walky_connection_type'])));
				$walky_del_no = trim($this->escape_string($this->strip_all($data['walky_del_no'])));
				$walky_rid = trim($this->escape_string($this->strip_all($data['walky_rid'])));
				$walky_parent_account = trim($this->escape_string($this->strip_all($data['walky_parent_account'])));
				$walky_handset_id = trim($this->escape_string($this->strip_all($data['walky_handset_id'])));
				$walky_addon_account = trim($this->escape_string($this->strip_all($data['walky_addon_account'])));
				$walky_trai_id = trim($this->escape_string($this->strip_all($data['walky_trai_id'])));
				$walky_calling_level = trim($this->escape_string($this->strip_all($data['walky_calling_level'])));

				$this->query("insert into ".PREFIX."caf_walky_details(caf_id, walky_sns, walky_type, walky_cug_type, walky_cug_no, walky_billing_cycle, walky_pm_email, walky_connection_type, walky_del_no, walky_rid, walky_parent_account, walky_handset_id, walky_addon_account, walky_trai_id, walky_calling_level) values('".$caf_id."', '".$walky_sns."', '".$walky_type."', '".$walky_cug_type."', '".$walky_cug_no."', '".$walky_billing_cycle."', '".$walky_pm_email."', '".$walky_connection_type."', '".$walky_del_no."', '".$walky_rid."', '".$walky_parent_account."', '".$walky_handset_id."', '".$walky_addon_account."', '".$walky_trai_id."', '".$walky_calling_level."')");
			}
			else if($product=='Mobile') {
				$mobile_sns = trim($this->escape_string($this->strip_all($data['mobile_sns'])));
				$mobile_cug_type = trim($this->escape_string($this->strip_all($data['mobile_cug_type'])));
				$mobile_cug_no = trim($this->escape_string($this->strip_all($data['mobile_cug_no'])));
				$mobile_billing_cycle = trim($this->escape_string($this->strip_all($data['mobile_billing_cycle'])));
				$mobile_pm_email = trim($this->escape_string($this->strip_all($data['mobile_pm_email'])));
				$mobile_connection_type = trim($this->escape_string($this->strip_all($data['mobile_connection_type'])));
				$mobile_del_no = trim($this->escape_string($this->strip_all($data['mobile_del_no'])));
				$mobile_reserved_id = trim($this->escape_string($this->strip_all($data['mobile_reserved_id'])));
				$mobile_parent_account = trim($this->escape_string($this->strip_all($data['mobile_parent_account'])));
				$mobile_handset_id = trim($this->escape_string($this->strip_all($data['mobile_handset_id'])));
				$mobile_addon_account = trim($this->escape_string($this->strip_all($data['mobile_addon_account'])));
				$mobile_addon_account2 = trim($this->escape_string($this->strip_all($data['mobile_addon_account2'])));
				$mobile_addon_account3 = trim($this->escape_string($this->strip_all($data['mobile_addon_account3'])));
				$mobile_spm = trim($this->escape_string($this->strip_all($data['mobile_spm'])));
				$mobile_spn_scheme = trim($this->escape_string($this->strip_all($data['mobile_spn_scheme'])));
				$mobile_calling_level = trim($this->escape_string($this->strip_all($data['mobile_calling_level'])));

				$this->query("insert into ".PREFIX."caf_mobile_details(caf_id, mobile_sns, mobile_cug_type, mobile_cug_no, mobile_billing_cycle, mobile_pm_email, mobile_connection_type, mobile_del_no, mobile_reserved_id, mobile_parent_account, mobile_handset_id, mobile_addon_account, mobile_addon_account2, mobile_addon_account3, mobile_spm, mobile_spn_scheme, mobile_calling_level) values('".$caf_id."', '".$mobile_sns."', '".$mobile_cug_type."', '".$mobile_cug_no."', '".$mobile_billing_cycle."', '".$mobile_pm_email."', '".$mobile_connection_type."', '".$mobile_del_no."', '".$mobile_reserved_id."', '".$mobile_parent_account."', '".$mobile_handset_id."', '".$mobile_addon_account."', '".$mobile_addon_account2."', '".$mobile_addon_account3."', '".$mobile_spm."', '".$mobile_spn_scheme."', '".$mobile_calling_level."')");
			}
			else if($product=='Fleet Management' || $product=='School Bus Tracking' || $product=='Asset Management' || $product=='Workforce Management' || $product=='LaaS') {
				$lbs_connection_type = trim($this->escape_string($this->strip_all($data['lbs_connection_type'])));
				$lbs_del_no = trim($this->escape_string($this->strip_all($data['lbs_del_no'])));
				$lbs_rid = trim($this->escape_string($this->strip_all($data['lbs_rid'])));
				$lbs_pm_email = trim($this->escape_string($this->strip_all($data['lbs_pm_email'])));
				$lbs_billing_cycle = trim($this->escape_string($this->strip_all($data['lbs_billing_cycle'])));
				$lbs_parent_account = trim($this->escape_string($this->strip_all($data['lbs_parent_account'])));
				$lbs_addon_account = trim($this->escape_string($this->strip_all($data['lbs_addon_account'])));
				$lbs_handset_id = trim($this->escape_string($this->strip_all($data['lbs_handset_id'])));
				$lbs_type = trim($this->escape_string($this->strip_all($data['lbs_type'])));
				$lbs_vehicle_no = trim($this->escape_string($this->strip_all($data['lbs_vehicle_no'])));
				$lbs_imei_no = trim($this->escape_string($this->strip_all($data['lbs_imei_no'])));
				$lbs_vendor_type = trim($this->escape_string($this->strip_all($data['lbs_vendor_type'])));
				$lbs_product_type = trim($this->escape_string($this->strip_all($data['lbs_product_type'])));

				$this->query("insert into ".PREFIX."caf_lbs_details(caf_id, lbs_connection_type, lbs_del_no, lbs_rid, lbs_pm_email, lbs_billing_cycle, lbs_parent_account, lbs_addon_account, lbs_handset_id, lbs_type, lbs_vehicle_no, lbs_imei_no, lbs_vendor_type, lbs_product_type) values('".$caf_id."', '".$lbs_connection_type."', '".$lbs_del_no."', '".$lbs_rid."', '".$lbs_pm_email."', '".$lbs_billing_cycle."', '".$lbs_parent_account."', '".$lbs_addon_account."', '".$lbs_handset_id."', '".$lbs_type."', '".$lbs_vehicle_no."', '".$lbs_imei_no."', '".$lbs_vendor_type."', '".$lbs_product_type."')");
			}
			else if($product=='M2M Sim') {
				if($variant=='M2M Standard') {
					if($subvariant=='Vehicle Tracking' || $subvariant=='Others') {
						$lbs_connection_type = trim($this->escape_string($this->strip_all($data['lbs_connection_type'])));
						$lbs_del_no = trim($this->escape_string($this->strip_all($data['lbs_del_no'])));
						$lbs_rid = trim($this->escape_string($this->strip_all($data['lbs_rid'])));
						$lbs_pm_email = trim($this->escape_string($this->strip_all($data['lbs_pm_email'])));
						$lbs_billing_cycle = trim($this->escape_string($this->strip_all($data['lbs_billing_cycle'])));
						$lbs_parent_account = trim($this->escape_string($this->strip_all($data['lbs_parent_account'])));
						$lbs_addon_account = trim($this->escape_string($this->strip_all($data['lbs_addon_account'])));
						$lbs_handset_id = trim($this->escape_string($this->strip_all($data['lbs_handset_id'])));
						$lbs_type = trim($this->escape_string($this->strip_all($data['lbs_type'])));
						$lbs_vehicle_no = trim($this->escape_string($this->strip_all($data['lbs_vehicle_no'])));
						$lbs_imei_no = trim($this->escape_string($this->strip_all($data['lbs_imei_no'])));
						$lbs_vendor_type = trim($this->escape_string($this->strip_all($data['lbs_vendor_type'])));
						$lbs_product_type = trim($this->escape_string($this->strip_all($data['lbs_product_type'])));

						$this->query("insert into ".PREFIX."caf_lbs_details(caf_id, lbs_connection_type, lbs_del_no, lbs_rid, lbs_pm_email, lbs_billing_cycle, lbs_parent_account, lbs_addon_account, lbs_handset_id, lbs_type, lbs_vehicle_no, lbs_imei_no, lbs_vendor_type, lbs_product_type) values('".$caf_id."', '".$lbs_connection_type."', '".$lbs_del_no."', '".$lbs_rid."', '".$lbs_pm_email."', '".$lbs_billing_cycle."', '".$lbs_parent_account."', '".$lbs_addon_account."', '".$lbs_handset_id."', '".$lbs_type."', '".$lbs_vehicle_no."', '".$lbs_imei_no."', '".$lbs_vendor_type."', '".$lbs_product_type."')");
					}
				}
			}
			else if($product=='Toll Free Services' || $product=='Call Register Services') {
				$crs_connection_type = trim($this->escape_string($this->strip_all($data['crs_connection_type'])));
				$crs_ibs = trim($this->escape_string($this->strip_all($data['crs_ibs'])));
				$crs_provision_on = trim($this->escape_string($this->strip_all($data['crs_provision_on'])));
				$crs_present = trim($this->escape_string($this->strip_all($data['crs_present'])));
				$crs_mapped_on = trim($this->escape_string($this->strip_all($data['crs_mapped_on'])));
				$crs_calling_level = trim($this->escape_string($this->strip_all($data['crs_calling_level'])));
				$crs_billing_cycle = trim($this->escape_string($this->strip_all($data['crs_billing_cycle'])));
				$crs_pm_email = trim($this->escape_string($this->strip_all($data['crs_pm_email'])));
				$crs_parent_account = trim($this->escape_string($this->strip_all($data['crs_parent_account'])));
				$crs_addon_account = trim($this->escape_string($this->strip_all($data['crs_addon_account'])));
				$crs_rid = trim($this->escape_string($this->strip_all($data['crs_rid'])));
				$crs_del_no = trim($this->escape_string($this->strip_all($data['crs_del_no'])));

				$this->query("insert into ".PREFIX."caf_crs_details(caf_id, crs_connection_type, crs_ibs, crs_provision_on, crs_present, crs_mapped_on, crs_calling_level, crs_billing_cycle, crs_pm_email, crs_parent_account, crs_addon_account, crs_rid, crs_del_no) values('".$caf_id."', '".$crs_connection_type."', '".$crs_ibs."', '".$crs_provision_on."', '".$crs_present."', '".$crs_mapped_on."', '".$crs_calling_level."', '".$crs_billing_cycle."', '".$crs_pm_email."', '".$crs_parent_account."', '".$crs_addon_account."', '".$crs_rid."', '".$crs_del_no."')");
			}
			else if($product=='HIVR' || $product=='Webconnect') {
				$toll_free_cug_type = trim($this->escape_string($this->strip_all($data['toll_free_cug_type'])));
				$toll_free_del_no = trim($this->escape_string($this->strip_all($data['toll_free_del_no'])));
				$toll_free_billing_cycle = trim($this->escape_string($this->strip_all($data['toll_free_billing_cycle'])));
				$toll_free_pm_email = trim($this->escape_string($this->strip_all($data['toll_free_pm_email'])));
				$toll_free_connection_type = trim($this->escape_string($this->strip_all($data['toll_free_connection_type'])));
				$toll_free_parent_account = trim($this->escape_string($this->strip_all($data['toll_free_parent_account'])));
				$toll_free_rid = trim($this->escape_string($this->strip_all($data['toll_free_rid'])));
				$toll_free_wepbax_config = trim($this->escape_string($this->strip_all($data['toll_free_wepbax_config'])));
				$toll_free_addon_account = trim($this->escape_string($this->strip_all($data['toll_free_addon_account'])));
				$toll_free_service_type_wireline = trim($this->escape_string($this->strip_all($data['toll_free_service_type_wireline'])));
				$toll_free_pilot_no = trim($this->escape_string($this->strip_all($data['toll_free_pilot_no'])));
				$toll_free_did_count = trim($this->escape_string($this->strip_all($data['toll_free_did_count'])));
				$toll_free_switch_name = trim($this->escape_string($this->strip_all($data['toll_free_switch_name'])));
				$toll_free_dial_code = trim($this->escape_string($this->strip_all($data['toll_free_dial_code'])));
				$toll_free_zone_id = trim($this->escape_string($this->strip_all($data['toll_free_zone_id'])));
				$toll_free_msgn_node = trim($this->escape_string($this->strip_all($data['toll_free_msgn_node'])));
				$toll_free_d_channel = trim($this->escape_string($this->strip_all($data['toll_free_d_channel'])));
				$toll_free_channel_count = trim($this->escape_string($this->strip_all($data['toll_free_channel_count'])));
				$toll_free_sponsered_pri = trim($this->escape_string($this->strip_all($data['toll_free_sponsered_pri'])));
				$toll_free_epabx_procured = trim($this->escape_string($this->strip_all($data['toll_free_epabx_procured'])));
				$toll_free_cost_epabx = trim($this->escape_string($this->strip_all($data['toll_free_cost_epabx'])));
				$toll_free_penalty_matrix = trim($this->escape_string($this->strip_all($data['toll_free_penalty_matrix'])));
				$toll_free_contract_period_pri = trim($this->escape_string($this->strip_all($data['toll_free_contract_period_pri'])));
				$toll_free_cost_pri_card = trim($this->escape_string($this->strip_all($data['toll_free_cost_pri_card'])));
				$toll_free_vendor_name = trim($this->escape_string($this->strip_all($data['toll_free_vendor_name'])));
				$toll_free_ebabx_make = trim($this->escape_string($this->strip_all($data['toll_free_ebabx_make'])));
				$toll_free_mis_entry = trim($this->escape_string($this->strip_all($data['toll_free_mis_entry'])));
				$toll_free_calling_level = trim($this->escape_string($this->strip_all($data['toll_free_calling_level'])));
				$toll_free_call_per_day = trim($this->escape_string($this->strip_all($data['toll_free_call_per_day'])));
				$toll_free_call_duration = trim($this->escape_string($this->strip_all($data['toll_free_call_duration'])));
				$toll_free_call_concurrency = trim($this->escape_string($this->strip_all($data['toll_free_call_concurrency'])));
				$toll_free_call_unit = trim($this->escape_string($this->strip_all($data['toll_free_call_unit'])));
				$toll_free_recording_required = trim($this->escape_string($this->strip_all($data['toll_free_recording_required'])));
				$toll_free_ct_required = trim($this->escape_string($this->strip_all($data['toll_free_ct_required'])));
				$toll_free_acd_required = trim($this->escape_string($this->strip_all($data['toll_free_acd_required'])));
				$toll_free_prompt_recording_required = trim($this->escape_string($this->strip_all($data['toll_free_prompt_recording_required'])));
				$toll_free_languages = trim($this->escape_string($this->strip_all($data['toll_free_languages'])));
				$toll_free_routing_required = trim($this->escape_string($this->strip_all($data['toll_free_routing_required'])));
				$toll_free_crm_integration_required = trim($this->escape_string($this->strip_all($data['toll_free_crm_integration_required'])));
				$toll_free_ivr_level = trim($this->escape_string($this->strip_all($data['toll_free_ivr_level'])));
				$toll_free_avg_hold_time_ivr = trim($this->escape_string($this->strip_all($data['toll_free_avg_hold_time_ivr'])));

				$this->query("insert into ".PREFIX."caf_toll_free_details(caf_id, toll_free_cug_type, toll_free_del_no, toll_free_billing_cycle, toll_free_pm_email, toll_free_connection_type, toll_free_parent_account, toll_free_rid, toll_free_wepbax_config, toll_free_addon_account, toll_free_service_type_wireline, toll_free_pilot_no, toll_free_did_count, toll_free_channel_count, toll_free_switch_name, toll_free_dial_code, toll_free_zone_id, toll_free_msgn_node, toll_free_d_channel, toll_free_sponsered_pri, toll_free_epabx_procured, toll_free_cost_epabx, toll_free_penalty_matrix, toll_free_contract_period_pri, toll_free_cost_pri_card, toll_free_vendor_name, toll_free_ebabx_make, toll_free_mis_entry, toll_free_calling_level, toll_free_call_per_day, toll_free_call_duration, toll_free_call_concurrency, toll_free_call_unit, toll_free_recording_required, toll_free_ct_required, toll_free_acd_required, toll_free_prompt_recording_required, toll_free_languages, toll_free_routing_required, toll_free_crm_integration_required, toll_free_ivr_level, toll_free_avg_hold_time_ivr) values('".$caf_id."', '".$toll_free_cug_type."', '".$toll_free_del_no."', '".$toll_free_billing_cycle."', '".$toll_free_pm_email."', '".$toll_free_connection_type."', '".$toll_free_parent_account."', '".$toll_free_rid."', '".$toll_free_wepbax_config."', '".$toll_free_addon_account."', '".$toll_free_service_type_wireline."', '".$toll_free_pilot_no."', '".$toll_free_did_count."', '".$toll_free_channel_count."', '".$toll_free_switch_name."', '".$toll_free_dial_code."', '".$toll_free_zone_id."', '".$toll_free_msgn_node."', '".$toll_free_d_channel."', '".$toll_free_sponsered_pri."', '".$toll_free_epabx_procured."', '".$toll_free_cost_epabx."', '".$toll_free_penalty_matrix."', '".$toll_free_contract_period_pri."', '".$toll_free_cost_pri_card."', '".$toll_free_vendor_name."', '".$toll_free_ebabx_make."', '".$toll_free_mis_entry."', '".$toll_free_calling_level."', '".$toll_free_call_per_day."', '".$toll_free_call_duration."', '".$toll_free_call_concurrency."', '".$toll_free_call_unit."', '".$toll_free_recording_required."', '".$toll_free_ct_required."', '".$toll_free_acd_required."', '".$toll_free_prompt_recording_required."', '".$toll_free_languages."', '".$toll_free_routing_required."', '".$toll_free_crm_integration_required."', '".$toll_free_ivr_level."', '".$toll_free_avg_hold_time_ivr."')");
			}
			else if($product=='SMS Solutions') {
				$sms_connection_type = trim($this->escape_string($this->strip_all($data['sms_connection_type'])));
				$sms_del_no = trim($this->escape_string($this->strip_all($data['sms_del_no'])));
				$sms_reserved_id = trim($this->escape_string($this->strip_all($data['sms_reserved_id'])));
				$sms_pm_email = trim($this->escape_string($this->strip_all($data['sms_pm_email'])));
				$sms_billing_cycle = trim($this->escape_string($this->strip_all($data['sms_billing_cycle'])));
				$sms_parent_account = trim($this->escape_string($this->strip_all($data['sms_parent_account'])));
				$sms_addon_account = trim($this->escape_string($this->strip_all($data['sms_addon_account'])));
				$sms_handset_id = trim($this->escape_string($this->strip_all($data['sms_handset_id'])));
				$sms_type = trim($this->escape_string($this->strip_all($data['sms_type'])));
				$sms_te_type = trim($this->escape_string($this->strip_all($data['sms_te_type'])));
				$sms_trai_id = trim($this->escape_string($this->strip_all($data['sms_trai_id'])));
				$sms_transactional_sender_id = trim($this->escape_string($this->strip_all($data['sms_transactional_sender_id'])));
				$sms_promotional_sender_id = trim($this->escape_string($this->strip_all($data['sms_promotional_sender_id'])));
				$sms_ip_address1 = trim($this->escape_string($this->strip_all($data['sms_ip_address1'])));
				$sms_ip_address2 = trim($this->escape_string($this->strip_all($data['sms_ip_address2'])));
				$sms_pull_url = trim($this->escape_string($this->strip_all($data['sms_pull_url'])));
				$sms_push_type = trim($this->escape_string($this->strip_all($data['sms_push_type'])));
				$sms_customer_server_location = trim($this->escape_string($this->strip_all($data['sms_customer_server_location'])));
				$sms_additional_maas_details = trim($this->escape_string($this->strip_all($data['sms_additional_maas_details'])));
				$sms_connectivity = trim($this->escape_string($this->strip_all($data['sms_connectivity'])));
				$sms_web_based_gui = trim($this->escape_string($this->strip_all($data['sms_web_based_gui'])));
				$sms_api_integration = trim($this->escape_string($this->strip_all($data['sms_api_integration'])));
				$sms_standard_reports = trim($this->escape_string($this->strip_all($data['sms_standard_reports'])));
				$sms_customization = trim($this->escape_string($this->strip_all($data['sms_customization'])));
				$sms_calling_level = trim($this->escape_string($this->strip_all($data['sms_calling_level'])));

				if(!empty($file['sms_ip_upload']['name'])) {
					/* $file_name = strtolower( pathinfo($file['sms_ip_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['sms_ip_upload']['name'], PATHINFO_EXTENSION));
					$sms_ip_upload = time().'7.'.$ext;
					move_uploaded_file($file['sms_ip_upload']['tmp_name'],$uploadDir.$sms_ip_upload); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['sms_ip_upload']['name'], PATHINFO_EXTENSION));
					$sms_ip_upload = time().'7.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$sms_ip_upload, fopen($file['sms_ip_upload']['tmp_name'], 'rb'), 'public-read');
				}
				$this->query("insert into ".PREFIX."caf_sms_details(caf_id, sms_connection_type, sms_del_no, sms_reserved_id, sms_pm_email, sms_billing_cycle, sms_parent_account, sms_addon_account, sms_trai_id, sms_type, sms_te_type, sms_transactional_sender_id, sms_promotional_sender_id, sms_ip_address1, sms_ip_address2, sms_ip_upload, sms_pull_url, sms_push_type, sms_customer_server_location, sms_additional_maas_details, sms_connectivity, sms_web_based_gui, sms_api_integration, sms_standard_reports, sms_customization, sms_calling_level) values('".$caf_id."', '".$sms_connection_type."', '".$sms_del_no."', '".$sms_reserved_id."', '".$sms_pm_email."', '".$sms_billing_cycle."', '".$sms_parent_account."', '".$sms_addon_account."', '".$sms_trai_id."', '".$sms_type."', '".$sms_te_type."', '".$sms_transactional_sender_id."', '".$sms_promotional_sender_id."', '".$sms_ip_address1."', '".$sms_ip_address2."', '".$sms_ip_upload."', '".$sms_pull_url."', '".$sms_push_type."', '".$sms_customer_server_location."', '".$sms_additional_maas_details."', '".$sms_connectivity."', '".$sms_web_based_gui."', '".$sms_api_integration."', '".$sms_standard_reports."', '".$sms_customization."', '".$sms_calling_level."')");
			}
			else if($product=='SNS Solution') {
				$sns_present = trim($this->escape_string($this->strip_all($data['sns_present'])));
				$sns_type = trim($this->escape_string($this->strip_all($data['sns_type'])));
				$sns_calling_level = trim($this->escape_string($this->strip_all($data['sns_calling_level'])));
				$sns_switch_name = trim($this->escape_string($this->strip_all($data['sns_switch_name'])));
				$sns_dial_code = trim($this->escape_string($this->strip_all($data['sns_dial_code'])));
				$sns_zone = trim($this->escape_string($this->strip_all($data['sns_zone'])));
				$sns_zte_pnr = trim($this->escape_string($this->strip_all($data['sns_zte_pnr'])));
				$sns_msgn_node = trim($this->escape_string($this->strip_all($data['sns_msgn_node'])));

				$this->query("insert into ".PREFIX."caf_sns_details(caf_id, sns_present, sns_type, sns_calling_level, sns_switch_name, sns_dial_code, sns_zone, sns_zte_pnr, sns_msgn_node) values('".$caf_id."', '".$sns_present."', '".$sns_type."', '".$sns_calling_level."', '".$sns_switch_name."', '".$sns_dial_code."', '".$sns_zone."', '".$sns_zte_pnr."', '".$sns_msgn_node."')");
			}
			else if($product=='Tele Marketing- 140') {
				if($variant=='SIP') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
				else if($variant=='PRI') {
					$pri_cug_type = trim($this->escape_string($this->strip_all($data['pri_cug_type'])));
					$pri_connection_type = trim($this->escape_string($this->strip_all($data['pri_connection_type'])));
					$pri_billing_cycle = trim($this->escape_string($this->strip_all($data['pri_billing_cycle'])));
					$pri_pm_email = trim($this->escape_string($this->strip_all($data['pri_pm_email'])));
					$pri_parent_account = trim($this->escape_string($this->strip_all($data['pri_parent_account'])));
					$pri_addon_account = trim($this->escape_string($this->strip_all($data['pri_addon_account'])));
					$pri_rid = trim($this->escape_string($this->strip_all($data['pri_rid'])));
					$pri_del_no = trim($this->escape_string($this->strip_all($data['pri_del_no'])));
					$pri_wepbax_config = trim($this->escape_string($this->strip_all($data['pri_wepbax_config'])));
					$pri_service_type_wireline = trim($this->escape_string($this->strip_all($data['pri_service_type_wireline'])));
					$pri_pilot_no = trim($this->escape_string($this->strip_all($data['pri_pilot_no'])));
					$pri_did_count = trim($this->escape_string($this->strip_all($data['pri_did_count'])));
					$pri_channel_count = trim($this->escape_string($this->strip_all($data['pri_channel_count'])));
					$pri_switch_name = trim($this->escape_string($this->strip_all($data['pri_switch_name'])));
					$pri_dial_code = trim($this->escape_string($this->strip_all($data['pri_dial_code'])));
					$pri_zone_id = trim($this->escape_string($this->strip_all($data['pri_zone_id'])));
					$pri_msgn_node = trim($this->escape_string($this->strip_all($data['pri_msgn_node'])));
					$pri_d_channel = trim($this->escape_string($this->strip_all($data['pri_d_channel'])));
					$pri_sponsered = trim($this->escape_string($this->strip_all($data['pri_sponsered'])));
					$pri_epabx_procured = trim($this->escape_string($this->strip_all($data['pri_epabx_procured'])));
					$pri_cost_epabx = trim($this->escape_string($this->strip_all($data['pri_cost_epabx'])));
					$pri_penalty_matrix = trim($this->escape_string($this->strip_all($data['pri_penalty_matrix'])));
					$pri_contract_period = trim($this->escape_string($this->strip_all($data['pri_contract_period'])));
					$pri_cost_pri_card = trim($this->escape_string($this->strip_all($data['pri_cost_pri_card'])));
					$pri_vendor_name = trim($this->escape_string($this->strip_all($data['pri_vendor_name'])));
					$pri_ebabx_make = trim($this->escape_string($this->strip_all($data['pri_ebabx_make'])));
					$pri_mis_entry = trim($this->escape_string($this->strip_all($data['pri_mis_entry'])));
					$pri_calling_level = trim($this->escape_string($this->strip_all($data['pri_calling_level'])));
					$pri_hosted_ivr = trim($this->escape_string($this->strip_all($data['pri_hosted_ivr'])));
					$pri_hivr_no = trim($this->escape_string($this->strip_all($data['pri_hivr_no'])));
					$pri_type = trim($this->escape_string($this->strip_all($data['pri_type'])));

					$this->query("insert into ".PREFIX."caf_pri_details(caf_id, pri_cug_type, pri_del_no, pri_billing_cycle, pri_pm_email, pri_connection_type, pri_parent_account, pri_wepbax_config, pri_rid, pri_addon_account, pri_service_type_wireline, pri_pilot_no, pri_did_count, pri_switch_name, pri_dial_code, pri_zone_id, pri_msgn_node, pri_d_channel, pri_channel_count, pri_sponsered, pri_epabx_procured, pri_cost_epabx, pri_penalty_matrix, pri_contract_period, pri_cost_pri_card, pri_vendor_name, pri_ebabx_make, pri_mis_entry, pri_calling_level, pri_hosted_ivr, pri_hivr_no, pri_type) values('".$caf_id."', '".$pri_cug_type."', '".$pri_del_no."', '".$pri_billing_cycle."', '".$pri_pm_email."', '".$pri_connection_type."', '".$pri_parent_account."', '".$pri_wepbax_config."', '".$pri_rid."', '".$pri_addon_account."', '".$pri_service_type_wireline."', '".$pri_pilot_no."', '".$pri_did_count."', '".$pri_switch_name."', '".$pri_dial_code."', '".$pri_zone_id."', '".$pri_msgn_node."', '".$pri_d_channel."', '".$pri_channel_count."', '".$pri_sponsered."', '".$pri_epabx_procured."', '".$pri_cost_epabx."', '".$pri_penalty_matrix."', '".$pri_contract_period."', '".$pri_cost_pri_card."', '".$pri_vendor_name."', '".$pri_ebabx_make."', '".$pri_mis_entry."', '".$pri_calling_level."', '".$pri_hosted_ivr."', '".$pri_hivr_no."', '".$pri_type."')");
				}
			}
			else if($product=='Hosted OBD') {
				$hosted_obd_ivr = trim($this->escape_string($this->strip_all($data['hosted_obd_ivr'])));
				$hosted_obd_hivr_no = trim($this->escape_string($this->strip_all($data['hosted_obd_hivr_no'])));
				$hosted_obd_switch_type = trim($this->escape_string($this->strip_all($data['hosted_obd_switch_type'])));
				$hosted_obd_switch_details = trim($this->escape_string($this->strip_all($data['hosted_obd_switch_details'])));
				$hosted_obd_zone_id = trim($this->escape_string($this->strip_all($data['hosted_obd_zone_id'])));
				$hosted_obd_type = trim($this->escape_string($this->strip_all($data['hosted_obd_type'])));
				$hosted_obd_billing_cycle = trim($this->escape_string($this->strip_all($data['hosted_obd_billing_cycle'])));
				$hosted_obd_pm_email = trim($this->escape_string($this->strip_all($data['hosted_obd_pm_email'])));
				$hosted_obd_calling_level = trim($this->escape_string($this->strip_all($data['hosted_obd_calling_level'])));
				$hosted_obd_connection_type = trim($this->escape_string($this->strip_all($data['hosted_obd_connection_type'])));
				$hosted_obd_del_no = trim($this->escape_string($this->strip_all($data['hosted_obd_del_no'])));
				$hosted_obd_reserved_id = trim($this->escape_string($this->strip_all($data['hosted_obd_reserved_id'])));

				$this->query("insert into ".PREFIX."caf_hosted_obd_details(caf_id, hosted_obd_ivr, hosted_obd_hivr_no, hosted_obd_switch_type, hosted_obd_switch_details, hosted_obd_zone_id, hosted_obd_type, hosted_obd_billing_cycle, hosted_obd_pm_email, hosted_obd_connection_type, hosted_obd_del_no, hosted_obd_reserved_id, hosted_obd_parent_account, hosted_obd_addon_account, hosted_obd_calling_level) values('".$caf_id."', '".$hosted_obd_ivr."', '".$hosted_obd_hivr_no."', '".$hosted_obd_switch_type."', '".$hosted_obd_switch_details."', '".$hosted_obd_zone_id."', '".$hosted_obd_type."', '".$hosted_obd_billing_cycle."', '".$hosted_obd_pm_email."', '".$hosted_obd_connection_type."', '".$hosted_obd_del_no."', '".$hosted_obd_reserved_id."', '".$hosted_obd_parent_account."', '".$hosted_obd_addon_account."', '".$hosted_obd_calling_level."')");
			}
			else if($product=='Conferencing') {
				if($variant=='Audio Conferencing' || $variant=='Web Conferencing') {
					$audio_conf_pgi_landline_no = trim($this->escape_string($this->strip_all($data['audio_conf_pgi_landline_no'])));
					$audio_conf_del_number = trim($this->escape_string($this->strip_all($data['audio_conf_del_number'])));
					$this->query("insert into ".PREFIX."caf_audio_conf_details(caf_id, audio_conf_pgi_landline_no, audio_conf_del_no) values('".$caf_id."', '".$audio_conf_pgi_landline_no."', '".$audio_conf_del_number."')");
				}
			}
			// Service Enrollment
			
			$emp_data = $this->getUniqueCafDetailsById($id);
			if($emp_data['caf_status'] == "Draft")
			{
				if(isset($data['update'])) 
				{
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with Customer' ,caf_submission_date = '".date('Y-m-d H:i:s')."' where id='".$caf_id."'");
					
					// Service Enrollment
					$responseArr = array();
					$responseArr['caf_id'] = $caf_id;
					$unique_id = $emp_data['unique_id'] ;
					$caf_no = $emp_data['caf_no'] ;
					$sales_support_email = $emp_data['sales_support_email'] ; 
					$additional_customer_email = $emp_data['additional_customer_email'];
					$loggedInUserDetailsArr = $this->sessionExists();
					$verification_link=md5($caf_id.'#'.$name);
					$this->query("update ".PREFIX."caf_details set verification_link='$verification_link' where id='".$caf_id."'");
					
					include("caf-verification-mail.inc-update.php");	
					
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($email);
					$mail->AddCC($loggedInUserDetailsArr['username']);
					if(!empty($sales_support_email)) { $mail->AddCC($sales_support_email); }
					if(!empty($additional_customer_email)) { $mail->AddCC($additional_customer_email); }
					
					$mail->IsHTML(true);
					$mail->Subject = "Thank you for choosing Tata Tele Business Services. Company Name:".$emp_data['company_name']." and CAF no ".$caf_no.".";
					$mail->Body = $emailMsg;
					$mail->Send();
					$mail->SmtpClose();
					//echo $emailMsg;
					
					include("caf-verification-mail-sales.inc-update.php");
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($loggedInUserDetailsArr['username']);
					$mail->IsHTML(true);
					//$mail->Subject = "CAF Status of ".$company_name;
					$mail->Subject = "Company Name:".$emp_data['company_name']." and CAF no ".$caf_no." received and has been forwarded for verification ";
					$mail->Body = $emailMsg;
					$mail->Send();
					//echo $emailMsg;
					
					return $responseArr;
				}
				
			}
			if($emp_data['caf_status'] == "Duplicate"){
				
				if(isset($data['save_update'])) 
				{
					$this->query("update ".PREFIX."caf_details set caf_status='Draft' where id='".$caf_id."'");
				}
				else{
					$this->query("update ".PREFIX."caf_details set caf_status='Pending with Customer' , caf_submission_date = '".date('Y-m-d H:i:s')."' where id='".$caf_id."'");
					
					// Service Enrollment
					$responseArr = array();
					$responseArr['caf_id'] = $caf_id;
					$unique_id = $emp_data['unique_id'] ;
					$caf_no = $emp_data['caf_no'] ;
					$sales_support_email = $emp_data['sales_support_email'] ; 
					$additional_customer_email = $emp_data['additional_customer_email'];
					$loggedInUserDetailsArr = $this->sessionExists();
					$verification_link=md5($caf_id.'#'.$name);
					$this->query("update ".PREFIX."caf_details set verification_link='$verification_link' where id='".$caf_id."'");
					$email = $emp_data['email'];
					
					include("caf-verification-mail.inc-update.php");	
					
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($email);
					$mail->AddCC($loggedInUserDetailsArr['username']);
					if(!empty($sales_support_email)) { $mail->AddCC($sales_support_email); }
					if(!empty($additional_customer_email)) { $mail->AddCC($additional_customer_email); }
					$mail->IsHTML(true);
					$mail->Subject = "Thank you for choosing Tata Tele Business Services. Company Name:".$emp_data['company_name']." and CAF Number:".$caf_no.". ";
					$mail->Body = $emailMsg;
					$mail->Send();
					$mail->SmtpClose();
					//echo $emailMsg;
					
					include("caf-verification-mail-sales.inc-update.php");
					$mail = new PHPMailer();
					$mail->IsSMTP();
					$mail->SMTPAuth = true;
					$mail->AddAddress($loggedInUserDetailsArr['username']);
					$mail->IsHTML(true);
					//$mail->Subject = "CAF Status of ".$company_name;
					$mail->Subject = "Company Name: ".$emp_data['company_name']." and CAF no ".$caf_no." received and has been forwarded for verification ";
					$mail->Body = $emailMsg;
					$mail->Send();
					//echo $emailMsg;
					
					return $responseArr;
				}
			}
		}

		function updateCustomerCafData($data,$file) {
			
			$id = trim($this->escape_string($this->strip_all($data['id'])));
			
			$olddata = $this->getUniqueCafDetailsById($id);
			
			$company_name = empty($data['company_name']) ? $olddata['company_name'] : trim($this->escape_string($this->strip_all($data['company_name'])));
			//$company_name = trim($this->escape_string($this->strip_all($data['company_name'])));
			
			$pan = empty($data['pan']) ? $olddata['pan'] : trim($this->escape_string($this->strip_all($data['pan'])));
			//$pan = trim($this->escape_string($this->strip_all($data['pan'])));
			
			$osp = empty($data['osp']) ? $olddata['osp'] : trim($this->escape_string($this->strip_all($data['osp'])));
			//$osp = trim($this->escape_string($this->strip_all($data['osp'])));
			
			$title = empty($data['title']) ? $olddata['title'] : trim($this->escape_string($this->strip_all($data['title'])));
			//$title = trim($this->escape_string($this->strip_all($data['title'])));
			
			$sez_certificate_no = empty($data['sez_certificate_no']) ? $olddata['sez_certificate_no'] : trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			//$sez_certificate_no = trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			
			$salutation = empty($data['salutation']) ? $olddata['salutation'] : trim($this->escape_string($this->strip_all($data['salutation'])));
			//$salutation = trim($this->escape_string($this->strip_all($data['salutation'])));
			
			$name = empty($data['name']) ? $olddata['name'] : trim($this->escape_string($this->strip_all($data['name'])));
			//$name = trim($this->escape_string($this->strip_all($data['name'])));
			
			$designation = empty($data['designation']) ? $olddata['designation'] : trim($this->escape_string($this->strip_all($data['designation'])));
			//$designation = trim($this->escape_string($this->strip_all($data['designation'])));
			
			$anotherdesignation = empty($data['anotherdesignation']) ? $olddata['anotherdesignation'] : trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			//$anotherdesignation = trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			
			$email = empty($data['email']) ? $olddata['email'] : trim($this->escape_string($this->strip_all($data['email'])));
			//$email = trim($this->escape_string($this->strip_all($data['email'])));
			
			$mobile = empty($data['mobile']) ? $olddata['mobile'] : trim($this->escape_string($this->strip_all($data['mobile'])));
			//$mobile = trim($this->escape_string($this->strip_all($data['mobile'])));
			
			$telephone = empty($data['telephone']) ? $olddata['telephone'] : trim($this->escape_string($this->strip_all($data['telephone'])));
			//$telephone = trim($this->escape_string($this->strip_all($data['telephone'])));
			
			$aadhar_no = empty($data['aadhar_no']) ? $olddata['aadhar_no'] : trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			//$aadhar_no = trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			$same_contact_person = trim($this->escape_string($this->strip_all($data['same_contact_person'])));
			if($data['same_contact_person']=='Yes') {
				$contact_person = $this->escape_string($this->strip_all($name));
				$contact_person_designation = $this->escape_string($this->strip_all($designation));
				$contact_person_email = $this->escape_string($this->strip_all($email));
				$contact_person_mobile = $this->escape_string($this->strip_all($mobile));
			} else {
				$contact_person = trim($this->escape_string($this->strip_all($data['contact_person'])));
				$contact_person_designation = trim($this->escape_string($this->strip_all($data['contact_person_designation'])));
				$contact_person_email = trim($this->escape_string($this->strip_all($data['contact_person_email'])));
				$contact_person_mobile = trim($this->escape_string($this->strip_all($data['contact_person_mobile'])));
			}
			$alternate_bdlg_no = trim($this->escape_string($this->strip_all($data['alternate_bdlg_no'])));
			$alternate_bdlg_name = trim($this->escape_string($this->strip_all($data['alternate_bdlg_name'])));
			$alternate_floor = trim($this->escape_string($this->strip_all($data['alternate_floor'])));
			$alternate_street_name = trim($this->escape_string($this->strip_all($data['alternate_street_name'])));
			$alternate_area = trim($this->escape_string($this->strip_all($data['alternate_area'])));
			$alternate_landmark = trim($this->escape_string($this->strip_all($data['alternate_landmark'])));
			$alternate_state = trim($this->escape_string($this->strip_all($data['alternate_state'])));
			$alternate_city = trim($this->escape_string($this->strip_all($data['alternate_city'])));
			$alternate_pincode = trim($this->escape_string($this->strip_all($data['alternate_pincode'])));
			//$alternate_multiple_installation_address = trim($this->escape_string($this->strip_all($data['alternate_multiple_installation_address'])));
			
			$same_billing_address = trim($this->escape_string($this->strip_all($data['same_billing_address'])));
			$acceptgst = trim($this->escape_string($this->strip_all($data['acceptgst'])));
			if($data['same_billing_address']=='Yes') {
				$billing_bdlg_no = $this->escape_string($this->strip_all($alternate_bdlg_no));
				$billing_bdlg_name = $this->escape_string($this->strip_all($alternate_bdlg_name));
				$billing_floor = $this->escape_string($this->strip_all($alternate_floor));
				$billing_street_name = $this->escape_string($this->strip_all($alternate_street_name));
				$billing_area = $this->escape_string($this->strip_all($alternate_area));
				$billing_landmark = $this->escape_string($this->strip_all($alternate_landmark));
				$billing_state = $this->escape_string($this->strip_all($alternate_state));
				$billing_city = $this->escape_string($this->strip_all($alternate_city));
				$billing_pincode = $this->escape_string($this->strip_all($alternate_pincode));
			} else {
				$billing_bdlg_no = trim($this->escape_string($this->strip_all($data['billing_bdlg_no'])));
				$billing_bdlg_name = trim($this->escape_string($this->strip_all($data['billing_bdlg_name'])));
				$billing_floor = trim($this->escape_string($this->strip_all($data['billing_floor'])));
				$billing_street_name = trim($this->escape_string($this->strip_all($data['billing_street_name'])));
				$billing_area = trim($this->escape_string($this->strip_all($data['billing_area'])));
				$billing_landmark = trim($this->escape_string($this->strip_all($data['billing_landmark'])));
				$billing_state = trim($this->escape_string($this->strip_all($data['billing_state'])));
				$billing_city = trim($this->escape_string($this->strip_all($data['billing_city'])));
				$billing_pincode = trim($this->escape_string($this->strip_all($data['billing_pincode'])));
			}
			//$caf_no = '';
			
			$caf_no = trim($this->escape_string($this->strip_all($data['caf_no'])));
			if(!empty($alternate_state) && !empty($alternate_city) && $caf_no == '0'){
				$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
			}


			$uploadDir = 'caf-uploads/';
			if(!empty($file['image_name']['name'])) {
				/* $file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				move_uploaded_file($file['image_name']['tmp_name'],$uploadDir.$image_name); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$image_name, fopen($file['image_name']['tmp_name'], 'rb'), 'public-read');
				if(!empty($image_name)) {
					$this->query("update ".PREFIX."caf_details set image_name='".$image_name."' where id='".$id."'");
				}
			}
			if(!empty($file['alternate_multiple_installation_address']['name'])) {
				//echo "here";exit;
				$file_name = strtolower( pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_EXTENSION));
				$alternate_multiple_installation_address = time().'01.'.$ext;
				move_uploaded_file($file['alternate_multiple_installation_address']['tmp_name'],$uploadDir.$alternate_multiple_installation_address);
				$this->query("update ".PREFIX."caf_details set alternate_multiple_installation_address='".$alternate_multiple_installation_address."' where id='".$id."'");
			}
			// $dealer_detail_code = trim($this->escape_string($this->strip_all($data['dealer_detail_code'])));
			// $dealer_detail_name = trim($this->escape_string($this->strip_all($data['dealer_detail_name'])));
			
			$detailselect = $this->query("select id from tata_caf_details WHERE caf_no='".$caf_no."'");
			$detailfetch = $this->fetch($detailselect);
			$new_id= $detailfetch['id'];
			
			$prodselect = $this->query("select * from tata_caf_product_details WHERE caf_id='".$new_id."'");
			$prodfetch = $this->fetch($prodselect);
			$product = $prodfetch['product'];
			$prodvariant = $prodfetch['variant'];
			$date = date("Y-m-d H:i:s");
			
			if($product == 'Internet Leased Line' || $product == 'Smart VPN' || $product == 'Leased Line' || $product == 'L2 Multicast Solution' || $product == 'Dark Fiber' || $prodvariant == 'Internet Leased Line'){
				//echo "here";exit;
				$cafstatus = 'Pending with EBA';
				$verification_datetime = ", eba_start_datetime ='".$date."'";
			}else{
				$cafstatus = 'Pending with OE';
				$verification_datetime = ", verification_datetime='".$date."'";
			} 
			
			
			
			$query = "update ".PREFIX."caf_details set caf_no='".$caf_no."', company_name='".$company_name."', pan='".$pan."', osp='".$osp."', title='".$title."', sez_certificate_no='".$sez_certificate_no."', salutation='".$salutation."', name='".$name."', designation='".$designation."', anotherdesignation='".$anotherdesignation."', email='".$email."', mobile='".$mobile."', telephone='".$telephone."', aadhar_no='".$aadhar_no."', same_contact_person='".$same_contact_person."', contact_person='".$contact_person."', contact_person_designation='".$contact_person_designation."', contact_person_email='".$contact_person_email."', contact_person_mobile='".$contact_person_mobile."', alternate_bdlg_no='".$alternate_bdlg_no."', alternate_bdlg_name='".$alternate_bdlg_name."', alternate_floor='".$alternate_floor."', alternate_street_name='".$alternate_street_name."', alternate_area='".$alternate_area."', alternate_landmark='".$alternate_landmark."', alternate_state='".$alternate_state."', alternate_city='".$alternate_city."', alternate_pincode='".$alternate_pincode."', same_billing_address='".$same_billing_address."', billing_bdlg_no='".$billing_bdlg_no."', billing_bdlg_name='".$billing_bdlg_name."', billing_floor='".$billing_floor."', billing_street_name='".$billing_street_name."', billing_area='".$billing_area."', billing_landmark='".$billing_landmark."', billing_state='".$billing_state."', billing_city='".$billing_city."', billing_pincode='".$billing_pincode."', acceptgst='".$acceptgst."', customer_verified='Yes', caf_status='".$cafstatus."' $verification_datetime where id='".$id."'"; // change Pending with OE to Pending with EBA
 			$sql = $this->query($query);
            
			$caf_id = $id;
			
			// PRODUCT DETAILS
			/* $product = trim($this->escape_string($this->strip_all($data['product'])));
			$variant = trim($this->escape_string($this->strip_all($data['variant'])));
			$sub_variant = trim($this->escape_string($this->strip_all($data['sub_variant'])));
			$category = trim($this->escape_string($this->strip_all($data['category'])));
			$no_del = trim($this->escape_string($this->strip_all($data['no_del'])));
			$no_did = trim($this->escape_string($this->strip_all($data['no_did'])));
			$no_channel = trim($this->escape_string($this->strip_all($data['no_channel'])));
			$no_drop_locations = trim($this->escape_string($this->strip_all($data['no_drop_locations'])));
			$mobile_no = trim($this->escape_string($this->strip_all($data['mobile_no'])));
			$del_no = trim($this->escape_string($this->strip_all($data['del_no'])));
			$pilot_no = trim($this->escape_string($this->strip_all($data['pilot_no'])));
			$imsi_no = trim($this->escape_string($this->strip_all($data['imsi_no'])));
			$did_range = trim($this->escape_string($this->strip_all($data['did_range'])));
			$did_range_to = trim($this->escape_string($this->strip_all($data['did_range_to'])));
			$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			$arc = trim($this->escape_string($this->strip_all($data['arc'])));
			$arc_type = trim($this->escape_string($this->strip_all($data['arc_type'])));
			$monthly_rental = trim($this->escape_string($this->strip_all($data['monthly_rental'])));
			$nrc = trim($this->escape_string($this->strip_all($data['nrc'])));
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));
			$lockin_period = trim($this->escape_string($this->strip_all($data['lockin_period'])));
			$security_deposit = trim($this->escape_string($this->strip_all($data['security_deposit'])));
			$activation_fee = trim($this->escape_string($this->strip_all($data['activation_fee'])));
			$trai_id = trim($this->escape_string($this->strip_all($data['trai_id'])));
			$billing_frequency = trim($this->escape_string($this->strip_all($data['billing_frequency'])));
			$billing_type = trim($this->escape_string($this->strip_all($data['billing_type']))); */
			$bill_mode = trim($this->escape_string($this->strip_all($data['bill_mode'])));
			/* $cug_id = trim($this->escape_string($this->strip_all($data['cug_id']))); */

			/* if(!empty($file['drop_location_sheet']['name'])) {
				$file_name = strtolower( pathinfo($file['drop_location_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				move_uploaded_file($file['drop_location_sheet']['tmp_name'],$uploadDir.$drop_location_sheet);
				$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$drop_location_sheet."' where caf_id='".$caf_id."'");
			} */

			/* $query = "update ".PREFIX."caf_product_details set product='".$product."', variant='".$variant."', sub_variant='".$sub_variant."', category='".$category."', no_del='".$no_del."', no_did='".$no_did."', no_channel='".$no_channel."', no_drop_locations='".$no_drop_locations."', mobile_no='".$mobile_no."', del_no='".$del_no."', pilot_no='".$pilot_no."', imsi_no='".$imsi_no."', did_range='".$did_range."', did_range_to='".$did_range_to."', bandwidth='".$bandwidth."', arc='".$arc."', arc_type='".$arc_type."', monthly_rental='".$monthly_rental."', nrc='".$nrc."', bill_plan_opted='".$bill_plan_opted."', lockin_period='".$lockin_period."', security_deposit='".$security_deposit."', activation_fee='".$activation_fee."', trai_id='".$trai_id."', billing_frequency='".$billing_frequency."', billing_type='".$billing_type."', bill_mode='".$bill_mode."', cug_id='".$cug_id."' where caf_id='".$caf_id."'"; */
			$query = "update ".PREFIX."caf_product_details set bill_mode='".$bill_mode."' where caf_id='".$caf_id."'";
			$this->query($query);
			// PRODUCT DETAILS

			// DOCUMENT DETAILS
			$registration_document_type = trim($this->escape_string($this->strip_all($data['registration_document_type'])));
			$registration_document_type_other = trim($this->escape_string($this->strip_all($data['registration_document_type_other'])));
			$address_document_type = trim($this->escape_string($this->strip_all($data['address_document_type'])));
			$address_document_type_other = trim($this->escape_string($this->strip_all($data['address_document_type_other'])));
			$identity_document_type = trim($this->escape_string($this->strip_all($data['identity_document_type'])));
			$identity_document_type_other = trim($this->escape_string($this->strip_all($data['identity_document_type_other'])));
			$authorisation_document_type = trim($this->escape_string($this->strip_all($data['authorisation_document_type'])));
			$authorisation_document_type_other = trim($this->escape_string($this->strip_all($data['authorisation_document_type_other'])));
			
			$installation_document_type = trim($this->escape_string($this->strip_all($data['installation_document_type'])));
			$installation_document_type_other = trim($this->escape_string($this->strip_all($data['installation_document_type_other'])));
			
			$documentData_sql = $this->query("select * from ".PREFIX."caf_document_details where caf_id='".$caf_id."'");
			
			if($this->num_rows($documentData_sql) > 0) {
				//new by dhanashree*/
				$query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."', installation_document_type_other='".$installation_document_type_other."', installation_document_type ='".$installation_document_type."' where caf_id='".$caf_id."'";
				$this->query($query);
			}else{
				
				$query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, address_document_type, address_document_type_other, identity_document_type, identity_document_type_other, authorisation_document_type, authorisation_document_type_other,installation_document_type, installation_document_type_other) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$address_document_type."', '".$address_document_type_other."', '".$identity_document_type."', '".$identity_document_type_other."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$installation_document_type."', '".$installation_document_type_other."')";
				$this->query($query);
			}

			/* if(!empty($file['registration_document']['name'])) {
				$file_name = strtolower( pathinfo($file['registration_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['registration_document']['name'], PATHINFO_EXTENSION));
				$registration_document = time().'21.'.$ext;
				move_uploaded_file($file['registration_document']['tmp_name'],$uploadDir.$registration_document);
				$this->query("update ".PREFIX."caf_document_details set registration_document='".$registration_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['address_document']['name'])) {
				$file_name = strtolower( pathinfo($file['address_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['address_document']['name'], PATHINFO_EXTENSION));
				$address_document = time().'22.'.$ext;
				move_uploaded_file($file['address_document']['tmp_name'],$uploadDir.$address_document);
				$this->query("update ".PREFIX."caf_document_details set address_document='".$address_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['identity_document']['name'])) {
				$file_name = strtolower( pathinfo($file['identity_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['identity_document']['name'], PATHINFO_EXTENSION));
				$identity_document = time().'23.'.$ext;
				move_uploaded_file($file['identity_document']['tmp_name'],$uploadDir.$identity_document);
				$this->query("update ".PREFIX."caf_document_details set identity_document='".$identity_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['authorisation_document']['name'])) {
				$file_name = strtolower( pathinfo($file['authorisation_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['authorisation_document']['name'], PATHINFO_EXTENSION));
				$authorisation_document = time().'24.'.$ext;
				move_uploaded_file($file['authorisation_document']['tmp_name'],$uploadDir.$authorisation_document);
				$this->query("update ".PREFIX."caf_document_details set authorisation_document='".$authorisation_document."' where caf_id='".$caf_id."'");
			} */
			
			$currentdatetime = date("Y-m-d H:i:s");
			if(!empty($file['tef_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tef_download='".$tef_download."', tef_download_date='".$currentdatetime."' where caf_id='".$caf_id."'");
			}
			if(!empty($file['tef_download1']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download1']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download1']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tef_download='".$tef_download."', tef_download_date='".$currentdatetime."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['tm_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tm_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				move_uploaded_file($file['tm_download']['tmp_name'],$uploadDir.$tm_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tm_download, fopen($file['tm_download']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tm_download='".$tm_download."', tm_download_date ='".$currentdatetime."'  where caf_id='".$caf_id."'");
			}

			if(!empty($file['trai_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['trai_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				move_uploaded_file($file['trai_form']['tmp_name'],$uploadDir.$trai_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$trai_form, fopen($file['trai_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set trai_form='".$trai_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['dd_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['dd_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				move_uploaded_file($file['dd_form']['tmp_name'],$uploadDir.$dd_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$dd_form, fopen($file['dd_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set dd_form='".$dd_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['osp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['osp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				move_uploaded_file($file['osp_form']['tmp_name'],$uploadDir.$osp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$osp_form, fopen($file['osp_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set osp_form='".$osp_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['sez_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['sez_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				move_uploaded_file($file['sez_form']['tmp_name'],$uploadDir.$sez_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$sez_form, fopen($file['sez_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set sez_form='".$sez_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['bulk_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['bulk_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				move_uploaded_file($file['bulk_form']['tmp_name'],$uploadDir.$bulk_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$bulk_form, fopen($file['bulk_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set bulk_form='".$bulk_form."' where caf_id='".$caf_id."'");
			}
			//done by dhanashree*/
			if(!empty($file['billing_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['billing_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				move_uploaded_file($file['billing_form']['tmp_name'],$uploadDir.$billing_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$billing_form, fopen($file['billing_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set billing_form='".$billing_form."' where caf_id='".$caf_id."'");
			}
			if(!empty($file['gst_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['gst_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				move_uploaded_file($file['gst_form']['tmp_name'],$uploadDir.$gst_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$gst_form, fopen($file['gst_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set gst_form='".$gst_form."' where caf_id='".$caf_id."'");
			}
			//done by dhanashree
			if($data['segment'] != "LE" ) { 
			
				if(!empty($file['all_form']['name'])) {
					/* $file_name = strtolower( pathinfo($file['all_form']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
					$all_form = time().'18.'.$ext;
					move_uploaded_file($file['all_form']['tmp_name'],$uploadDir.$all_form); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
					$all_form = time().'18.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$all_form, fopen($file['all_form']['tmp_name'], 'rb'), 'public-read');
					$this->query("update ".PREFIX."caf_document_details set all_form='".$all_form."' where caf_id='".$caf_id."'");
				}
			}

			if(!empty($file['logical_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['logical_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				move_uploaded_file($file['logical_form']['tmp_name'],$uploadDir.$logical_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$logical_form, fopen($file['logical_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set logical_form='".$logical_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['stc_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['stc_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				move_uploaded_file($file['stc_form']['tmp_name'],$uploadDir.$stc_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$stc_form, fopen($file['stc_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set stc_form='".$stc_form."' where caf_id='".$caf_id."'");
			}

			// $query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."' where caf_id='".$caf_id."'";
			
			
			
			/* done by dhanashree*/
			$po_upload_date = date("Y-m-d H:i:s");
			if(!empty($file['po_upload']['name'])) {
				/* $file_name = strtolower( pathinfo($file['po_upload']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				move_uploaded_file($file['po_upload']['tmp_name'],$uploadDir.$po_upload); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$po_upload, fopen($file['po_upload']['tmp_name'], 'rb'), 'public-read');
				
				$this->query("update ".PREFIX."caf_details set po_upload='".$po_upload."',po_upload_date='".$po_upload_date."' where id='".$caf_id."'");
			}
			
			

			$c=30;

			$documentResultRS = $this->getCAFRegistrationDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['registration_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_registration_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['registration_document']['name'] as $key=>$value) {
				if(!empty($data['registration_id'][$key])) {
					$registration_id = $this->escape_string($this->strip_all($data['registration_id'][$key]));
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_registration_document set document='".$registration_document."' where id='".$registration_id."'");
					}
				} else {
					$registration_document = '';
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_registration_document(caf_id, document) values ('$caf_id', '$registration_document')");
				}
			}

			$documentResultRS = $this->getCAFAddressDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['address_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_address_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['address_document']['name'] as $key=>$value) {
				if(!empty($data['address_id'][$key])) {
					$address_id = $this->escape_string($this->strip_all($data['address_id'][$key]));
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_address_document set document='".$address_document."' where id='".$address_id."'");
					}
				} else {
					$address_document = '';
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_address_document(caf_id, document) values ('$caf_id', '$address_document')");
				}
			}

			$documentResultRS = $this->getCAFIdentityDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['identity_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_identity_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['identity_document']['name'] as $key=>$value) {
				if(!empty($data['identity_id'][$key])) {
					$identity_id = $this->escape_string($this->strip_all($data['identity_id'][$key]));
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_identity_document set document='".$identity_document."' where id='".$identity_id."'");
					}
				} else {
					$identity_document = '';
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_identity_document(caf_id, document) values ('$caf_id', '$identity_document')");
				}
			}

			$documentResultRS = $this->getCAFAuthDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['authorisation_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_authorisation_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['authorisation_document']['name'] as $key=>$value) {
				if(!empty($data['authorisation_id'][$key])) {
					$authorisation_id = $this->escape_string($this->strip_all($data['authorisation_id'][$key]));
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_authorisation_document set document='".$authorisation_document."' where id='".$authorisation_id."'");
					}
				} else {
					$authorisation_document = '';
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_authorisation_document(caf_id, document) values ('$caf_id', '$authorisation_document')");
				}
			}
			
			//new by dhanashree*/
			$documentResultRS = $this->getCAFInstallationDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['installation_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_installation_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['installation_document']['name'] as $key=>$value) {
				if(!empty($data['installation_id'][$key])) {
					$installation_id = $this->escape_string($this->strip_all($data['installation_id'][$key]));
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_installation_document set document='".$installation_document."' where id='".$installation_id."'");
					}
				} else {
					$installation_document = '';
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_installation_document(caf_id, document) values ('$caf_id', '$installation_document')");
				}
			}
			//done by dhanashree
			if($data['segment'] != "LE" ) { 
				//for other
				$documentResultRS = $this->getCAFOtherDocuments($caf_id);
				while($documentResult = $this->fetch($documentResultRS)){
					if(!in_array($documentResult['id'],$data['other_form_id'])) {
						unlink($uploadDir.$documentResult['document']);
						$this->query("delete from ".PREFIX."caf_other_document where id='".$documentResult['id']."'");
					}
				}
				foreach($file['other_form']['name'] as $key=>$value) {
					if(!empty($data['other_form_id'][$key])) {
						$other_form_id = $this->escape_string($this->strip_all($data['other_form_id'][$key]));
						if(!empty($file['other_form']['name'][$key])) {
							$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
							/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_other_document set document='".$other_form."' where id='".$other_form_id."'");
						}
					} else {
						$other_form = '';
						if(!empty($file['other_form']['name'][$key])) {
							$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
							/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'], 'rb'), 'public-read');
						}
						$this->query("insert into ".PREFIX."caf_other_document(caf_id, document) values ('$caf_id', '$other_form')");
					}
				}
			}

			// DOCUMENT DETAILS

			// OTHER DETAILS
			$mobile_connection1 = trim($this->escape_string($this->strip_all($data['mobile_connection1'])));
			$mobile_connection2 = trim($this->escape_string($this->strip_all($data['mobile_connection2'])));
			$mobile_connection3 = trim($this->escape_string($this->strip_all($data['mobile_connection3'])));
			$mobile_connection4 = trim($this->escape_string($this->strip_all($data['mobile_connection4'])));
			$no_connection1 = trim($this->escape_string($this->strip_all($data['no_connection1'])));
			$no_connection2 = trim($this->escape_string($this->strip_all($data['no_connection2'])));
			$no_connection3 = trim($this->escape_string($this->strip_all($data['no_connection3'])));
			$no_connection4 = trim($this->escape_string($this->strip_all($data['no_connection4'])));
			$mobile_connection_total = trim($this->escape_string($this->strip_all($data['mobile_connection_total'])));
			$is_mnp = trim($this->escape_string($this->strip_all($data['is_mnp'])));
			$upc_code = trim($this->escape_string($this->strip_all($data['upc_code'])));
			$upc_code_date = trim($this->escape_string($this->strip_all($data['upc_code_date'])));
			$existing_operator = trim($this->escape_string($this->strip_all($data['existing_operator'])));
			$porting_imsi_no = trim($this->escape_string($this->strip_all($data['porting_imsi_no'])));
			/* $payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'])));
			$payment_type = trim($this->escape_string($this->strip_all($data['payment_type']))); */
			// if($payment_mode != 'Cash') {
				$paymentDetailsRS = $this->getCAFPaymentDetailsByCAFId($caf_id);
				while($paymentDetails = $this->fetch($paymentDetailsRS)){
					if(!in_array($paymentDetails['id'],$data['payment_id'])){
						$this->query("delete from ".PREFIX."caf_payment_details where id='".$paymentDetails['id']."'");
					}
				}
				foreach($data['payment_type'] as $key=>$value) {
					$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'][$key])));
					$payment_type = trim($this->escape_string($this->strip_all($data['payment_type'][$key])));
					$bank_name = trim($this->escape_string($this->strip_all($data['bank_name'][$key])));
					$bank_acc_no = trim($this->escape_string($this->strip_all($data['bank_acc_no'][$key])));
					$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
					$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
					$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
					if(!empty($data['payment_id'][$key])) {
						$payment_id = $this->escape_string($this->strip_all($data['payment_id'][$key]));
						$this->query("update ".PREFIX."caf_payment_details set payment_mode='".$payment_mode."', bank_name='".$bank_name."', bank_acc_no='".$bank_acc_no."', branch_address='".$branch_address."', transactional_details='".$transactional_details."', transaction_amount='".$transaction_amount."' where id='".$payment_id."'");
					} else {
						$this->query("insert into ".PREFIX."caf_payment_details(caf_id, payment_mode, payment_type, bank_name, bank_acc_no, branch_address, transactional_details, transaction_amount) values ('".$caf_id."', '".$payment_mode."', '".$payment_type."', '".$bank_name."', '".$bank_acc_no."', '".$branch_address."', '".$transactional_details."', '".$transaction_amount."')");
					}
				}
			// }
			$grand_amount = trim($this->escape_string($this->strip_all($data['grand_amount'])));

			if($is_mnp=='Yes' and !empty($file['mnp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['mnp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				move_uploaded_file($file['mnp_form']['tmp_name'],$uploadDir.$mnp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$mnp_form, fopen($file['mnp_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_other_details set mnp_form='".$mnp_form."' where caf_id='".$caf_id."'");
			}

			$query = "update ".PREFIX."caf_other_details set mobile_connection1='".$mobile_connection1."', mobile_connection2='".$mobile_connection2."', mobile_connection3='".$mobile_connection3."', mobile_connection4='".$mobile_connection4."', no_connection1='".$no_connection1."', no_connection2='".$no_connection2."', no_connection3='".$no_connection3."', no_connection4='".$no_connection4."', mobile_connection_total='".$mobile_connection_total."', is_mnp='".$is_mnp."', upc_code='".$upc_code."', upc_code_date='".$upc_code_date."', existing_operator='".$existing_operator."', porting_imsi_no='".$porting_imsi_no."', grand_amount='".$grand_amount."' where caf_id='".$caf_id."'";
			$this->query($query);
			// OTHER DETAILS
			//need email integration
			
			$emp_caf_data = $this->getUniqueCafDetailsById($caf_id);
			$sales_data = $this->getUniqueUserById($emp_caf_data['emp_id']);
			$emp_name = $emp_caf_data['name'];
			$sales_name = $sales_data['full_name'];
			$caf_no = $emp_caf_data['caf_no'];
			$sales_to = $sales_data['username']; 
			$unique_id = $emp_caf_data['unique_id'];
			$sales_support_email = $emp_caf_data['sales_support_email'] ; 
			$additional_customer_email = $emp_caf_data['additional_customer_email'] ; 
			include_once("caf-form-submission-mail.inc.php");
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
			$mail->AddAddress($email);
			if(!empty($sales_support_email)) { $mail->AddCC($sales_support_email); }
			if(!empty($additional_customer_email)) { $mail->AddCC($additional_customer_email); }
			$mail->IsHTML(true);
			//$mail->Subject = "CAF Status of ".$company_name;
			$mail->Subject = "Company Name: ".$emp_caf_data['company_name']." and CAF no ".$caf_no." received and has been forwarded for verification ";
			$mail->Body = $emailMsg;
			$mail->Send();
			
			include_once("caf-form-submission-mail-sales.inc.php");
			$mail = new PHPMailer();
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
			$mail->AddAddress($sales_to);
			$mail->IsHTML(true);
			//$mail->Subject = "CAF Status of ".$company_name;
			$mail->Subject = "Company Name: ".$emp_caf_data['company_name']." and CAF no ".$caf_no." received and has been forwarded for verification ";
			$mail->Body = $emailMsg;
			$mail->Send();
			//echo $emailMsg;
		}

		function addCustomerCafData($data,$file) {
			$company_name = trim($this->escape_string($this->strip_all($data['company_name'])));
			$pan = trim($this->escape_string($this->strip_all($data['pan'])));
			$osp = trim($this->escape_string($this->strip_all($data['osp'])));
			$title = trim($this->escape_string($this->strip_all($data['title'])));
			$sez_certificate_no = trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			$salutation = trim($this->escape_string($this->strip_all($data['salutation'])));
			$name = trim($this->escape_string($this->strip_all($data['name'])));
			$designation = trim($this->escape_string($this->strip_all($data['designation'])));
			$anotherdesignation = trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			$email = trim($this->escape_string($this->strip_all($data['email'])));
			$mobile = trim($this->escape_string($this->strip_all($data['mobile'])));
			$telephone = trim($this->escape_string($this->strip_all($data['telephone'])));
			$aadhar_no = trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			$same_contact_person = trim($this->escape_string($this->strip_all($data['same_contact_person'])));
			$emp_id = trim($this->escape_string($this->strip_all($data['emp_id'])));
			if(isset($data['segment'])) { $segment = trim($this->escape_string($this->strip_all($data['segment']))); }else{ $segment = 'NULL'; }
			
			if($data['same_contact_person']=='Yes') {
				$contact_person = $this->escape_string($this->strip_all($name));
				$contact_person_designation = $this->escape_string($this->strip_all($designation));
				$contact_person_email = $this->escape_string($this->strip_all($email));
				$contact_person_mobile = $this->escape_string($this->strip_all($mobile));
			} else {
				$contact_person = trim($this->escape_string($this->strip_all($data['contact_person'])));
				$contact_person_designation = trim($this->escape_string($this->strip_all($data['contact_person_designation'])));
				$contact_person_email = trim($this->escape_string($this->strip_all($data['contact_person_email'])));
				$contact_person_mobile = trim($this->escape_string($this->strip_all($data['contact_person_mobile'])));
			}
			$alternate_bdlg_no = trim($this->escape_string($this->strip_all($data['alternate_bdlg_no'])));
			$alternate_bdlg_name = trim($this->escape_string($this->strip_all($data['alternate_bdlg_name'])));
			$alternate_floor = trim($this->escape_string($this->strip_all($data['alternate_floor'])));
			$alternate_street_name = trim($this->escape_string($this->strip_all($data['alternate_street_name'])));
			$alternate_area = trim($this->escape_string($this->strip_all($data['alternate_area'])));
			$alternate_landmark = trim($this->escape_string($this->strip_all($data['alternate_landmark'])));
			$alternate_state = trim($this->escape_string($this->strip_all($data['alternate_state'])));
			$alternate_city = trim($this->escape_string($this->strip_all($data['alternate_city'])));
			$alternate_pincode = trim($this->escape_string($this->strip_all($data['alternate_pincode'])));
			//$alternate_multiple_installation_address = trim($this->escape_string($this->strip_all($data['alternate_multiple_installation_address'])));
			
			$same_billing_address = trim($this->escape_string($this->strip_all($data['same_billing_address'])));
			if($data['same_billing_address']=='Yes') {
				$billing_bdlg_no = $this->escape_string($this->strip_all($alternate_bdlg_no));
				$billing_bdlg_name = $this->escape_string($this->strip_all($alternate_bdlg_name));
				$billing_floor = $this->escape_string($this->strip_all($alternate_floor));
				$billing_street_name = $this->escape_string($this->strip_all($alternate_street_name));
				$billing_area = $this->escape_string($this->strip_all($alternate_area));
				$billing_landmark = $this->escape_string($this->strip_all($alternate_landmark));
				$billing_state = $this->escape_string($this->strip_all($alternate_state));
				$billing_city = $this->escape_string($this->strip_all($alternate_city));
				$billing_pincode = $this->escape_string($this->strip_all($alternate_pincode));
			} else {
				$billing_bdlg_no = trim($this->escape_string($this->strip_all($data['billing_bdlg_no'])));
				$billing_bdlg_name = trim($this->escape_string($this->strip_all($data['billing_bdlg_name'])));
				$billing_floor = trim($this->escape_string($this->strip_all($data['billing_floor'])));
				$billing_street_name = trim($this->escape_string($this->strip_all($data['billing_street_name'])));
				$billing_area = trim($this->escape_string($this->strip_all($data['billing_area'])));
				$billing_landmark = trim($this->escape_string($this->strip_all($data['billing_landmark'])));
				$billing_state = trim($this->escape_string($this->strip_all($data['billing_state'])));
				$billing_city = trim($this->escape_string($this->strip_all($data['billing_city'])));
				$billing_pincode = trim($this->escape_string($this->strip_all($data['billing_pincode'])));
			}
		
			// $user_data = $this->fetch($this->query("select * from tata_admin where id = '$emp_id' "));
			
			// if(!empty($user_data['login_circle'])){
				
				// //$caf_no = $this->generateCafNo_new($user_data['login_circle'],$alternate_state); 
				// $caf_no = $this->generateCafNo($alternate_state,$alternate_city);
			
			// }else{
				
				$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
			//}
			//$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
			$accepttermscondition = $this->escape_string($data['accepttermscondition']);
			$acceptgst = $this->escape_string($data['acceptgst']);
			if($accepttermscondition=='accepttermscondition') {
				$customer_form_sent='Yes';
			} else {
				$customer_form_sent='No';
			}

			$uploadDir = 'caf-uploads/';
			if(!empty($file['image_name']['name'])) {
				/* $file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				move_uploaded_file($file['image_name']['tmp_name'],$uploadDir.$image_name); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$image_name, fopen($file['image_name']['tmp_name'], 'rb'), 'public-read');
			}
			// if(!empty($file['alternate_multiple_installation_address']['name'])) {
				// //echo "here";exit;
				// $file_name = strtolower( pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_FILENAME));
				// $ext = strtolower(pathinfo($file['alternate_multiple_installation_address']['name'], PATHINFO_EXTENSION));
				// $alternate_multiple_installation_address = time().'01.'.$ext;
				// move_uploaded_file($file['alternate_multiple_installation_address']['tmp_name'],$uploadDir.$alternate_multiple_installation_address);
				// //$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$ill_fan_nmber_upload."' where caf_id='".$caf_id."'");
			// }
			
			/*by dhanashree*/
				$random_no 		= str_shuffle('1234567890-');
				$random_no		= substr($random_no,0,5);
				$random_letters = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
				$random_letters	= substr($random_letters,0,5);
				$random_str 	= str_shuffle($random_letters.$random_no);
				
				$unique_id = $this->generate_no($random_str,'caf_details','unique_id');
				if(empty($emp_id)){ $emp_id = '0'; }
			
			$query = "insert into ".PREFIX."caf_details(emp_id,segment,unique_id,caf_no, company_name, pan, osp, title, sez_certificate_no, salutation, name, designation, anotherdesignation, email, mobile, telephone, aadhar_no, same_contact_person, contact_person, contact_person_designation, contact_person_email, contact_person_mobile, image_name, alternate_bdlg_no, alternate_bdlg_name, alternate_floor, alternate_street_name, alternate_area, alternate_landmark, alternate_state, alternate_city, alternate_pincode, alternate_multiple_installation_address, same_billing_address, billing_bdlg_no, billing_bdlg_name, billing_floor, billing_street_name, billing_area, billing_landmark, billing_state, billing_city, billing_pincode, acceptgst, customer_form_sent, customer_verified , form_flag , caf_status) values (".$emp_id.",'".$segment."','".$unique_id."','".$caf_no."', '".$company_name."', '".$pan."', '".$osp."', '".$title."', '".$sez_certificate_no."', '".$salutation."', '".$name."', '".$designation."', '".$anotherdesignation."', '".$email."', '".$mobile."', '".$telephone."', '".$aadhar_no."', '".$same_contact_person."', '".$contact_person."', '".$contact_person_designation."', '".$contact_person_email."', '".$contact_person_mobile."', '".$image_name."', '".$alternate_bdlg_no."', '".$alternate_bdlg_name."', '".$alternate_floor."', '".$alternate_street_name."', '".$alternate_area."', '".$alternate_landmark."', '".$alternate_state."', '".$alternate_city."', '".$alternate_pincode."', '".$alternate_multiple_installation_address."', '".$same_billing_address."', '".$billing_bdlg_no."', '".$billing_bdlg_name."', '".$billing_floor."', '".$billing_street_name."', '".$billing_area."', '".$billing_landmark."', '".$billing_state."', '".$billing_city."', '".$billing_pincode."', '".$acceptgst."', '".$customer_form_sent."', 'Yes', 'DCAF_link','Pending with Sales')";
			
			$sql = $this->query($query);

			$caf_id = $this->last_insert_id();
			
			
			// PRODUCT DETAILS
			$product = trim($this->escape_string($this->strip_all($data['product'])));
			$variant = trim($this->escape_string($this->strip_all($data['variant'])));
			$sub_variant = trim($this->escape_string($this->strip_all($data['sub_variant'])));
			$category = trim($this->escape_string($this->strip_all($data['category'])));
			$no_del = trim($this->escape_string($this->strip_all($data['no_del'])));
			$no_did = trim($this->escape_string($this->strip_all($data['no_did'])));
			$no_channel = trim($this->escape_string($this->strip_all($data['no_channel'])));
			$no_drop_locations = trim($this->escape_string($this->strip_all($data['no_drop_locations'])));
			$mobile_no = trim($this->escape_string($this->strip_all($data['mobile_no'])));
			$del_no = trim($this->escape_string($this->strip_all($data['del_no'])));
			$pilot_no = trim($this->escape_string($this->strip_all($data['pilot_no'])));
			$imsi_no = trim($this->escape_string($this->strip_all($data['imsi_no'])));
			$did_range = trim($this->escape_string($this->strip_all($data['did_range'])));
			$did_range_to = trim($this->escape_string($this->strip_all($data['did_range_to'])));
			//$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			if(!empty($product) && $product == "Internet Leased Line"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['ill_text_bandwidth'])));
			}else if(!empty($product) && $product == "Smart VPN"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['mpls_text_bandwidth'])));
			}else if(!empty($product) && $product == "SmartOffice"){
				$bandwidth = trim($this->escape_string($this->strip_all($data['sill_text_bandwidth'])));
			}
			else{
				$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			}
			$arc = trim($this->escape_string($this->strip_all($data['arc'])));
			$arc_type = trim($this->escape_string($this->strip_all($data['arc_type'])));
			$monthly_rental = trim($this->escape_string($this->strip_all($data['monthly_rental'])));
			$nrc = trim($this->escape_string($this->strip_all($data['nrc'])));
			//$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));
			if($data['bill_plan_opted']){
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));
			}else{
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted_select'])));	
			}
			$lockin_period = trim($this->escape_string($this->strip_all($data['lockin_period'])));
			$security_deposit = trim($this->escape_string($this->strip_all($data['security_deposit'])));
			$activation_fee = trim($this->escape_string($this->strip_all($data['activation_fee'])));
			$trai_id = trim($this->escape_string($this->strip_all($data['trai_id'])));
			//done by dhanashree
			$rack_type = trim($this->escape_string($this->strip_all($data['rack_type'])));
			$sales_type = trim($this->escape_string($this->strip_all($data['sales_type'])));
			$billing_frequency = trim($this->escape_string($this->strip_all($data['billing_frequency'])));
			
			if($data['billing_type']){
				$billing_type = trim($this->escape_string($this->strip_all($data['billing_type'])));
			}else{
				$billing_type = "Arrears";
			}
			$bill_mode = trim($this->escape_string($this->strip_all($data['bill_mode'])));
			$cug_id = trim($this->escape_string($this->strip_all($data['cug_id'])));

			if(!empty($file['drop_location_sheet']['name'])) {
				/* $file_name = strtolower( pathinfo($file['drop_location_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				move_uploaded_file($file['drop_location_sheet']['tmp_name'],$uploadDir.$drop_location_sheet); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$drop_location_sheet, fopen($file['drop_location_sheet']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['del_sheet']['name'])) {
				/* $file_name = strtolower( pathinfo($file['del_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['del_sheet']['name'], PATHINFO_EXTENSION));
				$del_sheet = time().'2.'.$ext;
				move_uploaded_file($file['del_sheet']['tmp_name'],$uploadDir.$del_sheet); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['del_sheet']['name'], PATHINFO_EXTENSION));
				$del_sheet = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$del_sheet, fopen($file['del_sheet']['tmp_name'], 'rb'), 'public-read');
			}

			$query = "insert into ".PREFIX."caf_product_details(caf_id, product, variant, sub_variant, category, no_del, del_sheet, no_did, no_channel, no_drop_locations, drop_location_sheet, mobile_no, del_no, pilot_no, imsi_no, did_range, did_range_to, bandwidth, arc, arc_type, monthly_rental, nrc, bill_plan_opted, lockin_period, security_deposit, activation_fee, trai_id, billing_frequency, billing_type, bill_mode, cug_id, rack_type, sales_type) values ('".$caf_id."', '".$product."', '".$variant."', '".$sub_variant."', '".$category."', '".$no_del."', '".$del_sheet."', '".$no_did."', '".$no_channel."', '".$no_drop_locations."', '".$drop_location_sheet."', '".$mobile_no."', '".$del_no."', '".$pilot_no."', '".$imsi_no."', '".$did_range."', '".$did_range_to."', '".$bandwidth."', '".$arc."', '".$arc_type."', '".$monthly_rental."', '".$nrc."', '".$bill_plan_opted."', '".$lockin_period."', '".$security_deposit."', '".$activation_fee."', '".$trai_id."', '".$billing_frequency."', '".$billing_type."', '".$bill_mode."', '".$cug_id."', '".$rack_type."', '".$sales_type."')";
			$this->query($query);
			// PRODUCT DETAILS

			// DOCUMENT DETAILS
			$registration_document_type = trim($this->escape_string($this->strip_all($data['registration_document_type'])));
			$registration_document_type_other = trim($this->escape_string($this->strip_all($data['registration_document_type_other'])));
			$address_document_type = trim($this->escape_string($this->strip_all($data['address_document_type'])));
			$address_document_type_other = trim($this->escape_string($this->strip_all($data['address_document_type_other'])));
			$identity_document_type = trim($this->escape_string($this->strip_all($data['identity_document_type'])));
			$identity_document_type_other = trim($this->escape_string($this->strip_all($data['identity_document_type_other'])));
			$authorisation_document_type = trim($this->escape_string($this->strip_all($data['authorisation_document_type'])));
			$authorisation_document_type_other = trim($this->escape_string($this->strip_all($data['authorisation_document_type_other'])));
			
			//new
			$installation_document_type = trim($this->escape_string($this->strip_all($data['installation_document_type'])));
			$installation_document_type_other = trim($this->escape_string($this->strip_all($data['installation_document_type_other'])));
			
			/* if(!empty($file['registration_document']['name'])) {
				$file_name = strtolower( pathinfo($file['registration_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['registration_document']['name'], PATHINFO_EXTENSION));
				$registration_document = time().'21.'.$ext;
				move_uploaded_file($file['registration_document']['tmp_name'],$uploadDir.$registration_document);
			}

			if(!empty($file['address_document']['name'])) {
				$file_name = strtolower( pathinfo($file['address_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['address_document']['name'], PATHINFO_EXTENSION));
				$address_document = time().'22.'.$ext;
				move_uploaded_file($file['address_document']['tmp_name'],$uploadDir.$address_document);
			}

			if(!empty($file['identity_document']['name'])) {
				$file_name = strtolower( pathinfo($file['identity_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['identity_document']['name'], PATHINFO_EXTENSION));
				$identity_document = time().'23.'.$ext;
				move_uploaded_file($file['identity_document']['tmp_name'],$uploadDir.$identity_document);
			}

			if(!empty($file['authorisation_document']['name'])) {
				$file_name = strtolower( pathinfo($file['authorisation_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['authorisation_document']['name'], PATHINFO_EXTENSION));
				$authorisation_document = time().'24.'.$ext;
				move_uploaded_file($file['authorisation_document']['tmp_name'],$uploadDir.$authorisation_document);
			} */

			if(!empty($file['tef_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['tm_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tm_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				move_uploaded_file($file['tm_download']['tmp_name'],$uploadDir.$tm_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tm_download, fopen($file['tm_download']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['trai_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['trai_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				move_uploaded_file($file['trai_form']['tmp_name'],$uploadDir.$trai_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$trai_form, fopen($file['trai_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['dd_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['dd_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				move_uploaded_file($file['dd_form']['tmp_name'],$uploadDir.$dd_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$dd_form, fopen($file['dd_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['osp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['osp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				move_uploaded_file($file['osp_form']['tmp_name'],$uploadDir.$osp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$osp_form, fopen($file['osp_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['sez_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['sez_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				move_uploaded_file($file['sez_form']['tmp_name'],$uploadDir.$sez_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$sez_form, fopen($file['sez_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['bulk_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['bulk_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				move_uploaded_file($file['bulk_form']['tmp_name'],$uploadDir.$bulk_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$bulk_form, fopen($file['bulk_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['all_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['all_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				move_uploaded_file($file['all_form']['tmp_name'],$uploadDir.$all_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$all_form, fopen($file['all_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['logical_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['logical_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				move_uploaded_file($file['logical_form']['tmp_name'],$uploadDir.$logical_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$logical_form, fopen($file['logical_form']['tmp_name'], 'rb'), 'public-read');
			}

			if(!empty($file['stc_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['stc_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				move_uploaded_file($file['stc_form']['tmp_name'],$uploadDir.$stc_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$stc_form, fopen($file['stc_form']['tmp_name'], 'rb'), 'public-read');
			}

			// $query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, address_document_type, address_document_type_other, identity_document_type, identity_document_type_other, authorisation_document_type, authorisation_document_type_other, tef_download, tm_download, trai_form, dd_form, osp_form, sez_form, bulk_form, billing_form, gst_form, all_form, logical_form, stc_form) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$address_document_type."', '".$address_document_type_other."', '".$identity_document_type."', '".$identity_document_type_other."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$tef_download."', '".$tm_download."', '".$trai_form."', '".$dd_form."', '".$osp_form."', '".$sez_form."', '".$bulk_form."', '".$billing_form."', '".$gst_form."', '".$all_form."', '".$logical_form."', '".$stc_form."')";
			
			//new by dhanashree*/
			$query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, address_document_type, address_document_type_other, identity_document_type, identity_document_type_other, authorisation_document_type, authorisation_document_type_other,installation_document_type, installation_document_type_other, tef_download, tm_download, trai_form, dd_form, osp_form, sez_form, bulk_form, billing_form, gst_form, all_form, logical_form, stc_form) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$address_document_type."', '".$address_document_type_other."', '".$identity_document_type."', '".$identity_document_type_other."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$installation_document_type."', '".$installation_document_type_other."', '".$tef_download."', '".$tm_download."', '".$trai_form."', '".$dd_form."', '".$osp_form."', '".$sez_form."', '".$bulk_form."', '".$billing_form."', '".$gst_form."', '".$all_form."', '".$logical_form."', '".$stc_form."')";
			$this->query($query);

			$c=30;
			foreach($file['registration_document']['name'] as $key=>$value) {
				if(!empty($file['registration_document']['name'][$key])) {
					$registration_document = '';
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_registration_document(caf_id, document) values ('$caf_id', '$registration_document')");
				}
			}
			foreach($file['address_document']['name'] as $key=>$value) {
				if(!empty($file['address_document']['name'][$key])) {
					$address_document = '';
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_address_document(caf_id, document) values ('$caf_id', '$address_document')");
				}
			}
			foreach($file['identity_document']['name'] as $key=>$value) {
				if(!empty($file['identity_document']['name'][$key])) {
					$identity_document = '';
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_identity_document(caf_id, document) values ('$caf_id', '$identity_document')");
				}
			}
			foreach($file['authorisation_document']['name'] as $key=>$value) {
				if(!empty($file['authorisation_document']['name'][$key])) {
					$authorisation_document = '';
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_authorisation_document(caf_id, document) values ('$caf_id', '$authorisation_document')");
				}
			}
			
			
			foreach($file['installation_document']['name'] as $key=>$value) {
				if(!empty($file['installation_document']['name'][$key])) {
					$installation_document = '';
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_installation_document(caf_id, document) values ('$caf_id', '$installation_document')");
				}
			}
			
			foreach($file['other_form']['name'] as $key=>$value) {
				if(!empty($file['other_form']['name'][$key])) {
					$other_form = '';
					if(!empty($file['other_form']['name'][$key])) {
						$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_other_document(caf_id, document) values ('$caf_id', '$other_form')");
				}
			}
			
			/* done by dhanashree*/
			$po_upload_date = date("Y-m-d H:i:s");
			if(!empty($file['po_upload']['name'])) {
				/* $file_name = strtolower( pathinfo($file['po_upload']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				move_uploaded_file($file['po_upload']['tmp_name'],$uploadDir.$po_upload); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'], 'rb'), 'public-read');
				
				$this->query("update ".PREFIX."caf_details set po_upload='".$po_upload."', po_upload_date='".$po_upload_date."' where id='".$caf_id."'");
			}
			
			
			
			// DOCUMENT DETAILS

			// OTHER DETAILS
			$mobile_connection1 = trim($this->escape_string($this->strip_all($data['mobile_connection1'])));
			$mobile_connection2 = trim($this->escape_string($this->strip_all($data['mobile_connection2'])));
			$mobile_connection3 = trim($this->escape_string($this->strip_all($data['mobile_connection3'])));
			$mobile_connection4 = trim($this->escape_string($this->strip_all($data['mobile_connection4'])));
			$no_connection1 = trim($this->escape_string($this->strip_all($data['no_connection1'])));
			$no_connection2 = trim($this->escape_string($this->strip_all($data['no_connection2'])));
			$no_connection3 = trim($this->escape_string($this->strip_all($data['no_connection3'])));
			$no_connection4 = trim($this->escape_string($this->strip_all($data['no_connection4'])));
			$mobile_connection_total = trim($this->escape_string($this->strip_all($data['mobile_connection_total'])));
			$is_mnp = trim($this->escape_string($this->strip_all($data['is_mnp'])));
			$upc_code = trim($this->escape_string($this->strip_all($data['upc_code'])));
			$upc_code_date = trim($this->escape_string($this->strip_all($data['upc_code_date'])));
			$existing_operator = trim($this->escape_string($this->strip_all($data['existing_operator'])));
			$porting_imsi_no = trim($this->escape_string($this->strip_all($data['porting_imsi_no'])));
			$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'])));
			/* $payment_type = trim($this->escape_string($this->strip_all($data['payment_type']))); */
			// $payment_type = implode(',', $data['payment_type']);
			// if($payment_mode != 'Cash') {
				foreach($data['payment_type'] as $key=>$value) {
					if(!empty($data['payment_type'][$key])) {
						$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'][$key])));
						$payment_type = trim($this->escape_string($this->strip_all($data['payment_type'][$key])));
						$bank_name = trim($this->escape_string($this->strip_all($data['bank_name'][$key])));
						$bank_acc_no = trim($this->escape_string($this->strip_all($data['bank_acc_no'][$key])));
						$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
						$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
						$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
						$this->query("insert into ".PREFIX."caf_payment_details(caf_id, payment_mode, payment_type, bank_name, bank_acc_no, branch_address, transactional_details, transaction_amount) values ('".$caf_id."', '".$payment_mode."', '".$payment_type."', '".$bank_name."', '".$bank_acc_no."', '".$branch_address."', '".$transactional_details."', '".$transaction_amount."')");
					}
				}
			// }
			$grand_amount = trim($this->escape_string($this->strip_all($data['grand_amount'])));

			if($is_mnp=='Yes' and !empty($file['mnp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['mnp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				move_uploaded_file($file['mnp_form']['tmp_name'],$uploadDir.$mnp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$mnp_form, fopen($file['mnp_form']['tmp_name'], 'rb'), 'public-read');
			}

			$query = "insert into ".PREFIX."caf_other_details(caf_id, mobile_connection1, mobile_connection2, mobile_connection3, mobile_connection4, no_connection1, no_connection2, no_connection3, no_connection4, mobile_connection_total, is_mnp, upc_code, upc_code_date, existing_operator, porting_imsi_no, mnp_form, grand_amount) values('".$caf_id."', '".$mobile_connection1."', '".$mobile_connection2."', '".$mobile_connection3."', '".$mobile_connection4."', '".$no_connection1."', '".$no_connection2."', '".$no_connection3."', '".$no_connection4."', '".$mobile_connection_total."', '".$is_mnp."', '".$upc_code."', '".$upc_code_date."', '".$existing_operator."', '".$porting_imsi_no."', '".$mnp_form."', '".$grand_amount."')";
			$this->query($query);
			// OTHER DETAILS
			$date = date('Y-m-d H:i:s');
			$verification_link=md5($caf_id.'#'.$name);
			if($product == 'Internet Leased Line' || $product == 'Smart VPN' || $product == 'Leased Line' || $product == 'L2 Multicast Solution' || $product == 'Dark Fiber' || $prodvariant == 'Internet Leased Line'){
				
				$verification_datetime = ",eba_start_datetime='".$date."'";
			}else{
				$verification_datetime = ",verification_datetime='".$date."'";
			}
			
			$this->query("update ".PREFIX."caf_details set verification_link='$verification_link' $verification_datetime  where id='".$caf_id."'");
			echo "update ".PREFIX."caf_details set verification_link='$verification_link' $verification_datetime where id='".$caf_id."'";exit;
		}

		function deleteCAFForm($id) {
			$caf_id = trim($this->escape_string($this->strip_all($id)));
			$query = "delete from ".PREFIX."caf_details where id='".$caf_id."'";
			$this->query($query);
			$this->query("delete from ".PREFIX."caf_ill_details	where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mpls_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mpls_express_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mpls_rw_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_dlc_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_nplc_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_l2mc_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_photon_dongal_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_photon_wifi_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_photon_mifi_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_pri_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_sip_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_standard_centrex_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_ip_centrex_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_standard_wrln_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_wepbax_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_ibs_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_walky_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_mobile_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_lbs_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_crs_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_toll_free_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_sms_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_sns_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_hosted_obd_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_audio_conf_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_product_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_other_details where caf_id='$caf_id'");
			$this->query("delete from ".PREFIX."caf_document_details where caf_id='$caf_id'");
		}
		
		//new by dhanashree*/
		function getCAFInstallationDocuments($id){
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_installation_document where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFOtherDocuments($id){
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_other_document where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFPaymentDetailsByCAFId($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_payment_details where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFExistingDetailsByCAFId($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_existing_details where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFRegistrationDocuments($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_registration_document where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFAddressDocuments($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_address_document where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFIdentityDocuments($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_identity_document where caf_id='".$id."'";
			return $this->query($query);
		}
		function getCAFAuthDocuments($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_authorisation_document where caf_id='".$id."'";
			return $this->query($query);
		}
		function getUniqueCafDetailsById($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_details where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueCafDetailsByOrderId($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_details where order_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function getUniqueCafDetailsByTransId($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_details where trans_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function getCAFProductDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_product_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFDocumentDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_document_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFOtherDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_other_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFIllDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_ill_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFMplsDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_mpls_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUserPermissions($designation){
			$role = $this->escape_string($this->strip_all($designation));
			$query = "select * from ".PREFIX."user_permissions where role='".$role."'";
			$sql = $this->fetch($this->query($query));
			return $sql;
		}
		function getCAFMplsExpressDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_mpls_express_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFMplsRwDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_mpls_rw_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFDlcDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_dlc_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFNplcDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_nplc_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFL2mcDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_l2mc_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFPhotonDongalDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_photon_dongal_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFPhotonWifiDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_photon_wifi_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFPhotonMifiDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_photon_mifi_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFPriDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_pri_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFSipDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_sip_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFStandardCentrexDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_standard_centrex_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFIpCentrexDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_ip_centrex_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFStandardWrlnDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_standard_wrln_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFWepbaxDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_wepbax_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFIbsDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_ibs_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFTollFreeDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_toll_free_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFWalkyDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_walky_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFMobileDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_mobile_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFLbsDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_lbs_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFCrsDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_crs_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFHostedObdDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_hosted_obd_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFAudioConfDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_audio_conf_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFSmsDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_sms_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getCAFSnsDetails($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_sns_details where caf_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getKMCDetailsByCin($cin) {
			$cin = trim($this->escape_string($this->strip_all($cin)));
			$query = "select * from ".PREFIX."kyc_form_details where cin='".$cin."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		/* ===CAF FORM ENDS=== */
		
		function getUniqueAttemptById($username) {
			$username = $this->escape_string($this->strip_all($username));
			$query = "select * from ".PREFIX."attempts where username='".$username."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function getUniqueAdmin() {
			
			$query = "select * from ".PREFIX."admin where designation='Super-admin' limit 1,1";
			$sql = $this->query($query);
			return $this->fetch($sql);
		
		}
		
		function generate_no($random_str, $tableName, $fieldName){
			$rnd_no=$random_str;
			$chkNo=$this->query("select * from ".PREFIX.$tableName." where ".$fieldName."='$rnd_no'");
			if($this->num_rows($chkNo)>0){
				$random_no 		= str_shuffle('1234567890-');
				$random_no		= substr($random_no,0,5);
				$random_letters = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
				$random_letters	= substr($random_letters,0,5);
				$random_str 	= str_shuffle($random_letters.$random_no);
				return $this->generate_no($random_str, $tableName, $fieldName);
			}else{
				return  $rnd_no;
			}
		}
		
			function getUniqueCafDetailsByUniqueId($id) {
			$id = trim($this->escape_string($this->strip_all($id)));
			$query = "select * from ".PREFIX."caf_details where unique_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function getCafDetailsByEmpCode($id){
			return $this->fetch($this->query("select unique_id from ".PREFIX."caf_details where id='".$id."'"));
		}
		
		
		function AddCustomerDetails($data,$file){
				
				$product_name 			= $this->escape_string($this->strip_all($data['product_name']));
				$rental_plan 			= $this->escape_string($this->strip_all($data['rental_plan']));
				$basic_value 			= $this->escape_string($this->strip_all($data['rental_price_point']));
				$monthly_annual_billing = $this->escape_string($this->strip_all($data['monthly_annual_billing']));
				$customer_name 			= $this->escape_string($this->strip_all($data['customer_name']));
				$customer_mobile 		= $this->escape_string($this->strip_all($data['customer_mobile']));
				$customer_email 		= $this->escape_string($this->strip_all($data['customer_email']));
				$company_name 			= $this->escape_string($this->strip_all($data['company_name']));
				
				if(!empty($data['alternate_gst_no'])){$alternate_gst_no = trim($this->escape_string($this->strip_all($data['alternate_gst_no'])));}else{$alternate_gst_no ="0";}
				
				// $alternate_bdlg_no 			= trim($this->escape_string($this->strip_all($data['alternate_bdlg_no'])));
				// $alternate_bdlg_name 		= trim($this->escape_string($this->strip_all($data['alternate_bdlg_name'])));
				// $alternate_floor 			= trim($this->escape_string($this->strip_all($data['alternate_floor'])));
				// $alternate_street_name 		= trim($this->escape_string($this->strip_all($data['alternate_street_name'])));
				// $alternate_area 				= trim($this->escape_string($this->strip_all($data['alternate_area'])));
				// $alternate_landmark 			= trim($this->escape_string($this->strip_all($data['alternate_landmark'])));
				// $alternate_state 			= trim($this->escape_string($this->strip_all($data['alternate_state'])));
				// $alternate_city             	= trim($this->escape_string($this->strip_all($data['alternate_city'])));
				$installation_address1 	    	= $this->escape_string($this->strip_all($data['installation_address1']));
				$installation_address2 	   	 	= $this->escape_string($this->strip_all($data['installation_address2']));
				$installation_address3 	    	= $this->escape_string($this->strip_all($data['installation_address3']));
				$installation_address4 	    	= $this->escape_string($this->strip_all($data['installation_address4']));
				$installation_address5	    	= $this->escape_string($this->strip_all($data['installation_address5']));
				
				$alternate_pincode 			= $this->escape_string($this->strip_all($data['pincode']));
				$alternate_state 			= $this->escape_string($this->strip_all($data['state']));
				$order_id 					= $this->escape_string($this->strip_all($data['order_id']));
				$pri_telemarketing 			= $this->escape_string($this->strip_all($data['pri_telemarketing']));
				$product_qty 				= $this->escape_string($this->strip_all($data['product_qty']));
				$onetimecost        		= $this->escape_string($this->strip_all($data['one_time_installation_cost']));
				$device_cost        		= $this->escape_string($this->strip_all($data['device_cost']));
				$cost 			    		= $this->escape_string($this->strip_all($data['cost']));
				$bill_plan_opted 			= $this->escape_string($this->strip_all($data['bill_plan_opted']));
				$manage_service_charge 		= $this->escape_string($this->strip_all($data['manage_service_charge']));
				$bandwidth					= $this->escape_string($this->strip_all($data['bandwidth']));
				$annual_charges 			= ($cost * $product_qty) + $manage_service_charge ;
				
				$random_no 		= str_shuffle('1234567890-');
				$random_no		= substr($random_no,0,5);
				$random_letters = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
				$random_letters	= substr($random_letters,0,5);
				$random_str 	= str_shuffle($random_letters.$random_no);
				
				$unique_id = $this->generate_no($random_str,'caf_details','unique_id');
				
				if($rental_plan == "Monthly"){
					$billing_frequency = "Monthly";
				}else if($rental_plan == "Annual"){
					$billing_frequency = "Annually";
				}else{
					$billing_frequency ="";
				}
				
				//$order_id = $this->generate_no($random_str,'caf_details','order_id');
				if(!empty($alternate_state)){ $alternate_state1 = $this->getStateByName($alternate_state);  }
				
				if(!empty($alternate_state1)){ $state = $alternate_state1['id']; }else { $state = 0; }
		
				if(!empty($alternate_city)){ $alternate_city1 = $this->getCityByName($alternate_city); }
				if(!empty($alternate_city1)){  $city = $alternate_city1['id']; }else { $city = 0; }
				
				if(!empty($alternate_state1) && !empty($alternate_city1)){ $caf_no = $this->generateCafNo($state,$city); }else{$caf_no = 0; }
				
				$sql="INSERT INTO ".PREFIX."caf_details(caf_no,order_id, unique_id,`name`, `mobile`,`email`, `company_name`,`rental_plan`,`monthly_annual_billing`,alternate_gst_no, alternate_bdlg_no, alternate_bdlg_name, alternate_floor,alternate_street_name, alternate_area, alternate_landmark, alternate_state, alternate_city,alternate_pincode,installation_address1,installation_address2,installation_address3,installation_address4,installation_address5,form_flag) VALUES ('".$caf_no."','".$order_id."','".$unique_id."','".$customer_name."','".$customer_mobile."','".$customer_email."','".$company_name."','".$rental_plan."','".$monthly_annual_billing."','".$alternate_gst_no."', '".$alternate_bdlg_no."', '".$alternate_bdlg_name."', '".$alternate_floor."', '".$alternate_street_name."', '".$alternate_area."', '".$alternate_landmark."', ".$state.", ".$city.", '".$alternate_pincode."','".$installation_address1."','".$installation_address2."','".$installation_address3."','".$installation_address4."','".$installation_address5."','DACF_click2buy')";
				
				$sql_query =  $this->query($sql);
				$caf_id = $this->last_insert_id();
				$caf_id = $this->escape_string($this->strip_all($caf_id));
				$varient_value = "";
				$variant_name = "";
				// if($product_name == "PRI"){
					// $product_name = "Voice Solutions";
					// $variant_name = "PRI";
				// }else{
					// $variant_name = "";
				// }
				if($product_name == "PRI"){
					if(!empty($pri_telemarketing)){
						$variant_name = 'Tele Marketing PRI';
					}else{
						$variant_name = 'Standard  PRI';
					}
				}else{
					
					$varient_value = $pri_telemarketing;
				}
				if($product_name == "Internet Leased Line"){
					if($manage_service_charge != '0'){
						$variant_name = 'Managed';
					}else{
						$variant_name = 'Standard';
					}
					$billing_frequency = "Quarterly";
				}
				if($product_name == "School Bus Tracking" || $product_name == "Workforce Management" || $product_name == "Fleet Management" || $product_name == "Mobile Device Management"){
					$monthly_annual_billing_new = ($monthly_annual_billing / 1.18);
					$monthly_annual_billing_new1 = number_format((float)$monthly_annual_billing_new, 0, '.', '');
				}else{
					$monthly_annual_billing_new1 = $monthly_annual_billing;
				}
				if(!empty($caf_id))
				{
					$sql1 = "INSERT INTO ".PREFIX."caf_product_details(`caf_id`,`product`,`variant`,`no_del`,`nrc`,`monthly_rental`,`activation_fee`,`basic_cost`,`cost`,`varient_value`,`billing_frequency`,`bill_plan_opted`,`arc`,`bandwidth`,`manage_service_charge`) VALUES ('".$caf_id."','".$product_name."','".$variant_name."','".$product_qty."','".$device_cost."','".$basic_value."','".$onetimecost."','".$cost."','".$monthly_annual_billing_new1."','".$varient_value."','".$billing_frequency."','".$bill_plan_opted."','".$annual_charges."','".$bandwidth."','".$manage_service_charge."')";
					$sql_query =  $this->query($sql1);
					
				}
			
				return $order_id;	
		}
		
		function getPlanByProduct($product) {
			$product = trim($this->escape_string($this->strip_all($product)));
			$query = "select DISTINCT(plan_name) from ".PREFIX."bill_plan_opted_master where product='".$product."'";
			return $this->query($query);
		}
		function getPlanByVarient($product,$variant) {
			$product = trim($this->escape_string($this->strip_all($product)));
			$variant = trim($this->escape_string($this->strip_all($variant)));
			$query = "select DISTINCT(plan_name) from ".PREFIX."bill_plan_opted_master where product='".$product."' and variant='".$variant."'";
			return $this->query($query);
		}
		function getPlanBySubVarient($product,$variant,$subvariant) {
			$product = trim($this->escape_string($this->strip_all($product)));
			$variant = trim($this->escape_string($this->strip_all($variant)));
			$subvariant = trim($this->escape_string($this->strip_all($subvariant)));
			$query = "select DISTINCT(plan_name) from ".PREFIX."bill_plan_opted_master where product='".$product."' and variant='".$variant."' and sub_variant='".$subvariant."'";
			return $this->query($query);
		}
		
		
		function updateCustomerCafData_OrderForm($data,$file) {
			$empty_fields = array();
			$id = trim($this->escape_string($this->strip_all($data['id'])));
			
			$olddata = $this->getUniqueCafDetailsById($id);
			
			$company_name = empty($data['company_name']) ? $olddata['company_name'] : trim($this->escape_string($this->strip_all($data['company_name'])));
			//$company_name = trim($this->escape_string($this->strip_all($data['company_name'])));
			
			$pan = empty($data['pan']) ? $olddata['pan'] : trim($this->escape_string($this->strip_all($data['pan'])));
			//$pan = trim($this->escape_string($this->strip_all($data['pan'])));
			
			$osp = empty($data['osp']) ? $olddata['osp'] : trim($this->escape_string($this->strip_all($data['osp'])));
			//$osp = trim($this->escape_string($this->strip_all($data['osp'])));
			
			$title = empty($data['title']) ? $olddata['title'] : trim($this->escape_string($this->strip_all($data['title'])));
			//$title = trim($this->escape_string($this->strip_all($data['title'])));
			
			$sez_certificate_no = empty($data['sez_certificate_no']) ? $olddata['sez_certificate_no'] : trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			//$sez_certificate_no = trim($this->escape_string($this->strip_all($data['sez_certificate_no'])));
			
			$salutation = empty($data['salutation']) ? $olddata['salutation'] : trim($this->escape_string($this->strip_all($data['salutation'])));
			//$salutation = trim($this->escape_string($this->strip_all($data['salutation'])));
			
			$name = empty($data['name']) ? $olddata['name'] : trim($this->escape_string($this->strip_all($data['name'])));
			//$name = trim($this->escape_string($this->strip_all($data['name'])));
			
			$designation = empty($data['designation']) ? $olddata['designation'] : trim($this->escape_string($this->strip_all($data['designation'])));
			//$designation = trim($this->escape_string($this->strip_all($data['designation'])));
			
			$anotherdesignation = empty($data['anotherdesignation']) ? $olddata['anotherdesignation'] : trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			//$anotherdesignation = trim($this->escape_string($this->strip_all($data['anotherdesignation'])));
			
			$email = empty($data['email']) ? $olddata['email'] : trim($this->escape_string($this->strip_all($data['email'])));
			//$email = trim($this->escape_string($this->strip_all($data['email'])));
			
			$mobile = empty($data['mobile']) ? $olddata['mobile'] : trim($this->escape_string($this->strip_all($data['mobile'])));
			//$mobile = trim($this->escape_string($this->strip_all($data['mobile'])));
			
			$telephone = empty($data['telephone']) ? $olddata['telephone'] : trim($this->escape_string($this->strip_all($data['telephone'])));
			//$telephone = trim($this->escape_string($this->strip_all($data['telephone'])));
			
			$aadhar_no = empty($data['aadhar_no']) ? $olddata['aadhar_no'] : trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			//$aadhar_no = trim($this->escape_string($this->strip_all($data['aadhar_no'])));
			
			$same_contact_person = trim($this->escape_string($this->strip_all($data['same_contact_person'])));
			if($data['same_contact_person']=='Yes') {
				$contact_person = $this->escape_string($this->strip_all($name));
				$contact_person_designation = $this->escape_string($this->strip_all($designation));
				$contact_person_email = $this->escape_string($this->strip_all($email));
				$contact_person_mobile = $this->escape_string($this->strip_all($mobile));
			} else {
				$contact_person = trim($this->escape_string($this->strip_all($data['contact_person'])));
				$contact_person_designation = trim($this->escape_string($this->strip_all($data['contact_person_designation'])));
				$contact_person_email = trim($this->escape_string($this->strip_all($data['contact_person_email'])));
				$contact_person_mobile = trim($this->escape_string($this->strip_all($data['contact_person_mobile'])));
			}
			$alternate_bdlg_no = trim($this->escape_string($this->strip_all($data['alternate_bdlg_no'])));
			$alternate_bdlg_name = trim($this->escape_string($this->strip_all($data['alternate_bdlg_name'])));
			$alternate_floor = trim($this->escape_string($this->strip_all($data['alternate_floor'])));
			$alternate_street_name = trim($this->escape_string($this->strip_all($data['alternate_street_name'])));
			$alternate_area = trim($this->escape_string($this->strip_all($data['alternate_area'])));
			$alternate_landmark = trim($this->escape_string($this->strip_all($data['alternate_landmark'])));
			$alternate_state = trim($this->escape_string($this->strip_all($data['alternate_state'])));
			$alternate_city = trim($this->escape_string($this->strip_all($data['alternate_city'])));
			$alternate_pincode = trim($this->escape_string($this->strip_all($data['alternate_pincode'])));
			$same_billing_address = trim($this->escape_string($this->strip_all($data['same_billing_address'])));
			$acceptgst = trim($this->escape_string($this->strip_all($data['acceptgst'])));
			if($data['same_billing_address']=='Yes') {
				$billing_bdlg_no = $this->escape_string($this->strip_all($alternate_bdlg_no));
				$billing_bdlg_name = $this->escape_string($this->strip_all($alternate_bdlg_name));
				$billing_floor = $this->escape_string($this->strip_all($alternate_floor));
				$billing_street_name = $this->escape_string($this->strip_all($alternate_street_name));
				$billing_area = $this->escape_string($this->strip_all($alternate_area));
				$billing_landmark = $this->escape_string($this->strip_all($alternate_landmark));
				$billing_state = $this->escape_string($this->strip_all($alternate_state));
				$billing_city = $this->escape_string($this->strip_all($alternate_city));
				$billing_pincode = $this->escape_string($this->strip_all($alternate_pincode));
			} else {
				$billing_bdlg_no = trim($this->escape_string($this->strip_all($data['billing_bdlg_no'])));
				$billing_bdlg_name = trim($this->escape_string($this->strip_all($data['billing_bdlg_name'])));
				$billing_floor = trim($this->escape_string($this->strip_all($data['billing_floor'])));
				$billing_street_name = trim($this->escape_string($this->strip_all($data['billing_street_name'])));
				$billing_area = trim($this->escape_string($this->strip_all($data['billing_area'])));
				$billing_landmark = trim($this->escape_string($this->strip_all($data['billing_landmark'])));
				$billing_state = trim($this->escape_string($this->strip_all($data['billing_state'])));
				$billing_city = trim($this->escape_string($this->strip_all($data['billing_city'])));
				$billing_pincode = trim($this->escape_string($this->strip_all($data['billing_pincode'])));
			}
			
			//New development for empty fields
				if(empty($company_name)){  array_push($empty_fields,"Company Name");}
				if(empty($pan)){  array_push($empty_fields,"Pan Number");}
				if(empty($sez_certificate_no)){  array_push($empty_fields,"Sez Certificate No.");}
				if(empty($name)){  array_push($empty_fields,"Name");}
				if(empty($designation)){  array_push($empty_fields,"Designation");}
				if(empty($email)){  array_push($empty_fields,"Email");}
				if(empty($mobile)){  array_push($empty_fields,"Mobile");}
				if(empty($aadhar_no)){  array_push($empty_fields,"Aadhar No.");}
				if(empty($contact_person)){  array_push($empty_fields,"Conatct Person Name");}
				if(empty($contact_person_designation)){  array_push($empty_fields,"Conatct Person Designation");}
				if(empty($contact_person_email)){  array_push($empty_fields,"Conatct Person Email");}
				if(empty($contact_person_mobile)){  array_push($empty_fields,"Conatct Person Mobile");}
				if(empty($billing_bdlg_no) || empty($billing_bdlg_name) || empty($billing_floor) ||empty($billing_bdlg_no) ||empty($billing_street_name) || empty($billing_area) || empty($billing_landmark) || empty($billing_state) || empty($billing_city)  || empty($billing_pincode) ){  array_push($empty_fields,"Billing Address ");}
			//$caf_no = '';
			
			//$caf_no = trim($this->escape_string($this->strip_all($data['caf_no'])));
			$caf_data = $this->fetch($this->query("select * from ".PREFIX."caf_details where id='".$id."'"));
			$caf_no = $caf_data['caf_no'];
				if(!empty($alternate_state) && !empty($alternate_city) && $caf_no == '0'){
					$caf_no = $this->generateCafNo($alternate_state,$alternate_city);
				}
			

			$uploadDir = 'caf-uploads/';
			if(!empty($file['image_name']['name'])) {
				/* $file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				move_uploaded_file($file['image_name']['tmp_name'],$uploadDir.$image_name); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['image_name']['name'], PATHINFO_EXTENSION));
				$image_name = time().'41.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$image_name, fopen($file['image_name']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_details set image_name='".$image_name."' where id='".$id."'");
			}
			$caf_status = trim($this->escape_string($this->strip_all($data['caf_status'])));
			
			if(empty($caf_status)){
				$caf_status_new = "Pending with Customer";
			}else if($caf_status == "Pending with Customer"){
				$caf_status_new="Pending with OE";
			}else if($caf_status == "Pending with Sales"){
				$caf_status_new="Pending with OE";
			}
			else{
				$caf_status_new= $caf_data['caf_status'];
			}
			
			$dealer_detail_code = trim($this->escape_string($this->strip_all($data['dealer_detail_code'])));
			$dealer_detail_name = trim($this->escape_string($this->strip_all($data['dealer_detail_name'])));
			$remark = trim($this->escape_string($this->strip_all($data['remark'])));
			 $date = date("Y-m-d H:i:s");
			
			
			$query = "update ".PREFIX."caf_details set dealer_detail_code ='".$dealer_detail_code."', dealer_detail_name='".$dealer_detail_name."', caf_no='".$caf_no."', company_name='".$company_name."', pan='".$pan."', osp='".$osp."', title='".$title."', sez_certificate_no='".$sez_certificate_no."', salutation='".$salutation."', name='".$name."', designation='".$designation."', anotherdesignation='".$anotherdesignation."', email='".$email."', mobile='".$mobile."', telephone='".$telephone."', aadhar_no='".$aadhar_no."', same_contact_person='".$same_contact_person."', contact_person='".$contact_person."', contact_person_designation='".$contact_person_designation."', contact_person_email='".$contact_person_email."', contact_person_mobile='".$contact_person_mobile."', alternate_bdlg_no='".$alternate_bdlg_no."', alternate_bdlg_name='".$alternate_bdlg_name."', alternate_floor='".$alternate_floor."', alternate_street_name='".$alternate_street_name."', alternate_area='".$alternate_area."', alternate_landmark='".$alternate_landmark."', alternate_state='".$alternate_state."', alternate_city='".$alternate_city."', alternate_pincode='".$alternate_pincode."', same_billing_address='".$same_billing_address."', billing_bdlg_no='".$billing_bdlg_no."', billing_bdlg_name='".$billing_bdlg_name."', billing_floor='".$billing_floor."', billing_street_name='".$billing_street_name."', billing_area='".$billing_area."', billing_landmark='".$billing_landmark."', billing_state='".$billing_state."', billing_city='".$billing_city."', billing_pincode='".$billing_pincode."', acceptgst='".$acceptgst."', customer_verified='Yes', caf_status='".$caf_status_new."', remarks='".$remark."',verification_datetime='".$date."' where id='".$id."'";
			
			$sql = $this->query($query);

			$caf_id = $id;
			
			if(!empty($remark)){
				$emp_data  = $this->fetch($this->query("select * from tata_caf_details where id='".$caf_id."'"));
				$remark_data  = $this->fetch($this->query("select * from ".PREFIX."caf_remarks where caf_id='".$caf_id."' order by id DESC limit 1"));
				if($remark_data['remarks'] != $emp_data['remarks']){
					$loggedInUserDetailsArr = $this->getLoggedInUserDetails();	
					$emp_id = trim($this->escape_string($this->strip_all($loggedInUserDetailsArr['id'])));
					$remarkQuery = "insert into ".PREFIX."caf_remarks (caf_id, user_id, remarks) values ('".$caf_id."','".$emp_id."','".$remark."')";
					$remarkresult = $this->query($remarkQuery);
				}
			}
			
			// PRODUCT DETAILS
			$product = trim($this->escape_string($this->strip_all($data['product'])));
			$varient_value = implode('-', $data['varient_value']);
			$variant = trim($this->escape_string($this->strip_all($data['variant'])));
			$sub_variant = trim($this->escape_string($this->strip_all($data['sub_variant'])));
			$category = trim($this->escape_string($this->strip_all($data['category'])));
			//$no_del = trim($this->escape_string($this->strip_all($data['no_del'])));
			$no_did = trim($this->escape_string($this->strip_all($data['no_did'])));
			$no_channel = trim($this->escape_string($this->strip_all($data['no_channel'])));
			$no_drop_locations = trim($this->escape_string($this->strip_all($data['no_drop_locations'])));
			$mobile_no = trim($this->escape_string($this->strip_all($data['mobile_no'])));
			$del_no = trim($this->escape_string($this->strip_all($data['del_no'])));
			$pilot_no = trim($this->escape_string($this->strip_all($data['pilot_no'])));
			$imsi_no = trim($this->escape_string($this->strip_all($data['imsi_no'])));
			$did_range = trim($this->escape_string($this->strip_all($data['did_range'])));
			$did_range_to = trim($this->escape_string($this->strip_all($data['did_range_to'])));
			$bandwidth = trim($this->escape_string($this->strip_all($data['bandwidth'])));
			$arc = trim($this->escape_string($this->strip_all($data['arc'])));
			$arc_type = trim($this->escape_string($this->strip_all($data['arc_type'])));
			//$monthly_rental = trim($this->escape_string($this->strip_all($data['monthly_rental'])));
			//$nrc = trim($this->escape_string($this->strip_all($data['nrc'])));
			$bill_plan_opted = trim($this->escape_string($this->strip_all($data['bill_plan_opted'])));
			$lockin_period = trim($this->escape_string($this->strip_all($data['lockin_period'])));
			$security_deposit = trim($this->escape_string($this->strip_all($data['security_deposit'])));
			$activation_fee = trim($this->escape_string($this->strip_all($data['activation_fee'])));
			$trai_id = trim($this->escape_string($this->strip_all($data['trai_id'])));
			$billing_frequency = trim($this->escape_string($this->strip_all($data['billing_frequency'])));
			$billing_type = trim($this->escape_string($this->strip_all($data['billing_type']))); 
			$bill_mode = trim($this->escape_string($this->strip_all($data['bill_mode'])));
			$cug_id = trim($this->escape_string($this->strip_all($data['cug_id']))); 
			$recurrsive_value = trim($this->escape_string($this->strip_all($data['recurrsive_value']))); 

			 if(!empty($file['drop_location_sheet']['name'])) {
				/* $file_name = strtolower( pathinfo($file['drop_location_sheet']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				move_uploaded_file($file['drop_location_sheet']['tmp_name'],$uploadDir.$drop_location_sheet); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['drop_location_sheet']['name'], PATHINFO_EXTENSION));
				$drop_location_sheet = time().'1.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$drop_location_sheet, fopen($file['drop_location_sheet']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$drop_location_sheet."' where caf_id='".$caf_id."'");
			} 

			$query = "update ".PREFIX."caf_product_details set product='".$product."', variant='".$variant."', sub_variant='".$sub_variant."', category='".$category."', no_did='".$no_did."', no_channel='".$no_channel."', no_drop_locations='".$no_drop_locations."', mobile_no='".$mobile_no."', del_no='".$del_no."', pilot_no='".$pilot_no."', imsi_no='".$imsi_no."', did_range='".$did_range."', did_range_to='".$did_range_to."', bandwidth='".$bandwidth."', arc='".$arc."', arc_type='".$arc_type."', bill_plan_opted='".$bill_plan_opted."', lockin_period='".$lockin_period."', security_deposit='".$security_deposit."', activation_fee='".$activation_fee."', trai_id='".$trai_id."', billing_frequency='".$billing_frequency."', billing_type='".$billing_type."', bill_mode='".$bill_mode."', cug_id='".$cug_id."', recurrsive_value='".$recurrsive_value."' , varient_value='".$varient_value."' where caf_id='".$caf_id."'"; 
			
			//$query = "update ".PREFIX."caf_product_details set bill_mode='".$bill_mode."' where caf_id='".$caf_id."'";
			$this->query($query);
			// PRODUCT DETAILS

			// DOCUMENT DETAILS
			$registration_document_type = trim($this->escape_string($this->strip_all($data['registration_document_type'])));
			$registration_document_type_other = trim($this->escape_string($this->strip_all($data['registration_document_type_other'])));
			$address_document_type = trim($this->escape_string($this->strip_all($data['address_document_type'])));
			$address_document_type_other = trim($this->escape_string($this->strip_all($data['address_document_type_other'])));
			$identity_document_type = trim($this->escape_string($this->strip_all($data['identity_document_type'])));
			$identity_document_type_other = trim($this->escape_string($this->strip_all($data['identity_document_type_other'])));
			$authorisation_document_type = trim($this->escape_string($this->strip_all($data['authorisation_document_type'])));
			$authorisation_document_type_other = trim($this->escape_string($this->strip_all($data['authorisation_document_type_other'])));
			$installation_document_type = trim($this->escape_string($this->strip_all($data['installation_document_type'])));
			$installation_document_type_other = trim($this->escape_string($this->strip_all($data['installation_document_type_other'])));
			
			//missing fields-mukunda
			$registration_document_no = trim($this->escape_string($this->strip_all($data['registration_document_no'])));
			$registration_place_issue = trim($this->escape_string($this->strip_all($data['registration_place_issue'])));
			$registration_issuing_authority = trim($this->escape_string($this->strip_all($data['registration_issuing_authority'])));
			$registration_issuing_date = trim($this->escape_string($this->strip_all($data['registration_issuing_date'])));
			$registration_expiry_date = trim($this->escape_string($this->strip_all($data['registration_expiry_date'])));
			$address_document_no = trim($this->escape_string($this->strip_all($data['address_document_no'])));
			$address_place_issue = trim($this->escape_string($this->strip_all($data['address_place_issue'])));
			$address_issuing_authority = trim($this->escape_string($this->strip_all($data['address_issuing_authority'])));
			$address_issuing_date = trim($this->escape_string($this->strip_all($data['address_issuing_date'])));
			$address_expiry_date = trim($this->escape_string($this->strip_all($data['address_expiry_date'])));
			$identity_document_no = trim($this->escape_string($this->strip_all($data['identity_document_no'])));
			$identity_place_issue = trim($this->escape_string($this->strip_all($data['identity_place_issue'])));
			$identity_issuing_authority = trim($this->escape_string($this->strip_all($data['identity_issuing_authority'])));
			$identity_issuing_date = trim($this->escape_string($this->strip_all($data['identity_issuing_date'])));
			$identity_expiry_date = trim($this->escape_string($this->strip_all($data['identity_expiry_date'])));
			$authorisation_document_no = trim($this->escape_string($this->strip_all($data['authorisation_document_no'])));
			$authorisation_place_issue = trim($this->escape_string($this->strip_all($data['authorisation_place_issue'])));
			$authorisation_issuing_authority = trim($this->escape_string($this->strip_all($data['authorisation_issuing_authority'])));
			$authorisation_issuing_date = trim($this->escape_string($this->strip_all($data['authorisation_issuing_date'])));
			$authorisation_expiry_date = trim($this->escape_string($this->strip_all($data['authorisation_expiry_date'])));
			$installation_document_no = trim($this->escape_string($this->strip_all($data['installation_document_no'])));
			$installation_place_issue = trim($this->escape_string($this->strip_all($data['installation_place_issue'])));
			$installation_issuing_authority = trim($this->escape_string($this->strip_all($data['installation_issuing_authority'])));
			$installation_issuing_date = trim($this->escape_string($this->strip_all($data['installation_issuing_date'])));
			$installation_expiry_date = trim($this->escape_string($this->strip_all($data['installation_expiry_date'])));
			
			//New development for empty fill
			if(empty($registration_document_type)){ array_push($empty_fields,"Proof of Registration of Company");}
			if(empty($address_document_type)){  array_push($empty_fields,"Proof of Address (Billing address) of Company");}
			if(empty($identity_document_type)){ array_push($empty_fields,"Proof of Identity (Authorised Person)");}
			if(empty($authorisation_document_type)){  array_push($empty_fields,"Proof of Authorisation");}
			if(empty($installation_document_type)){  array_push($empty_fields,"Installation Address Proof");}
			
			$documentData_sql = $this->query("select * from ".PREFIX."caf_document_details where caf_id='".$caf_id."'");
			
			if($this->num_rows($documentData_sql) > 0) {
			
			//new by dhanashree*/
			//$query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."', installation_document_type_other='".$installation_document_type_other."', installation_document_type ='".$installation_document_type."' where caf_id='".$caf_id."'";
			//$this->query($query);
			
			//Mukunda
			$query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', registration_document_no='".$registration_document_no."', registration_place_issue='".$registration_place_issue."', registration_issuing_authority='".$registration_issuing_authority."', registration_issuing_date='".$registration_issuing_date."', registration_expiry_date='".$registration_expiry_date."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', address_document_no='".$address_document_no."', address_place_issue='".$address_place_issue."', address_issuing_authority='".$address_issuing_authority."', address_issuing_date='".$address_issuing_date."', address_expiry_date='".$address_expiry_date."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', identity_document_no='".$identity_document_no."', identity_place_issue='".$identity_place_issue."', identity_issuing_authority='".$identity_issuing_authority."', identity_issuing_date='".$identity_issuing_date."', identity_expiry_date='".$identity_expiry_date."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."', authorisation_document_no='".$authorisation_document_no."', authorisation_place_issue='".$authorisation_place_issue."', authorisation_issuing_authority='".$authorisation_issuing_authority."', authorisation_issuing_date='".$authorisation_issuing_date."', authorisation_expiry_date='".$authorisation_expiry_date."', installation_document_type='".$installation_document_type."', installation_document_type_other='".$installation_document_type_other."', installation_document_no='".$installation_document_no."',installation_issuing_authority='".$installation_issuing_authority."', installation_place_issue='".$installation_place_issue."', installation_issuing_date='".$installation_issuing_date."', installation_expiry_date='".$installation_expiry_date."' where caf_id='".$caf_id."'";
			$this->query($query);
			
			}else{
			
			//$query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, address_document_type, address_document_type_other, identity_document_type, identity_document_type_other, authorisation_document_type, authorisation_document_type_other,installation_document_type, installation_document_type_other) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$address_document_type."', '".$address_document_type_other."', '".$identity_document_type."', '".$identity_document_type_other."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$installation_document_type."', '".$installation_document_type_other."')";
			//$this->query($query);
			
			//Mukunda
			$query = "insert into ".PREFIX."caf_document_details(caf_id, registration_document_type, registration_document_type_other, registration_document_no, registration_place_issue, registration_issuing_authority, registration_issuing_date, registration_expiry_date, registration_document, address_document_type, address_document_type_other, address_document_no, address_place_issue, address_issuing_authority, address_issuing_date, address_expiry_date, address_document, identity_document_type, identity_document_type_other, identity_document_no, identity_place_issue, identity_issuing_authority, identity_issuing_date, identity_expiry_date, identity_document, authorisation_document_type, authorisation_document_type_other, authorisation_document_no, authorisation_place_issue, authorisation_issuing_authority, authorisation_issuing_date, authorisation_expiry_date,installation_document_type, installation_document_type_other, installation_document_no, installation_issuing_authority, installation_place_issue, installation_issuing_date, installation_expiry_date) values('".$caf_id."', '".$registration_document_type."', '".$registration_document_type_other."', '".$registration_document_no."', '".$registration_place_issue."', '".$registration_issuing_authority."', '".$registration_issuing_date."', '".$registration_expiry_date."', '".$registration_document."', '".$address_document_type."', '".$address_document_type_other."', '".$address_document_no."', '".$address_place_issue."', '".$address_issuing_authority."', '".$address_issuing_date."', '".$address_expiry_date."', '".$address_document."', '".$identity_document_type."', '".$identity_document_type_other."', '".$identity_document_no."', '".$identity_place_issue."', '".$identity_issuing_authority."', '".$identity_issuing_date."', '".$identity_expiry_date."', '".$identity_document."', '".$authorisation_document_type."', '".$authorisation_document_type_other."', '".$authorisation_document_no."', '".$authorisation_place_issue."', '".$authorisation_issuing_authority."', '".$authorisation_issuing_date."', '".$authorisation_expiry_date."','".$installation_document_type."', '".$installation_document_type_other."', '".$installation_document_no."', '".$installation_issuing_authority."', '".$installation_place_issue."','".$installation_issuing_date."', '".$installation_expiry_date."')";
			$this->query($query);
				
			}

			/* if(!empty($file['registration_document']['name'])) {
				$file_name = strtolower( pathinfo($file['registration_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['registration_document']['name'], PATHINFO_EXTENSION));
				$registration_document = time().'21.'.$ext;
				move_uploaded_file($file['registration_document']['tmp_name'],$uploadDir.$registration_document);
				$this->query("update ".PREFIX."caf_document_details set registration_document='".$registration_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['address_document']['name'])) {
				$file_name = strtolower( pathinfo($file['address_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['address_document']['name'], PATHINFO_EXTENSION));
				$address_document = time().'22.'.$ext;
				move_uploaded_file($file['address_document']['tmp_name'],$uploadDir.$address_document);
				$this->query("update ".PREFIX."caf_document_details set address_document='".$address_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['identity_document']['name'])) {
				$file_name = strtolower( pathinfo($file['identity_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['identity_document']['name'], PATHINFO_EXTENSION));
				$identity_document = time().'23.'.$ext;
				move_uploaded_file($file['identity_document']['tmp_name'],$uploadDir.$identity_document);
				$this->query("update ".PREFIX."caf_document_details set identity_document='".$identity_document."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['authorisation_document']['name'])) {
				$file_name = strtolower( pathinfo($file['authorisation_document']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['authorisation_document']['name'], PATHINFO_EXTENSION));
				$authorisation_document = time().'24.'.$ext;
				move_uploaded_file($file['authorisation_document']['tmp_name'],$uploadDir.$authorisation_document);
				$this->query("update ".PREFIX."caf_document_details set authorisation_document='".$authorisation_document."' where caf_id='".$caf_id."'");
			} */
			
			$currentdatetime = date("Y-m-d H:i:s");
			
			if(!empty($file['tef_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tef_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				move_uploaded_file($file['tef_download']['tmp_name'],$uploadDir.$tef_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tef_download']['name'], PATHINFO_EXTENSION));
				$tef_download = time().'11.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tef_download, fopen($file['tef_download']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tef_download='".$tef_download."', tef_download_date='".$currentdatetime."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['tm_download']['name'])) {
				/* $file_name = strtolower( pathinfo($file['tm_download']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				move_uploaded_file($file['tm_download']['tmp_name'],$uploadDir.$tm_download); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['tm_download']['name'], PATHINFO_EXTENSION));
				$tm_download = time().'12.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$tm_download, fopen($file['tm_download']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set tm_download='".$tm_download."', tm_download_date ='".$currentdatetime."'  where caf_id='".$caf_id."'");
			}

			if(!empty($file['trai_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['trai_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				move_uploaded_file($file['trai_form']['tmp_name'],$uploadDir.$trai_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['trai_form']['name'], PATHINFO_EXTENSION));
				$trai_form = time().'13.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$trai_form, fopen($file['trai_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set trai_form='".$trai_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['dd_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['dd_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				move_uploaded_file($file['dd_form']['tmp_name'],$uploadDir.$dd_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['dd_form']['name'], PATHINFO_EXTENSION));
				$dd_form = time().'14.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$dd_form, fopen($file['dd_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set dd_form='".$dd_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['osp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['osp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				move_uploaded_file($file['osp_form']['tmp_name'],$uploadDir.$osp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['osp_form']['name'], PATHINFO_EXTENSION));
				$osp_form = time().'15.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$osp_form, fopen($file['osp_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set osp_form='".$osp_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['sez_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['sez_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				move_uploaded_file($file['sez_form']['tmp_name'],$uploadDir.$sez_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['sez_form']['name'], PATHINFO_EXTENSION));
				$sez_form = time().'16.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$sez_form, fopen($file['sez_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set sez_form='".$sez_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['bulk_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['bulk_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				move_uploaded_file($file['bulk_form']['tmp_name'],$uploadDir.$bulk_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['bulk_form']['name'], PATHINFO_EXTENSION));
				$bulk_form = time().'17.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$bulk_form, fopen($file['bulk_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set bulk_form='".$bulk_form."' where caf_id='".$caf_id."'");
			}
			//done by dhanashree*/
			if(!empty($file['billing_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['billing_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				move_uploaded_file($file['billing_form']['tmp_name'],$uploadDir.$billing_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['billing_form']['name'], PATHINFO_EXTENSION));
				$billing_form = time().'21.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$billing_form, fopen($file['billing_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set billing_form='".$billing_form."' where caf_id='".$caf_id."'");
			}
			if(!empty($file['gst_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['gst_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				move_uploaded_file($file['gst_form']['tmp_name'],$uploadDir.$gst_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['gst_form']['name'], PATHINFO_EXTENSION));
				$gst_form = time().'22.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$gst_form, fopen($file['gst_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set gst_form='".$gst_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['all_form']['name'])) {
				
				/* $file_name = strtolower( pathinfo($file['all_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				move_uploaded_file($file['all_form']['tmp_name'],$uploadDir.$all_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['all_form']['name'], PATHINFO_EXTENSION));
				$all_form = time().'18.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$all_form, fopen($file['all_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set all_form='".$all_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['logical_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['logical_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				move_uploaded_file($file['logical_form']['tmp_name'],$uploadDir.$logical_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['logical_form']['name'], PATHINFO_EXTENSION));
				$logical_form = time().'19.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$logical_form, fopen($file['logical_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set logical_form='".$logical_form."' where caf_id='".$caf_id."'");
			}

			if(!empty($file['stc_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['stc_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				move_uploaded_file($file['stc_form']['tmp_name'],$uploadDir.$stc_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['stc_form']['name'], PATHINFO_EXTENSION));
				$stc_form = time().'20.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$stc_form, fopen($file['stc_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_document_details set stc_form='".$stc_form."' where caf_id='".$caf_id."'");
			}

			// $query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."' where caf_id='".$caf_id."'";
			
			//new by dhanashree*/
			// $query = "update ".PREFIX."caf_document_details set registration_document_type='".$registration_document_type."', registration_document_type_other='".$registration_document_type_other."', address_document_type='".$address_document_type."', address_document_type_other='".$address_document_type_other."', identity_document_type='".$identity_document_type."', identity_document_type_other='".$identity_document_type_other."', authorisation_document_type='".$authorisation_document_type."', authorisation_document_type_other='".$authorisation_document_type_other."', installation_document_type_other='".$installation_document_type_other."', installation_document_type ='".$installation_document_type."' where caf_id='".$caf_id."'";
			// $this->query($query);
			
			/* done by dhanashree*/
			$po_upload_date = date("Y-m-d H:i:s");
			if(!empty($file['po_upload']['name'])) {
				/* $file_name = strtolower( pathinfo($file['po_upload']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				move_uploaded_file($file['po_upload']['tmp_name'],$uploadDir.$po_upload); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$po_upload, fopen($file['po_upload']['tmp_name'], 'rb'), 'public-read');
				
				$this->query("update ".PREFIX."caf_details set po_upload='".$po_upload."', po_upload_date='".$po_upload_date."' where id='".$caf_id."'");
			}
			
			if(empty($file['po_upload']['name'])){  array_push($empty_fields,"PO File");}
			if(empty($file['gst_form']['name'])){  array_push($empty_fields,"GST Certificate"); }

			$c=30;

			$documentResultRS = $this->getCAFRegistrationDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['registration_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_registration_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['registration_document']['name'] as $key=>$value) {
				if(!empty($data['registration_id'][$key])) {
					$registration_id = $this->escape_string($this->strip_all($data['registration_id'][$key]));
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_registration_document set document='".$registration_document."' where id='".$registration_id."'");
					}
				} else {
					$registration_document = '';
					if(!empty($file['registration_document']['name'][$key])) {
						$registration_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['registration_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['registration_document']['tmp_name'][$key],$uploadDir.$registration_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$registration_document, fopen($file['registration_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_registration_document(caf_id, document) values ('$caf_id', '$registration_document')");
				}
			}

			$documentResultRS = $this->getCAFAddressDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['address_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_address_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['address_document']['name'] as $key=>$value) {
				if(!empty($data['address_id'][$key])) {
					$address_id = $this->escape_string($this->strip_all($data['address_id'][$key]));
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_address_document set document='".$address_document."' where id='".$address_id."'");
					}
				} else {
					$address_document = '';
					if(!empty($file['address_document']['name'][$key])) {
						$address_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['address_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['address_document']['tmp_name'][$key],$uploadDir.$address_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$address_document, fopen($file['address_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_address_document(caf_id, document) values ('$caf_id', '$address_document')");
				}
			}

			$documentResultRS = $this->getCAFIdentityDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['identity_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_identity_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['identity_document']['name'] as $key=>$value) {
				if(!empty($data['identity_id'][$key])) {
					$identity_id = $this->escape_string($this->strip_all($data['identity_id'][$key]));
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_identity_document set document='".$identity_document."' where id='".$identity_id."'");
					}
				} else {
					$identity_document = '';
					if(!empty($file['identity_document']['name'][$key])) {
						$identity_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['identity_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['identity_document']['tmp_name'][$key],$uploadDir.$identity_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$identity_document, fopen($file['identity_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_identity_document(caf_id, document) values ('$caf_id', '$identity_document')");
				}
			}

			$documentResultRS = $this->getCAFAuthDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['authorisation_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_authorisation_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['authorisation_document']['name'] as $key=>$value) {
				if(!empty($data['authorisation_id'][$key])) {
					$authorisation_id = $this->escape_string($this->strip_all($data['authorisation_id'][$key]));
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_authorisation_document set document='".$authorisation_document."' where id='".$authorisation_id."'");
					}
				} else {
					$authorisation_document = '';
					if(!empty($file['authorisation_document']['name'][$key])) {
						$authorisation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['authorisation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['authorisation_document']['tmp_name'][$key],$uploadDir.$authorisation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$authorisation_document, fopen($file['authorisation_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_authorisation_document(caf_id, document) values ('$caf_id', '$authorisation_document')");
				}
			}
			
			//new by dhanashree*/
			$documentResultRS = $this->getCAFInstallationDocuments($caf_id);
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['installation_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_installation_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['installation_document']['name'] as $key=>$value) {
				if(!empty($data['installation_id'][$key])) {
					$installation_id = $this->escape_string($this->strip_all($data['installation_id'][$key]));
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_installation_document set document='".$installation_document."' where id='".$installation_id."'");
					}
				} else {
					$installation_document = '';
					if(!empty($file['installation_document']['name'][$key])) {
						$installation_document = time().'-'.$c++.'.'.strtolower(pathinfo($file['installation_document']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['installation_document']['tmp_name'][$key],$uploadDir.$installation_document); */
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$installation_document, fopen($file['installation_document']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_installation_document(caf_id, document) values ('$caf_id', '$installation_document')");
				}
			}
			
			//for other
			$documentResultRS = $this->getCAFOtherDocuments($caf_id);
			
			while($documentResult = $this->fetch($documentResultRS)){
				if(!in_array($documentResult['id'],$data['other_form_id'])) {
					unlink($uploadDir.$documentResult['document']);
					$this->query("delete from ".PREFIX."caf_other_document where id='".$documentResult['id']."'");
				}
			}
			foreach($file['other_form']['name'] as $key=>$value) {
				if(!empty($data['other_form_id'][$key])) {
					$other_form_id = $this->escape_string($this->strip_all($data['other_form_id'][$key]));
					if(!empty($file['other_form']['name'][$key])) {
						$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_other_document set document='".$other_form."' where id='".$other_form_id."'");
					}
				} else {
					$other_form = '';
					if(!empty($file['other_form']['name'][$key])) {
						$other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						/* move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$upload = $s3->upload($bucket, $mediaDir.$other_form, fopen($file['other_form']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_other_document(caf_id, document) values ('$caf_id', '$other_form')");
				}
			}
			// foreach($file['other_form']['name'] as $key=>$value) {
				// if(!empty($data['other_form_id'][$key])) {
					// $other_form_id = $this->escape_string($this->strip_all($data['other_form_id'][$key]));
					// if(!empty($file['other_form']['name'][$key])) {
						// $other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						// move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form);
						// $this->query("update ".PREFIX."caf_other_document set document='".$other_form."' where id='".$other_form_id."'");
					// }
				// } else {
					// $other_form = '';
					// if(!empty($file['other_form']['name'][$key])) {
						// $other_form = time().'-'.$c++.'.'.strtolower(pathinfo($file['other_form']['name'][$key], PATHINFO_EXTENSION));
						// move_uploaded_file($file['other_form']['tmp_name'][$key],$uploadDir.$other_form);
					// }
					// $this->query("insert into ".PREFIX."caf_other_document(caf_id, document) values ('$caf_id', '$other_form')");
				// }
			// }

			// DOCUMENT DETAILS

			// OTHER DETAILS
			$mobile_connection1 = trim($this->escape_string($this->strip_all($data['mobile_connection1'])));
			$mobile_connection2 = trim($this->escape_string($this->strip_all($data['mobile_connection2'])));
			$mobile_connection3 = trim($this->escape_string($this->strip_all($data['mobile_connection3'])));
			$mobile_connection4 = trim($this->escape_string($this->strip_all($data['mobile_connection4'])));
			$no_connection1 = trim($this->escape_string($this->strip_all($data['no_connection1'])));
			$no_connection2 = trim($this->escape_string($this->strip_all($data['no_connection2'])));
			$no_connection3 = trim($this->escape_string($this->strip_all($data['no_connection3'])));
			$no_connection4 = trim($this->escape_string($this->strip_all($data['no_connection4'])));
			$mobile_connection_total = trim($this->escape_string($this->strip_all($data['mobile_connection_total'])));
			$is_mnp = trim($this->escape_string($this->strip_all($data['is_mnp'])));
			$upc_code = trim($this->escape_string($this->strip_all($data['upc_code'])));
			$upc_code_date = trim($this->escape_string($this->strip_all($data['upc_code_date'])));
			$existing_operator = trim($this->escape_string($this->strip_all($data['existing_operator'])));
			$porting_imsi_no = trim($this->escape_string($this->strip_all($data['porting_imsi_no'])));
			/* $payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'])));
			$payment_type = trim($this->escape_string($this->strip_all($data['payment_type']))); */
			// if($payment_mode != 'Cash') {
				$paymentDetailsRS = $this->getCAFPaymentDetailsByCAFId($caf_id);
				while($paymentDetails = $this->fetch($paymentDetailsRS)){
					if(!in_array($paymentDetails['id'],$data['payment_id'])){
						$this->query("delete from ".PREFIX."caf_payment_details where id='".$paymentDetails['id']."'");
					}
				}
				foreach($data['payment_type'] as $key=>$value) {
					$payment_mode = trim($this->escape_string($this->strip_all($data['payment_mode'][$key])));
					$payment_type = trim($this->escape_string($this->strip_all($data['payment_type'][$key])));
					$bank_name = trim($this->escape_string($this->strip_all($data['bank_name'][$key])));
					$bank_acc_no = trim($this->escape_string($this->strip_all($data['bank_acc_no'][$key])));
					$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
					$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
					$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
					if(!empty($data['payment_id'][$key])) {
						$payment_id = $this->escape_string($this->strip_all($data['payment_id'][$key]));
						$this->query("update ".PREFIX."caf_payment_details set payment_mode='".$payment_mode."', bank_name='".$bank_name."', bank_acc_no='".$bank_acc_no."', branch_address='".$branch_address."', transactional_details='".$transactional_details."', transaction_amount='".$transaction_amount."' where id='".$payment_id."'");
					} else {
						$this->query("insert into ".PREFIX."caf_payment_details(caf_id, payment_mode, payment_type, bank_name, bank_acc_no, branch_address, transactional_details, transaction_amount) values ('".$caf_id."', '".$payment_mode."', '".$payment_type."', '".$bank_name."', '".$bank_acc_no."', '".$branch_address."', '".$transactional_details."', '".$transaction_amount."')");
					}
				}
			// }
			$grand_amount = trim($this->escape_string($this->strip_all($data['grand_amount'])));

			if($is_mnp=='Yes' and !empty($file['mnp_form']['name'])) {
				/* $file_name = strtolower( pathinfo($file['mnp_form']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				move_uploaded_file($file['mnp_form']['tmp_name'],$uploadDir.$mnp_form); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['mnp_form']['name'], PATHINFO_EXTENSION));
				$mnp_form = time().'2.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$mnp_form, fopen($file['mnp_form']['tmp_name'], 'rb'), 'public-read');
				$this->query("update ".PREFIX."caf_other_details set mnp_form='".$mnp_form."' where caf_id='".$caf_id."'");
			}

			$query = "update ".PREFIX."caf_other_details set mobile_connection1='".$mobile_connection1."', mobile_connection2='".$mobile_connection2."', mobile_connection3='".$mobile_connection3."', mobile_connection4='".$mobile_connection4."', no_connection1='".$no_connection1."', no_connection2='".$no_connection2."', no_connection3='".$no_connection3."', no_connection4='".$no_connection4."', mobile_connection_total='".$mobile_connection_total."', is_mnp='".$is_mnp."', upc_code='".$upc_code."', upc_code_date='".$upc_code_date."', existing_operator='".$existing_operator."', porting_imsi_no='".$porting_imsi_no."', grand_amount='".$grand_amount."' where caf_id='".$caf_id."'";
			$this->query($query);
			// OTHER DETAILS
			//$data = $this->getUniqueCafDetailsById($caf_id);
			// Service Enrollment
			
			$tar_id = trim($this->escape_string($this->strip_all($data['tar_id'])));
			$market_segment = trim($this->escape_string($this->strip_all($data['market_segment'])));
			$dealer_code = trim($this->escape_string($this->strip_all($data['dealer_code'])));
			$brm_code = trim($this->escape_string($this->strip_all($data['brm_code'])));
			$po_upload_date = date("Y-m-d H:i:s");
			if(!empty($file['po_upload']['name'])) {
				/* $file_name = strtolower( pathinfo($file['po_upload']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				move_uploaded_file($file['po_upload']['tmp_name'],$uploadDir.$po_upload); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['po_upload']['name'], PATHINFO_EXTENSION));
				$po_upload = time().'3.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$po_upload, fopen($file['po_upload']['tmp_name'], 'rb'), 'public-read');
			}
			if(!empty($file['other_approval_docs']['name'])) {
				/* $file_name = strtolower( pathinfo($file['other_approval_docs']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($file['other_approval_docs']['name'], PATHINFO_EXTENSION));
				$other_approval_docs = time().'4.'.$ext;
				move_uploaded_file($file['other_approval_docs']['tmp_name'],$uploadDir.$other_approval_docs); */
				
				$bucket = 'tata-ecaf';
				$mediaDir = 'caf-uploads/';	
				$s3 = S3Client::factory([
					'version' => 'latest',
					'region' => 'ap-south-1',
					'credentials' => [
						'key'    => "AKIAI75IRVIEIAYWEINA",
						'secret' => "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",
					],
				]); 
				$ext = strtolower(pathinfo($file['other_approval_docs']['name'], PATHINFO_EXTENSION));
				$other_approval_docs = time().'4.'.$ext;
				$upload = $s3->upload($bucket, $mediaDir.$other_approval_docs, fopen($file['other_approval_docs']['tmp_name'], 'rb'), 'public-read');
			}
			
			$this->query("update ".PREFIX."caf_details set tar_id='".$tar_id."', market_segment='".$market_segment."', dealer_code='".$dealer_code."', brm_code='".$brm_code."', po_upload='".$po_upload."', po_upload_date='".$po_upload_date."', other_approval_docs='".$other_approval_docs."' where id='".$caf_id."'");
			foreach($data['caf_no'] as $key=>$value) {
				if(!empty($data['caf_no'][$key])) {
					$caf_no = trim($this->escape_string($this->strip_all($data['caf_no'][$key])));
					$ref_doc = trim($this->escape_string($this->strip_all($data['ref_doc'][$key])));
					$branch_address = trim($this->escape_string($this->strip_all($data['branch_address'][$key])));
					$transactional_details = trim($this->escape_string($this->strip_all($data['transactional_details'][$key])));
					$transaction_amount = trim($this->escape_string($this->strip_all($data['transaction_amount'][$key])));
					$this->query("insert into ".PREFIX."caf_existing_details(caf_id, caf_no, ref_doc) values ('".$caf_id."', '".$caf_no."', '".$ref_doc."')");
				}
			}
			
			if($product=='Internet Leased Line') {
				$ill_connection_type = trim($this->escape_string($this->strip_all($data['ill_connection_type'])));
				$ill_del_no = trim($this->escape_string($this->strip_all($data['ill_del_no'])));
				$ill_billing_cycle = trim($this->escape_string($this->strip_all($data['ill_billing_cycle'])));
				$ill_exit_policy = trim($this->escape_string($this->strip_all($data['ill_exit_policy'])));
				$ill_pm_email = trim($this->escape_string($this->strip_all($data['ill_pm_email'])));
				$ill_super_account = trim($this->escape_string($this->strip_all($data['ill_super_account'])));
				$ill_addon_account = trim($this->escape_string($this->strip_all($data['ill_addon_account'])));
				$ill_circuit_id = trim($this->escape_string($this->strip_all($data['ill_circuit_id'])));
				$ill_fan_no = trim($this->escape_string($this->strip_all($data['ill_fan_no'])));
				$ill_srf_no = trim($this->escape_string($this->strip_all($data['ill_srf_no'])));
				$ill_bandwidth = trim($this->escape_string($this->strip_all($data['ill_bandwidth'])));
				$ill_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['ill_bandwidth_ratio'])));
				
				//echo $file['fan_nmber_upload']['name'];exit;
				if(!empty($file['ill_fan_nmber_upload']['name'])) {
					//echo "here";exit;
					/* $file_name = strtolower( pathinfo($file['ill_fan_nmber_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['ill_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$ill_fan_nmber_upload = time().'1.'.$ext;
					move_uploaded_file($file['ill_fan_nmber_upload']['tmp_name'],$uploadDir.$ill_fan_nmber_upload); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['ill_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$ill_fan_nmber_upload = time().'1.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$ill_fan_nmber_upload, fopen($file['ill_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
					$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$ill_fan_nmber_upload."' where caf_id='".$caf_id."'");
				}


				$this->query("insert into ".PREFIX."caf_ill_details(caf_id, ill_connection_type, ill_del_no, ill_billing_cycle, ill_exit_policy, ill_pm_email, ill_super_account, ill_addon_account, ill_circuit_id, ill_fan_no, ill_srf_no, ill_bandwidth, ill_bandwidth_ratio) values('".$caf_id."', '".$ill_connection_type."', '".$ill_del_no."', '".$ill_billing_cycle."', '".$ill_exit_policy."', '".$ill_pm_email."', '".$ill_super_account."', '".$ill_addon_account."', '".$ill_circuit_id."', '".$ill_fan_no."', '".$ill_srf_no."', '".$ill_bandwidth."', '".$ill_bandwidth_ratio."')");
			}
			else if($product=='Smart VPN') { 
				if($variant=='MPLS Standard' || $variant=='MPLS Managed') {
					$mpls_connection_type = trim($this->escape_string($this->strip_all($data['mpls_connection_type'])));
					$mpls_del_no = trim($this->escape_string($this->strip_all($data['mpls_del_no'])));
					$mpls_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_billing_cycle'])));
					$mpls_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_exit_policy'])));
					$mpls_pm_email = trim($this->escape_string($this->strip_all($data['mpls_pm_email'])));
					$mpls_super_account = trim($this->escape_string($this->strip_all($data['mpls_super_account'])));
					$mpls_addon_account = trim($this->escape_string($this->strip_all($data['mpls_addon_account'])));
					$mpls_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_circuit_id'])));
					$mpls_fan_no = trim($this->escape_string($this->strip_all($data['mpls_fan_no'])));
					$mpls_srf_no = trim($this->escape_string($this->strip_all($data['mpls_srf_no'])));
					$mpls_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_bandwidth'])));
					$mpls_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['mpls_bandwidth_ratio'])));

					//echo $file['fan_nmber_upload']['name'];exit;
					if(!empty($file['mpls_fan_nmber_upload']['name'])) {
						//echo "here";exit;
						/* $file_name = strtolower( pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$mpls_fan_nmber_upload = time().'1.'.$ext;
						move_uploaded_file($file['mpls_fan_nmber_upload']['tmp_name'],$uploadDir.$mpls_fan_nmber_upload); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
						$mpls_fan_nmber_upload = time().'1.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_fan_nmber_upload, fopen($file['mpls_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
						$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$mpls_fan_nmber_upload."' where caf_id='".$caf_id."'");
					}
				
					$this->query("insert into ".PREFIX."caf_mpls_details(caf_id, mpls_connection_type, mpls_del_no, mpls_billing_cycle, mpls_exit_policy, mpls_pm_email, mpls_super_account, mpls_addon_account, mpls_circuit_id, mpls_fan_no, mpls_srf_no, mpls_bandwidth, mpls_bandwidth_ratio) values('".$caf_id."', '".$mpls_connection_type."', '".$mpls_del_no."', '".$mpls_billing_cycle."', '".$mpls_exit_policy."', '".$mpls_pm_email."', '".$mpls_super_account."', '".$mpls_addon_account."', '".$mpls_circuit_id."', '".$mpls_fan_no."', '".$mpls_srf_no."', '".$mpls_bandwidth."', '".$mpls_bandwidth_ratio."')");
				}
				else if($variant=='Xpress VPN') {
					$mpls_express_connection_type = trim($this->escape_string($this->strip_all($data['mpls_express_connection_type'])));
					$mpls_express_del_no = trim($this->escape_string($this->strip_all($data['mpls_express_del_no'])));
					$mpls_express_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_express_exit_policy'])));
					$mpls_express_pm_email = trim($this->escape_string($this->strip_all($data['mpls_express_pm_email'])));
					$mpls_express_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_express_billing_cycle'])));
					$mpls_express_parent_account = trim($this->escape_string($this->strip_all($data['mpls_express_parent_account'])));
					$mpls_express_addon_account = trim($this->escape_string($this->strip_all($data['mpls_express_addon_account'])));
					$mpls_express_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_express_circuit_id'])));
					$mpls_express_client_del_creation = trim($this->escape_string($this->strip_all($data['mpls_express_client_del_creation'])));
					$mpls_express_apn_name = trim($this->escape_string($this->strip_all($data['mpls_express_apn_name'])));
					$mpls_express_dummy_del = trim($this->escape_string($this->strip_all($data['mpls_express_dummy_del'])));
					$mpls_express_user_id = trim($this->escape_string($this->strip_all($data['mpls_express_user_id'])));
					$mpls_express_password = trim($this->escape_string($this->strip_all($data['mpls_express_password'])));
					$mpls_express_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_express_bandwidth'])));
					$mpls_express_internet_blocking = trim($this->escape_string($this->strip_all($data['mpls_express_internet_blocking'])));
					$mpls_express_client_id_charges = trim($this->escape_string($this->strip_all($data['mpls_express_client_id_charges'])));
					$mpls_express_customer_apn = trim($this->escape_string($this->strip_all($data['mpls_express_customer_apn'])));
					$mpls_express_reserved_id = trim($this->escape_string($this->strip_all($data['mpls_express_reserved_id'])));
					$mpls_express_empower_id = trim($this->escape_string($this->strip_all($data['mpls_express_empower_id'])));
					$mpls_express_handset_id = trim($this->escape_string($this->strip_all($data['mpls_express_handset_id'])));
					$mpls_express_network_email = trim($this->escape_string($this->strip_all($data['mpls_express_network_email'])));

					if(!empty($file['mpls_express_apn_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_express_apn_excel = time().'5.'.$ext;
						move_uploaded_file($file['mpls_express_apn_excel']['tmp_name'],$uploadDir.$mpls_express_apn_excel); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_express_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_express_apn_excel = time().'5.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_express_apn_excel, fopen($file['mpls_express_apn_excel']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_mpls_express_details(caf_id, mpls_express_connection_type, mpls_express_del_no, mpls_express_exit_policy, mpls_express_pm_email, mpls_express_billing_cycle, mpls_express_parent_account, mpls_express_addon_account, mpls_express_apn_name, mpls_express_user_id, mpls_express_password, mpls_express_bandwidth, mpls_express_internet_blocking, mpls_express_client_id_charges, mpls_express_customer_apn, mpls_express_apn_excel, mpls_express_reserved_id, mpls_express_empower_id, mpls_express_handset_id, mpls_express_network_email) values('".$caf_id."', '".$mpls_express_connection_type."', '".$mpls_express_del_no."', '".$mpls_express_exit_policy."', '".$mpls_express_pm_email."', '".$mpls_express_billing_cycle."', '".$mpls_express_parent_account."', '".$mpls_express_addon_account."', '".$mpls_express_apn_name."', '".$mpls_express_user_id."', '".$mpls_express_password."', '".$mpls_express_bandwidth."', '".$mpls_express_internet_blocking."', '".$mpls_express_client_id_charges."', '".$mpls_express_customer_apn."', '".$mpls_express_apn_excel."', '".$mpls_express_reserved_id."', '".$mpls_express_empower_id."', '".$mpls_express_handset_id."', '".$mpls_express_network_email."')");
				}
				else if($variant=='Road warrior') {
					$mpls_rw_connection_type = trim($this->escape_string($this->strip_all($data['mpls_rw_connection_type'])));
					$mpls_rw_del_no = trim($this->escape_string($this->strip_all($data['mpls_rw_del_no'])));
					$mpls_rw_billing_cycle = trim($this->escape_string($this->strip_all($data['mpls_rw_billing_cycle'])));
					$mpls_rw_exit_policy = trim($this->escape_string($this->strip_all($data['mpls_rw_exit_policy'])));
					$mpls_rw_pw_email = trim($this->escape_string($this->strip_all($data['mpls_rw_pw_email'])));
					$mpls_rw_parent_account = trim($this->escape_string($this->strip_all($data['mpls_rw_parent_account'])));
					$mpls_rw_addon_account = trim($this->escape_string($this->strip_all($data['mpls_rw_addon_account'])));
					$mpls_rw_circuit_id = trim($this->escape_string($this->strip_all($data['mpls_rw_circuit_id'])));
					$mpls_rw_apn_name = trim($this->escape_string($this->strip_all($data['mpls_rw_apn_name'])));
					$mpls_rw_del_creation = trim($this->escape_string($this->strip_all($data['mpls_rw_del_creation'])));
					$mpls_rw_dummy_del = trim($this->escape_string($this->strip_all($data['mpls_rw_dummy_del'])));
					$mpls_rw_user_id = trim($this->escape_string($this->strip_all($data['mpls_rw_user_id'])));
					$mpls_rw_password = trim($this->escape_string($this->strip_all($data['mpls_rw_password'])));
					$mpls_rw_bandwidth = trim($this->escape_string($this->strip_all($data['mpls_rw_bandwidth'])));
					$mpls_rw_internet_blocking = trim($this->escape_string($this->strip_all($data['mpls_rw_internet_blocking'])));
					$mpls_rw_client_id_charges = trim($this->escape_string($this->strip_all($data['mpls_rw_client_id_charges'])));
					$mpls_rw_customer_apn = trim($this->escape_string($this->strip_all($data['mpls_rw_customer_apn'])));
					$mpls_rw_calling_level = trim($this->escape_string($this->strip_all($data['mpls_rw_calling_level'])));

					if(!empty($file['mpls_rw_apn_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_rw_apn_excel = time().'5.'.$ext;
						move_uploaded_file($file['mpls_rw_apn_excel']['tmp_name'],$uploadDir.$mpls_rw_apn_excel); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['mpls_rw_apn_excel']['name'], PATHINFO_EXTENSION));
						$mpls_rw_apn_excel = time().'5.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$mpls_rw_apn_excel, fopen($file['mpls_rw_apn_excel']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_mpls_rw_details(caf_id, mpls_rw_connection_type, mpls_rw_del_no, mpls_rw_exit_policy, mpls_rw_pw_email, mpls_rw_billing_cycle, mpls_rw_parent_account, mpls_rw_addon_account, mpls_rw_circuit_id, mpls_rw_apn_name, mpls_rw_del_creation, mpls_rw_dummy_del,  mpls_rw_user_id, mpls_rw_password, mpls_rw_bandwidth, mpls_rw_internet_blocking, mpls_rw_client_id_charges, mpls_rw_customer_apn, mpls_rw_apn_excel, mpls_rw_calling_level) values('".$caf_id."', '".$mpls_rw_connection_type."', '".$mpls_rw_del_no."', '".$mpls_rw_exit_policy."', '".$mpls_rw_pw_email."', '".$mpls_rw_billing_cycle."', '".$mpls_rw_parent_account."', '".$mpls_rw_addon_account."', '".$mpls_rw_circuit_id."', '".$mpls_rw_apn_name."', '".$mpls_rw_del_creation."', '".$mpls_rw_dummy_del."', '".$mpls_rw_user_id."', '".$mpls_rw_password."', '".$mpls_rw_bandwidth."', '".$mpls_rw_internet_blocking."', '".$mpls_rw_client_id_charges."', '".$mpls_rw_customer_apn."', '".$mpls_rw_apn_excel."', '".$mpls_rw_calling_level."')");
				}
			}
			else if($product=='Leased Line') {
				if($variant=='Standard' || $variant=='Premium') {
					if($sub_variant=='DLC') {
						$dlc_connection_type = trim($this->escape_string($this->strip_all($data['dlc_connection_type'])));
						$dlc_del_no = trim($this->escape_string($this->strip_all($data['dlc_del_no'])));
						$del_billing_cycle = trim($this->escape_string($this->strip_all($data['del_billing_cycle'])));
						$del_exit_policy = trim($this->escape_string($this->strip_all($data['del_exit_policy'])));
						$dlc_pm_email = trim($this->escape_string($this->strip_all($data['dlc_pm_email'])));
						$dlc_parent_account = trim($this->escape_string($this->strip_all($data['dlc_parent_account'])));
						$dlc_addon_account = trim($this->escape_string($this->strip_all($data['dlc_addon_account'])));
						$dlc_circuit_id = trim($this->escape_string($this->strip_all($data['dlc_circuit_id'])));
						$dlc_fan_no = trim($this->escape_string($this->strip_all($data['dlc_fan_no'])));
						$dlc_srf_no = trim($this->escape_string($this->strip_all($data['dlc_srf_no'])));
						$dlc_bandwidth = trim($this->escape_string($this->strip_all($data['dlc_bandwidth'])));
						$dlc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['dlc_bandwidth_ratio'])));
						//echo $file['fan_nmber_upload']['name'];exit;
						if(!empty($file['dlc_fan_nmber_upload']['name'])) {
							//echo "here";exit;
							/* $file_name = strtolower( pathinfo($file['dlc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
							$ext = strtolower(pathinfo($file['dlc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$dlc_fan_nmber_upload = time().'1.'.$ext;
							move_uploaded_file($file['dlc_fan_nmber_upload']['tmp_name'],$uploadDir.$dlc_fan_nmber_upload); */
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$ext = strtolower(pathinfo($file['dlc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$dlc_fan_nmber_upload = time().'1.'.$ext;
							$upload = $s3->upload($bucket, $mediaDir.$dlc_fan_nmber_upload, fopen($file['dlc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$dlc_fan_nmber_upload."' where caf_id='".$caf_id."'");
						}

						$this->query("insert into ".PREFIX."caf_dlc_details(caf_id, dlc_connection_type, dlc_del_no, del_billing_cycle, del_exit_policy, dlc_pm_email, dlc_parent_account, dlc_addon_account, dlc_circuit_id, dlc_fan_no, dlc_srf_no, dlc_bandwidth, dlc_bandwidth_ratio) values('".$caf_id."', '".$dlc_connection_type."', '".$dlc_del_no."', '".$del_billing_cycle."', '".$del_exit_policy."', '".$dlc_pm_email."', '".$dlc_parent_account."', '".$dlc_addon_account."', '".$dlc_circuit_id."', '".$dlc_fan_no."', '".$dlc_srf_no."', '".$dlc_bandwidth."', '".$dlc_bandwidth_ratio."')");
					}
					if($sub_variant=='NPLC') {
						$nplc_connection_type = trim($this->escape_string($this->strip_all($data['nplc_connection_type'])));
						$nplc_del_no = trim($this->escape_string($this->strip_all($data['nplc_del_no'])));
						$nplc_billing_cycle = trim($this->escape_string($this->strip_all($data['nplc_billing_cycle'])));
						$nplc_exit_policy = trim($this->escape_string($this->strip_all($data['nplc_exit_policy'])));
						$nplc_pm_email = trim($this->escape_string($this->strip_all($data['nplc_pm_email'])));
						$nplc_parent_account = trim($this->escape_string($this->strip_all($data['nplc_parent_account'])));
						$nplc_addon_account = trim($this->escape_string($this->strip_all($data['nplc_addon_account'])));
						$nplc_circuit_id = trim($this->escape_string($this->strip_all($data['nplc_circuit_id'])));
						$nplc_fan_no = trim($this->escape_string($this->strip_all($data['nplc_fan_no'])));
						$nplc_srf_no = trim($this->escape_string($this->strip_all($data['nplc_srf_no'])));
						$nplc_bandwidth = trim($this->escape_string($this->strip_all($data['nplc_bandwidth'])));
						$nplc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['nplc_bandwidth_ratio'])));

						//echo $file['fan_nmber_upload']['name'];exit;
						if(!empty($file['nplc_fan_nmber_upload']['name'])) {
							//echo "here";exit;
							/* $file_name = strtolower( pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
							$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$nplc_fan_nmber_upload = time().'1.'.$ext;
							move_uploaded_file($file['nplc_fan_nmber_upload']['tmp_name'],$uploadDir.$nplc_fan_nmber_upload); */
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$nplc_fan_nmber_upload = time().'1.'.$ext;
							$upload = $s3->upload($bucket, $mediaDir.$nplc_fan_nmber_upload, fopen($file['nplc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$nplc_fan_nmber_upload."' where caf_id='".$caf_id."'");
						}
						$this->query("insert into ".PREFIX."caf_nplc_details(caf_id, nplc_connection_type, nplc_del_no, nplc_billing_cycle, nplc_exit_policy, nplc_pm_email, nplc_parent_account, nplc_addon_account, nplc_circuit_id, nplc_fan_no, nplc_srf_no, nplc_bandwidth, nplc_bandwidth_ratio) values('".$caf_id."', '".$nplc_connection_type."', '".$nplc_del_no."', '".$nplc_billing_cycle."', '".$nplc_exit_policy."', '".$nplc_pm_email."', '".$nplc_parent_account."', '".$nplc_addon_account."', '".$nplc_circuit_id."', '".$nplc_fan_no."', '".$nplc_srf_no."', '".$nplc_bandwidth."', '".$nplc_bandwidth_ratio."')");
					}
				}
				if($variant=='Platinum' || $variant=='Ultra LoLa') {
					$nplc_connection_type = trim($this->escape_string($this->strip_all($data['nplc_connection_type'])));
					$nplc_del_no = trim($this->escape_string($this->strip_all($data['nplc_del_no'])));
					$nplc_billing_cycle = trim($this->escape_string($this->strip_all($data['nplc_billing_cycle'])));
					$nplc_exit_policy = trim($this->escape_string($this->strip_all($data['nplc_exit_policy'])));
					$nplc_pm_email = trim($this->escape_string($this->strip_all($data['nplc_pm_email'])));
					$nplc_parent_account = trim($this->escape_string($this->strip_all($data['nplc_parent_account'])));
					$nplc_addon_account = trim($this->escape_string($this->strip_all($data['nplc_addon_account'])));
					$nplc_circuit_id = trim($this->escape_string($this->strip_all($data['nplc_circuit_id'])));
					$nplc_fan_no = trim($this->escape_string($this->strip_all($data['nplc_fan_no'])));
					$nplc_srf_no = trim($this->escape_string($this->strip_all($data['nplc_srf_no'])));
					$nplc_bandwidth = trim($this->escape_string($this->strip_all($data['nplc_bandwidth'])));
					$nplc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['nplc_bandwidth_ratio'])));
					//echo $file['fan_nmber_upload']['name'];exit;
						if(!empty($file['nplc_fan_nmber_upload']['name'])) {
							//echo "here";exit;
							/* $file_name = strtolower( pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
							$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$nplc_fan_nmber_upload = time().'1.'.$ext;
							move_uploaded_file($file['nplc_fan_nmber_upload']['tmp_name'],$uploadDir.$nplc_fan_nmber_upload); */
							
							$bucket = 'tata-ecaf';
							$mediaDir = 'caf-uploads/';	
							$s3 = S3Client::factory([
								'version' => 'latest',
								'region' => 'ap-south-1',
								'credentials' => [
									'key'    => "AKIAI75IRVIEIAYWEINA",
									'secret' => "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",
								],
							]); 
							$ext = strtolower(pathinfo($file['nplc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
							$nplc_fan_nmber_upload = time().'1.'.$ext;
							$upload = $s3->upload($bucket, $mediaDir.$nplc_fan_nmber_upload, fopen($file['nplc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
							$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$nplc_fan_nmber_upload."' where caf_id='".$caf_id."'");
						}

					$this->query("insert into ".PREFIX."caf_nplc_details(caf_id, nplc_connection_type, nplc_del_no, nplc_billing_cycle, nplc_exit_policy, nplc_pm_email, nplc_parent_account, nplc_addon_account, nplc_circuit_id, nplc_fan_no, nplc_srf_no, nplc_bandwidth, nplc_bandwidth_ratio) values('".$caf_id."', '".$nplc_connection_type."', '".$nplc_del_no."', '".$nplc_billing_cycle."', '".$nplc_exit_policy."', '".$nplc_pm_email."', '".$nplc_parent_account."', '".$nplc_addon_account."', '".$nplc_circuit_id."', '".$nplc_fan_no."', '".$nplc_srf_no."', '".$nplc_bandwidth."', '".$nplc_bandwidth_ratio."')");
				}
			}
			else if($product=='L2 Multicast Solution') {
				$l2mc_connection_type = trim($this->escape_string($this->strip_all($data['l2mc_connection_type'])));
				$l2mc_del_no = trim($this->escape_string($this->strip_all($data['l2mc_del_no'])));
				$l2mc_billing_cycle = trim($this->escape_string($this->strip_all($data['l2mc_billing_cycle'])));
				$l2mc_exit_policy = trim($this->escape_string($this->strip_all($data['l2mc_exit_policy'])));
				$l2mc_pm_email = trim($this->escape_string($this->strip_all($data['l2mc_pm_email'])));
				$l2mc_parent_account = trim($this->escape_string($this->strip_all($data['l2mc_parent_account'])));
				$l2mc_addon_account = trim($this->escape_string($this->strip_all($data['l2mc_addon_account'])));
				$l2mc_circuit_id = trim($this->escape_string($this->strip_all($data['l2mc_circuit_id'])));
				$l2mc_fan_no = trim($this->escape_string($this->strip_all($data['l2mc_fan_no'])));
				$l2mc_srf_no = trim($this->escape_string($this->strip_all($data['l2mc_srf_no'])));
				$l2mc_bandwidth = trim($this->escape_string($this->strip_all($data['l2mc_bandwidth'])));
				$l2mc_bandwidth_ratio = trim($this->escape_string($this->strip_all($data['l2mc_bandwidth_ratio'])));
				//echo $file['fan_nmber_upload']['name'];exit;
				if(!empty($file['l2mc_fan_nmber_upload']['name'])) {
					//echo "here";exit;
					/* $file_name = strtolower( pathinfo($file['l2mc_fan_nmber_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['l2mc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$l2mc_fan_nmber_upload = time().'1.'.$ext;
					move_uploaded_file($file['l2mc_fan_nmber_upload']['tmp_name'],$uploadDir.$l2mc_fan_nmber_upload); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['l2mc_fan_nmber_upload']['name'], PATHINFO_EXTENSION));
					$l2mc_fan_nmber_upload = time().'1.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$l2mc_fan_nmber_upload, fopen($file['l2mc_fan_nmber_upload']['tmp_name'], 'rb'), 'public-read');
					$this->query("update ".PREFIX."caf_product_details set drop_location_sheet='".$l2mc_fan_nmber_upload."' where caf_id='".$caf_id."'");
				}

				$this->query("insert into ".PREFIX."caf_l2mc_details(caf_id, l2mc_connection_type, l2mc_del_no, l2mc_billing_cycle, l2mc_exit_policy, l2mc_pm_email, l2mc_parent_account, l2mc_addon_account, l2mc_circuit_id, l2mc_fan_no, l2mc_srf_no, l2mc_bandwidth, l2mc_bandwidth_ratio) values('".$caf_id."', '".$l2mc_connection_type."', '".$l2mc_del_no."', '".$l2mc_billing_cycle."', '".$l2mc_exit_policy."', '".$l2mc_pm_email."', '".$l2mc_parent_account."', '".$l2mc_addon_account."', '".$l2mc_circuit_id."', '".$l2mc_fan_no."', '".$l2mc_srf_no."', '".$l2mc_bandwidth."', '".$l2mc_bandwidth_ratio."')");
			}
			else if($product=='Photon') {
				if($variant=='Photon Dongle') {
					$photon_dongal_connection_type = trim($this->escape_string($this->strip_all($data['photon_dongal_connection_type'])));
					$photon_dongal_del_no = trim($this->escape_string($this->strip_all($data['photon_dongal_del_no'])));
					$photon_dongal_rid = trim($this->escape_string($this->strip_all($data['photon_dongal_rid'])));
					$photon_dongal_pm_email = trim($this->escape_string($this->strip_all($data['photon_dongal_pm_email'])));
					$photon_dongal_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_dongal_billing_cycle'])));
					$photon_dongal_parent_account = trim($this->escape_string($this->strip_all($data['photon_dongal_parent_account'])));
					$photon_dongal_addon_account = trim($this->escape_string($this->strip_all($data['photon_dongal_addon_account'])));
					$photon_dongal_handset_id = trim($this->escape_string($this->strip_all($data['photon_dongal_handset_id'])));

					$this->query("insert into ".PREFIX."caf_photon_dongal_details(caf_id, photon_dongal_connection_type, photon_dongal_del_no, photon_dongal_rid, photon_dongal_pm_email, photon_dongal_billing_cycle, photon_dongal_parent_account, photon_dongal_addon_account, photon_dongal_handset_id) values('".$caf_id."', '".$photon_dongal_connection_type."', '".$photon_dongal_del_no."', '".$photon_dongal_rid."', '".$photon_dongal_pm_email."', '".$photon_dongal_billing_cycle."', '".$photon_dongal_parent_account."', '".$photon_dongal_addon_account."', '".$photon_dongal_handset_id."')");
				}
				else if($variant=='Photon Dongle Wifi') {
					$photon_wifi_connection_type = trim($this->escape_string($this->strip_all($data['photon_wifi_connection_type'])));
					$photon_wifi_del_no = trim($this->escape_string($this->strip_all($data['photon_wifi_del_no'])));
					$photon_wifi_rid = trim($this->escape_string($this->strip_all($data['photon_wifi_rid'])));
					$photon_wifi_pm_email = trim($this->escape_string($this->strip_all($data['photon_wifi_pm_email'])));
					$photon_wifi_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_wifi_billing_cycle'])));
					$photon_wifi_parent_account = trim($this->escape_string($this->strip_all($data['photon_wifi_parent_account'])));
					$photon_wifi_addon_account = trim($this->escape_string($this->strip_all($data['photon_wifi_addon_account'])));
					$photon_wifi_handset_id = trim($this->escape_string($this->strip_all($data['photon_wifi_handset_id'])));

					$this->query("insert into ".PREFIX."caf_photon_wifi_details(caf_id, photon_wifi_connection_type, photon_wifi_del_no, photon_wifi_rid, photon_wifi_pm_email, photon_wifi_billing_cycle, photon_wifi_parent_account, photon_wifi_addon_account, photon_wifi_handset_id) values('".$caf_id."', '".$photon_wifi_connection_type."', '".$photon_wifi_del_no."', '".$photon_wifi_rid."', '".$photon_wifi_pm_email."', '".$photon_wifi_billing_cycle."', '".$photon_wifi_parent_account."', '".$photon_wifi_addon_account."', '".$photon_wifi_handset_id."')");
				}
				else if($variant=='Photon Mifi') {
					$photon_mifi_existing_caf = trim($this->escape_string($this->strip_all($data['photon_mifi_existing_caf'])));
					$photon_mifi_proof_company_id = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_company_id'])));
					$photon_mifi_proof_authorization = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_authorization'])));
					$photon_mifi_proof_address = trim($this->escape_string($this->strip_all($data['photon_mifi_proof_address'])));
					$photon_mifi_other_docs = trim($this->escape_string($this->strip_all($data['photon_mifi_other_docs'])));
					$photon_mifi_govt_id_proof = trim($this->escape_string($this->strip_all($data['photon_mifi_govt_id_proof'])));
					$photon_mifi_connection_type = trim($this->escape_string($this->strip_all($data['photon_mifi_connection_type'])));
					$photon_mifi_del_no = trim($this->escape_string($this->strip_all($data['photon_mifi_del_no'])));
					$photon_mifi_rid = trim($this->escape_string($this->strip_all($data['photon_mifi_rid'])));
					$photon_mifi_pm_email = trim($this->escape_string($this->strip_all($data['photon_mifi_pm_email'])));
					$photon_mifi_billing_cycle = trim($this->escape_string($this->strip_all($data['photon_mifi_billing_cycle'])));
					$photon_mifi_parent_account = trim($this->escape_string($this->strip_all($data['photon_mifi_parent_account'])));
					$photon_mifi_addon_account = trim($this->escape_string($this->strip_all($data['photon_mifi_addon_account'])));
					$photon_mifi_handset_id = trim($this->escape_string($this->strip_all($data['photon_mifi_handset_id'])));

					if(!empty($file['photon_mifi_approval']['name'])) {
						/* $file_name = strtolower( pathinfo($file['photon_mifi_approval']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['photon_mifi_approval']['name'], PATHINFO_EXTENSION));
						$photon_mifi_approval = time().'7.'.$ext;
						move_uploaded_file($file['photon_mifi_approval']['tmp_name'],$uploadDir.$photon_mifi_approval); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['photon_mifi_approval']['name'], PATHINFO_EXTENSION));
						$photon_mifi_approval = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$photon_mifi_approval, fopen($file['photon_mifi_approval']['tmp_name'], 'rb'), 'public-read');
					}
					$this->query("insert into ".PREFIX."caf_photon_mifi_details(caf_id, photon_mifi_existing_caf, photon_mifi_proof_company_id, photon_mifi_proof_authorization, photon_mifi_proof_address, photon_mifi_other_docs, photon_mifi_govt_id_proof, photon_mifi_approval, photon_mifi_connection_type, photon_mifi_del_no, photon_mifi_rid, photon_mifi_pm_email, photon_mifi_billing_cycle, photon_mifi_parent_account, photon_mifi_addon_account, photon_mifi_handset_id) values('".$caf_id."', '".$photon_mifi_existing_caf."', '".$photon_mifi_proof_company_id."', '".$photon_mifi_proof_authorization."', '".$photon_mifi_proof_address."', '".$photon_mifi_other_docs."', '".$photon_mifi_govt_id_proof."', '".$photon_mifi_approval."', '".$photon_mifi_connection_type."', '".$photon_mifi_del_no."', '".$photon_mifi_rid."', '".$photon_mifi_pm_email."', '".$photon_mifi_billing_cycle."', '".$photon_mifi_parent_account."', '".$photon_mifi_addon_account."', '".$photon_mifi_handset_id."')");
				}
			}
			else if($product=='PRI') {
					$pri_cug_type = trim($this->escape_string($this->strip_all($data['pri_cug_type'])));
					$pri_connection_type = trim($this->escape_string($this->strip_all($data['pri_connection_type'])));
					$pri_billing_cycle = trim($this->escape_string($this->strip_all($data['pri_billing_cycle'])));
					$pri_pm_email = trim($this->escape_string($this->strip_all($data['pri_pm_email'])));
					$pri_parent_account = trim($this->escape_string($this->strip_all($data['pri_parent_account'])));
					$pri_addon_account = trim($this->escape_string($this->strip_all($data['pri_addon_account'])));
					$pri_rid = trim($this->escape_string($this->strip_all($data['pri_rid'])));
					$pri_del_no = trim($this->escape_string($this->strip_all($data['pri_del_no'])));
					$pri_wepbax_config = trim($this->escape_string($this->strip_all($data['pri_wepbax_config'])));
					$pri_service_type_wireline = trim($this->escape_string($this->strip_all($data['pri_service_type_wireline'])));
					$pri_pilot_no = trim($this->escape_string($this->strip_all($data['pri_pilot_no'])));
					$pri_did_count = trim($this->escape_string($this->strip_all($data['pri_did_count'])));
					$pri_channel_count = trim($this->escape_string($this->strip_all($data['pri_channel_count'])));
					$pri_switch_name = trim($this->escape_string($this->strip_all($data['pri_switch_name'])));
					$pri_dial_code = trim($this->escape_string($this->strip_all($data['pri_dial_code'])));
					$pri_zone_id = trim($this->escape_string($this->strip_all($data['pri_zone_id'])));
					$pri_msgn_node = trim($this->escape_string($this->strip_all($data['pri_msgn_node'])));
					$pri_d_channel = trim($this->escape_string($this->strip_all($data['pri_d_channel'])));
					$pri_sponsered = trim($this->escape_string($this->strip_all($data['pri_sponsered'])));
					$pri_epabx_procured = trim($this->escape_string($this->strip_all($data['pri_epabx_procured'])));
					$pri_cost_epabx = trim($this->escape_string($this->strip_all($data['pri_cost_epabx'])));
					$pri_penalty_matrix = trim($this->escape_string($this->strip_all($data['pri_penalty_matrix'])));
					$pri_contract_period = trim($this->escape_string($this->strip_all($data['pri_contract_period'])));
					$pri_cost_pri_card = trim($this->escape_string($this->strip_all($data['pri_cost_pri_card'])));
					$pri_vendor_name = trim($this->escape_string($this->strip_all($data['pri_vendor_name'])));
					$pri_ebabx_make = trim($this->escape_string($this->strip_all($data['pri_ebabx_make'])));
					$pri_mis_entry = trim($this->escape_string($this->strip_all($data['pri_mis_entry'])));
					$pri_calling_level = trim($this->escape_string($this->strip_all($data['pri_calling_level'])));
					$pri_hosted_ivr = trim($this->escape_string($this->strip_all($data['pri_hosted_ivr'])));
					$pri_hivr_no = trim($this->escape_string($this->strip_all($data['pri_hivr_no'])));
					$pri_type = trim($this->escape_string($this->strip_all($data['pri_type'])));

					$this->query("insert into ".PREFIX."caf_pri_details(caf_id, pri_cug_type, pri_del_no, pri_billing_cycle, pri_pm_email, pri_connection_type, pri_parent_account, pri_wepbax_config, pri_rid, pri_addon_account, pri_service_type_wireline, pri_pilot_no, pri_did_count, pri_switch_name, pri_dial_code, pri_zone_id, pri_msgn_node, pri_d_channel, pri_channel_count, pri_sponsered, pri_epabx_procured, pri_cost_epabx, pri_penalty_matrix, pri_contract_period, pri_cost_pri_card, pri_vendor_name, pri_ebabx_make, pri_mis_entry, pri_calling_level, pri_hosted_ivr, pri_hivr_no, pri_type) values('".$caf_id."', '".$pri_cug_type."', '".$pri_del_no."', '".$pri_billing_cycle."', '".$pri_pm_email."', '".$pri_connection_type."', '".$pri_parent_account."', '".$pri_wepbax_config."', '".$pri_rid."', '".$pri_addon_account."', '".$pri_service_type_wireline."', '".$pri_pilot_no."', '".$pri_did_count."', '".$pri_switch_name."', '".$pri_dial_code."', '".$pri_zone_id."', '".$pri_msgn_node."', '".$pri_d_channel."', '".$pri_channel_count."', '".$pri_sponsered."', '".$pri_epabx_procured."', '".$pri_cost_epabx."', '".$pri_penalty_matrix."', '".$pri_contract_period."', '".$pri_cost_pri_card."', '".$pri_vendor_name."', '".$pri_ebabx_make."', '".$pri_mis_entry."', '".$pri_calling_level."', '".$pri_hosted_ivr."', '".$pri_hivr_no."', '".$pri_type."')");
				}
			else if($product=='SIP Trunk') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
			else if($product=='Standard Centrex') {
					$standard_centrex_group_type = trim($this->escape_string($this->strip_all($data['standard_centrex_group_type'])));
					$standard_centrex_bgid = trim($this->escape_string($this->strip_all($data['standard_centrex_bgid'])));
					$standard_centrex_idp_id = trim($this->escape_string($this->strip_all($data['standard_centrex_idp_id'])));
					$standard_centrex_switch_name = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_name'])));
					$standard_centrex_dail_code = trim($this->escape_string($this->strip_all($data['standard_centrex_dail_code'])));
					$standard_centrex_zone = trim($this->escape_string($this->strip_all($data['standard_centrex_zone'])));
					$standard_centrex_zte_pnr = trim($this->escape_string($this->strip_all($data['standard_centrex_zte_pnr'])));
					$standard_centrex_switch_type = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_type'])));
					$standard_centrex_switch_details = trim($this->escape_string($this->strip_all($data['standard_centrex_switch_details'])));
					$standard_centrex_zone_id = trim($this->escape_string($this->strip_all($data['standard_centrex_zone_id'])));
					$standard_centrex_calling_level = trim($this->escape_string($this->strip_all($data['standard_centrex_calling_level'])));

					$this->query("insert into ".PREFIX."caf_standard_centrex_details(caf_id, standard_centrex_group_type, standard_centrex_bgid, standard_centrex_idp_id, standard_centrex_dail_code, standard_centrex_switch_name, standard_centrex_zone, standard_centrex_zte_pnr, standard_centrex_switch_type, standard_centrex_switch_details, standard_centrex_zone_id, standard_centrex_calling_level) values('".$caf_id."', '".$standard_centrex_group_type."', '".$standard_centrex_bgid."', '".$standard_centrex_idp_id."', '".$standard_centrex_dail_code."', '".$standard_centrex_switch_name."', '".$standard_centrex_zone."', '".$standard_centrex_zte_pnr."', '".$standard_centrex_switch_type."', '".$standard_centrex_switch_details."', '".$standard_centrex_zone_id."', '".$standard_centrex_calling_level."')");
				}
			else if($product=='IP Centrex') {
					$ip_centrex_group_type = trim($this->escape_string($this->strip_all($data['ip_centrex_group_type'])));
					$ip_centrex_bgid = trim($this->escape_string($this->strip_all($data['ip_centrex_bgid'])));
					$ip_centrex_idp_id = trim($this->escape_string($this->strip_all($data['ip_centrex_idp_id'])));
					$ip_centrex_switch_name = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_name'])));
					$ip_centrex_zone_code = trim($this->escape_string($this->strip_all($data['ip_centrex_zone_code'])));
					$ip_centrex_dail_code = trim($this->escape_string($this->strip_all($data['ip_centrex_dail_code'])));
					$ip_centrex_zte_pnr = trim($this->escape_string($this->strip_all($data['ip_centrex_zte_pnr'])));
					$ip_centrex_switch_type = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_type'])));
					$ip_centrex_switch_details = trim($this->escape_string($this->strip_all($data['ip_centrex_switch_details'])));
					$ip_centrex_zone_id = trim($this->escape_string($this->strip_all($data['ip_centrex_zone_id'])));
					$ip_centrex_calling_level = trim($this->escape_string($this->strip_all($data['ip_centrex_calling_level'])));
					$ip_centrex_cug_type = trim($this->escape_string($this->strip_all($data['ip_centrex_cug_type'])));
					$ip_centrex_del_no = trim($this->escape_string($this->strip_all($data['ip_centrex_del_no'])));
					$ip_centrex_billing_cycle = trim($this->escape_string($this->strip_all($data['ip_centrex_billing_cycle'])));
					$ip_centrex_pm_email = trim($this->escape_string($this->strip_all($data['ip_centrex_pm_email'])));
					$ip_centrex_connection_type = trim($this->escape_string($this->strip_all($data['ip_centrex_connection_type'])));
					$ip_centrex_parent_account = trim($this->escape_string($this->strip_all($data['ip_centrex_parent_account'])));
					$ip_centrex_handset_id = trim($this->escape_string($this->strip_all($data['ip_centrex_handset_id'])));
					$ip_centrex_addon_account = trim($this->escape_string($this->strip_all($data['ip_centrex_addon_account'])));
					$ip_centrex_ip_address1 = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_address1'])));
					$ip_centrex_ip_address2 = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_address2'])));
					$ip_centrex_ip_mask = trim($this->escape_string($this->strip_all($data['ip_centrex_ip_mask'])));
					$ip_centrex_vlan_tag = trim($this->escape_string($this->strip_all($data['ip_centrex_vlan_tag'])));
					$ip_centrex_vlan_id = trim($this->escape_string($this->strip_all($data['ip_centrex_vlan_id'])));
					$ip_centrex_dealer_contact = trim($this->escape_string($this->strip_all($data['ip_centrex_dealer_contact'])));
					$ip_centrex_je_email = trim($this->escape_string($this->strip_all($data['ip_centrex_je_email'])));
					$ip_centrex_zte_ctxgrpnr = trim($this->escape_string($this->strip_all($data['ip_centrex_zte_ctxgrpnr'])));
					$ip_centrex_type = trim($this->escape_string($this->strip_all($data['ip_centrex_type'])));
					$ip_centrex_customer_type = trim($this->escape_string($this->strip_all($data['ip_centrex_customer_type'])));
					$ip_centrex_customer_owned_equipment = trim($this->escape_string($this->strip_all($data['ip_centrex_customer_owned_equipment'])));
					$ip_centrex_operator_type = trim($this->escape_string($this->strip_all($data['ip_centrex_operator_type'])));

					if(!empty($file['ip_centrex_ip_address_excel']['name'])) {
						/* $file_name = strtolower( pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_EXTENSION));
						$ip_centrex_ip_address_excel = time().'7.'.$ext;
						move_uploaded_file($file['ip_centrex_ip_address_excel']['tmp_name'],$uploadDir.$ip_centrex_ip_address_excel); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['ip_centrex_ip_address_excel']['name'], PATHINFO_EXTENSION));
						$ip_centrex_ip_address_excel = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$ip_centrex_ip_address_excel, fopen($file['ip_centrex_ip_address_excel']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_ip_centrex_details(caf_id, ip_centrex_group_type, ip_centrex_bgid, ip_centrex_idp_id, ip_centrex_switch_name, ip_centrex_zone_code, ip_centrex_dail_code, ip_centrex_zte_pnr, ip_centrex_switch_type, ip_centrex_switch_details, ip_centrex_zone_id, ip_centrex_calling_level, ip_centrex_cug_type, ip_centrex_del_no, ip_centrex_billing_cycle, ip_centrex_pm_email, ip_centrex_connection_type, ip_centrex_parent_account, ip_centrex_handset_id, ip_centrex_addon_account, ip_centrex_ip_address1, ip_centrex_ip_address2, ip_centrex_ip_address3, ip_centrex_ip_address4, ip_centrex_ip_address_excel, ip_centrex_ip_mask, ip_centrex_vlan_tag, ip_centrex_vlan_id, ip_centrex_dealer_contact, ip_centrex_je_email, ip_centrex_zte_ctxgrpnr, ip_centrex_type, ip_centrex_customer_type, ip_centrex_customer_owned_equipment, ip_centrex_operator_type) values('".$caf_id."', '".$ip_centrex_group_type."', '".$ip_centrex_bgid."', '".$ip_centrex_idp_id."', '".$ip_centrex_switch_name."', '".$ip_centrex_zone_code."', '".$ip_centrex_dail_code."', '".$ip_centrex_zte_pnr."', '".$ip_centrex_switch_type."', '".$ip_centrex_switch_details."', '".$ip_centrex_zone_id."', '".$ip_centrex_calling_level."', '".$ip_centrex_cug_type."', '".$ip_centrex_del_no."', '".$ip_centrex_billing_cycle."', '".$ip_centrex_pm_email."', '".$ip_centrex_connection_type."', '".$ip_centrex_parent_account."', '".$ip_centrex_handset_id."', '".$ip_centrex_addon_account."', '".$ip_centrex_ip_address1."', '".$ip_centrex_ip_address2."', '".$ip_centrex_ip_address3."', '".$ip_centrex_ip_address4."', '".$ip_centrex_ip_address_excel."', '".$ip_centrex_ip_mask."', '".$ip_centrex_vlan_tag."', '".$ip_centrex_vlan_id."', '".$ip_centrex_dealer_contact."', '".$ip_centrex_je_email."', '".$ip_centrex_zte_ctxgrpnr."', '".$ip_centrex_type."', '".$ip_centrex_customer_type."', '".$ip_centrex_customer_owned_equipment."', '".$ip_centrex_operator_type."')");
				}
			else if($product=='Standard Wireline') {
					$standard_wrln_existing_caf = trim($this->escape_string($this->strip_all($data['standard_wrln_existing_caf'])));
					$standard_wrln_proof_company_id = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_company_id'])));
					$standard_wrln_proof_authorization = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_authorization'])));
					$standard_wrln_proof_address = trim($this->escape_string($this->strip_all($data['standard_wrln_proof_address'])));
					$standard_wrln_other_docs = trim($this->escape_string($this->strip_all($data['standard_wrln_other_docs'])));
					$standard_wrln_govt_id_proof = trim($this->escape_string($this->strip_all($data['standard_wrln_govt_id_proof'])));
					$standard_wrln_cug_type = trim($this->escape_string($this->strip_all($data['standard_wrln_cug_type'])));
					$standard_wrln_cug_no = trim($this->escape_string($this->strip_all($data['standard_wrln_cug_no'])));
					$standard_wrln_billing_cycle = trim($this->escape_string($this->strip_all($data['standard_wrln_billing_cycle'])));
					$standard_wrln_pm_email = trim($this->escape_string($this->strip_all($data['standard_wrln_pm_email'])));
					$standard_wrln_connection_type = trim($this->escape_string($this->strip_all($data['standard_wrln_connection_type'])));
					$standard_wrln_parent_account = trim($this->escape_string($this->strip_all($data['standard_wrln_parent_account'])));
					$standard_wrln_trai_id = trim($this->escape_string($this->strip_all($data['standard_wrln_trai_id'])));
					$standard_wrln_handset_id = trim($this->escape_string($this->strip_all($data['standard_wrln_handset_id'])));
					$standard_wrln_addon_account = trim($this->escape_string($this->strip_all($data['standard_wrln_addon_account'])));
					$standard_wrln_service_type = trim($this->escape_string($this->strip_all($data['standard_wrln_service_type'])));
					$standard_wrln_del_no = trim($this->escape_string($this->strip_all($data['standard_wrln_del_no'])));
					$standard_wrln_operator_type = trim($this->escape_string($this->strip_all($data['standard_wrln_operator_type'])));
					$standard_wrln_operation_id = trim($this->escape_string($this->strip_all($data['standard_wrln_operation_id'])));
					$standard_wrln_customer_type = trim($this->escape_string($this->strip_all($data['standard_wrln_customer_type'])));
					$standard_wrln_ip_type = trim($this->escape_string($this->strip_all($data['standard_wrln_ip_type'])));
					$standard_wrln_dslam_id = trim($this->escape_string($this->strip_all($data['standard_wrln_dslam_id'])));
					$standard_wrln_static_ip_discount = trim($this->escape_string($this->strip_all($data['standard_wrln_static_ip_discount'])));
					$standard_wrln_calling_level = trim($this->escape_string($this->strip_all($data['standard_wrln_calling_level'])));

					if(!empty($file['standard_wrln_approval']['name'])) {
						/* $file_name = strtolower( pathinfo($file['standard_wrln_approval']['name'], PATHINFO_FILENAME));
						$ext = strtolower(pathinfo($file['standard_wrln_approval']['name'], PATHINFO_EXTENSION));
						$standard_wrln_approval = time().'7.'.$ext;
						move_uploaded_file($file['standard_wrln_approval']['tmp_name'],$uploadDir.$standard_wrln_approval); */
						
						$bucket = 'tata-ecaf';
						$mediaDir = 'caf-uploads/';	
						$s3 = S3Client::factory([
							'version' => 'latest',
							'region' => 'ap-south-1',
							'credentials' => [
								'key'    => "AKIAI75IRVIEIAYWEINA",
								'secret' => "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",
							],
						]); 
						$ext = strtolower(pathinfo($file['standard_wrln_approval']['name'], PATHINFO_EXTENSION));
						$standard_wrln_approval = time().'7.'.$ext;
						$upload = $s3->upload($bucket, $mediaDir.$standard_wrln_approval, fopen($file['standard_wrln_approval']['tmp_name'], 'rb'), 'public-read');
					}

					$this->query("insert into ".PREFIX."caf_standard_wrln_details(caf_id, standard_wrln_existing_caf, standard_wrln_proof_company_id, standard_wrln_proof_authorization, standard_wrln_proof_address, standard_wrln_other_docs, standard_wrln_govt_id_proof, standard_wrln_approval, standard_wrln_cug_type, standard_wrln_cug_no, standard_wrln_billing_cycle, standard_wrln_pm_email, standard_wrln_connection_type, standard_wrln_parent_account, standard_wrln_trai_id, standard_wrln_handset_id, standard_wrln_addon_account, standard_wrln_service_type, standard_wrln_del_no, standard_wrln_operator_type, standard_wrln_operation_id, standard_wrln_customer_type, standard_wrln_ip_type, standard_wrln_dslam_id, standard_wrln_static_ip_discount, standard_wrln_calling_level) values('".$caf_id."', '".$standard_wrln_existing_caf."', '".$standard_wrln_proof_company_id."', '".$standard_wrln_proof_authorization."', '".$standard_wrln_proof_address."', '".$standard_wrln_other_docs."', '".$standard_wrln_govt_id_proof."', '".$standard_wrln_approval."', '".$standard_wrln_cug_type."', '".$standard_wrln_cug_no."', '".$standard_wrln_billing_cycle."', '".$standard_wrln_pm_email."', '".$standard_wrln_connection_type."', '".$standard_wrln_parent_account."', '".$standard_wrln_trai_id."', '".$standard_wrln_handset_id."', '".$standard_wrln_addon_account."', '".$standard_wrln_service_type."', '".$standard_wrln_del_no."', '".$standard_wrln_operator_type."', '".$standard_wrln_operation_id."', '".$standard_wrln_customer_type."', '".$standard_wrln_ip_type."', '".$standard_wrln_dslam_id."', '".$standard_wrln_static_ip_discount."', '".$standard_wrln_calling_level."')");
				}
			else if($product=='WEPABX') {
					$wepbax_cug_type = trim($this->escape_string($this->strip_all($data['wepbax_cug_type'])));
					$wepbax_connection_type = trim($this->escape_string($this->strip_all($data['wepbax_connection_type'])));
					$wepbax_billing_cycle = trim($this->escape_string($this->strip_all($data['wepbax_billing_cycle'])));
					$wepbax_pm_email = trim($this->escape_string($this->strip_all($data['wepbax_pm_email'])));
					$wepbax_parent_account = trim($this->escape_string($this->strip_all($data['wepbax_parent_account'])));
					$wepbax_addon_account = trim($this->escape_string($this->strip_all($data['wepbax_addon_account'])));
					$wepbax_del_no = trim($this->escape_string($this->strip_all($data['wepbax_del_no'])));
					$wepbax_rid = trim($this->escape_string($this->strip_all($data['wepbax_rid'])));
					$wepbax_config = trim($this->escape_string($this->strip_all($data['wepbax_config'])));
					$wepbax_calling_level = trim($this->escape_string($this->strip_all($data['wepbax_calling_level'])));
					$wepbax_cug_no = trim($this->escape_string($this->strip_all($data['wepbax_cug_no'])));
					$wepbax_handset_id = trim($this->escape_string($this->strip_all($data['wepbax_handset_id'])));

					$this->query("insert into ".PREFIX."caf_wepbax_details(caf_id, wepbax_cug_type, wepbax_billing_cycle, wepbax_pm_email, wepbax_connection_type, wepbax_del_no, wepbax_rid, wepbax_parent_account, wepbax_addon_account, wepbax_config, wepbax_calling_level, wepbax_cug_no, wepbax_handset_id) values('".$caf_id."', '".$wepbax_cug_type."', '".$wepbax_billing_cycle."', '".$wepbax_pm_email."', '".$wepbax_connection_type."', '".$wepbax_del_no."', '".$wepbax_rid."', '".$wepbax_parent_account."', '".$wepbax_addon_account."', '".$wepbax_config."', '".$wepbax_calling_level."', '".$wepbax_cug_no."', '".$wepbax_handset_id."')");
				
			}
			else if($product=='IBS') {
				$ibs_existing_caf = trim($this->escape_string($this->strip_all($data['ibs_existing_caf'])));
				$ibs_proof_company_id = trim($this->escape_string($this->strip_all($data['ibs_proof_company_id'])));
				$ibs_proof_authorization = trim($this->escape_string($this->strip_all($data['ibs_proof_authorization'])));
				$ibs_proof_address = trim($this->escape_string($this->strip_all($data['ibs_proof_address'])));
				$ibs_other_docs = trim($this->escape_string($this->strip_all($data['ibs_other_docs'])));
				$ibs_govt_id_proof = trim($this->escape_string($this->strip_all($data['ibs_govt_id_proof'])));
				$ibs_connection_type = trim($this->escape_string($this->strip_all($data['ibs_connection_type'])));
				$ibs_billing_cycle = trim($this->escape_string($this->strip_all($data['ibs_billing_cycle'])));
				$ibs_pm_email = trim($this->escape_string($this->strip_all($data['ibs_pm_email'])));
				$ibs_del_no = trim($this->escape_string($this->strip_all($data['ibs_del_no'])));
				$ibs_reserved_id = trim($this->escape_string($this->strip_all($data['ibs_reserved_id'])));
				$ibs_addon_account = trim($this->escape_string($this->strip_all($data['ibs_addon_account'])));
				$ibs_parent_account = trim($this->escape_string($this->strip_all($data['ibs_parent_account'])));
				$ibs_type = trim($this->escape_string($this->strip_all($data['ibs_type'])));
				$ibs_provision_on = trim($this->escape_string($this->strip_all($data['ibs_provision_on'])));
				$ibs_present = trim($this->escape_string($this->strip_all($data['ibs_present'])));
				$ibs_mapped_on = trim($this->escape_string($this->strip_all($data['ibs_mapped_on'])));
				$ibs_calling_level = trim($this->escape_string($this->strip_all($data['ibs_calling_level'])));

				if(!empty($file['ibs_approval']['name'])) {
					/* $file_name = strtolower( pathinfo($file['ibs_approval']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['ibs_approval']['name'], PATHINFO_EXTENSION));
					$ibs_approval = time().'7.'.$ext;
					move_uploaded_file($file['ibs_approval']['tmp_name'],$uploadDir.$ibs_approval); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['ibs_approval']['name'], PATHINFO_EXTENSION));
					$ibs_approval = time().'7.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$ibs_approval, fopen($file['ibs_approval']['tmp_name'], 'rb'), 'public-read');
				}

				$this->query("insert into ".PREFIX."caf_ibs_details(caf_id, ibs_existing_caf, ibs_proof_company_id, ibs_proof_authorization, ibs_proof_address, ibs_other_docs, ibs_govt_id_proof, ibs_approval, ibs_connection_type, ibs_billing_cycle, ibs_pm_email, ibs_del_no, ibs_reserved_id, ibs_addon_account, ibs_parent_account, ibs_type, ibs_provision_on, ibs_present, ibs_mapped_on, ibs_calling_level) values('".$caf_id."', '".$ibs_existing_caf."', '".$ibs_proof_company_id."', '".$ibs_proof_authorization."', '".$ibs_proof_address."', '".$ibs_other_docs."', '".$ibs_govt_id_proof."', '".$ibs_approval."', '".$ibs_connection_type."', '".$ibs_billing_cycle."', '".$ibs_pm_email."', '".$ibs_del_no."', '".$ibs_reserved_id."', '".$ibs_addon_account."', '".$ibs_parent_account."', '".$ibs_type."', '".$ibs_provision_on."', '".$ibs_present."', '".$ibs_mapped_on."', '".$ibs_calling_level."')");
			}
			else if($product=='Walky') {
				$walky_sns = trim($this->escape_string($this->strip_all($data['walky_sns'])));
				$walky_type = trim($this->escape_string($this->strip_all($data['walky_type'])));
				$walky_cug_type = trim($this->escape_string($this->strip_all($data['walky_cug_type'])));
				$walky_cug_no = trim($this->escape_string($this->strip_all($data['walky_cug_no'])));
				$walky_billing_cycle = trim($this->escape_string($this->strip_all($data['walky_billing_cycle'])));
				$walky_pm_email = trim($this->escape_string($this->strip_all($data['walky_pm_email'])));
				$walky_connection_type = trim($this->escape_string($this->strip_all($data['walky_connection_type'])));
				$walky_del_no = trim($this->escape_string($this->strip_all($data['walky_del_no'])));
				$walky_rid = trim($this->escape_string($this->strip_all($data['walky_rid'])));
				$walky_parent_account = trim($this->escape_string($this->strip_all($data['walky_parent_account'])));
				$walky_handset_id = trim($this->escape_string($this->strip_all($data['walky_handset_id'])));
				$walky_addon_account = trim($this->escape_string($this->strip_all($data['walky_addon_account'])));
				$walky_trai_id = trim($this->escape_string($this->strip_all($data['walky_trai_id'])));
				$walky_calling_level = trim($this->escape_string($this->strip_all($data['walky_calling_level'])));

				$this->query("insert into ".PREFIX."caf_walky_details(caf_id, walky_sns, walky_type, walky_cug_type, walky_cug_no, walky_billing_cycle, walky_pm_email, walky_connection_type, walky_del_no, walky_rid, walky_parent_account, walky_handset_id, walky_addon_account, walky_trai_id, walky_calling_level) values('".$caf_id."', '".$walky_sns."', '".$walky_type."', '".$walky_cug_type."', '".$walky_cug_no."', '".$walky_billing_cycle."', '".$walky_pm_email."', '".$walky_connection_type."', '".$walky_del_no."', '".$walky_rid."', '".$walky_parent_account."', '".$walky_handset_id."', '".$walky_addon_account."', '".$walky_trai_id."', '".$walky_calling_level."')");
			}
			else if($product=='Mobile') {
				$mobile_sns = trim($this->escape_string($this->strip_all($data['mobile_sns'])));
				$mobile_cug_type = trim($this->escape_string($this->strip_all($data['mobile_cug_type'])));
				$mobile_cug_no = trim($this->escape_string($this->strip_all($data['mobile_cug_no'])));
				$mobile_billing_cycle = trim($this->escape_string($this->strip_all($data['mobile_billing_cycle'])));
				$mobile_pm_email = trim($this->escape_string($this->strip_all($data['mobile_pm_email'])));
				$mobile_connection_type = trim($this->escape_string($this->strip_all($data['mobile_connection_type'])));
				$mobile_del_no = trim($this->escape_string($this->strip_all($data['mobile_del_no'])));
				$mobile_reserved_id = trim($this->escape_string($this->strip_all($data['mobile_reserved_id'])));
				$mobile_parent_account = trim($this->escape_string($this->strip_all($data['mobile_parent_account'])));
				$mobile_handset_id = trim($this->escape_string($this->strip_all($data['mobile_handset_id'])));
				$mobile_addon_account = trim($this->escape_string($this->strip_all($data['mobile_addon_account'])));
				$mobile_addon_account2 = trim($this->escape_string($this->strip_all($data['mobile_addon_account2'])));
				$mobile_addon_account3 = trim($this->escape_string($this->strip_all($data['mobile_addon_account3'])));
				$mobile_spm = trim($this->escape_string($this->strip_all($data['mobile_spm'])));
				$mobile_spn_scheme = trim($this->escape_string($this->strip_all($data['mobile_spn_scheme'])));
				$mobile_calling_level = trim($this->escape_string($this->strip_all($data['mobile_calling_level'])));

				$this->query("insert into ".PREFIX."caf_mobile_details(caf_id, mobile_sns, mobile_cug_type, mobile_cug_no, mobile_billing_cycle, mobile_pm_email, mobile_connection_type, mobile_del_no, mobile_reserved_id, mobile_parent_account, mobile_handset_id, mobile_addon_account, mobile_addon_account2, mobile_addon_account3, mobile_spm, mobile_spn_scheme, mobile_calling_level) values('".$caf_id."', '".$mobile_sns."', '".$mobile_cug_type."', '".$mobile_cug_no."', '".$mobile_billing_cycle."', '".$mobile_pm_email."', '".$mobile_connection_type."', '".$mobile_del_no."', '".$mobile_reserved_id."', '".$mobile_parent_account."', '".$mobile_handset_id."', '".$mobile_addon_account."', '".$mobile_addon_account2."', '".$mobile_addon_account3."', '".$mobile_spm."', '".$mobile_spn_scheme."', '".$mobile_calling_level."')");
			}
			else if($product=='Fleet Management' || $product=='School Bus Tracking' || $product=='Asset Management' || $product=='Workforce Management' || $product=='LaaS') {
				$lbs_connection_type = trim($this->escape_string($this->strip_all($data['lbs_connection_type'])));
				$lbs_del_no = trim($this->escape_string($this->strip_all($data['lbs_del_no'])));
				$lbs_rid = trim($this->escape_string($this->strip_all($data['lbs_rid'])));
				$lbs_pm_email = trim($this->escape_string($this->strip_all($data['lbs_pm_email'])));
				$lbs_billing_cycle = trim($this->escape_string($this->strip_all($data['lbs_billing_cycle'])));
				$lbs_parent_account = trim($this->escape_string($this->strip_all($data['lbs_parent_account'])));
				$lbs_addon_account = trim($this->escape_string($this->strip_all($data['lbs_addon_account'])));
				$lbs_handset_id = trim($this->escape_string($this->strip_all($data['lbs_handset_id'])));
				$lbs_type = trim($this->escape_string($this->strip_all($data['lbs_type'])));
				$lbs_vehicle_no = trim($this->escape_string($this->strip_all($data['lbs_vehicle_no'])));
				$lbs_imei_no = trim($this->escape_string($this->strip_all($data['lbs_imei_no'])));
				$lbs_vendor_type = trim($this->escape_string($this->strip_all($data['lbs_vendor_type'])));
				$lbs_product_type = trim($this->escape_string($this->strip_all($data['lbs_product_type'])));

				$this->query("insert into ".PREFIX."caf_lbs_details(caf_id, lbs_connection_type, lbs_del_no, lbs_rid, lbs_pm_email, lbs_billing_cycle, lbs_parent_account, lbs_addon_account, lbs_handset_id, lbs_type, lbs_vehicle_no, lbs_imei_no, lbs_vendor_type, lbs_product_type) values('".$caf_id."', '".$lbs_connection_type."', '".$lbs_del_no."', '".$lbs_rid."', '".$lbs_pm_email."', '".$lbs_billing_cycle."', '".$lbs_parent_account."', '".$lbs_addon_account."', '".$lbs_handset_id."', '".$lbs_type."', '".$lbs_vehicle_no."', '".$lbs_imei_no."', '".$lbs_vendor_type."', '".$lbs_product_type."')");
			}
			else if($product=='M2M Sim') {
				if($variant=='M2M Standard') {
					if($subvariant=='Vehicle Tracking' || $subvariant=='Others') {
						$lbs_connection_type = trim($this->escape_string($this->strip_all($data['lbs_connection_type'])));
						$lbs_del_no = trim($this->escape_string($this->strip_all($data['lbs_del_no'])));
						$lbs_rid = trim($this->escape_string($this->strip_all($data['lbs_rid'])));
						$lbs_pm_email = trim($this->escape_string($this->strip_all($data['lbs_pm_email'])));
						$lbs_billing_cycle = trim($this->escape_string($this->strip_all($data['lbs_billing_cycle'])));
						$lbs_parent_account = trim($this->escape_string($this->strip_all($data['lbs_parent_account'])));
						$lbs_addon_account = trim($this->escape_string($this->strip_all($data['lbs_addon_account'])));
						$lbs_handset_id = trim($this->escape_string($this->strip_all($data['lbs_handset_id'])));
						$lbs_type = trim($this->escape_string($this->strip_all($data['lbs_type'])));
						$lbs_vehicle_no = trim($this->escape_string($this->strip_all($data['lbs_vehicle_no'])));
						$lbs_imei_no = trim($this->escape_string($this->strip_all($data['lbs_imei_no'])));
						$lbs_vendor_type = trim($this->escape_string($this->strip_all($data['lbs_vendor_type'])));
						$lbs_product_type = trim($this->escape_string($this->strip_all($data['lbs_product_type'])));

						$this->query("insert into ".PREFIX."caf_lbs_details(caf_id, lbs_connection_type, lbs_del_no, lbs_rid, lbs_pm_email, lbs_billing_cycle, lbs_parent_account, lbs_addon_account, lbs_handset_id, lbs_type, lbs_vehicle_no, lbs_imei_no, lbs_vendor_type, lbs_product_type) values('".$caf_id."', '".$lbs_connection_type."', '".$lbs_del_no."', '".$lbs_rid."', '".$lbs_pm_email."', '".$lbs_billing_cycle."', '".$lbs_parent_account."', '".$lbs_addon_account."', '".$lbs_handset_id."', '".$lbs_type."', '".$lbs_vehicle_no."', '".$lbs_imei_no."', '".$lbs_vendor_type."', '".$lbs_product_type."')");
					}
				}
			}
			else if($product=='Toll Free Services' || $product=='Call Register Services') {
				$crs_connection_type = trim($this->escape_string($this->strip_all($data['crs_connection_type'])));
				$crs_ibs = trim($this->escape_string($this->strip_all($data['crs_ibs'])));
				$crs_provision_on = trim($this->escape_string($this->strip_all($data['crs_provision_on'])));
				$crs_present = trim($this->escape_string($this->strip_all($data['crs_present'])));
				$crs_mapped_on = trim($this->escape_string($this->strip_all($data['crs_mapped_on'])));
				$crs_calling_level = trim($this->escape_string($this->strip_all($data['crs_calling_level'])));
				$crs_billing_cycle = trim($this->escape_string($this->strip_all($data['crs_billing_cycle'])));
				$crs_pm_email = trim($this->escape_string($this->strip_all($data['crs_pm_email'])));
				$crs_parent_account = trim($this->escape_string($this->strip_all($data['crs_parent_account'])));
				$crs_addon_account = trim($this->escape_string($this->strip_all($data['crs_addon_account'])));
				$crs_rid = trim($this->escape_string($this->strip_all($data['crs_rid'])));
				$crs_del_no = trim($this->escape_string($this->strip_all($data['crs_del_no'])));

				$this->query("insert into ".PREFIX."caf_crs_details(caf_id, crs_connection_type, crs_ibs, crs_provision_on, crs_present, crs_mapped_on, crs_calling_level, crs_billing_cycle, crs_pm_email, crs_parent_account, crs_addon_account, crs_rid, crs_del_no) values('".$caf_id."', '".$crs_connection_type."', '".$crs_ibs."', '".$crs_provision_on."', '".$crs_present."', '".$crs_mapped_on."', '".$crs_calling_level."', '".$crs_billing_cycle."', '".$crs_pm_email."', '".$crs_parent_account."', '".$crs_addon_account."', '".$crs_rid."', '".$crs_del_no."')");
			}
			else if($product=='HIVR' || $product=='Webconnect') {
				$toll_free_cug_type = trim($this->escape_string($this->strip_all($data['toll_free_cug_type'])));
				$toll_free_del_no = trim($this->escape_string($this->strip_all($data['toll_free_del_no'])));
				$toll_free_billing_cycle = trim($this->escape_string($this->strip_all($data['toll_free_billing_cycle'])));
				$toll_free_pm_email = trim($this->escape_string($this->strip_all($data['toll_free_pm_email'])));
				$toll_free_connection_type = trim($this->escape_string($this->strip_all($data['toll_free_connection_type'])));
				$toll_free_parent_account = trim($this->escape_string($this->strip_all($data['toll_free_parent_account'])));
				$toll_free_rid = trim($this->escape_string($this->strip_all($data['toll_free_rid'])));
				$toll_free_wepbax_config = trim($this->escape_string($this->strip_all($data['toll_free_wepbax_config'])));
				$toll_free_addon_account = trim($this->escape_string($this->strip_all($data['toll_free_addon_account'])));
				$toll_free_service_type_wireline = trim($this->escape_string($this->strip_all($data['toll_free_service_type_wireline'])));
				$toll_free_pilot_no = trim($this->escape_string($this->strip_all($data['toll_free_pilot_no'])));
				$toll_free_did_count = trim($this->escape_string($this->strip_all($data['toll_free_did_count'])));
				$toll_free_switch_name = trim($this->escape_string($this->strip_all($data['toll_free_switch_name'])));
				$toll_free_dial_code = trim($this->escape_string($this->strip_all($data['toll_free_dial_code'])));
				$toll_free_zone_id = trim($this->escape_string($this->strip_all($data['toll_free_zone_id'])));
				$toll_free_msgn_node = trim($this->escape_string($this->strip_all($data['toll_free_msgn_node'])));
				$toll_free_d_channel = trim($this->escape_string($this->strip_all($data['toll_free_d_channel'])));
				$toll_free_channel_count = trim($this->escape_string($this->strip_all($data['toll_free_channel_count'])));
				$toll_free_sponsered_pri = trim($this->escape_string($this->strip_all($data['toll_free_sponsered_pri'])));
				$toll_free_epabx_procured = trim($this->escape_string($this->strip_all($data['toll_free_epabx_procured'])));
				$toll_free_cost_epabx = trim($this->escape_string($this->strip_all($data['toll_free_cost_epabx'])));
				$toll_free_penalty_matrix = trim($this->escape_string($this->strip_all($data['toll_free_penalty_matrix'])));
				$toll_free_contract_period_pri = trim($this->escape_string($this->strip_all($data['toll_free_contract_period_pri'])));
				$toll_free_cost_pri_card = trim($this->escape_string($this->strip_all($data['toll_free_cost_pri_card'])));
				$toll_free_vendor_name = trim($this->escape_string($this->strip_all($data['toll_free_vendor_name'])));
				$toll_free_ebabx_make = trim($this->escape_string($this->strip_all($data['toll_free_ebabx_make'])));
				$toll_free_mis_entry = trim($this->escape_string($this->strip_all($data['toll_free_mis_entry'])));
				$toll_free_calling_level = trim($this->escape_string($this->strip_all($data['toll_free_calling_level'])));
				$toll_free_call_per_day = trim($this->escape_string($this->strip_all($data['toll_free_call_per_day'])));
				$toll_free_call_duration = trim($this->escape_string($this->strip_all($data['toll_free_call_duration'])));
				$toll_free_call_concurrency = trim($this->escape_string($this->strip_all($data['toll_free_call_concurrency'])));
				$toll_free_call_unit = trim($this->escape_string($this->strip_all($data['toll_free_call_unit'])));
				$toll_free_recording_required = trim($this->escape_string($this->strip_all($data['toll_free_recording_required'])));
				$toll_free_ct_required = trim($this->escape_string($this->strip_all($data['toll_free_ct_required'])));
				$toll_free_acd_required = trim($this->escape_string($this->strip_all($data['toll_free_acd_required'])));
				$toll_free_prompt_recording_required = trim($this->escape_string($this->strip_all($data['toll_free_prompt_recording_required'])));
				$toll_free_languages = trim($this->escape_string($this->strip_all($data['toll_free_languages'])));
				$toll_free_routing_required = trim($this->escape_string($this->strip_all($data['toll_free_routing_required'])));
				$toll_free_crm_integration_required = trim($this->escape_string($this->strip_all($data['toll_free_crm_integration_required'])));
				$toll_free_ivr_level = trim($this->escape_string($this->strip_all($data['toll_free_ivr_level'])));
				$toll_free_avg_hold_time_ivr = trim($this->escape_string($this->strip_all($data['toll_free_avg_hold_time_ivr'])));

				$this->query("insert into ".PREFIX."caf_toll_free_details(caf_id, toll_free_cug_type, toll_free_del_no, toll_free_billing_cycle, toll_free_pm_email, toll_free_connection_type, toll_free_parent_account, toll_free_rid, toll_free_wepbax_config, toll_free_addon_account, toll_free_service_type_wireline, toll_free_pilot_no, toll_free_did_count, toll_free_channel_count, toll_free_switch_name, toll_free_dial_code, toll_free_zone_id, toll_free_msgn_node, toll_free_d_channel, toll_free_sponsered_pri, toll_free_epabx_procured, toll_free_cost_epabx, toll_free_penalty_matrix, toll_free_contract_period_pri, toll_free_cost_pri_card, toll_free_vendor_name, toll_free_ebabx_make, toll_free_mis_entry, toll_free_calling_level, toll_free_call_per_day, toll_free_call_duration, toll_free_call_concurrency, toll_free_call_unit, toll_free_recording_required, toll_free_ct_required, toll_free_acd_required, toll_free_prompt_recording_required, toll_free_languages, toll_free_routing_required, toll_free_crm_integration_required, toll_free_ivr_level, toll_free_avg_hold_time_ivr) values('".$caf_id."', '".$toll_free_cug_type."', '".$toll_free_del_no."', '".$toll_free_billing_cycle."', '".$toll_free_pm_email."', '".$toll_free_connection_type."', '".$toll_free_parent_account."', '".$toll_free_rid."', '".$toll_free_wepbax_config."', '".$toll_free_addon_account."', '".$toll_free_service_type_wireline."', '".$toll_free_pilot_no."', '".$toll_free_did_count."', '".$toll_free_channel_count."', '".$toll_free_switch_name."', '".$toll_free_dial_code."', '".$toll_free_zone_id."', '".$toll_free_msgn_node."', '".$toll_free_d_channel."', '".$toll_free_sponsered_pri."', '".$toll_free_epabx_procured."', '".$toll_free_cost_epabx."', '".$toll_free_penalty_matrix."', '".$toll_free_contract_period_pri."', '".$toll_free_cost_pri_card."', '".$toll_free_vendor_name."', '".$toll_free_ebabx_make."', '".$toll_free_mis_entry."', '".$toll_free_calling_level."', '".$toll_free_call_per_day."', '".$toll_free_call_duration."', '".$toll_free_call_concurrency."', '".$toll_free_call_unit."', '".$toll_free_recording_required."', '".$toll_free_ct_required."', '".$toll_free_acd_required."', '".$toll_free_prompt_recording_required."', '".$toll_free_languages."', '".$toll_free_routing_required."', '".$toll_free_crm_integration_required."', '".$toll_free_ivr_level."', '".$toll_free_avg_hold_time_ivr."')");
			}
			else if($product=='SMS Solutions') {
				$sms_connection_type = trim($this->escape_string($this->strip_all($data['sms_connection_type'])));
				$sms_del_no = trim($this->escape_string($this->strip_all($data['sms_del_no'])));
				$sms_reserved_id = trim($this->escape_string($this->strip_all($data['sms_reserved_id'])));
				$sms_pm_email = trim($this->escape_string($this->strip_all($data['sms_pm_email'])));
				$sms_billing_cycle = trim($this->escape_string($this->strip_all($data['sms_billing_cycle'])));
				$sms_parent_account = trim($this->escape_string($this->strip_all($data['sms_parent_account'])));
				$sms_addon_account = trim($this->escape_string($this->strip_all($data['sms_addon_account'])));
				$sms_handset_id = trim($this->escape_string($this->strip_all($data['sms_handset_id'])));
				$sms_type = trim($this->escape_string($this->strip_all($data['sms_type'])));
				$sms_te_type = trim($this->escape_string($this->strip_all($data['sms_te_type'])));
				$sms_trai_id = trim($this->escape_string($this->strip_all($data['sms_trai_id'])));
				$sms_transactional_sender_id = trim($this->escape_string($this->strip_all($data['sms_transactional_sender_id'])));
				$sms_promotional_sender_id = trim($this->escape_string($this->strip_all($data['sms_promotional_sender_id'])));
				$sms_ip_address1 = trim($this->escape_string($this->strip_all($data['sms_ip_address1'])));
				$sms_ip_address2 = trim($this->escape_string($this->strip_all($data['sms_ip_address2'])));
				$sms_pull_url = trim($this->escape_string($this->strip_all($data['sms_pull_url'])));
				$sms_push_type = trim($this->escape_string($this->strip_all($data['sms_push_type'])));
				$sms_customer_server_location = trim($this->escape_string($this->strip_all($data['sms_customer_server_location'])));
				$sms_additional_maas_details = trim($this->escape_string($this->strip_all($data['sms_additional_maas_details'])));
				$sms_connectivity = trim($this->escape_string($this->strip_all($data['sms_connectivity'])));
				$sms_web_based_gui = trim($this->escape_string($this->strip_all($data['sms_web_based_gui'])));
				$sms_api_integration = trim($this->escape_string($this->strip_all($data['sms_api_integration'])));
				$sms_standard_reports = trim($this->escape_string($this->strip_all($data['sms_standard_reports'])));
				$sms_customization = trim($this->escape_string($this->strip_all($data['sms_customization'])));
				$sms_calling_level = trim($this->escape_string($this->strip_all($data['sms_calling_level'])));

				if(!empty($file['sms_ip_upload']['name'])) {
					/* $file_name = strtolower( pathinfo($file['sms_ip_upload']['name'], PATHINFO_FILENAME));
					$ext = strtolower(pathinfo($file['sms_ip_upload']['name'], PATHINFO_EXTENSION));
					$sms_ip_upload = time().'7.'.$ext;
					move_uploaded_file($file['sms_ip_upload']['tmp_name'],$uploadDir.$sms_ip_upload); */
					
					$bucket = 'tata-ecaf';
					$mediaDir = 'caf-uploads/';	
					$s3 = S3Client::factory([
						'version' => 'latest',
						'region' => 'ap-south-1',
						'credentials' => [
							'key'    => "AKIAI75IRVIEIAYWEINA",
							'secret' => "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",
						],
					]); 
					$ext = strtolower(pathinfo($file['sms_ip_upload']['name'], PATHINFO_EXTENSION));
					$sms_ip_upload = time().'7.'.$ext;
					$upload = $s3->upload($bucket, $mediaDir.$sms_ip_upload, fopen($file['sms_ip_upload']['tmp_name'], 'rb'), 'public-read');
				}
				$this->query("insert into ".PREFIX."caf_sms_details(caf_id, sms_connection_type, sms_del_no, sms_reserved_id, sms_pm_email, sms_billing_cycle, sms_parent_account, sms_addon_account, sms_trai_id, sms_type, sms_te_type, sms_transactional_sender_id, sms_promotional_sender_id, sms_ip_address1, sms_ip_address2, sms_ip_upload, sms_pull_url, sms_push_type, sms_customer_server_location, sms_additional_maas_details, sms_connectivity, sms_web_based_gui, sms_api_integration, sms_standard_reports, sms_customization, sms_calling_level) values('".$caf_id."', '".$sms_connection_type."', '".$sms_del_no."', '".$sms_reserved_id."', '".$sms_pm_email."', '".$sms_billing_cycle."', '".$sms_parent_account."', '".$sms_addon_account."', '".$sms_trai_id."', '".$sms_type."', '".$sms_te_type."', '".$sms_transactional_sender_id."', '".$sms_promotional_sender_id."', '".$sms_ip_address1."', '".$sms_ip_address2."', '".$sms_ip_upload."', '".$sms_pull_url."', '".$sms_push_type."', '".$sms_customer_server_location."', '".$sms_additional_maas_details."', '".$sms_connectivity."', '".$sms_web_based_gui."', '".$sms_api_integration."', '".$sms_standard_reports."', '".$sms_customization."', '".$sms_calling_level."')");
			}
			else if($product=='SNS Solution') {
				$sns_present = trim($this->escape_string($this->strip_all($data['sns_present'])));
				$sns_type = trim($this->escape_string($this->strip_all($data['sns_type'])));
				$sns_calling_level = trim($this->escape_string($this->strip_all($data['sns_calling_level'])));
				$sns_switch_name = trim($this->escape_string($this->strip_all($data['sns_switch_name'])));
				$sns_dial_code = trim($this->escape_string($this->strip_all($data['sns_dial_code'])));
				$sns_zone = trim($this->escape_string($this->strip_all($data['sns_zone'])));
				$sns_zte_pnr = trim($this->escape_string($this->strip_all($data['sns_zte_pnr'])));
				$sns_msgn_node = trim($this->escape_string($this->strip_all($data['sns_msgn_node'])));

				$this->query("insert into ".PREFIX."caf_sns_details(caf_id, sns_present, sns_type, sns_calling_level, sns_switch_name, sns_dial_code, sns_zone, sns_zte_pnr, sns_msgn_node) values('".$caf_id."', '".$sns_present."', '".$sns_type."', '".$sns_calling_level."', '".$sns_switch_name."', '".$sns_dial_code."', '".$sns_zone."', '".$sns_zte_pnr."', '".$sns_msgn_node."')");
			}
			else if($product=='Tele Marketing- 140') {
				if($variant=='SIP') {
					$sip_cug_type = trim($this->escape_string($this->strip_all($data['sip_cug_type'])));
					$sip_del_no = trim($this->escape_string($this->strip_all($data['sip_del_no'])));
					$sip_billing_cycle = trim($this->escape_string($this->strip_all($data['sip_billing_cycle'])));
					$sip_pm_email = trim($this->escape_string($this->strip_all($data['sip_pm_email'])));
					$sip_connection_type = trim($this->escape_string($this->strip_all($data['sip_connection_type'])));
					$sip_parent_account = trim($this->escape_string($this->strip_all($data['sip_parent_account'])));
					$sip_rid = trim($this->escape_string($this->strip_all($data['sip_rid'])));
					$sip_wepbax_config = trim($this->escape_string($this->strip_all($data['sip_wepbax_config'])));
					$sip_addon_account = trim($this->escape_string($this->strip_all($data['sip_addon_account'])));
					$sip_service_type_wireline = trim($this->escape_string($this->strip_all($data['sip_service_type_wireline'])));
					$sip_pilot_no = trim($this->escape_string($this->strip_all($data['sip_pilot_no'])));
					$sip_did_count = trim($this->escape_string($this->strip_all($data['sip_did_count'])));
					$sip_switch_name = trim($this->escape_string($this->strip_all($data['sip_switch_name'])));
					$sip_dial_code = trim($this->escape_string($this->strip_all($data['sip_dial_code'])));
					$sip_zone_id = trim($this->escape_string($this->strip_all($data['sip_zone_id'])));
					$sip_msgn_node = trim($this->escape_string($this->strip_all($data['sip_msgn_node'])));
					$sip_d_channel = trim($this->escape_string($this->strip_all($data['sip_d_channel'])));
					$sip_channel_count = trim($this->escape_string($this->strip_all($data['sip_channel_count'])));
					$sip_sponsered_pri = trim($this->escape_string($this->strip_all($data['sip_sponsered_pri'])));
					$sip_epabx_procured = trim($this->escape_string($this->strip_all($data['sip_epabx_procured'])));
					$sip_cost_epabx = trim($this->escape_string($this->strip_all($data['sip_cost_epabx'])));
					$sip_penalty_matrix = trim($this->escape_string($this->strip_all($data['sip_penalty_matrix'])));
					$sip_contract_period_pri = trim($this->escape_string($this->strip_all($data['sip_contract_period_pri'])));
					$sip_cost_pri_card = trim($this->escape_string($this->strip_all($data['sip_cost_pri_card'])));
					$sip_vendor_name = trim($this->escape_string($this->strip_all($data['sip_vendor_name'])));
					$sip_ebabx_make = trim($this->escape_string($this->strip_all($data['sip_ebabx_make'])));
					$sip_mis_entry = trim($this->escape_string($this->strip_all($data['sip_mis_entry'])));
					$sip_calling_level = trim($this->escape_string($this->strip_all($data['sip_calling_level'])));
					$sip_hosted_ivr = trim($this->escape_string($this->strip_all($data['sip_hosted_ivr'])));
					$sip_hivr_no = trim($this->escape_string($this->strip_all($data['sip_hivr_no'])));
					$sip_type = trim($this->escape_string($this->strip_all($data['sip_type'])));

					$this->query("insert into ".PREFIX."caf_sip_details(caf_id, sip_cug_type, sip_del_no, sip_billing_cycle, sip_pm_email, sip_connection_type, sip_parent_account, sip_rid, sip_wepbax_config, sip_addon_account, sip_service_type_wireline, sip_pilot_no, sip_did_count, sip_channel_count, sip_switch_name, sip_dial_code, sip_zone_id, sip_msgn_node, sip_d_channel, sip_sponsered_pri, sip_epabx_procured, sip_cost_epabx, sip_penalty_matrix, sip_contract_period_pri, sip_cost_pri_card, sip_vendor_name, sip_ebabx_make, sip_mis_entry, sip_calling_level, sip_hosted_ivr, sip_hivr_no, sip_type) values('".$caf_id."', '".$sip_cug_type."', '".$sip_del_no."', '".$sip_billing_cycle."', '".$sip_pm_email."', '".$sip_connection_type."', '".$sip_parent_account."', '".$sip_rid."', '".$sip_wepbax_config."', '".$sip_addon_account."', '".$sip_service_type_wireline."', '".$sip_pilot_no."', '".$sip_did_count."', '".$sip_channel_count."', '".$sip_switch_name."', '".$sip_dial_code."', '".$sip_zone_id."', '".$sip_msgn_node."', '".$sip_d_channel."', '".$sip_sponsered_pri."', '".$sip_epabx_procured."', '".$sip_cost_epabx."', '".$sip_penalty_matrix."', '".$sip_contract_period_pri."', '".$sip_cost_pri_card."', '".$sip_vendor_name."', '".$sip_ebabx_make."', '".$sip_mis_entry."', '".$sip_calling_level."', '".$sip_hosted_ivr."', '".$sip_hivr_no."', '".$sip_type."')");
				}
				else if($variant=='PRI') {
					$pri_cug_type = trim($this->escape_string($this->strip_all($data['pri_cug_type'])));
					$pri_connection_type = trim($this->escape_string($this->strip_all($data['pri_connection_type'])));
					$pri_billing_cycle = trim($this->escape_string($this->strip_all($data['pri_billing_cycle'])));
					$pri_pm_email = trim($this->escape_string($this->strip_all($data['pri_pm_email'])));
					$pri_parent_account = trim($this->escape_string($this->strip_all($data['pri_parent_account'])));
					$pri_addon_account = trim($this->escape_string($this->strip_all($data['pri_addon_account'])));
					$pri_rid = trim($this->escape_string($this->strip_all($data['pri_rid'])));
					$pri_del_no = trim($this->escape_string($this->strip_all($data['pri_del_no'])));
					$pri_wepbax_config = trim($this->escape_string($this->strip_all($data['pri_wepbax_config'])));
					$pri_service_type_wireline = trim($this->escape_string($this->strip_all($data['pri_service_type_wireline'])));
					$pri_pilot_no = trim($this->escape_string($this->strip_all($data['pri_pilot_no'])));
					$pri_did_count = trim($this->escape_string($this->strip_all($data['pri_did_count'])));
					$pri_channel_count = trim($this->escape_string($this->strip_all($data['pri_channel_count'])));
					$pri_switch_name = trim($this->escape_string($this->strip_all($data['pri_switch_name'])));
					$pri_dial_code = trim($this->escape_string($this->strip_all($data['pri_dial_code'])));
					$pri_zone_id = trim($this->escape_string($this->strip_all($data['pri_zone_id'])));
					$pri_msgn_node = trim($this->escape_string($this->strip_all($data['pri_msgn_node'])));
					$pri_d_channel = trim($this->escape_string($this->strip_all($data['pri_d_channel'])));
					$pri_sponsered = trim($this->escape_string($this->strip_all($data['pri_sponsered'])));
					$pri_epabx_procured = trim($this->escape_string($this->strip_all($data['pri_epabx_procured'])));
					$pri_cost_epabx = trim($this->escape_string($this->strip_all($data['pri_cost_epabx'])));
					$pri_penalty_matrix = trim($this->escape_string($this->strip_all($data['pri_penalty_matrix'])));
					$pri_contract_period = trim($this->escape_string($this->strip_all($data['pri_contract_period'])));
					$pri_cost_pri_card = trim($this->escape_string($this->strip_all($data['pri_cost_pri_card'])));
					$pri_vendor_name = trim($this->escape_string($this->strip_all($data['pri_vendor_name'])));
					$pri_ebabx_make = trim($this->escape_string($this->strip_all($data['pri_ebabx_make'])));
					$pri_mis_entry = trim($this->escape_string($this->strip_all($data['pri_mis_entry'])));
					$pri_calling_level = trim($this->escape_string($this->strip_all($data['pri_calling_level'])));
					$pri_hosted_ivr = trim($this->escape_string($this->strip_all($data['pri_hosted_ivr'])));
					$pri_hivr_no = trim($this->escape_string($this->strip_all($data['pri_hivr_no'])));
					$pri_type = trim($this->escape_string($this->strip_all($data['pri_type'])));

					$this->query("insert into ".PREFIX."caf_pri_details(caf_id, pri_cug_type, pri_del_no, pri_billing_cycle, pri_pm_email, pri_connection_type, pri_parent_account, pri_wepbax_config, pri_rid, pri_addon_account, pri_service_type_wireline, pri_pilot_no, pri_did_count, pri_switch_name, pri_dial_code, pri_zone_id, pri_msgn_node, pri_d_channel, pri_channel_count, pri_sponsered, pri_epabx_procured, pri_cost_epabx, pri_penalty_matrix, pri_contract_period, pri_cost_pri_card, pri_vendor_name, pri_ebabx_make, pri_mis_entry, pri_calling_level, pri_hosted_ivr, pri_hivr_no, pri_type) values('".$caf_id."', '".$pri_cug_type."', '".$pri_del_no."', '".$pri_billing_cycle."', '".$pri_pm_email."', '".$pri_connection_type."', '".$pri_parent_account."', '".$pri_wepbax_config."', '".$pri_rid."', '".$pri_addon_account."', '".$pri_service_type_wireline."', '".$pri_pilot_no."', '".$pri_did_count."', '".$pri_switch_name."', '".$pri_dial_code."', '".$pri_zone_id."', '".$pri_msgn_node."', '".$pri_d_channel."', '".$pri_channel_count."', '".$pri_sponsered."', '".$pri_epabx_procured."', '".$pri_cost_epabx."', '".$pri_penalty_matrix."', '".$pri_contract_period."', '".$pri_cost_pri_card."', '".$pri_vendor_name."', '".$pri_ebabx_make."', '".$pri_mis_entry."', '".$pri_calling_level."', '".$pri_hosted_ivr."', '".$pri_hivr_no."', '".$pri_type."')");
				}
			}
			else if($product=='Hosted OBD') {
				$hosted_obd_ivr = trim($this->escape_string($this->strip_all($data['hosted_obd_ivr'])));
				$hosted_obd_hivr_no = trim($this->escape_string($this->strip_all($data['hosted_obd_hivr_no'])));
				$hosted_obd_switch_type = trim($this->escape_string($this->strip_all($data['hosted_obd_switch_type'])));
				$hosted_obd_switch_details = trim($this->escape_string($this->strip_all($data['hosted_obd_switch_details'])));
				$hosted_obd_zone_id = trim($this->escape_string($this->strip_all($data['hosted_obd_zone_id'])));
				$hosted_obd_type = trim($this->escape_string($this->strip_all($data['hosted_obd_type'])));
				$hosted_obd_billing_cycle = trim($this->escape_string($this->strip_all($data['hosted_obd_billing_cycle'])));
				$hosted_obd_pm_email = trim($this->escape_string($this->strip_all($data['hosted_obd_pm_email'])));
				$hosted_obd_calling_level = trim($this->escape_string($this->strip_all($data['hosted_obd_calling_level'])));
				$hosted_obd_connection_type = trim($this->escape_string($this->strip_all($data['hosted_obd_connection_type'])));
				$hosted_obd_del_no = trim($this->escape_string($this->strip_all($data['hosted_obd_del_no'])));
				$hosted_obd_reserved_id = trim($this->escape_string($this->strip_all($data['hosted_obd_reserved_id'])));

				$this->query("insert into ".PREFIX."caf_hosted_obd_details(caf_id, hosted_obd_ivr, hosted_obd_hivr_no, hosted_obd_switch_type, hosted_obd_switch_details, hosted_obd_zone_id, hosted_obd_type, hosted_obd_billing_cycle, hosted_obd_pm_email, hosted_obd_connection_type, hosted_obd_del_no, hosted_obd_reserved_id, hosted_obd_parent_account, hosted_obd_addon_account, hosted_obd_calling_level) values('".$caf_id."', '".$hosted_obd_ivr."', '".$hosted_obd_hivr_no."', '".$hosted_obd_switch_type."', '".$hosted_obd_switch_details."', '".$hosted_obd_zone_id."', '".$hosted_obd_type."', '".$hosted_obd_billing_cycle."', '".$hosted_obd_pm_email."', '".$hosted_obd_connection_type."', '".$hosted_obd_del_no."', '".$hosted_obd_reserved_id."', '".$hosted_obd_parent_account."', '".$hosted_obd_addon_account."', '".$hosted_obd_calling_level."')");
			}
			else if($product=='Conferencing') {
				if($variant=='Audio Conferencing' || $variant=='Web Conferencing') {
					$audio_conf_pgi_landline_no = trim($this->escape_string($this->strip_all($data['audio_conf_pgi_landline_no'])));
					$audio_conf_del_number = trim($this->escape_string($this->strip_all($data['audio_conf_del_number'])));
					$this->query("insert into ".PREFIX."caf_audio_conf_details(caf_id, audio_conf_pgi_landline_no,audio_conf_del_no) values('".$caf_id."', '".$audio_conf_pgi_landline_no."', '".$audio_conf_del_number."')");
				}
			}
			// Service Enrollment
			
			
			
			//second time submission-mail-sales
			if($caf_status == "Pending with Customer")
			{
				include_once("caf-order-verification-sales.inc.php");
				
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true;
				$mail->AddAddress($email);
				//$mail->AddCC($loggedInUserDetailsArr['username']);
				$mail->IsHTML(true);
				$mail->Subject = "Company Name: ".$company_name." and CAF no ".$caf_no." received and has been forwarded for verification ";
				$mail->Body = $emailMsg;
				$mail->Send();
				//$mail->SmtpClose();
				$msg = "Your CAF number is $caf_no and it is currently under verification. For any queries contact 18002661800 with your CAF reference number.";
				
				$this->callsms($mobile,$msg,'Dcaf_click2buy');
			}
			if(empty($caf_status) && !empty($empty_fields)){
				
				include_once("caf-order-incomplete.inc.php");
				//echo $emailMsg;exit;
				$mail = new PHPMailer();
				$mail->IsSMTP();
				$mail->SMTPAuth = true;
				$mail->AddAddress($email);
				//$mail->AddCC($loggedInUserDetailsArr['username']);
				$mail->IsHTML(true);
				$mail->Subject = "Incomplete CAF/Transaction Company Name:".$company_name." ";
				$mail->Body = $emailMsg;
				$mail->Send();
				$msg = "Thank you for choosing Tata Tele Business Services. You have submitted an incomplete form, our representative will connect with you shortly to help you.";
				$this->callsms($mobile,$msg,'Dcaf_click2buy');
			}
			
		}
		function callsms($to,$msg,$msg_for)
        {
			$keyword = 'TATele';
            $sms = new Sms('A6a6864d9587dbc08228738b5aee4708e', $keyword, 'http://smsalerts.cheetahmailmobile.com/api/v4/?');			
            //$dlr_url = 'http://192.168.1.113/staging/php/tata-kyc/trigger.php?sent={sent}&delivered={delivered}&msgid={msgid}&sid={sid}&status={status}&reference={reference}&custom1={custom1}&custom2={custom2}';
            $obj = $sms->sendSms($to,$msg); 
			$query = "insert into ".PREFIX."smslog(smslog_mobile, smslog_message, smslog_is_success, smslog_output, smslog_datetime, smslog_keyword, smslog_msg_for) values ('".$to."', '".$msg."', '1', '".$obj."', '".date("Y-m-d H:i:s")."', '".$keyword."', '".$msg_for."')";
			$this->query($query);
			return $query;
        }
		
		function getLockinByProduct($product) {
			$product = trim($this->escape_string($this->strip_all($product)));
			$query = "select DISTINCT(lockin_period) from ".PREFIX."lockin_peroid_master where product='".$product."' order by lockin_period ";
			return $this->query($query);
		}	
			
	}
	error_reporting(0);
?>