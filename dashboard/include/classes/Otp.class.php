<?php
	/*
	 * v1 - custom OTP class for na-dashboard
	 * v2.1 - basic OTP class
	 * v2.1.1 - bugfixes
	 *
	 * TEMPLATE CODE TO IMPLEMENT CLASS
		include_once("Otp.class.php");
		$otpObj = new OTP();
		$otpObj->setSMSAPIUserName(SMS_API_USERNAME);
		$otpObj->setSMSAPIPassword(SMS_API_PASSWORD);
		$otpObj->setFromAddress(SMS_API_SENDER_ID);

		$newOTP = $otpObj->generateOTP(4);
		$otpObj->setAddress($contact);
		$msg = "OTP: ".$newOTP."\nPlease enter the OTP to verify your ".SITE_NAME." account";
		$otpObj->setOTPMessage($msg);
		$otpObj->sendOTP();
	 *
	 */
	class OTP{
		private $to;
		private $from = '';
		private $admin = '';
		private $userName = "";
		private $password = "";
		private $msg = "";

		function setSMSAPIUserName($userName){
			$this->userName = $userName;
		}
		function setSMSAPIPassword($password){
			$this->password = $password;
		}
		function setAddress($to){
			$this->to = $to;
		}
		function setAdminAddress($to){
			$this->admin = $to;
		}
		function setFromAddress($from){
			$this->from = $from;
		}
		function setOTPMessage($msg){
			$this->msg = $msg;
		}
		function sendOTP(){
			if(PROJECTSTATUS!="LIVE" && PROJECTSTATUS!="STAGING"){ // DO NOT WASTE SMS
				return true;
			}

			// == VALIDATION ==
			if(!isset($this->userName) || empty($this->userName)){
				return false;
			}
			if(!isset($this->password) || empty($this->password)){
				return false;
			}
			if(!isset($this->to) || empty($this->to)){
				return false;
			}
			if(!isset($this->msg) || empty($this->msg)){
				return false;
			}
			if(!isset($this->from) || empty($this->from)){
				return false;
			}
			// == VALIDATION ==

			$encodedMessage = urlencode($this->msg);
			$url = 'http://174.143.34.193/MtSendSMS/SingleSMS.aspx?usr='.$this->userName.'&pass='.$this->password.'&msisdn='.$this->to.'&msg='.$encodedMessage.'&sid='.$this->from.'&mt=0'; //Sending Message to single user

			$responseStr = file_get_contents($url);

			// == TEST ==
				// echo "<pre>";
				// print_r($responseStr);
				// echo "</pre>";
				// 919987053623-201611116323681
				// 919594226020-201611116592502
			// == TEST ==
			$pos = strpos($responseStr, "-");
// TEST 
// var_dump($pos);
// echo strlen(substr($responseStr, $pos+1, strlen($responseStr)));
// exit;
// TEST
			if($pos!=false && $pos>=0){
				if(strlen(substr($responseStr, $pos+1, strlen($responseStr)))==15){ // success - found 201611116323681
					// send a copy to ADMIN
					if(isset($this->admin) && !empty($this->admin)){
						if(PROJECTSTATUS!="LIVE" && PROJECTSTATUS!="STAGING"){ // DO NOT WASTE SMS
							// $responseStr = file_get_contents($this->msg);
						}
					}
					return true;
				} else {
					return false;
				}
			}
		}
		function generateOTP($length){
			$id = substr(str_shuffle("12345678901234567890"), 0, $length);
			return $id;
		}
	}
	
?>