<?php
	include('database.php');
	include('SaveImage.class.php');
	include("smtp/class.phpmailer.php");
	error_reporting(0);
	session_start();
	/*
	 * AdminFunctions
	 * v1 - updated loginSession(), logoutSession(), adminLogin()
	 */
	class Functions extends Database {
		private $userType = 'admin';

		// === LOGIN BEGINS ===
		function loginSession($userId, $userFirstName, $userLastName, $userType,$role) {
			/* DEPRECATED $_SESSION[SITE_NAME] = array(
				$this->userType."UserId" => $userId,
				$this->userType."UserFirstName" => $userFirstName,
				$this->userType."UserLastName" => $userLastName,
				$this->userType."UserType" => $this->userType
			); DEPRECATED */
			$_SESSION[SITE_NAME][$this->userType."UserId"] = $userId;
			$_SESSION[SITE_NAME][$this->userType."UserFirstName"] = $userFirstName;
			$_SESSION[SITE_NAME][$this->userType."UserLastName"] = $userLastName;
			$_SESSION[SITE_NAME][$this->userType."UserType"] = $this->userType;
			$_SESSION[SITE_NAME][$this->userType."role"] = $role;
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
				if(isset($_SESSION[SITE_NAME][$this->userType."UserFirstName"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserFirstName"]);
				}
				if(isset($_SESSION[SITE_NAME][$this->userType."UserLastName"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserLastName"]);
				}
				if(isset($_SESSION[SITE_NAME][$this->userType."UserType"])){
					unset($_SESSION[SITE_NAME][$this->userType."UserType"]);
				}
				return true;
			} else {
				return false;
			}
		}
		function adminLogin($data, $successURL, $failURL = "admin-login.php?failed") {
			$username = $this->escape_string($this->strip_all($data['username']));
			$password = $this->escape_string($this->strip_all($data['password']));
			$query = "select * from ".PREFIX."admin where username='".$username."'";
			$result = $this->query($query);

			if($this->num_rows($result) == 1) { // only one unique user should be present in the system
				$row = $this->fetch($result);
				if(password_verify($password, $row['password'])) {
					$this->loginSession($row['id'], $row['fname'], $row['lname'], $this->userType,$row['role']);
					$this->close_connection();
					header("location: ".$successURL);
					exit;
				} else {
					$this->close_connection();
					header("location: ".$failURL);
					exit;
				}
			} else {
				$this->close_connection();
				header("location: ".$failURL);
				exit;
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
		// === LOGIN ENDS ====

		// == EXTRA FUNCTIONS STARTS ==
		
		function addPersonalDetails1($data){
			$first_name = $this->escape_string($this->strip_all($data['first_name']));
			$last_name = $this->escape_string($this->strip_all($data['last_name']));
			$mobile = $this->escape_string($this->strip_all($data['mobile']));
			$email = $this->escape_string($this->strip_all($data['email']));
			$address = $this->escape_string($this->strip_all($data['address']));
			$landmark = $this->escape_string($this->strip_all($data['landmark']));
			$zipcode = $this->escape_string($this->strip_all($data['zipcode']));
			$booking_date = $this->escape_string($this->strip_all($data['date']));

			$booking_time = $this->escape_string($this->strip_all($data['time']));
			$_SESSION['booking_time'] = $booking_time;

			if(!empty($data['no_vehicles'])){ 
				$no_vehicles = $this->escape_string($this->strip_all($data['no_vehicles']));
			}else{
				$no_vehicles = '';
			}
			if(!empty($data['gifting'])){
			$gifting = $this->escape_string($this->strip_all($data['gifting']));
			$gifting = '1';
			}else{
				$gifting = '0';
			}
			
			// $booking_id = "DNC-".uniqid();

			// $check = $this->query("SELECT id FROM ".PREFIX."personal_details WHERE booking_id = '".$booking_id."' ");
			// if($this->num_rows($check) > 0){
			// 	$booking_id = "DNC-".uniqid();
			// }

			$this->query("INSERT INTO ".PREFIX."personal_details (first_name, last_name, mobile, email, address, landmark, zipcode, booking_date, no_vehicles, gifted) VALUES ('".$first_name."', '".$last_name."', '".$mobile."', '".$email."', '".$address."', '".$landmark."', '".$zipcode."', '".$booking_date."', '".$no_vehicles."', '".$gifting."')");

			$last_id=$this->last_insert_id();
			if(!empty($data['gifting'])){
			$g_first_name = $this->escape_string($this->strip_all($data['g_first_name']));
			$g_last_name = $this->escape_string($this->strip_all($data['g_last_name']));
			$g_mobile = $this->escape_string($this->strip_all($data['g_mobile']));
			$g_email = $this->escape_string($this->strip_all($data['g_email']));
			$g_address = $this->escape_string($this->strip_all($data['g_address']));
			$g_landmark = $this->escape_string($this->strip_all($data['g_landmark']));
			$g_zipcode = $this->escape_string($this->strip_all($data['g_zipcode']));
			$this->query("INSERT INTO ".PREFIX."personal_details (first_name, last_name, mobile, email, address, landmark, zipcode, gifted_from) VALUES ('".$g_first_name."', '".$g_last_name."', '".$g_mobile."', '".$g_email."', '".$g_address."', '".$g_landmark."', '".$g_zipcode."', '".$last_id."')");
			}
			//echo sizeof($data['category']);
			if(sizeof($data['category'])>1) {
				$j=0;
				foreach($data['category'] as $key=>$value) {
				//	if($data['category'][$key]!=''){
						$category = $this->escape_string($this->strip_all($data['category'][$key]));
						$package = $this->escape_string($this->strip_all($data['package'][$key]));
						$price = $this->getPrice($category,$package);
						$this->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package, price) values ('".$last_id."','".$category."','".$package."','".$price."')");
						$j++;
					//}
				}
			}else{
					$category = $this->escape_string($this->strip_all($data['category_id']));
					$package = $this->escape_string($this->strip_all($data['package_id']));
					$price = $this->getPrice($category,$package);
					
					$this->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package, price) values ('".$last_id."','".$category."','".$package."','".$price."')");
			} 

			/* $mail = new PHPMailer();
			include('invoice-email.inc.php');
			$mail->IsSMTP();
			$mail->Host = "shareittofriends.com";
			$mail->SMTPAuth = true;
			//$mail->SMTPSecure = "ssl";
			$mail->Port = 587;
			$mail->Username = "noreply@shareittofriends.com";
			$mail->Password = "noreply@1234";
			$mail->SMTPDebug = 2;
			$mail->From = "noreply@shareittofriends.com";
			$mail->AddAddress('mukunda.v@innovins.com');
			$mail->AddAddress($email);
			//$mail->AddAddress($data['email']);
			$mail->IsHTML(true);
			$mail->Subject = "Booking Details";
			$mail->Body = $invoiceMsg;
			//$mail->Body = "Booking Details";
			$mail->Send(); */
			return $last_id;
			
		}
		function updatePersonalPaymentDetails($data){
			$person_id = $this->escape_string($this->strip_all($data['person_id']));
			$mode_of_payment = $this->escape_string($this->strip_all($data['mode_of_payment']));
			$discountAmt = $this->escape_string($this->strip_all($data['dis_amt']));

			$data = $this->getPersonalDetails($person_id);
			$giftedData = $this->getGiftedPersonDetails($person_id);

			$total='';
			$summary = $this->getBookingDetails($data['id']);			
			while($res = $this->fetch($summary)){ 
				$total = $total + $res['price'];
			}
			//echo $total;exit;
			$sub_total = str_replace(',', '', $discountAmt);
			$final_total = $total - $sub_total;
			//echo $final_total;exit;
			session_start();
			$time = $this->escape_string($this->strip_all($_SESSION['booking_time']));
			
			$booking_id = '';
			//echo $time;exit;
			$qery123 = $this->query("SELECT booking_id from ".PREFIX."personal_details WHERE booking_id != '' order by id desc limit 1");
			if($this->num_rows($qery123) == 0){
				$booking_id = 'DNC-10001';
			}else{
				$last_booking = $this->fetch($qery123);
				$pieces = explode("-", $last_booking['booking_id']);
				$series = $pieces[1]+1;
				$booking_id = "DNC-".$series;
				// $check = $this->query("SELECT id FROM ".PREFIX."personal_details WHERE booking_id = '".$booking_id."' ");
				// if($this->num_rows($check) > 0){
				// 	$last_booking = $this->fetch($this->query("SELECT booking_id from ".PREFIX."personal_details order by id DESC limit 1"));
				// 	$pieces = explode("-", $last_booking['booking_id']);
				// 	$booking_id = "DNC-".$pieces[1] + 1;
				// }
			}
			//echo $booking_id;exit;
			if($mode_of_payment == 'COD'){
				$payment_status = 'Pending';
			}else{
				$payment_status = 'Completed';
			}
			$query = $this->query("update ".PREFIX."personal_details set final_total='".$final_total."', booking_time='".$time."', payment_mode='".$mode_of_payment."', payment_status = '".$payment_status."',booking_id='".$booking_id."' where id='".$person_id."'");
			
			session_destroy(); 

			
			$mail = new PHPMailer();
			include('invoice-email.inc.php');
			$mail->IsSMTP();
			$mail->Host = "shareittofriends.com";
			$mail->SMTPAuth = true;
			//$mail->SMTPSecure = "ssl";
			$mail->Port = 587;
			$mail->Username = "noreply@shareittofriends.com";
			$mail->Password = "noreply@1234";
			$mail->SMTPDebug = 2;
			$mail->From = "noreply@shareittofriends.com";
			$mail->AddAddress('mukunda.v@innovins.com');
			$mail->AddAddress($data['email']);
			//$mail->AddAddress($data['email']);
			$mail->IsHTML(true);
			$mail->Subject = "Booking Details";
			$mail->Body = $invoiceMsg;
			//$mail->Body = "Booking Details";
			$mail->Send();  
			
			header("location: thank-you.php");

		}
		function updatePersonalDetails($data){
			
			$id = $this->escape_string($this->strip_all($data['id']));
			$first_name = $this->escape_string($this->strip_all($data['first_name']));
			$last_name = $this->escape_string($this->strip_all($data['last_name']));
			$mobile = $this->escape_string($this->strip_all($data['mobile']));
			$email = $this->escape_string($this->strip_all($data['email']));
			$address = $this->escape_string($this->strip_all($data['address']));
			$landmark = $this->escape_string($this->strip_all($data['landmark']));
			$zipcode = $this->escape_string($this->strip_all($data['zipcode']));
			$booking_date = $this->escape_string($this->strip_all($data['date']));
			//$booking_time = $this->escape_string($this->strip_all($data['time']));
			if(!empty($data['no_vehicles'])){ 
				$no_vehicles = $this->escape_string($this->strip_all($data['no_vehicles']));
			}else{
				$no_vehicles = '';
			}
			if(!empty($data['gifting'])){
			$gifting = $this->escape_string($this->strip_all($data['gifting']));
			$gifting = '1';
			}else{
				$gifting = '0';
			}
		
			
			$this->query("update ".PREFIX."personal_details set first_name='".$first_name."', last_name='".$last_name."', mobile='".$mobile."', email='".$email."', address='".$address."', landmark='".$landmark."',zipcode='".$zipcode."', booking_date='".$booking_date."', no_vehicles='".$no_vehicles."'  where id='".$id."'");

			//$last_id=$this->last_insert_id();
			if(!empty($data['gifting'])){
			$g_first_name = $this->escape_string($this->strip_all($data['g_first_name']));
			$g_last_name = $this->escape_string($this->strip_all($data['g_last_name']));
			$g_mobile = $this->escape_string($this->strip_all($data['g_mobile']));
			$g_email = $this->escape_string($this->strip_all($data['g_email']));
			$g_address = $this->escape_string($this->strip_all($data['g_address']));
			$g_landmark = $this->escape_string($this->strip_all($data['g_landmark']));
			$g_zipcode = $this->escape_string($this->strip_all($data['g_zipcode']));
			$this->query("update ".PREFIX."personal_details set first_name='".$first_name."', last_name='".$last_name."', mobile='".$mobile."', email='".$email."', address='".$address."', landmark='".$landmark."',zipcode='".$zipcode."'  where gifted_from='".$id."'");
			}
			$this->query("delete from ".PREFIX."booking_details where personal_id='".$id."'");
			//echo sizeof($data['category']);exit;
			if(sizeof($data['category'])>0) {
				$j=0;
				foreach($data['category'] as $key=>$value) {
				if(!empty($data['category'][$key])){
						$category = $this->escape_string($this->strip_all($data['category'][$key]));
						$package = $this->escape_string($this->strip_all($data['package'][$key]));
						$price = $this->getPrice($category,$package);
						$this->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package, price) values ('".$id."','".$category."','".$package."','".$price."')");
						$j++;
				}
					
				}
			}
			/*  else{
					$category = $this->escape_string($this->strip_all($data['category_id']));
					$package = $this->escape_string($this->strip_all($data['package_id']));
					$price = $this->getPrice($category,$package);
					
					$this->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package, price) values ('".$id."','".$category."','".$package."','".$price."')");
			}   */
			
		}
		function getPrice($category,$package){
			$category = $this->escape_string($this->strip_all($category));
			$package = $this->escape_string($this->strip_all($package));
			if($package == 'quick-wash'){
			$sql = $this->query("SELECT * FROM ".PREFIX."quick_wash_master WHERE category = '".$category."'");
			$price = $price = $this->fetch($sql);
			return $price['price'];
			}else{
			$sql = $this->query("SELECT * FROM ".PREFIX."subscription_master WHERE category = '".$category."' and package_name = '".$package."'");
			$price = $price = $this->fetch($sql);
			return $price['package_price'];
			}
		}
		function getBookingDetails($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = $this->query("SELECT * from ".PREFIX."booking_details where personal_id='".$id."'");
			return $query;
		}
		
		function getCategoryDetails($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = $this->query("SELECT * FROM ".PREFIX."category_master WHERE id = '".$id."'");
			return $this->fetch($query);
		}
		function getPackageDetailsDetails($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = $this->query("SELECT * FROM ".PREFIX."package_master WHERE id = '".$id."'");
			return $this->fetch($query);
		}
		

		function addPersonalDetails($data){
			$first_name = $this->escape_string($this->strip_all($data['first_name']));
			$last_name = $this->escape_string($this->strip_all($data['last_name']));
			$mobile = $this->escape_string($this->strip_all($data['mobile']));
			$email = $this->escape_string($this->strip_all($data['email']));
			$address = $this->escape_string($this->strip_all($data['address']));
			$landmark = $this->escape_string($this->strip_all($data['landmark']));
			$zipcode = $this->escape_string($this->strip_all($data['zipcode']));
			$booking_date = $this->escape_string($this->strip_all($data['date']));
			$booking_time = $this->escape_string($this->strip_all($data['time']));
			if(!empty($data['no_vehicles'])){ 
				$no_vehicles = $this->escape_string($this->strip_all($data['no_vehicles']));
			}else{
				$no_vehicles = '';
			}
			if(!empty($data['gifting'])){
			$gifting = $this->escape_string($this->strip_all($data['gifting']));
			}else{
				$gifting = '';
			}
			if($gifting == 'on'){ 
				$gifting = '1';
			}else{
				$gifting = '0';
			}

			$this->query("INSERT INTO ".PREFIX."personal_details (first_name, last_name, mobile, email, address, landmark, zipcode, booking_date, booking_time, no_vehicles, gifted) VALUES ('".$first_name."', '".$last_name."', '".$mobile."', '".$email."', '".$address."', '".$landmark."', '".$zipcode."', '".$booking_date."', '".$booking_time."', '".$no_vehicles."', '".$gifting."')");

			$last_id=$this->last_insert_id();

			if(sizeof($data['category'])>0) {
				$j=0;
				foreach($data['category'] as $key=>$value) {
					if($data['category'][$key]!=''){
						$category = $this->escape_string($this->strip_all($data['category'][$key]));
						$package = $this->escape_string($this->strip_all($data['package'][$key]));
						$this->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package) values ('".$last_id."','".$category."','".$package."')");
						$j++;
					}
				}
			}
		}

		function getValidatedPermalink($permalink){ // v2
			$permalink = trim($permalink, '()');
			$replace_keywords = array("-:-", "-:", ":-", " : ", " :", ": ", ":",
				"-@-", "-@", "@-", " @ ", " @", "@ ", "@", 
				"-.-", "-.", ".-", " . ", " .", ". ", ".", 
				"-\\-", "-\\", "\\-", " \\ ", " \\", "\\ ", "\\",
				"-/-", "-/", "/-", " / ", " /", "/ ", "/", 
				"-&-", "-&", "&-", " & ", " &", "& ", "&", 
				"-,-", "-,", ",-", " , ", " ,", ", ", ",", 
				" ", "\r", "\n", 
				"---", "--", " - ", " -", "- ",
				"-#-", "-#", "#-", " # ", " #", "# ", "#",
				"-$-", "-$", "$-", " $ ", " $", "$ ", "$",
				"-%-", "-%", "%-", " % ", " %", "% ", "%",
				"-^-", "-^", "^-", " ^ ", " ^", "^ ", "^",
				"-*-", "-*", "*-", " * ", " *", "* ", "*",
				"-(-", "-(", "(-", " ( ", " (", "( ", "(",
				"-)-", "-)", ")-", " ) ", " )", ") ", ")",
				"-;-", "-;", ";-", " ; ", " ;", "; ", ";",
				"-'-", "-'", "'-", " ' ", " '", "' ", "'",
				'-"-', '-"', '"-', ' " ', ' "', '" ', '"',
				"-?-", "-?", "?-", " ? ", " ?", "? ", "?",
				"-+-", "-+", "+-", " + ", " +", "+ ", "+",
				"-!-", "-!", "!-", " ! ", " !", "! ", "!");
			$escapedPermalink = str_replace($replace_keywords, '-', $permalink); 
			return strtolower($escapedPermalink);
		}
		function getUniquePermalink($permalink,$tableName,$main_menu,$newPermalink='',$num=1) {
			if($newPermalink=='') {
				$checkPerma = $permalink;
			} else {
				$checkPerma = $newPermalink;
			}
			$sql = $this->query("select * from ".PREFIX.$tableName." where permalink='$checkPerma' and main_menu='$main_menu'");
			if($this->num_rows($sql)>0) {
				$count = $num+1;
				$newPermalink = $permalink.$count;
				return $this->getUniquePermalink($permalink,$tableName,$main_menu,$newPermalink,$count);
			} else {
				return $checkPerma;
			}
		}
		function getActiveLabel($isActive){
			if($isActive){
				return 'Yes';
			} else {
				return 'No';
			}
		}
		function getImageUrl($imageFor, $fileName, $imageSuffix){
			$image_name = strtolower(pathinfo($fileName, PATHINFO_FILENAME));
			$image_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
			switch($imageFor){
				case "banner":
					$fileDir = "../img/banner/";
					break;
				case "career":
					$fileDir = "../images/career/";
					break;
				case "products":
					$fileDir = "../img/products/";
					break;
				case "category":
					$fileDir = "../img/category/";
					break;
				case "sub_category":
					$fileDir = "../img/sub_category/";
					break;
				case "our-philosophy":
					$fileDir = "../images/our-philosophy/";
					break;
				case "team-testimonial":
					$fileDir = "../images/team-testimonial/";
					break;
				case "news":
					$fileDir = "../images/news/";
					break;
				case "csr-objectives":
					$fileDir = "../images/csr-objectives/";
					break;
				case "team":
					$fileDir = "../images/team/";
					break;
				case "vendor-guideline":
					$fileDir = "../images/vendor-guideline/";
					break;
				case "service":
					$fileDir = "../images/service/";
					break;
				case "our_presence":
					$fileDir = "../images/our_presence/";
					break;
				default:
					return false;
					break;
			}
			$imageUrl = $fileDir.$image_name."_".$imageSuffix.".".$image_ext;
			if(file_exists($imageUrl)){
				return $imageUrl;
				// $imageUrl = BASE_URL.'/'.$imageUrl;
			} else {
				return false;
				// $imageUrl = BASE_URL."/images/no_img.jpg";
			}
		}
		function unlinkImage($imageFor, $fileName, $imageSuffix){
			$imagePath = $this->getImageUrl($imageFor, $fileName, $imageSuffix);
			$status = false;
			if($imagePath!==false){
				$status = unlink($imagePath);
			}
			return $status;
		}
		function checkUserPermissions($permission,$loggedInUserDetailsArr) {
			$userPermissionsArray = explode(',',$loggedInUserDetailsArr['permissions']);
			if(!in_array($permission,$userPermissionsArray) and $loggedInUserDetailsArr['role']!='super') {
				header("location: dashboard.php");
				exit;
			}
		}
		
		// === BANNER STARTS ===
		function getAllBanners() {
			$query = "select * from ".PREFIX."banner_master where active ='Yes' order by display_order ASC";
			$sql = $this->query($query);
			return $sql;
		}

		function getUniqueBannerById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."banner_master where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}

		function addBanner($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$title = $this->escape_string($this->strip_selected($data['title'], $allowTags));
			$sub_title = $this->escape_string($this->strip_selected($data['sub_title'], $allowTags));
			$text = $this->escape_string($this->strip_all($data['text']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 909, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}

			$query = "insert into ".PREFIX."banner_master (title,sub_title,image_name,text,link,active,display_order) values ('".$title."','".$sub_title."','$image_name', '$text', '$link', '$active', '$display_order')";
			return $this->query($query);
		}
		function getUniqueAwardsImageById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."awards_image where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueExportImageById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."aboutus_export_image where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueAwardsContentById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."awards_content where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function getUniqueAwardImageById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."awards_image where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function addAwardsImage($data,$file) {
			
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 225, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."awards_image (image_name,active,display_order) values ('$image_name', '$active', '$display_order')";
			return $this->query($query);
		}
		function getUniqueAnnualRepotById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."annual_report where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getUniqueCompositionCategoryById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."composition_board_category where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getUniqueFinancialHighlightsImageById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."financial_highlights where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getUniqueCompositionBoardById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."composition_board where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function addCompositionBoard($data) {
			$category = $this->escape_string($this->strip_all($data['category']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$position = $this->escape_string($this->strip_all($data['position']));
			$date = date("Y-m-d H:i:s");
			
			$query = "insert into ".PREFIX."composition_board(category,name,position,created) values ('$category', '$name', '$position','$date')";
			
			return $this->query($query);
		}
		function updateCompositionBoard($data) {
			$category = $this->escape_string($this->strip_all($data['category']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$position = $this->escape_string($this->strip_all($data['position']));
			$id = $this->escape_string($this->strip_all($data['id']));
			
			$query = "update ".PREFIX."composition_board set category='".$category."',name='".$name."',position='".$position."' where id='".$id."' ";
			
			return $this->query($query);
		}
		function addAnnualReport($data,$file) {
			
			$year = $this->escape_string($this->strip_all($data['year']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			$pdfDir = '../pdf/annual_report/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 231, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$pdfDir,$file_name);
			
			}else {
				$pdf = '';
				
			}
			$query = "insert into ".PREFIX."annual_report(year,image_name,pdf,display_order,created) values ('$year','$image_name', '$pdf', '$display_order','$date')";
			
			return $this->query($query);
		} 
		function addLeadershipTeam($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$name = $this->escape_string($this->strip_all($data['name']));
			$designation = $this->escape_string($this->strip_all($data['designation']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../images/team/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 215, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."leadership_team(`name`, `image_name`, `designation`, `description`, `display_order`, `created`) values ('$name','$image_name', '$designation','$description','$display_order' ,'$date')";
			
			return $this->query($query);
		}
		function updateLeadershipTeam($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$name = $this->escape_string($this->strip_all($data['name']));
			$designation = $this->escape_string($this->strip_all($data['designation']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$id = $this->escape_string($this->strip_all($data['id']));
			$SaveImage = new SaveImage();
			$imgDir = '../images/team/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 215, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."leadership_team set image_name='".$image_name."' where id='".$id."'");
			}
			$query = "update ".PREFIX."leadership_team set name='".$name."',designation='".$designation."',description='".$description."',display_order='".$display_order."'  where id='".$id."'";
			
			return $this->query($query);
		}
		function addBoardOfDirector($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$image_description = $this->escape_string($this->strip_selected($data['image_description'],$allowTags));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../images/team/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 585, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."board_of_directors(`image_name`, `image_description`, `display_order`, `created`) values ('$image_name', '$image_description','$display_order','$date')";
			
			return $this->query($query);
		}
		function updateBoardOfDirector($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$image_description = $this->escape_string($this->strip_selected($data['image_description'],$allowTags));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$id = $this->escape_string($this->strip_all($data['id']));
			$SaveImage = new SaveImage();
			$imgDir = '../images/team/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 585, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."board_of_directors set image_name = '".$image_name."' where id='".$id."'");
			}
			$query = "update ".PREFIX."board_of_directors set image_description='".$image_description."',display_order='".$display_order."' where id='".$id."'";
			
			return $this->query($query);
		}
		function addGroupCompanies($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$description_title = $this->escape_string($this->strip_selected($data['description_title'],$allowTags));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			$pdfDir = '../pdf/annual_report/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 500, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			
			$query = "insert into ".PREFIX."group_companies(`image_name`, `title`,`description_title`, `description`, `display_order`, `created`) values ('$image_name','$title', '$description_title', '$description', '$display_order','$date')";
			
			return $this->query($query);
		}
		function UpdateGroupCompaniesDetail($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$description_title = $this->escape_string($this->strip_selected($data['description_title'],$allowTags));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$id = $this->escape_string($this->strip_all($data['id']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			$pdfDir = '../pdf/annual_report/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 500, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."group_companies set image_name = '$image_name' where id='".$id."'");
			}
			
			$query = "update ".PREFIX."group_companies set title='".$title."',description='".$description."',description_title='".$description_title."',display_order='".$display_order."' where id='".$id."'";
			
			return $this->query($query);
		}
		function addFinancialHighlightsImage($data,$file) {
			
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				//$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 299, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			
			$query = "insert into ".PREFIX."financial_highlights(title,image_name,display_order,created) values ('$title','$image_name', '$display_order','$date')";
			
			return $this->query($query);
		} 
		function updateFinancialHighlightsImage($data,$file) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueAnnualRepotById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 299, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."financial_highlights set image_name='$image_name' where id='$id'");
			}
			

			$query = "update ".PREFIX."financial_highlights set title='$title', display_order='$display_order' where id='$id'";
			return $this->query($query);
		}
		function addCompositionBoardCategory($data){
			$category = $this->escape_string($this->strip_all($data['category']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$date = date("Y-m-d h:i:s");
			$query = "insert into ".PREFIX."composition_board_category(`category`, `name`, `created`) values('".$category."','".$name."','".$date."')";
			return $this->query($query);
		}
		function updateCompositionBoardCategory($data){
			$category = $this->escape_string($this->strip_all($data['category']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$id = $this->escape_string($this->strip_all($data['id']));
			$query = "update ".PREFIX."composition_board_category set category='".$category."',name='".$name."' where id='".$id."'";
			return $this->query($query);
		}
		function updateAnnualReport($data,$file) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			$year = $this->escape_string($this->strip_all($data['year']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			$pdfDir = '../pdf/annual_report/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueAnnualRepotById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 231, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."annual_report set image_name='$image_name' where id='$id'");
			}
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$pdfDir,$file_name);
				$this->query("update ".PREFIX."annual_report set pdf='$pdf' where id='$id'");
			}

			$query = "update ".PREFIX."annual_report set year='$year', display_order='$display_order' where id='$id'";
			return $this->query($query);
		}
		function deleteAnnualReport($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueAnnualRepotById($id);
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."annual_report where id='$id'";
			$this->query($query);
			return true;
		}
		function getUniqueGroupCompaniesById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."group_companies where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getUniqueBoardofDirectorById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."board_of_directors where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getUniqueLeaderShipTeamById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."leadership_team where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteBoardOfDirector($id) {
			$id = $this->escape_string($this->strip_all($id));
			/* $Detail = $this->getUniqueBoardofDirectorById($id);
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop"); */
			$query = "delete from ".PREFIX."board_of_directors where id='".$id."'";
			$this->query($query);
		}
		function deleteLeadershipTeam($id) {
			$id = $this->escape_string($this->strip_all($id));
			/* $Detail = $this->getUniqueBoardofDirectorById($id);
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop"); */
			$query = "delete from ".PREFIX."leadership_team where id='".$id."'";
			$this->query($query);
		}
		function deleteGroupCompanies($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueGroupCompaniesById($id);
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."group_companies where id='$id'";
			$this->query($query);
			return true;
		}
		function deleteCompositionBoardCategory($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."composition_board_category where id='$id'";
			$this->query($query);
			return true;
		}
		function deleteFinancialHighlightsImage($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueFinancialHighlightsImageById($id);
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."financial_highlights where id='$id'";
			$this->query($query);
			return true;
		}
		function getCompositionCategory(){
			$query = "select * from ".PREFIX."composition_board_category";
			return $this->query($query);
		}
		function deleteCompositionBoard($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."composition_board where id='$id'";
			$this->query($query);
			return true;
		}
		function updateFinancialHighlights($data,$file) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			$title = $this->escape_string($this->strip_all($data['title']));
			
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueAnnualRepotById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 299, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."financial_highlights set image_name='$image_name' where id='$id'");
			}
			

			$query = "update ".PREFIX."financial_highlights set title='$title' where id='$id'";
			return $this->query($query);
		}
		function updateStockExchange($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueAnnualRepotById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 600, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."stock_exchange set image_name='$image_name' where id='$id'");
			}
			

			$query = "update ".PREFIX."stock_exchange set description='$description' where id='$id'";
			return $this->query($query);
		}
		function updateUnclaimedDividendContent($data) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$nodal_officer = $this->escape_string($this->strip_all($data['nodal_officer']));
			$email = $this->escape_string($this->strip_all($data['email']));
			$content = $this->escape_string($this->strip_all($data['content']));
			$page_url = $this->escape_string($this->strip_all($data['page_url']));
			
			$query = "update ".PREFIX."unclaimed_dividend_content set nodal_officer='$nodal_officer',email='$email',content='$content',page_url='$page_url' where id='$id' ";
			return $this->query($query);
		}
		function updateInvestorRelations($data) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$reg_company_name = $this->escape_string($this->strip_all($data['reg_company_name']));
			$reg_address = $this->escape_string($this->strip_all($data['reg_address']));
			$reg_contact = $this->escape_string($this->strip_all($data['reg_contact']));
			$reg_fax = $this->escape_string($this->strip_all($data['reg_fax']));
			$reg_email = $this->escape_string($this->strip_all($data['reg_email']));
			$reg_website = $this->escape_string($this->strip_all($data['reg_website']));
			$com_name = $this->escape_string($this->strip_all($data['com_name']));
			$com_company_name = $this->escape_string($this->strip_all($data['com_company_name']));
			$com_address1 = $this->escape_string($this->strip_all($data['com_address1']));
			$com_address2 = $this->escape_string($this->strip_all($data['com_address2']));
			$com_contact = $this->escape_string($this->strip_all($data['com_contact']));
			$com_fax = $this->escape_string($this->strip_all($data['com_fax']));
			$com_email = $this->escape_string($this->strip_all($data['com_email']));
			$com_website = $this->escape_string($this->strip_all($data['com_website']));
		
			$query = "UPDATE ".PREFIX."investor_relations SET `reg_company_name`='".$reg_company_name."',`reg_address`='".$reg_address."',`reg_contact`='".$reg_contact."',`reg_fax`='".$reg_fax."',`reg_email`='".$reg_email."',`reg_website`='".$reg_website."',`com_name`='".$com_name."',`com_company_name`='".$com_company_name."',`com_address1`='".$com_address1."',`com_address2`='".$com_address2."',`com_contact`='".$com_contact."',`com_fax`='".$com_fax."',`com_email`='".$com_email."',`com_website`='".$com_website."'";
			return $this->query($query);
		}
		function addExportImage($data,$file) {
			
			$content = $this->escape_string($this->strip_all($data['content']));
			$aboutus_id = $this->escape_string($this->strip_all($data['aboutus_id']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d h:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/about_img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 585, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."aboutus_export_image (aboutus_id,image_name,content,display_order,created) values ('$aboutus_id','$image_name', '$content', '$display_order','$date')";
			return $this->query($query);
		}
		function addAwardsContent($data) {
			
			$content = $this->escape_string($this->strip_all($data['content']));
			$from = $this->escape_string($this->strip_all($data['from_year']));
			$to = $this->escape_string($this->strip_all($data['to_year']));
			
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			
			$query = "INSERT INTO ".PREFIX."awards_content( `content`, `from_year`, `to_year`, `display_order`, `created`) VALUES ('".$content."','".$from."','".$to."','".$display_order."','".$date."')";
			return $this->query($query);
		}
		function updateAwardsContent($data) {
			
			$content = $this->escape_string($this->strip_all($data['content']));
			$from = $this->escape_string($this->strip_all($data['from_year']));
			$to = $this->escape_string($this->strip_all($data['to_year']));
			
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$id = $this->escape_string($this->strip_all($data['id']));
			
			$query = "update ".PREFIX."awards_content set content='".$content."',from_year='".$from."',to_year='".$to."', display_order='".$display_order."' where id='".$id."' ";
			
			return $this->query($query);
		}
		function updateAwardsImage($data,$file) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueAwardImageById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 225, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."awards_image set image_name='$image_name' where id='$id'");
			}

			$query = "update ".PREFIX."awards_image set active='$active', display_order='$display_order' where id='$id'";
			return $this->query($query);
		}
		function updateExportImage($data,$file) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			$content = $this->escape_string($this->strip_all($data['content']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/about_img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueExportImageById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("about_img", $Detail['image_name'], "large");
				$this->unlinkImage("about_img", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 585, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."aboutus_export_image set image_name='$image_name' where id='$id'");
			}

			$query = "update ".PREFIX."aboutus_export_image set content='$content', display_order='$display_order' where id='$id'";
			return $this->query($query);
		}

		function updateBanner($data,$file) {
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$title = $this->escape_string($this->strip_selected($data['title'], $allowTags));
			$sub_title = $this->escape_string($this->strip_selected($data['sub_title'], $allowTags));
			$id = $this->escape_string($this->strip_all($data['id']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$text = $this->escape_string($this->strip_all($data['text']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueBannerById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("banner", $Detail['image_name'], "large");
				$this->unlinkImage("banner", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 909, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."banner_master set image_name='$image_name' where id='$id'");
			}

			$query = "update ".PREFIX."banner_master set title = '".$title."', sub_title='".$sub_title."', text='$text', active='$active', link='$link', display_order='$display_order' where id='$id'";
			return $this->query($query);
		}

		function deleteBanner($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueBannerById($id);
			$this->unlinkImage("banner", $Detail['image_name'], "large");
			$this->unlinkImage("banner", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."banner_master where id='$id'";
			$this->query($query);
			return true;
		}
		

		// Banner end
		// Awards 
		function deleteAwardImage($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueAwardImageById($id);
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."awards_image where id='$id'";
			$this->query($query);
			return true;
		}
		function deleteAwardContent($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."awards_content where id='$id'";
			$this->query($query);
			return true;
		}
		function deleteFinancialHighlights($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."financial_highlights_pdf where id='$id'";
			$this->query($query);
			return true;
		}
		
		
		// Awards END
		
		// menu start
		
		function getMenu() {
			$query = "select * from ".PREFIX."menu_master";
			$sql = $this->query($query);
			
			return $this->fetch($sql);
		}

		function getUniqueMenuById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."menu_master where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}

		function addMenu($data,$files) {
			
			
			if(!empty($files['image_name']['name'])){
					
				$uploadDir = '../img/menu/';
				$tmpFilePath = $files['image_name']['tmp_name'];
				$shortname = $files['image_name']['name'];
				$file_name = strtolower(pathinfo($files['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($files['image_name']['name'], PATHINFO_EXTENSION));
				$upload = $file_name.rand(0,500).'.'.$ext;
				move_uploaded_file($tmpFilePath,$uploadDir.$upload);

				$insertAttachment = "insert into ".PREFIX."menu_master(image_name) values ('$upload')";

				$this->query($insertAttachment);
				
			}

			
			
		}

		function updateMenu($data,$files) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			//$delsql = "delete from ".PREFIX."menu_master where id= '".$id."'";
			//$this->query($delsql);

			if(!empty($files['image_name']['name'])){
					
				$uploadDir = '../img/menu/';
				$tmpFilePath = $files['image_name']['tmp_name'];
				$shortname = $files['image_name']['name'];
				$file_name = strtolower(pathinfo($files['image_name']['name'], PATHINFO_FILENAME));
				$ext = strtolower(pathinfo($files['image_name']['name'], PATHINFO_EXTENSION));
				$upload = $file_name.rand(0,500).'.'.$ext;
				move_uploaded_file($tmpFilePath,$uploadDir.$upload);

				//$insertAttachment = "insert into ".PREFIX."menu_master(image_name) values ('$upload')";
				$insertAttachment = "update ".PREFIX."menu_master set image_name='".$upload."' where id='".$id."'";	
				$this->query($insertAttachment);
				
			}

		}

		function deleteMenu($id) {
			$id = $this->escape_string($this->strip_all($id));
			
			
			$query = "delete from ".PREFIX."menu_master where id='$id'";
			$this->query($query);
			return true;
		}
		
		
		// menu end
		// Subsidiary Financials Start
		function getSubsidiaryFinancials($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."subsidiary_financials where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function AddSubsidiaryFinancials($data,$file) {
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/subsidiary_financials/';
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$pdfName = str_replace( " ", "-", $file['pdf']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$imgDir,$file_name.'-'.time());
				$res = $this->query("insert into ".PREFIX."subsidiary_financials(title,pdf,display_order,created) values('".$title."','".$pdf."','".$display_order."','".$date."')");
			}
			return $res;
		}
		function UpdateSubsidiaryFinancials($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/subsidiary_financials/';
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$pdfName = str_replace( " ", "-", $file['pdf']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$imgDir,$file_name.'-'.time());
				
				$res = $this->query("update ".PREFIX."subsidiary_financials set pdf='".$pdf."' where id='".$id."'");
		}
			$query = "update ".PREFIX."subsidiary_financials set title='".$title."',display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		}
		function deleteSubsidiaryFinancials($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."subsidiary_financials where id='".$id."'";
			$this->query($query);
		}
		// Subsidiary Financials End
		// Financial Highlights Start
		function getFinancialHighlightsPDF($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."financial_highlights_pdf where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function AddFinancialHighlights($data,$file) {
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/financial_highlights/';
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$pdfName = str_replace( " ", "-", $file['pdf']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$imgDir,$file_name.'-'.time());
				$res = $this->query("insert into ".PREFIX."financial_highlights_pdf(title,pdf,display_order,created) values('".$title."','".$pdf."','".$display_order."','".$date."')");
			}
			return $res;
		}
		function UpdateFinancialHighlightsPDF($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/financial_highlights/';
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$pdfName = str_replace( " ", "-", $file['pdf']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$imgDir,$file_name.'-'.time());
				
				$res = $this->query("update ".PREFIX."financial_highlights_pdf set pdf='".$pdf."' where id='".$id."'");
			}
			$query = "update ".PREFIX."financial_highlights_pdf set title='".$title."',display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		}
		// Financial Highlights End
		// stock-exchange-announcements Start
		function getStockExchange($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."stock_exchange_announcements where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteStockExchange($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."stock_exchange_announcements where id='".$id."'";
			$this->query($query);
		}
		function AddStockExchange($data,$file) {
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/stock_exchange/';
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$imgDir,$file_name);
				$res = $this->query("insert into ".PREFIX."stock_exchange_announcements(title,pdf,display_order,created) values('".$title."','".$pdf."','".$display_order."','".$date."')");
			}
			return $res;
		}
		function UpdateStockExchangeAnnouncement($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/stock_exchange/';
			if(isset($file['pdf']['name']) && !empty($file['pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				
				$pdf = $SaveImage->uploadFileFromForm($file['pdf'],$imgDir,$file_name);
				
				$res = $this->query("update ".PREFIX."stock_exchange_announcements set pdf='".$pdf."' where id='".$id."'");
			}
			$query = "update ".PREFIX."stock_exchange_announcements set title='".$title."',display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		}
		// stock-exchange-announcements End
		function getListingAndOtherInformation($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."listingAndOtherInformation where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteListingAndOtherInformation($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."listingAndOtherInformation where id='".$id."'";
			$this->query($query);
		}
		// Quarter start
		function getQuarter($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."quarter where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getAboutUsContent() {
			$query = "select * from ".PREFIX."about_us";
			return $this->fetch($this->query($query));
		}
		function deleteQuarter($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."quarter where id='".$id."'";
			$this->query($query);
		}
		function AddQuarter($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$quarter1_name = $this->escape_string($this->strip_all($data['quarter1_name']));
			$quarter2_name = $this->escape_string($this->strip_all($data['quarter2_name']));
			$quarter3_name = $this->escape_string($this->strip_all($data['quarter3_name']));
			$quarter4_name = $this->escape_string($this->strip_all($data['quarter4_name']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/quarter/';
			if(isset($file['quarter1_pdf']['name']) && !empty($file['quarter1_pdf']['name'])) {
				$quarter_pdf1 = str_replace( " ", "-", $file['quarter1_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf1, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter1_pdf = $SaveImage->uploadFileFromForm($file['quarter1_pdf'],$imgDir,$file_name.'-'.time());
			}else{
				$quarter1_pdf = '';
			}
			if(isset($file['quarter2_pdf']['name']) && !empty($file['quarter2_pdf']['name'])) {
				$quarter_pdf2 = str_replace( " ", "-", $file['quarter2_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf2, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter2_pdf'],$imgDir,$file_name.'-'.time());
			}else{
				$quarter2_pdf = '';
			}
			if(isset($file['quarter3_pdf']['name']) && !empty($file['quarter3_pdf']['name'])) {
				$quarter_pdf3 = str_replace( " ", "-", $file['quarter3_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf3, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter3_pdf = $SaveImage->uploadFileFromForm($file['quarter3_pdf'],$imgDir,$file_name.'-'.time());
			}else{
				$quarter3_pdf = '';
			}
			if(isset($file['quarter4_pdf']['name']) && !empty($file['quarter4_pdf']['name'])) {
				$quarter_pdf4 = str_replace( " ", "-", $file['quarter4_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf4, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter4_pdf = $SaveImage->uploadFileFromForm($file['quarter4_pdf'],$imgDir,$file_name.'-'.time());
			}else{
				$quarter4_pdf = '';
			}
			$query = "INSERT INTO ".PREFIX."quarter(`from_year`, `to_year`, `year`, `quarter1_name`, `quarter1_pdf`, `quarter2_name`, `quarter2_pdf`, `quarter3_name`, `quarter3_pdf`, `quarter4_name`, `quarter4_pdf`, `created`) VALUES ('".$from_year."','".$to_year."','".$year."','".$quarter1_name."','".$quarter1_pdf."','".$quarter2_name."','".$quarter2_pdf."','".$quarter3_name."','".$quarter3_pdf."','".$quarter4_name."','".$quarter4_pdf."','".$date."') ";
			return $this->query($query);
		}
		function UpdateQuarter($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$quarter1_name = $this->escape_string($this->strip_all($data['quarter1_name']));
			$quarter2_name = $this->escape_string($this->strip_all($data['quarter2_name']));
			$quarter3_name = $this->escape_string($this->strip_all($data['quarter3_name']));
			$quarter4_name = $this->escape_string($this->strip_all($data['quarter4_name']));
			$SaveImage = new SaveImage();
			$t = time();
			$imgDir = '../pdf/quarter/';
			if(isset($file['quarter1_pdf']['name']) && !empty($file['quarter1_pdf']['name'])) {
				$quarter_pdf1 = str_replace( " ", "-", $file['quarter1_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf1, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getQuarter($id);
				$this->unlinkImage("quarter", $Detail['quarter1_pdf']);
				$quarter1_pdf = $SaveImage->uploadFileFromForm($file['quarter1_pdf'],$imgDir,$file_Name.'-'.time());
				$this->query("update ".PREFIX."quarter set quarter1_pdf='$quarter1_pdf' where id='".$id."'");
			}
			if(isset($file['quarter2_pdf']['name']) && !empty($file['quarter2_pdf']['name'])) {
				$quarter_pdf2 = str_replace( " ", "-", $file['quarter2_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf2, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getQuarter($id);
				$this->unlinkImage("quarter", $Detail['quarter2_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter2_pdf'],$imgDir,$file_Name.'-'.time());
				$this->query("update ".PREFIX."quarter set quarter2_pdf='$quarter2_pdf' where id='".$id."'");
			}
			if(isset($file['quarter3_pdf']['name']) && !empty($file['quarter3_pdf']['name'])) {
				$quarter_pdf3 = str_replace( " ", "-", $file['quarter3_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf3, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getQuarter($id);
				$this->unlinkImage("quarter", $Detail['quarter3_pdf']);
				$quarter3_pdf = $SaveImage->uploadFileFromForm($file['quarter3_pdf'],$imgDir,$file_Name.'-'.time());
				$this->query("update ".PREFIX."quarter set quarter3_pdf='$quarter3_pdf' where id='".$id."'");
			}
			if(isset($file['quarter4_pdf']['name']) && !empty($file['quarter4_pdf']['name'])) {
				$quarter_pdf4 = str_replace( " ", "-", $file['quarter4_pdf']['name']);
				$file_name = strtolower( pathinfo($quarter_pdf4, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getQuarter($id);
				$this->unlinkImage("quarter", $Detail['quarter4_pdf']);
				$quarter4_pdf = $SaveImage->uploadFileFromForm($file['quarter4_pdf'],$imgDir,$file_Name.'-'.time());
				$this->query("update ".PREFIX."quarter set quarter4_pdf='$quarter4_pdf' where id='".$id."'");
			}
			$query = "update ".PREFIX."quarter set quarter1_name='$quarter1_name', quarter2_name='".$quarter2_name."',quarter3_name='".$quarter3_name."',quarter4_name='".$quarter4_name."' where id='".$id."' ";
			return $this->query($query);
			
		}
		
		
		// Quarter End
		// Unclaimed Dividend start 
		
		function getUnclaimedDividend($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."unclaimed_dividend where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteUnclaimedDividend($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."unclaimed_dividend where id='".$id."'";
			$this->query($query);
		}
		
		function AddUnclaimedDividend($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$title = $this->escape_string($this->strip_all($data['title']));
			
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/unclaimed_dividend/';
			if(isset($file['pdf_name']['name']) && !empty($file['pdf_name']['name'])) {
				$pdfName = str_replace( " ", "-", $file['pdf_name']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$pdf_name = $SaveImage->uploadFileFromForm($file['pdf_name'],$imgDir,$file_name.'-'.time());
			}else{
				$pdf_name = '';
			}
			
			$query = "INSERT INTO ".PREFIX."unclaimed_dividend(`from_year`, `to_year`, `year`, `title`, `pdf_name`, `created`) VALUES ('".$from_year."','".$to_year."','".$year."','".$title."','".$pdf_name."','".$date."') ";
			return $this->query($query);
		}
		function UpdateUnclaimedDividend($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$title = $this->escape_string($this->strip_all($data['title']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/unclaimed_dividend/';
			if(isset($file['pdf_name']['name']) && !empty($file['pdf_name']['name'])) {
				$pdfName = str_replace( " ", "-", $file['pdf_name']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUnclaimedDividend($id);
				$this->unlinkImage("unclaimed_dividend", $Detail['pdf_name']);
				$pdf_name = $SaveImage->uploadFileFromForm($file['pdf_name'],$imgDir,$file_name.'-'.time());
				$this->query("update ".PREFIX."unclaimed_dividend set pdf_name='$pdf_name' where id='".$id."'");
			}
			
			$query = "update ".PREFIX."unclaimed_dividend set from_year='".$from_year."',to_year='".$to_year."',year='".$year."',title='$title' where id='".$id."' ";
			return $this->query($query);
			
		}
		// Unclaimed Dividend End 
		// AGM start 
		function getAgm($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."agm where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function getAgmCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."agm_pdf where agm_id = '".$id."'");
			return $this->num_rows($count);
		}
		function getAgmSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."agm_pdf where agm_id = '".$id."' order by display_order<>0 DESC");
			return $count;
		}
		function deleteAgm($id) {
			$id = $this->escape_string($this->strip_all($id));
			$res = $this->deleteAgmPdf($id);
			$query = "delete from ".PREFIX."agm where id='".$id."'";
			$this->query($query);
		}
		function deleteAgmPdf($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "DELETE FROM ".PREFIX."agm_pdf  where agm_id='".$id."' ";
			$res = $this->query($query);
		}
		function AddAgm($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/board_meeting/';
			$query = "INSERT INTO ".PREFIX."agm(`from_year`, `to_year`, `year`,`created`) VALUES ('".$from_year."','".$to_year."','".$year."','".$date."') ";
			$res = $this->query($query);
			$agm_id = $this->last_insert_id();
			$count = count($file['quarter_pdf']['name']);
			$property_images = count($file['quarter_pdf']['name']);
			for($key=0;$key<$count;$key++){
				$quarter_pdf = str_replace( " ", "-", $file['quarter_pdf']['name'][$key]);
				$upload = move_uploaded_file($file['quarter_pdf']['tmp_name'][$key],$imgDir.$quarter_pdf);
				if(!$upload){
					$quarter_pdf ="";
				}
				
				$quarter_name = $this->escape_string($this->strip_all($data['quarter_name'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				$this->query("INSERT INTO ".PREFIX."agm_pdf(`agm_id`, `quarter_name`, `quarter_pdf`, `display_order`, `created`) VALUES ('".$agm_id."','".$quarter_name."','".$quarter_pdf."','".$display_order."','".$date."')");  
				
				} 
			
			
		}
		function UpdateAgm($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$date = date("Y-m-d H:i:s");
			$id = $this->escape_string($this->strip_all($data['id']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/board_meeting/';
			$query = "update ".PREFIX."agm set from_year='".$from_year."',to_year='".$to_year."',year='".$year."' where id='".$id."' ";
			$res = $this->query($query);
			$agm_id = $id;
			//$deleteRecord = $this->deleteCgReportQuarter($cg_id);
			$count = count($file['quarter_pdf']['name']);
			$property_images = count($file['quarter_pdf']['name']);
			for($key=0;$key<$count;$key++){
				$quarter_pdf = str_replace( " ", "-", $file['quarter_pdf']['name'][$key]);
				$upload = move_uploaded_file($file['quarter_pdf']['tmp_name'][$key],$imgDir.$quarter_pdf);
				if(!$upload){
					$quarter_pdf ="";
				}
				$quarter_id = $this->escape_string($this->strip_all($data['quarter_id'][$key]));
				$quarter_name = $this->escape_string($this->strip_all($data['quarter_name'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				
				if(empty($quarter_id)){
				$this->query("INSERT INTO ".PREFIX."agm_pdf(`agm_id`, `quarter_name`, `quarter_pdf`, `display_order`, `created`) VALUES ('".$agm_id."','".$quarter_name."','".$quarter_pdf."','".$display_order."','".$date."')"); 
				}else if($quarter_pdf!=''){
					$this->query("Update ".PREFIX."agm_pdf set quarter_name='".$quarter_name."',display_order='".$display_order."',quarter_pdf='".$quarter_pdf."' where id='".$quarter_id."'"); 
				}else{
					$this->query("Update ".PREFIX."agm_pdf set quarter_name='".$quarter_name."',display_order='".$display_order."' where id='".$quarter_id."'"); 
				}
				
				} 
		}
		/* function UpdateAgm($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$title = $this->escape_string($this->strip_all($data['title']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/board_meeting/';
			if(isset($file['pdf_name']['name']) && !empty($file['pdf_name']['name'])) {
				$file_name = strtolower( pathinfo($file['pdf_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getAgm($id);
				$this->unlinkImage("board_meeting", $Detail['pdf_name']);
				$pdf_name = $SaveImage->uploadFileFromForm($file['pdf_name'],$imgDir,$file_name);
				$this->query("update ".PREFIX."agm set pdf_name='$pdf_name' where id='".$id."'");
			}
			
			$query = "update ".PREFIX."agm set from_year='".$from_year."',to_year='".$to_year."',year='".$year."',title='$title' where id='".$id."' ";
			return $this->query($query);
			
		} */
		// AGM End
		// Governance Reports start
		function getCgReport($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."cg_report where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteCgReport($id) {
			$id = $this->escape_string($this->strip_all($id));
			$res = $this->deleteCgReportQuarter($id);
			$query = "delete from ".PREFIX."cg_report where id='".$id."'";
			$this->query($query);
		}
		function AddCgReport($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/cg_report/';
			$query = "INSERT INTO ".PREFIX."cg_report(`from_year`, `to_year`, `year`,`created`) VALUES ('".$from_year."','".$to_year."','".$year."','".$date."') ";
			$res = $this->query($query);
			$cg_id = $this->last_insert_id();
			$count = count($file['quarter_pdf']['name']);
			$property_images = count($file['quarter_pdf']['name']);
			for($key=0;$key<$count;$key++){
				$quarter_pdf = str_replace( " ", "-", $file['quarter_pdf']['name'][$key] );
				$upload = move_uploaded_file($file['quarter_pdf']['tmp_name'][$key],$imgDir.$quarter_pdf);
				if(!$upload){
					$quarter_pdf ="";
				}
				$quarter_type = $this->escape_string($this->strip_all($data['quarter_type'][$key]));
				$quarter_name = $this->escape_string($this->strip_all($data['quarter_name'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				$this->query("INSERT INTO ".PREFIX."cg_report_quarter(`cg_id`, `quarter_type`, `quarter_name`, `quarter_pdf`, `display_order`, `created`) VALUES ('".$cg_id."','".$quarter_type."','".$quarter_name."','".$quarter_pdf."','".$display_order."','".$date."')");  
				
				} 
			
			
		}
		function UpdateCgReport($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$date = date("Y-m-d H:i:s");
			$id = $this->escape_string($this->strip_all($data['id']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/cg_report/';
			$query = "update ".PREFIX."cg_report set from_year='".$from_year."',to_year='".$to_year."',year='".$year."' where id='".$id."' ";
			$res = $this->query($query);
			$cg_id = $id;
			//$deleteRecord = $this->deleteCgReportQuarter($cg_id);
			$count = count($file['quarter_pdf']['name']);
			$property_images = count($file['quarter_pdf']['name']);
			for($key=0;$key<$count;$key++){
				$quarter_pdf = str_replace( " ", "-", $file['quarter_pdf']['name'][$key] );
				$upload = move_uploaded_file($file['quarter_pdf']['tmp_name'][$key],$imgDir.$quarter_pdf);
				if(!$upload){
					$quarter_pdf ="";
				}
				$quarter_id = $this->escape_string($this->strip_all($data['quarter_id'][$key]));
				$quarter_type = $this->escape_string($this->strip_all($data['quarter_type'][$key]));
				$quarter_name = $this->escape_string($this->strip_all($data['quarter_name'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				
				if(empty($quarter_id)){
				$this->query("INSERT INTO ".PREFIX."cg_report_quarter(`cg_id`, `quarter_type`, `quarter_name`, `quarter_pdf`, `display_order`, `created`) VALUES ('".$cg_id."','".$quarter_type."','".$quarter_name."','".$quarter_pdf."','".$display_order."','".$date."')"); 
				}else if($quarter_pdf!=''){
					$this->query("Update ".PREFIX."cg_report_quarter set quarter_type='".$quarter_type."',quarter_name='".$quarter_name."',display_order='".$display_order."',quarter_pdf='".$quarter_pdf."' where id='".$quarter_id."'"); 
				}else{
					$this->query("Update ".PREFIX."cg_report_quarter set quarter_type='".$quarter_type."',quarter_name='".$quarter_name."',display_order='".$display_order."' where id='".$quarter_id."'"); 
				}
				
				} 
		}
		function deleteCgReportQuarter($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "DELETE FROM ".PREFIX."cg_report_quarter  where cg_id='".$id."' ";
			$res = $this->query($query);
		}
		/* function AddCgReport($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$quarter1_name = $this->escape_string($this->strip_all($data['quarter1_name']));
			$quarter2_name = $this->escape_string($this->strip_all($data['quarter2_name']));
			$quarter3_name = $this->escape_string($this->strip_all($data['quarter3_name']));
			$quarter4_name = $this->escape_string($this->strip_all($data['quarter4_name']));
			
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/cg_report/';
			if(isset($file['quarter1_pdf']['name']) && !empty($file['quarter1_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter1_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter1_pdf = $SaveImage->uploadFileFromForm($file['quarter1_pdf'],$imgDir,$file_name);
			}else{
				$quarter1_pdf = '';
			}
			if(isset($file['quarter2_pdf']['name']) && !empty($file['quarter2_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter2_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter2_pdf'],$imgDir,$file_name);
			}else{
				$quarter2_pdf = '';
			}
			if(isset($file['quarter3_pdf']['name']) && !empty($file['quarter3_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter3_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter3_pdf = $SaveImage->uploadFileFromForm($file['quarter3_pdf'],$imgDir,$file_name);
			}else{
				$quarter3_pdf = '';
			}
			if(isset($file['quarter4_pdf']['name']) && !empty($file['quarter4_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter4_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$quarter4_pdf = $SaveImage->uploadFileFromForm($file['quarter4_pdf'],$imgDir,$file_name);
			}else{
				$quarter4_pdf = '';
			}
			
			$query = "INSERT INTO ".PREFIX."cg_report(`from_year`, `to_year`, `year`, `quarter1_name`, `quarter1_pdf`, `quarter2_name`, `quarter2_pdf`, `quarter3_name`, `quarter3_pdf`, `quarter4_name`, `quarter4_pdf`, `created`) VALUES ('".$from_year."','".$to_year."','".$year."','".$quarter1_name."','".$quarter1_pdf."','".$quarter2_name."','".$quarter2_pdf."','".$quarter3_name."','".$quarter3_pdf."','".$quarter4_name."','".$quarter4_pdf."','".$date."') ";
			return $this->query($query);
		} */
		/* function UpdateCgReport($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$quarter1_name = $this->escape_string($this->strip_all($data['quarter1_name']));
			$quarter2_name = $this->escape_string($this->strip_all($data['quarter2_name']));
			$quarter3_name = $this->escape_string($this->strip_all($data['quarter3_name']));
			$quarter4_name = $this->escape_string($this->strip_all($data['quarter4_name']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/cg_report/';
			if(isset($file['quarter1_pdf']['name']) && !empty($file['quarter1_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter1_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("cg_report", $Detail['quarter1_pdf']);
				$quarter1_pdf = $SaveImage->uploadFileFromForm($file['quarter1_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."cg_report set quarter1_pdf='$quarter1_pdf' where id='".$id."'");
			}
			if(isset($file['quarter2_pdf']['name']) && !empty($file['quarter2_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter2_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("cg_report", $Detail['quarter2_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter2_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."cg_report set quarter2_pdf='$quarter2_pdf' where id='".$id."'");
			}
			if(isset($file['quarter3_pdf']['name']) && !empty($file['quarter3_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter3_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("cg_report", $Detail['quarter3_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter3_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."cg_report set quarter3_pdf='$quarter3_pdf' where id='".$id."'");
			}
			if(isset($file['quarter4_pdf']['name']) && !empty($file['quarter4_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter4_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("cg_report", $Detail['quarter4_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter4_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."cg_report set quarter4_pdf='$quarter4_pdf' where id='".$id."'");
			}
			$query = "update ".PREFIX."cg_report set from_year='".$from_year."',to_year='".$to_year."',year='".$year."', quarter1_name='$quarter1_name', quarter2_name='".$quarter2_name."',quarter3_name='".$quarter3_name."',quarter4_name='".$quarter4_name."' where id='".$id."' ";
			return $this->query($query);
			
		} */
		
		
		// Governance Reports End
		
		// Board Meeting & AGM Start
		function getBoardMeetingCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."board_meeting_quarter where board_id = '".$id."'");
			return $this->num_rows($count);
		}
		function getBoardMeetingSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."board_meeting_quarter where board_id = '".$id."' order by display_order<>0 DESC");
			return $count;
		}
		function getBoardMeeting($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."board_meeting where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteBoardMeeting($id) {
			$id = $this->escape_string($this->strip_all($id));
			$res = $this->deleteBoardMeetingQuarter($id);
			$query = "delete from ".PREFIX."board_meeting where id='".$id."'";
			$this->query($query);
		}
		
		function AddBoardMeeting($data,$file) {
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/board_meeting/';
			$query = "INSERT INTO ".PREFIX."board_meeting(`from_year`, `to_year`, `year`,`created`) VALUES ('".$from_year."','".$to_year."','".$year."','".$date."') ";
			$res = $this->query($query);
			$board_id = $this->last_insert_id();
			$count = count($file['quarter_pdf']['name']);
			$property_images = count($file['quarter_pdf']['name']);
			for($key=0;$key<$count;$key++){
				$quarter_pdf = str_replace( " ", "-", $file['quarter_pdf']['name'][$key]);
				$upload = move_uploaded_file($file['quarter_pdf']['tmp_name'][$key],$imgDir.$quarter_pdf);
				if(!$upload){
					$quarter_pdf ="";
				}
				$quarter_type = $this->escape_string($this->strip_all($data['quarter_type'][$key]));
				$quarter_name = $this->escape_string($this->strip_all($data['quarter_name'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				$this->query("INSERT INTO ".PREFIX."board_meeting_quarter(`board_id`, `quarter_type`, `quarter_name`, `quarter_pdf`, `display_order`, `created`) VALUES ('".$board_id."','".$quarter_type."','".$quarter_name."','".$quarter_pdf."','".$display_order."','".$date."')");   
				
				} 
			
			
		}
		
		function UpdateBoardMeeting($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$quarter1_name = $this->escape_string($this->strip_all($data['quarter1_name']));
			$quarter2_name = $this->escape_string($this->strip_all($data['quarter2_name']));
			$quarter3_name = $this->escape_string($this->strip_all($data['quarter3_name']));
			$quarter4_name = $this->escape_string($this->strip_all($data['quarter4_name']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/board_meeting/';
			$query = "update ".PREFIX."board_meeting set from_year='".$from_year."',to_year='".$to_year."',year='".$year."' where id='".$id."' ";
			$res = $this->query($query);
			$board_id = $id;
			//$deleteRecord = $this->deleteCgReportQuarter($cg_id);
			$count = count($file['quarter_pdf']['name']);
			$property_images = count($file['quarter_pdf']['name']);
			for($key=0;$key<$count;$key++){
				$quarter_pdf = str_replace( " ", "-", $file['quarter_pdf']['name'][$key] );
				$upload = move_uploaded_file($file['quarter_pdf']['tmp_name'][$key],$imgDir.$quarter_pdf);
				if(!$upload){
					$quarter_pdf ="";
				}
				$quarter_id = $this->escape_string($this->strip_all($data['quarter_id'][$key]));
				$quarter_type = $this->escape_string($this->strip_all($data['quarter_type'][$key]));
				$quarter_name = $this->escape_string($this->strip_all($data['quarter_name'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				
				if(empty($quarter_id)){
				$this->query("INSERT INTO ".PREFIX."board_meeting_quarter(`board_id`, `quarter_type`, `quarter_name`, `quarter_pdf`, `display_order`, `created`) VALUES ('".$board_id."','".$quarter_type."','".$quarter_name."','".$quarter_pdf."','".$display_order."','".$date."')"); 
				}else if($quarter_pdf!=''){
					$this->query("Update ".PREFIX."board_meeting_quarter set quarter_type='".$quarter_type."',quarter_name='".$quarter_name."',display_order='".$display_order."',quarter_pdf='".$quarter_pdf."' where id='".$quarter_id."'"); 
				}else{
					$this->query("Update ".PREFIX."board_meeting_quarter set quarter_type='".$quarter_type."',quarter_name='".$quarter_name."',display_order='".$display_order."' where id='".$quarter_id."'"); 
				}
				
				} 
		}
		function deleteBoardMeetingQuarter($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "DELETE FROM ".PREFIX."board_meeting_quarter  where board_id='".$id."' ";
			$res = $this->query($query);
		}
		function deleteBoardMeetingQuarterRow($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "DELETE FROM ".PREFIX."board_meeting_quarter  where id='".$id."' ";
			$res = $this->query($query);
		}
		function deleteAgmRow($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "DELETE FROM ".PREFIX."agm_pdf  where id='".$id."' ";
			$res = $this->query($query);
		}
		function deleteCgReportRow($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "DELETE FROM ".PREFIX."cg_report_quarter  where id='".$id."' ";
			$res = $this->query($query);
		}
		/* function UpdateBoardMeeting($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$from_year = $this->escape_string($this->strip_all($data['from_year']));
			$to_year = $this->escape_string($this->strip_all($data['to_year']));
			$year = $from_year.'-'.$to_year;
			$quarter1_name = $this->escape_string($this->strip_all($data['quarter1_name']));
			$quarter2_name = $this->escape_string($this->strip_all($data['quarter2_name']));
			$quarter3_name = $this->escape_string($this->strip_all($data['quarter3_name']));
			$quarter4_name = $this->escape_string($this->strip_all($data['quarter4_name']));
			$SaveImage = new SaveImage();
			$imgDir = '../pdf/board_meeting/';
			if(isset($file['quarter1_pdf']['name']) && !empty($file['quarter1_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter1_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("board_meeting", $Detail['quarter1_pdf']);
				$quarter1_pdf = $SaveImage->uploadFileFromForm($file['quarter1_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."board_meeting set quarter1_pdf='$quarter1_pdf' where id='".$id."'");
			}
			if(isset($file['quarter2_pdf']['name']) && !empty($file['quarter2_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter2_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("board_meeting", $Detail['quarter2_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter2_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."board_meeting set quarter2_pdf='$quarter2_pdf' where id='".$id."'");
			}
			if(isset($file['quarter3_pdf']['name']) && !empty($file['quarter3_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter3_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("board_meeting", $Detail['quarter3_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter3_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."board_meeting set quarter3_pdf='$quarter3_pdf' where id='".$id."'");
			}
			if(isset($file['quarter4_pdf']['name']) && !empty($file['quarter4_pdf']['name'])) {
				$file_name = strtolower( pathinfo($file['quarter4_pdf']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getCgReport($id);
				$this->unlinkImage("board_meeting", $Detail['quarter4_pdf']);
				$quarter2_pdf = $SaveImage->uploadFileFromForm($file['quarter4_pdf'],$imgDir,$file_Name);
				$this->query("update ".PREFIX."board_meeting set quarter4_pdf='$quarter4_pdf' where id='".$id."'");
			}
			$query = "update ".PREFIX."board_meeting set from_year='".$from_year."',to_year='".$to_year."',year='".$year."', quarter1_name='$quarter1_name', quarter2_name='".$quarter2_name."',quarter3_name='".$quarter3_name."',quarter4_name='".$quarter4_name."' where id='".$id."' ";
			return $this->query($query);
			
		} */
		// Board Meeting & AGM END
		
		
		// === CATEGORY STARTS ===
		function getUniqueCategoryById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."category_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getAllCategories() {
			$query = "select * from ".PREFIX."category_master where active='Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		
		function getAllPackages() {
			$query = "select * from ".PREFIX."package_master order by display_order ASC";
			$sql = $this->query($query);
			return $sql;
		}
		function addCategory($data,$file){
			$category_name = $this->escape_string($this->strip_all($data['category_name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$permalink = $this->getValidatedPermalink($category_name);
			$SaveImage = new SaveImage();
			$imgDir = '../img/category/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 240, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			} 
			
			$imgDir2 = '../img/cat-banner/';
			if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name2']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData2']);
				$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 1366, $cropData, $imgDir2, $file_name.'-'.time().'-1');
			} else {
				$image_name2 = '';
			} 
			$query = "insert into ".PREFIX."category_master(image_name, category_name, permalink, active, image_name2, display_order) values ('".$image_name."','".$category_name."', '".$permalink."', '".$active."', '".$image_name2."', '".$display_order."')";
			$this->query($query);

			$category_id = $this->last_insert_id();

			
		}
		function updateCategory($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$category_name = $this->escape_string($this->strip_all($data['category_name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$permalink = $this->getValidatedPermalink($category_name);
			$Detail = $this->getUniqueCategoryById($id);
			$SaveImage = new SaveImage();
			$imgDir = '../img/category/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$cropData = $this->strip_all($data['cropData1']);
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$this->unlinkImage("category", $Detail['image_name'], "large");
				$this->unlinkImage("category", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 240, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."category_master set image_name='$image_name' where id='$id'");
			} 
			
			$imgDir2 = '../img/cat-banner/';
			if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])) {
				$cropData = $this->strip_all($data['cropData2']);
				$imageName = str_replace( " ", "-", $file['image_name2']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$this->unlinkImage("cat-banner", $Detail['image_name2'], "large");
				$this->unlinkImage("cat-banner", $Detail['image_name2'], "crop");
				$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 1366, $cropData, $imgDir2, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."category_master set image_name2='$image_name2' where id='$id'");
			} 
			$query = "update ".PREFIX."category_master set category_name='".$category_name."', permalink='".$permalink."', active='".$active."', display_order='".$display_order."' where id='".$id."'";
			$this->query($query);

			$category_id = $id;

		}
		function deleteCategory($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."category_master where id='".$id."'";
			$this->query($query);
			$sql = $this->query("select id from ".PREFIX."sub_category_master where category_id='$id'");
			while($result = $this->fetch($sql)) {
				$this->deleteSubCategory($result['id']);
			}
			$sql = $this->query("select id from ".PREFIX."product_master where category_id='$id'");
			while($detail = $this->fetch($sql)) {
				$product_id = $detail['id'];
				$this->deleteProduct($product_id);
			}
			
		}

		
		// === CATEGORY ENDS ===

		// === SUB CATEGORY STARTS ===
		function getUniqueSubCategoryById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."sub_category_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		/* function getCategoryBySubCategoryId($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."sub_category_master where category_id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		} */
		function getAllSubCategories($category_id) {
			$category_id = $this->escape_string($this->strip_all($category_id));
			$query = "select * from ".PREFIX."sub_category_master where category_id='$category_id' and active='Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		function addSubCategory($data,$file){
			$category_name = $this->escape_string($this->strip_all($data['name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$category_id = $this->escape_string($this->strip_all($data['category_id']));
			$category = $this->getUniqueCategoryById($category_id);
			$permalink = $this->getValidatedPermalink($category_name);
			$catParma = $this->getValidatedPermalink($category['category_name']);
			$display_order = $this->getValidatedPermalink($data['display_order']);
			
			$permalink = $catParma.'/'.$permalink;
			$SaveImage = new SaveImage();
			$imgDir = '../img/cat-banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 1350, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			} 
			$query = "insert into ".PREFIX."sub_category_master(image_name, category_id, sub_category_name, permalink, active, display_order) values ('".$image_name."','".$category_id."', '".$category_name."', '".$permalink."', '".$active."','".$display_order."')";
			$this->query($query);
		}
		function updateSubCategory($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$category_name = $this->escape_string($this->strip_all($data['name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$category_id = $this->escape_string($this->strip_all($data['category_id']));
			$category = $this->getUniqueCategoryById($category_id);
			$permalink = $this->getValidatedPermalink($category_name);
			$catParma = $this->getValidatedPermalink($category['category_name']);
			$display_order = $this->getValidatedPermalink($data['display_order']);
			
			$permalink = $catParma.'/'.$permalink;
			$Detail = $this->getUniqueSubCategoryById($id);
			$SaveImage = new SaveImage();
			$imgDir = '../img/cat-banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$cropData = $this->strip_all($data['cropData1']);
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$this->unlinkImage("cat-banner", $Detail['image_name'], "large");
				$this->unlinkImage("cat-banner", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 1350, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."sub_category_master set image_name='$image_name' where id='$id'");
			} 
			$query = "update ".PREFIX."sub_category_master set sub_category_name='".$category_name."', permalink='".$permalink."', active='".$active."',display_order='".$display_order."' where id='".$id."'";
			$this->query($query);
		}
		function deleteSubCategory($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."sub_category_master where id='".$id."'";
			$this->query($query);
			
			/* $sql = $this->query("select id from ".PREFIX."products where sub_category_id='$id'");
			while($detail = $this->fetch($sql)) {
				$product_id = $detail['id'];
				$this->deleteProduct($product_id);
			} */
			
		}
		
		function deleteSubSubCategory($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."sub_sub_category_master where id='".$id."'";
			$this->query($query);
			
			/* $sql = $this->query("select id from ".PREFIX."products where sub_category_id='$id'");
			while($detail = $this->fetch($sql)) {
				$product_id = $detail['id'];
				$this->deleteProduct($product_id);
			} */
			
		}
		// === SUB CATEGORY ENDS ===
		
		
		// === SUB SUB CATEGORY STARTS ===
		function getUniqueSubSubCategoryById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."sub_sub_category_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getAllSubSubCategories($category_id) {
			$category_id = $this->escape_string($this->strip_all($category_id));
			$query = "select * from ".PREFIX."sub_sub_category_master where sub_category_id='$category_id' and active='Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		function addSubSubCategory($data,$file){
			/* $category_name = $this->escape_string($this->strip_all($data['name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$category_id = $this->escape_string($this->strip_all($data['category_id']));
			$category = $this->getUniqueCategoryById($category_id);
			$permalink = $this->getValidatedPermalink($category_name);
			$catParma = $this->getValidatedPermalink($category['category_name']);
			
			$permalink = $catParma.'/'.$permalink;
			$SaveImage = new SaveImage();
			$imgDir = '../img/cat-banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 1000, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}  */
		
			$SaveImage = new SaveImage();
			$imgDir = '../img/cat-banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 1350, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			} 
			$name = $this->escape_string($this->strip_all($data['name']));
			$permalink = $this->getValidatedPermalink($name);
			$active = $this->escape_string($this->strip_all($data['active']));
			$sub_category_id = $this->escape_string($this->strip_all($data['sub_category_id']));
			$subCategory = $this->getUniqueSubCategoryById($sub_category_id);
			$permalink = $subCategory['permalink'].'/'.$permalink;
			
			$query = "insert into ".PREFIX."sub_sub_category_master(category_id, sub_category_id, image_name, sub_sub_category_name, active, permalink) values ('".$subCategory['category_id']."','".$sub_category_id."', '".$image_name."', '".$name."', '".$active."', '".$permalink."')";
			$this->query($query);
		}
		function updateSubSubCategory($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			
			/* $category_name = $this->escape_string($this->strip_all($data['name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$category_id = $this->escape_string($this->strip_all($data['category_id']));
			$category = $this->getUniqueCategoryById($category_id);
			$permalink = $this->getValidatedPermalink($category_name);
			$catParma = $this->getValidatedPermalink($category['category_name']);
			
			$permalink = $catParma.'/'.$permalink;
			  */
			$Detail = $this->getUniqueSubCategoryById($id);
			$SaveImage = new SaveImage();
			$imgDir = '../img/cat-banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$cropData = $this->strip_all($data['cropData1']);
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$this->unlinkImage("cat-banner", $Detail['image_name'], "large");
				$this->unlinkImage("cat-banner", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 1350, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."sub_sub_category_master set image_name='$image_name' where id='$id'");
			}
			
			$name = $this->escape_string($this->strip_all($data['name']));
			$permalink = $this->getValidatedPermalink($name);
			//echo $permalink;exit;
			$active = $this->escape_string($this->strip_all($data['active']));
			$sub_category_id = $this->escape_string($this->strip_all($data['sub_category_id']));
			$subCategory = $this->getUniqueSubCategoryById($sub_category_id);
			$permalink = $subCategory['permalink'].'/'.$permalink;
			$query = "update ".PREFIX."sub_sub_category_master set sub_sub_category_name='".$name."', active='".$active."', permalink='".$permalink."', category_id='".$subCategory['category_id']."' where id='".$id."'";
			$this->query($query);
		}
		/*function deleteSubSubCategory($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."sub_category_master where id='".$id."'";
			$this->query($query);
			
			 $sql = $this->query("select id from ".PREFIX."products where sub_category_id='$id'");
			while($detail = $this->fetch($sql)) {
				$product_id = $detail['id'];
				$this->deleteProduct($product_id);
			} 
			
		}*/
		// === SUB SUB CATEGORY ENDS ===
		
		
		
		
		// === PRODUCT STARTS ===
		function getAllProducts() {
			$query = "select * from ".PREFIX."product_master where active='Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		
		function getAllProductsShowInAboutsUs() {
			$query = "select * from ".PREFIX."product_master where active='Yes' and aboutus = 'Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		function isProductCodeUnique($product_code) {
			$product_code = $this->escape_string($this->strip_all($product_code));
			$query = "select * from ".PREFIX."product_master where product_code='".$product_code."'";
			$result = $this->query($query);
			// if($result->num_rows==1){ // exists // DEPRECATED
			if($result->num_rows>0){ // at lease one email exists 
				return false;
			} else {
				return true;
			}
		}
		function getUniqueProductById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."product_master where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueProduct($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."product_master where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getAllProductByCategoryId($categoryId) {
			$categoryId = $this->escape_string($this->strip_all($categoryId));
			$query = "select * from ".PREFIX."product_master where category_id='$categoryId'";
			return $this->query($query);
		}	
		function addProduct($data,$file) {
			
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$category_id = $this->escape_string($this->strip_all($data['category']));
			$sub_category_id = $this->escape_string($this->strip_all($data['sub_category']));
			$sub_sub_category_id = $this->escape_string($this->strip_all($data['sub_sub_category']));
			$product_name = $this->escape_string($this->strip_all($data['product_name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$season_product = $this->escape_string($this->strip_all($data['season_product']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$code = $this->escape_string($this->strip_all($data['code']));
			$aboutus = $this->escape_string($this->strip_all($data['aboutus']));
			//$aboutus = 'No';
			
			$permalink = $this->getValidatedPermalink($product_name);
			$categoryDetail = $this->getUniqueCategoryById($category_id);
			$catPermalink = $this->getValidatedPermalink($categoryDetail['category_name']);
			if(!empty($sub_category_id)) {
				$subcatDetail = $this->getUniqueSubCategoryById($sub_category_id);
				$subcatPermalink = $this->getValidatedPermalink($subcatDetail['sub_category_name']);
			} else {
				$subcatPermalink = '';
			}
			$permalink = $catPermalink.'/'.$subcatPermalink.'/'.$permalink.'/'.time();

			$SaveImage = new SaveImage();
			$imgDir = '../img/products/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 400, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			
			if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name2']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData2']);
				$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 400, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name2 = '';
			}
			
			if(isset($file['image_name3']['name']) && !empty($file['image_name3']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name3']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData3']);
				$image_name3 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name3'], 400, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name3 = '';
			}
			
			if(isset($file['image_name4']['name']) && !empty($file['image_name4']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name4']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData4']);
				$image_name4 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name4'], 400, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name4 = '';
			}
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$specification = $this->escape_string($this->strip_selected($data['specification'],$allowTags));
			$overview = $this->escape_string($this->strip_selected($data['overview'],$allowTags));
			
			$brochurePath = '../uploads/brochure/';
            
			if(isset($file['brochure']['name']) and !empty($file['brochure']['name'])) {
					$imageName = str_replace( " ", "-", $file['brochure']['name'] );
					$brochure_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
					//$registration_document = $this->getValidatedPermalink($registration_document);
					$brochure_ext = pathinfo($file['brochure']['name'], PATHINFO_EXTENSION);
					$brochure = $brochure_name.'.'.$brochure_ext;
					move_uploaded_file($file['brochure']['tmp_name'],$brochurePath.$brochure);
			} else {
				$brochure = '';
			}
			
			if(isset($file['complete_brochure']['name']) and !empty($file['complete_brochure']['name'])) {
					$brochure_name = strtolower( pathinfo($file['complete_brochure']['name'], PATHINFO_FILENAME));
					//$registration_document = $this->getValidatedPermalink($registration_document);
					$brochure_ext = pathinfo($file['complete_brochure']['name'], PATHINFO_EXTENSION);
					$complete_brochure = $brochure_name.'.'.$brochure_ext;
					move_uploaded_file($file['complete_brochure']['tmp_name'],$brochurePath.$complete_brochure);
			} else {
				$complete_brochure = '';
			}
			
			$query = "insert into ".PREFIX."product_master(image_name, image_name2, image_name3, image_name4, product_name, code, category_id, sub_category_id, sub_sub_category_id, aboutus, specification, overview, description, complete_brochure, brochure,  link, active,season_product, permalink)values('$image_name','$image_name2','$image_name3','$image_name4', '$product_name', '$code', '$category_id', '$sub_category_id', '$sub_sub_category_id', '$aboutus', '$specification', '$overview', '$description', '$complete_brochure', '$brochure', '$link', '$active','$season_product', '$permalink')";
			$this->query($query);
			
		}

		function updateProduct($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			$category_id = $this->escape_string($this->strip_all($data['category']));
			$sub_category_id = $this->escape_string($this->strip_all($data['sub_category']));
			$sub_sub_category_id = $this->escape_string($this->strip_all($data['sub_sub_category']));
			$product_name = $this->escape_string($this->strip_all($data['product_name']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$code = $this->escape_string($this->strip_all($data['code']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$season_product = $this->escape_string($this->strip_all($data['season_product']));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$specification = $this->escape_string($this->strip_selected($data['specification'],$allowTags));
			$overview = $this->escape_string($this->strip_selected($data['overview'],$allowTags));
			$Detail = $this->getUniqueProductById($id);
			$permalink = $this->getValidatedPermalink($product_name);
			$categoryDetail = $this->getUniqueCategoryById($category_id);
			$catPermalink = $this->getValidatedPermalink($categoryDetail['category_name']);
			$aboutus = $this->escape_string($this->strip_all($data['aboutus']));
			//$aboutus = 'No';
			if(!empty($sub_category_id)) {
				$subcatDetail = $this->getUniqueSubCategoryById($sub_category_id);
				$subcatPermalink = $this->getValidatedPermalink($subcatDetail['sub_category_name']);
			} else {
				$subcatPermalink = '';
			}
			$permalink = $catPermalink.'/'.$subcatPermalink.'/'.$permalink.'/'.time();

			$SaveImage = new SaveImage();
			$imgDir = '../img/products/';
			$Detail = $this->getUniqueProductById($id);
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$cropData = $this->strip_all($data['cropData1']);
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$this->unlinkImage("products", $Detail['image_name'], "large");
				$this->unlinkImage("products", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 400, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."product_master set image_name='$image_name' where id='$id'");
				$productUpdateArr[] = 'image_name';
			}

			if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])) {
				$cropData = $this->strip_all($data['cropData2']);
				$file_name = strtolower( pathinfo($file['image_name2']['name'], PATHINFO_FILENAME));
				$this->unlinkImage("products", $Detail['image_name2'], "large");
				$this->unlinkImage("products", $Detail['image_name2'], "crop");
				$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 400, $cropData, $imgDir, $file_name.'-'.time().'-2');
				$this->query("update ".PREFIX."product_master set image_name2='$image_name2' where id='$id'");
				$productUpdateArr[] = 'image_name2';
			}

			if(isset($file['image_name3']['name']) && !empty($file['image_name3']['name'])) {
				$cropData = $this->strip_all($data['cropData3']);
				$file_name = strtolower( pathinfo($file['image_name3']['name'], PATHINFO_FILENAME));
				$this->unlinkImage("products", $Detail['image_name3'], "large");
				$this->unlinkImage("products", $Detail['image_name3'], "crop");
				$image_name3 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name3'], 400, $cropData, $imgDir, $file_name.'-'.time().'-2');
				$this->query("update ".PREFIX."product_master set image_name3='$image_name3' where id='$id'");
				$productUpdateArr[] = 'image_name3';
			}

			if(isset($file['image_name4']['name']) && !empty($file['image_name4']['name'])) {
				$cropData = $this->strip_all($data['cropData4']);
				$file_name = strtolower( pathinfo($file['image_name4']['name'], PATHINFO_FILENAME));
				$this->unlinkImage("products", $Detail['image_name4'], "large");
				$this->unlinkImage("products", $Detail['image_name4'], "crop");
				$image_name4 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name4'], 400, $cropData, $imgDir, $file_name.'-'.time().'-2');
				$this->query("update ".PREFIX."product_master set image_name4='$image_name4' where id='$id'");
				$productUpdateArr[] = 'image_name4';
			}
			$brochurePath = '../uploads/brochure/';
			
			if(!empty($file['brochure']['name'])) {
					$brochure_name = strtolower(pathinfo($file['brochure']['name'], PATHINFO_FILENAME));
					$brochure_ext = pathinfo($file['brochure']['name'], PATHINFO_EXTENSION);
					$brochure = $brochure_name.'.'.$brochure_ext;
					if(file_exists($brochurePath.$brochure)) {
						unlink($brochurePath.$brochure);
					}
					move_uploaded_file($file['brochure']['tmp_name'],$brochurePath.$brochure);
					$this->query("update ".PREFIX."product_master set brochure = '$brochure' where id ='$id'");
			}
			
			if(!empty($file['complete_brochure']['name'])) {
					$brochure_name = strtolower(pathinfo($file['complete_brochure']['name'], PATHINFO_FILENAME));
					$brochure_ext = pathinfo($file['complete_brochure']['name'], PATHINFO_EXTENSION);
					$complete_brochure = $brochure_name.'.'.$brochure_ext;
					if(file_exists($brochurePath.$complete_brochure)) {
						unlink($brochurePath.$complete_brochure);
					}
					move_uploaded_file($file['complete_brochure']['tmp_name'],$brochurePath.$complete_brochure);
					$this->query("update ".PREFIX."product_master set complete_brochure = '$complete_brochure' where id ='$id'");
			}

			

			$query = "update ".PREFIX."product_master set category_id='$category_id', sub_category_id='$sub_category_id', sub_sub_category_id='$sub_sub_category_id', active='$active',season_product='$season_product', product_name='$product_name', permalink='$permalink', code='$code', specification='$specification', overview='$overview', description='$description', link='$link', aboutus='$aboutus' where id='$id'";
			$this->query($query);
			$product_id = $id;

			
		}

		
		function deleteProduct($product_id) {
			$product_id = $this->escape_string($this->strip_all($product_id));
			$Detail = $this->getUniqueProductById($product_id);
			$this->unlinkImage("products", $Detail['image_name'], "large");
			$this->unlinkImage("products", $Detail['image_name'], "crop");
			
			$this->query("delete from ".PREFIX."product_master where id='$product_id'");
			
		}
		
		
		// === PRODUCT ENDS ===
		
		// news
		
		function getUniqueNewsById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."news_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getAllNews() {
			
			$query = "select * from ".PREFIX."news_master where active='Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		function addNews($data,$file){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$active = $this->escape_string($this->strip_all($data['active']));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$permalink = $this->getValidatedPermalink($title);
			$permalink = $permalink.'/'.date('Ymd');
			
			$SaveImage = new SaveImage();
			$imgDir = '../images/news/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 435, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			
			$query = "insert into ".PREFIX."news_master(title, image_name, description, permalink, active) values ('".$title."','".$image_name."','".$description."', '".$permalink."','".$active."')";
			$this->query($query);
		}
		function updateNews($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$permalink = $this->getValidatedPermalink($title);
			$permalink = $permalink.'/'.date('Ymd');
			$SaveImage = new SaveImage();
			$imgDir = '../images/news/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueNewsById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("news", $Detail['image_name'], "large");
				$this->unlinkImage("news", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 435, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."news_master set image_name='$image_name' where id='$id'");
			}
			$query = "update ".PREFIX."news_master set title='".$title."', permalink='".$permalink."', active='".$active."', description='".$description."' where id='".$id."'";
			$this->query($query);
		}
		function deleteNews($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."news_master where id='".$id."'";
			$this->query($query);
			
			/* $sql = $this->query("select id from ".PREFIX."products where sub_category_id='$id'");
			while($detail = $this->fetch($sql)) {
				$product_id = $detail['id'];
				$this->deleteProduct($product_id);
			} */
			
		}
		// === SUB CATEGORY ENDS ===
		
		// end news
		
		// === About STARTS ===
		function getAboutus() {
			$query = "select * from ".PREFIX."aboutus";
			$sql = $this->query($query);
			return $sql;
		}
		
		function getUniqueAboutUsById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."aboutus where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getHomePageById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."home_page where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function addAboutUs($data,$file) {
			
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$title = $this->escape_string($this->strip_all($data['title']));
			$link = $this->escape_string($this->strip_all($data['link']));
			
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			
			//$videoPath = '../video/';
			$company_profilePath = '../uploads/profile/';
            
			/* if(isset($file['video_name']['name']) and !empty($file['video_name']['name'])) {
					$video_name = strtolower( pathinfo($file['video_name']['name'], PATHINFO_FILENAME));
					//$registration_document = $this->getValidatedPermalink($registration_document);
					$video_ext = pathinfo($file['video_name']['name'], PATHINFO_EXTENSION);
					$video = $video_name.'.'.$video_ext;
					move_uploaded_file($file['video_name']['tmp_name'],$videoPath.$video);
			} else {
					$video = '';
			} */
			
			if(isset($file['company_profile']['name']) and !empty($file['company_profile']['name'])) {
				$document = strtolower( pathinfo($file['company_profile']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['company_profile']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['company_profile']['tmp_name'],$company_profilePath.$upload);
			} else {
				$upload = '';
			}
			
			
			$query = "insert into ".PREFIX."aboutus(title, description, company_profile, link, title1, description2, title2, description3)values( '$title', '$description', '$upload', '$link', '$title1', '$description2', '$title2', '$description3')";
			$this->query($query);
			
		}
		function AddAboutusContent($data) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$date = date("Y-m-d h:i:s");
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$query = "insert into ".PREFIX."aboutus (title,description,display_order,created) values ('".$title."','".$description."','".$display_order."','".$date."')";
			$this->query($query);
			return $this->last_insert_id();
		}

		function updateAboutus($data) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$query = "UPDATE ".PREFIX."aboutus SET `description`='$description',`title`='$title',display_order='".$display_order."' where id='$id'";
			return $this->query($query);
		}
		function deleteAboutUsImage($id){
			$id = $this->escape_string($this->strip_all($id));
			$qry = "SELECT * FROM ".PREFIX."aboutus_export_image WHERE `id`='$id'";
			$res = $this->query($qry);
			$imgDir = '../img/about_img/';
			$Detail = $this->fetch($res);
			unlink($imgDir.$Detail['image_name']); 
			$query = "DELETE FROM ".PREFIX."aboutus_export_image WHERE `id`='$id'";
			
			return $this->query($query);
			
			
		}
		function updateHomePage($data,$file){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			//$link = $this->escape_string($this->strip_all($data['link']));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$SaveImage = new SaveImage();
			$imgDir = '../uploads/profile/';
			if(isset($file['company_profile']['name']) and !empty($file['company_profile']['name'])) {
				
				$detail = $this->getUniqueWelcomeById($id);
				unlink($company_profilePath.$detail['company_profile']);
				$file_name = strtolower( pathinfo($file['company_profile']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$image_name = $SaveImage->uploadFileFromForm($file['company_profile'],$imgDir,$file_Name);
				$res = $this->query("update ".PREFIX."home_page set company_profile='$image_name'");
			}
			$query = "update ".PREFIX."home_page set title='$title', description='$description' where id='$id'";
			$this->query($query);
			$home_id = $id;
			$this->deleteHomeVideoLinkbyId($home_id);
			foreach($data['video_link'] as $key=>$value) {
				$video_link = $this->escape_string($this->strip_all($data['video_link'][$key]));
				$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
				$this->query("insert into ".PREFIX."home_video_links(video_link, home_id, display_order) values ('$video_link', '$home_id', '$display_order')");
			}
			
		}
		function deleteHomeVideoLinkbyId($id) {
			
			$id = $this->escape_string($this->strip_all($id));
			
			$query = "delete from ".PREFIX."home_video_links where home_id='".$id."'";
			$this->query($query);
			
		}
		function getUniqueWelcomeById($id){
			$id = $this->escape_string($this->strip_all($id));
			$sql = "SELECT `company_profile` FROM ".PREFIX."home_page WHERE `id`='".$id."'";
			$this->query($sql);
		}

		
		function deleteAboutus($id) {
			$id = $this->escape_string($this->strip_all($id));
			$qry = "SELECT * FROM ".PREFIX."aboutus_export_image WHERE `aboutus_id`='$id'";
			$res = $this->query($qry);
			$imgDir = '../img/about_img/';
			$Detail = $this->fetch($res);
			unlink($imgDir.$Detail['image_name']);
			$this->query("delete from ".PREFIX."aboutus_export_image where aboutus_id='$id'");
			$this->query("delete from ".PREFIX."aboutus where id='$id'");
			
		}
		
		
		// === PRODUCT ENDS ===
		
		// About us cms
		
		function UpdateAboutusCMS($data){
			//print_r($data); exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$history 					= $this->escape_string($this->strip_selected($data['history'],$allowTags));
			$awards_recognizations 		= $this->escape_string($this->strip_selected($data['awards_recognizations'],$allowTags));
			$leadership_team 			= $this->escape_string($this->strip_selected($data['leadership_team'],$allowTags));
			$our_presence 				= $this->escape_string($this->strip_selected($data['our_presence'],$allowTags));
			$community 					= $this->escape_string($this->strip_selected($data['community'],$allowTags));
			$group_companies 			= $this->escape_string($this->strip_selected($data['group_companies'],$allowTags));
		
			
			$updateId 					= $this->escape_string($this->strip_all($data['updateid']));
			
			if(!empty($updateId)){
				$update = "UPDATE ".PREFIX."aboutus_cms SET `history`='".$history."',`awards_recognizations`='".$awards_recognizations."',`leadership_team`='".$leadership_team."',`our_presence`='".$our_presence."',`community`='".$community."',`group_companies`='".$group_companies."' WHERE id='".$updateId."'";
				$this->query($update);
			}else{
				$insert = "INSERT INTO ".PREFIX."aboutus_cms(`history`, `awards_recognizations`, `leadership_team`, `our_presence`, `community`, `group_companies`) VALUES ('".$history."','".$awards_recognizations."','".$leadership_team."','".$our_presence."','".$community."','".$group_companies."')";
				$this->query($insert);
			}
			
		}
		// end about us cms
		
		// investors
		// Audited Results
		
		function getUniqueAuditedResultsById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."audited_results where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
			
		}
		
		function addAuditedResults($data,$file){
			$name = $this->escape_string($this->strip_all($data['name']));
			$imgDir = "../uploads/audited/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
			} else {
				$upload = '';
			}
			
			$query = "insert into ".PREFIX."audited_results (name,document) values ('$name','$upload')";
			return $this->query($query);
		
		}
		
		function updateAuditedResults($data,$file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$imgDir = "../uploads/audited/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				
				$detail = $this->getUniqueAuditedResultsById($id);
				unlink($imgDir.$detail['document']);
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
				//$query = "insert into ".PREFIX."audited_results (name,document) values ('$name','$upload')";
				$this->query("update ".PREFIX."audited_results set document = '".$upload."' where id = '".$id."'");
			}
			
			$query = "update ".PREFIX."audited_results set name = '".$name."' where id = '".$id."'";
			return $this->query($query);
		
		}
		
		function deleteAuditedResults($id) {
			$imgDir = "../uploads/audited/";
			$id = $this->escape_string($this->strip_all($id));
			$detail = $this->getUniqueAuditedResultsById($id);
			unlink($imgDir.$detail['document']);
			$query = "delete from ".PREFIX."audited_results where id='".$id."'";
			$this->query($query);
			
		}
		
		// End Audited Results
		
		// UnAudited Results
		
		function getUniqueUnAuditedResultsById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."unaudited_results where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
			
		}
		
		function addUnAuditedResults($data,$file){
			$name = $this->escape_string($this->strip_all($data['name']));
			$quarter = $this->escape_string($this->strip_all($data['quarter']));
			$imgDir = "../uploads/unaudited/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
			} else {
				$upload = '';
			}
			
			$query = "insert into ".PREFIX."unaudited_results (name,quarter,document) values ('$name','$quarter','$upload')";
			return $this->query($query);
		
		}
		
		function updateUnAuditedResults($data,$file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$quarter = $this->escape_string($this->strip_all($data['quarter']));
			$imgDir = "../uploads/unaudited/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				
				$detail = $this->getUniqueAuditedResultsById($id);
				unlink($imgDir.$detail['document']);
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
				//$query = "insert into ".PREFIX."audited_results (name,document) values ('$name','$upload')";
				$this->query("update ".PREFIX."unaudited_results set document = '".$upload."' where id = '".$id."'");
			}
			
			$query = "update ".PREFIX."unaudited_results set name = '".$name."',quarter = '".$quarter."' where id = '".$id."'";
			return $this->query($query);
		
		}
		
		function deleteUnAuditedResults($id) {
			$imgDir = "../uploads/unaudited/";
			$id = $this->escape_string($this->strip_all($id));
			$detail = $this->getUniqueUnAuditedResultsById($id);
			unlink($imgDir.$detail['document']);
			$query = "delete from ".PREFIX."unaudited_results where id='".$id."'";
			$this->query($query);
			
		}
		
		// end unaudited result
		
		// CG REPORTS
		
		function getUniqueCgReportsById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."cg_reports where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
			
		}
		
		function addCgReports($data,$file){
			$name = $this->escape_string($this->strip_all($data['name']));
			$imgDir = "../uploads/cg-reports/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
			} else {
				$upload = '';
			}
			
			$query = "insert into ".PREFIX."cg_reports(name,document) values ('$name','$upload')";
			return $this->query($query);
		
		}
		
		function updateCgReports($data,$file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$imgDir = "../uploads/cg-reports/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				
				$detail = $this->getUniqueCgReportsById($id);
				unlink($imgDir.$detail['document']);
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
				//$query = "insert into ".PREFIX."audited_results (name,document) values ('$name','$upload')";
				$this->query("update ".PREFIX."cg_reports set document = '".$upload."' where id = '".$id."'");
			}
			
			$query = "update ".PREFIX."cg_reports set name = '".$name."' where id = '".$id."'";
			return $this->query($query);
		
		}
		
		
		
		// End Audited Results
		
		// BoardMeetingIntimation
		
		
		function getUniqueBoardMeetingIntimationById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."board_meeting_intimation where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
			
		}
		
		function addBoardMeetingIntimation($data,$file){
			$name = $this->escape_string($this->strip_all($data['name']));
			//$year = $this->escape_string($this->strip_all($data['year']));
			$imgDir = "../uploads/intimation/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
			} else {
				$upload = '';
			}
			
			$query = "insert into ".PREFIX."board_meeting_intimation(name,document) values ('$name','$upload')";
			return $this->query($query);
		
		}
		
		function updateBoardMeetingIntimation($data,$file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$name = $this->escape_string($this->strip_all($data['name']));
			//$year = $this->escape_string($this->strip_all($data['year']));
			
			$imgDir = "../uploads/intimation/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				
				$detail = $this->getUniqueBoardMeetingIntimationById($id);
				unlink($imgDir.$detail['document']);
				$document = strtolower( pathinfo($file['document']['name'], PATHINFO_FILENAME));
				$document_name = $this->getValidatedPermalink($document);
				$document_ext = pathinfo($file['document']['name'], PATHINFO_EXTENSION);
				$upload = $document_name.'.'.$document_ext;
				move_uploaded_file($file['document']['tmp_name'],$imgDir.$upload);
				//$query = "insert into ".PREFIX."audited_results (name,document) values ('$name','$upload')";
				$this->query("update ".PREFIX."board_meeting_intimation set document = '".$upload."' where id = '".$id."'");
			}
			
			$query = "update ".PREFIX."board_meeting_intimation set name = '".$name."' where id = '".$id."'";
			return $this->query($query);
		
		}
		
		function deleteBoardMeetingIntimation($id) {
			$imgDir = "../uploads/intimation/";
			$id = $this->escape_string($this->strip_all($id));
			$detail = $this->getUniqueBoardMeetingIntimationById($id);
			unlink($imgDir.$detail['document']);
			$query = "delete from ".PREFIX."board_meeting_intimation where id='".$id."'";
			return $this->query($query);
			
		}
		
		// End Audited Results
		//
		
		
		// Codes & Policies
		
		function getUniqueCodesPoliciesById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."codes_policies where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
			
		}
		
		function addCodesPolicies($data,$file){
			$name = $this->escape_string($this->strip_all($data['name']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = "../uploads/codes_policies/";
			if(isset($file['document']['name']) && !empty($file['document']['name'])) {
				$pdfName = str_replace( " ", "-", $file['document']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$upload = $SaveImage->uploadFileFromForm($file['document'],$imgDir,$file_name.'-'.time());
			}else{
				$upload = '';
			}
			
			
			$query = "insert into ".PREFIX."codes_policies (name,document,display_order) values ('$name','$upload','$display_order')";
			return $this->query($query);
		
		}
		
		function updateCodesPolicies($data,$file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = "../uploads/codes_policies/";
			if(isset($file['document']['name']) and !empty($file['document']['name'])) {
				
				$detail = $this->getUniqueCodesPoliciesById($id);
				unlink($imgDir.$detail['document']);
				$pdfName = str_replace( " ", "-", $file['document']['name']);
				$file_name = strtolower( pathinfo($pdfName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$upload = $SaveImage->uploadFileFromForm($file['document'],$imgDir,$file_name.'-'.time());
				//$query = "insert into ".PREFIX."audited_results (name,document) values ('$name','$upload')";
				$this->query("update ".PREFIX."codes_policies set document = '".$upload."' where id = '".$id."'");
			}
			
			$query = "update ".PREFIX."codes_policies set name = '".$name."',display_order='".$display_order."' where id = '".$id."'";
			return $this->query($query);
		
		}
		
		function deleteCodesPolicies($id) {
			$imgDir = "../uploads/codes_policies/";
			$id = $this->escape_string($this->strip_all($id));
			$detail = $this->getUniqueCodesPoliciesById($id);
			unlink($imgDir.$detail['document']);
			$query = "delete from ".PREFIX."codes_policies where id='".$id."'";
			$this->query($query);
			
		}
		
		// End Codes & Policies
		
		//
		function UpdatelistingAndOtherInformation($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$address = $this->escape_string($this->strip_all($data['address']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$stock_code = $this->escape_string($this->strip_all($data['stock_code']));
			$cin_no = $this->escape_string($this->strip_all($data['cin_no']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			//$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$updateId= $this->escape_string($this->strip_all($data['id']));
			
			
			$update = "UPDATE ".PREFIX."listingAndOtherInformation SET `name`='$name',`address`='".$address."',`stock_code`='".$stock_code."',`cin_no`='".$cin_no."',display_order='".$display_order."' WHERE id='".$updateId."'";
				$this->query($update);
		}
		function AddlistingAndOtherInformation($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$address = $this->escape_string($this->strip_all($data['address']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$stock_code = $this->escape_string($this->strip_all($data['stock_code']));
			$cin_no = $this->escape_string($this->strip_all($data['cin_no']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			//$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$date= date("Y-m-d h:i:s");
			
			
			$update = "insert into ".PREFIX."listingAndOtherInformation (`name`, `address`, `stock_code`, `cin_no`, `display_order`, `created`) values ('".$name."','".$address."','".$stock_code."','".$cin_no."','".$display_order."','".$date."')";
				$this->query($update);
		}
		
		//
		//end invester
		
		// career
		
		function getUniqueCareerById($id){
			$id = $this->escape_string($this->strip_all($data['id']));
			$query = "select * from ".PREFIX."career where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
			
		}
		
		function updateCareer($data,$file){
			//print_r($data); exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$title 	= $this->escape_string($this->strip_all($data['title']));
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$resume_email = $this->escape_string($this->strip_all($data['resume_email']));
			$query_email = $this->escape_string($this->strip_all($data['query_email']));
			
			$updateId= $this->escape_string($this->strip_all($data['updateid']));
			
			$SaveImage = new SaveImage();
			$imgDir = '../images/career/';
			
			if(!empty($updateId)){
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
					$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
					$file_name = $this->getValidatedPermalink($file_name);
					$Detail = $this->getUniqueCareerById($updateId);
					$cropData = $this->strip_all($data['cropData1']);
					$this->unlinkImage("career", $Detail['image_name'], "large");
					$this->unlinkImage("career", $Detail['image_name'], "crop");
					$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 380, $cropData, $imgDir, $file_name.'-'.time().'-1');
					$this->query("update ".PREFIX."career set image_name='$image_name' where id='$updateId'");
				}
			
				$update = "UPDATE ".PREFIX."career SET `title`='".$title."',`description`='".$description."',`resume_email`='".$resume_email."',query_email='".$query_email."' WHERE id='".$updateId."'";
				$this->query($update);
			}else{
				
				
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
					$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
					$file_name = $this->getValidatedPermalink($file_name);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 380, $cropData, $imgDir, $file_name.'-'.time().'-1');
				} else {
					$image_name = '';
				}
				$insert = "INSERT INTO ".PREFIX."career(`title`, `resume_email`,`query_email`, `image_name`, `description`) VALUES ('".$title."','".$resume_email."','".$query_email."','".$image_name."','".$description."')";
				$this->query($insert);
			}
			
		}
		
		// end career
		
		// Contact us cms
		
		function UpdateContactusCMS($data){
			
			$coporate_address 	=$this->escape_string($this->strip_all($data['coporate_address']));
			$coporate_phone_no 	= $this->escape_string($this->strip_all($data['coporate_phone_no']));
			$coporate_fax_no 	= $this->escape_string($this->strip_all($data['coporate_fax_no']));
			$corporate_map = $this->escape_string($this->strip_all($data['corporate_map']));
			$registered_address = $this->escape_string($this->strip_all($data['registered_address']));
			$registered_website = $this->escape_string($this->strip_all($data['registered_website']));
			$registered_email = $this->escape_string($this->strip_all($data['registered_email']));
			$registered_map = $this->escape_string($this->strip_all($data['registered_map']));
			
			
			
				$update = "UPDATE ".PREFIX."contactus_cms SET `coporate_address`='".$coporate_address."',`coporate_phone_no`='".$coporate_phone_no."',`coporate_fax_no`='".$coporate_fax_no."',`corporate_map`='".$corporate_map."',`registered_address`='".$registered_address."',registered_website='".$registered_website."',registered_email='".$registered_email."',registered_map='".$registered_map."'";
				$this->query($update);
			
			
		}
		// end Contact us cms
		
		// Group Companies
		
		function updateGroupCompanies($data){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$text1 		= $this->escape_string($this->strip_selected($data['text1'],$allowTags));
			$text2 		= $this->escape_string($this->strip_selected($data['text2'],$allowTags));
			$text3 		= $this->escape_string($this->strip_selected($data['text3'],$allowTags));
			$text4 		= $this->escape_string($this->strip_selected($data['text4'],$allowTags));
			
			
			$updateId 	= $this->escape_string($this->strip_all($data['updateId']));
			
			if(!empty($updateId)){
				$update = "UPDATE ".PREFIX."group_companies SET `text1`='".$text1."',`text2`='".$text2."',`text3`='".$text3."',`text4`='".$text4."' WHERE id='".$updateId."'";
				$this->query($update);
			}else{
				$insert = "INSERT INTO ".PREFIX."group_companies(`text1`, `text2`, `text3`, `text4`) VALUES ('".$text1."','".$text2."','".$text3."','".$text4."')";
				$this->query($insert);
			}
			
		}
		// end Group Companies
		// Social Links 
		function updateSocialLinks($data){
			
			$facebook = $this->escape_string($this->strip_all($data['facebook']));
			$twitter = $this->escape_string($this->strip_all($data['twitter']));
			$linkedin = $this->escape_string($this->strip_all($data['linkedin']));
			$instagram = $this->escape_string($this->strip_all($data['instagram']));
			$youtube = $this->escape_string($this->strip_all($data['youtube']));
			$date = date("Y-m-d h:i:s");
			$updateId 	= $this->escape_string($this->strip_all($data['updateId']));
			
			if(!empty($updateId)){
				$update = "UPDATE ".PREFIX."social_links SET `facebook`='".$facebook."',`twitter`='".$twitter."',`linkedin`='".$linkedin."',`instagram`='".$instagram."',youtube='".$youtube."' WHERE id='".$updateId."'";
				$this->query($update);
			}else{
				$insert = "INSERT INTO ".PREFIX."social_links( `facebook`, `twitter`, `linkedin`, `instagram`, `youtube`, `created`) VALUES ('".$facebook."','".$twitter."','".$linkedin."','".$instagram."','".$youtube."','".$date."')";
				$this->query($insert);
			}
			
		}
		// End Social Links
		
		// Our Presence
		
		function getUniqueOurPresenceById(){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."our_presence where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function updateOurPresence($data,$file){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			
			$address1 		= $this->escape_string($this->strip_selected($data['address1'],$allowTags));
			$address2 		= $this->escape_string($this->strip_selected($data['address2'],$allowTags));
			
			$updateId 	= $this->escape_string($this->strip_all($data['updateid']));
			
			$SaveImage = new SaveImage();
			$imgDir = '../images/our_presence/';
			
			if(!empty($updateId)){
				if(isset($file['image_name1']['name']) && !empty($file['image_name1']['name'])) {
					$file_name1 = strtolower( pathinfo($file['image_name1']['name'], PATHINFO_FILENAME));
					$file_name1 = $this->getValidatedPermalink($file_name1);
					$Detail = $this->getUniqueOurPresenceById($updateId);
					$cropData = $this->strip_all($data['cropData1']);
					$this->unlinkImage("our_presence", $Detail['image_name1'], "large");
					$this->unlinkImage("our_presence", $Detail['image_name1'], "crop");
					$image_name1 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name1'], 500, $cropData, $imgDir, $file_name1.'-'.time().'-1');
					$this->query("update ".PREFIX."our_presence set image_name1='$image_name1' where id='$updateId'");
				}
				
				if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])) {
					$file_name2 = strtolower( pathinfo($file['image_name2']['name'], PATHINFO_FILENAME));
					$file_name2 = $this->getValidatedPermalink($file_name2);
					$Detail = $this->getUniqueOurPresenceById($updateId);
					$cropData = $this->strip_all($data['cropData1']);
					$this->unlinkImage("our_presence", $Detail['image_name2'], "large");
					$this->unlinkImage("our_presence", $Detail['image_name2'], "crop");
					$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 1366, $cropData, $imgDir, $file_name2.'-'.time().'-1');
					$this->query("update ".PREFIX."our_presence set image_name2='$image_name2' where id='$updateId'");
				}
			
				$update = "UPDATE ".PREFIX."our_presence SET address1= '".$address1."' , address2= '".$address2."' WHERE id='".$updateId."'";
				$this->query($update);
			}else{
				
				
				if(isset($file['image_name1']['name']) && !empty($file['image_name1']['name'])){
					$file_name1 = strtolower( pathinfo($file['image_name1']['name'], PATHINFO_FILENAME));
					$file_name1 = $this->getValidatedPermalink($file_name1);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name1 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name1'], 1366, $cropData, $imgDir, $file_name1.'-'.time().'-1');
				} else {
					$image_name1 = '';
				}
				
				if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])){
					$file_name2 = strtolower( pathinfo($file['image_name2']['name'], PATHINFO_FILENAME));
					$file_name2 = $this->getValidatedPermalink($file_name2);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 1366, $cropData, $imgDir, $file_name2.'-'.time().'-1');
				} else {
					$image_name2 = '';
				}
				$insert = "INSERT INTO ".PREFIX."our_presence(`address1`, `image_name1`, `address2`, `image_name2`) VALUES ('".$address1."','".$image_name1."','".$address2."','".$image_name2."')";
				$this->query($insert);
			}
			
			
			
		}
		// end  Our Presence
		
		// addCommunityInitiatives
		
		function getUniqueCommunityInitiatives($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."community_initiatives where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function getUniqueOurInitiatives($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."our_initiatives where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function addCommunityInitiatives($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->escape_string($data['description'],$allowTags));
			
			$query = "insert into ".PREFIX."community_initiatives(description) values ('$description')";
			return $this->query($query);
			
		}
		function updateCommunityInitiatives($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			//$id = $this->escape_string($this->strip_all($data['id']));
			$update = "UPDATE ".PREFIX."community_initiatives SET description= '".$description."'";
			return $this->query($update);
			
		}
		// addOurCommunityInitiatives
		
		/* function getUniqueOurInitiatives($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."our_initiatives where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		} */
		/* function getUniqueOurInitiatives($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."our_initiatives where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		} */
		
		function addOurInitiatives($data,$file){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$description 	= $this->escape_string($this->strip_selected($data['description'],$allowTags));
			//$cummunity_id = $this->escape_string($this->strip_all($data['cummunity_id']));
			$year = $this->escape_string($this->strip_all($data['year']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/our_initiative/';
				if(isset($file['image_name1']['name']) && !empty($file['image_name1']['name'])){
					$image_name1 = strtolower( pathinfo($file['image_name1']['name'], PATHINFO_FILENAME));
					$image_name1 = $this->getValidatedPermalink($image_name1);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name1 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name1'], 339, $cropData, $imgDir, $image_name1.'-'.time().'-1');
				} else {
					$image_name1 = '';
				}
				 if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])){
					$image_name2 = strtolower( pathinfo($file['image_name2']['name'], PATHINFO_FILENAME));
					$image_name2 = $this->getValidatedPermalink($image_name2);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 339, $cropData, $imgDir, $image_name2.'-'.time().'-1');
				} else {
					$image_name2 = '';
				}
				if(isset($file['image_name3']['name']) && !empty($file['image_name3']['name'])){
					$image_name3 = strtolower( pathinfo($file['image_name3']['name'], PATHINFO_FILENAME));
					$image_name3 = $this->getValidatedPermalink($image_name3);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name3 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name3'], 339, $cropData, $imgDir, $image_name3.'-'.time().'-1');
				} else {
					$image_name3 = '';
				} 
				if(isset($file['image_name4']['name']) && !empty($file['image_name4']['name'])){
					$image_name4 = strtolower( pathinfo($file['image_name4']['name'], PATHINFO_FILENAME));
					$image_name4 = $this->getValidatedPermalink($image_name4);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name4 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name4'], 339, $cropData, $imgDir, $image_name4.'-'.time().'-1');
				} else {
					$image_name4 = '';
				} 
				if(isset($file['image_name5']['name']) && !empty($file['image_name5']['name'])){
					$image_name5 = strtolower( pathinfo($file['image_name5']['name'], PATHINFO_FILENAME));
					$image_name5 = $this->getValidatedPermalink($image_name5);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name5 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name5'], 339, $cropData, $imgDir, $image_name5.'-'.time().'-1');
				} else {
					$image_name5 = '';
				} 
				if(isset($file['image_name6']['name']) && !empty($file['image_name6']['name'])){
					$image_name6 = strtolower( pathinfo($file['image_name6']['name'], PATHINFO_FILENAME));
					$image_name6 = $this->getValidatedPermalink($image_name6);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name6 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name6'], 339, $cropData, $imgDir, $image_name6.'-'.time().'-1');
				} else {
					$image_name6 = '';
				} 
			$query = "insert into ".PREFIX."our_initiatives(description,image_name1,image_name2,image_name3,image_name4,image_name5,image_name6,year) values ('$description','$image_name1','$image_name2','$image_name3','$image_name4','$image_name5','$image_name6','$year')";
			return $this->query($query);
		}
		function UpdateOurInitiatives($data,$file){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$description 	= $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$id = $this->escape_string($this->strip_all($data['id']));
			$year = $this->escape_string($this->strip_all($data['year']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/our_initiative/';
				if(isset($file['image_name1']['name']) && !empty($file['image_name1']['name'])){
					$image_name1 = strtolower( pathinfo($file['image_name1']['name'], PATHINFO_FILENAME));
					$image_name1 = $this->getValidatedPermalink($image_name1);
					$Detail = $this->getUniqueOurInitiatives($id);
					$this->unlinkImage("our_initiative", $Detail['image_name1']);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name1 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name1'], 339, $cropData, $imgDir, $image_name1.'-'.time().'-1');
					$query = $this->query("update ".PREFIX."our_initiatives set image_name1='$image_name1' where id='$id'");
				}
				 if(isset($file['image_name2']['name']) && !empty($file['image_name2']['name'])){
					$image_name2 = strtolower( pathinfo($file['image_name2']['name'], PATHINFO_FILENAME));
					$image_name2 = $this->getValidatedPermalink($image_name2);
					$Detail = $this->getUniqueOurInitiatives($id);
					$this->unlinkImage("our_initiative", $Detail['image_name2']);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name2 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name2'], 339, $cropData, $imgDir, $image_name2.'-'.time().'-1');
					$query = $this->query("update ".PREFIX."our_initiatives set image_name2='$image_name2' where id='$id'");
				} 
				if(isset($file['image_name3']['name']) && !empty($file['image_name3']['name'])){
					$image_name3 = strtolower( pathinfo($file['image_name3']['name'], PATHINFO_FILENAME));
					$image_name3 = $this->getValidatedPermalink($image_name3);
					$Detail = $this->getUniqueOurInitiatives($id);
					$this->unlinkImage("our_initiative", $Detail['image_name3']);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name3 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name3'], 339, $cropData, $imgDir, $image_name3.'-'.time().'-1');
					$query = $this->query("update ".PREFIX."our_initiatives set image_name3='$image_name3' where id='$id'");
				}
				if(isset($file['image_name4']['name']) && !empty($file['image_name4']['name'])){
					$image_name4 = strtolower( pathinfo($file['image_name4']['name'], PATHINFO_FILENAME));
					$image_name4 = $this->getValidatedPermalink($image_name4);
					$Detail = $this->getUniqueOurInitiatives($id);
					$this->unlinkImage("our_initiative", $Detail['image_name4']);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name4 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name4'], 339, $cropData, $imgDir, $image_name4.'-'.time().'-1');
					$query = $this->query("update ".PREFIX."our_initiatives set image_name4='$image_name4' where id='$id'");
				}
				if(isset($file['image_name5']['name']) && !empty($file['image_name5']['name'])){
					$image_name5 = strtolower( pathinfo($file['image_name5']['name'], PATHINFO_FILENAME));
					$image_name5 = $this->getValidatedPermalink($image_name5);
					$Detail = $this->getUniqueOurInitiatives($id);
					$this->unlinkImage("our_initiative", $Detail['image_name5']);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name5 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name5'], 339, $cropData, $imgDir, $image_name5.'-'.time().'-1');
					$query = $this->query("update ".PREFIX."our_initiatives set image_name5='$image_name5' where id='$id'");
				}
				if(isset($file['image_name6']['name']) && !empty($file['image_name6']['name'])){
					$image_name6 = strtolower( pathinfo($file['image_name6']['name'], PATHINFO_FILENAME));
					$image_name6 = $this->getValidatedPermalink($image_name6);
					$Detail = $this->getUniqueOurInitiatives($id);
					$this->unlinkImage("our_initiative", $Detail['image_name6']);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name6 = $SaveImage->uploadCroppedImageFileFromForm($file['image_name6'], 339, $cropData, $imgDir, $image_name6.'-'.time().'-1');
					$query = $this->query("update ".PREFIX."our_initiatives set image_name6='$image_name6' where id='$id'");
				}
			$query = "update ".PREFIX."our_initiatives set description='$description',year='$year' where id='$id'";
			return $this->query($query);
		}
		
		
		/* function updateCommunityInitiatives($data){
			$description = $this->escape_string($this->strip_all($data['description']));
			$id = $this->escape_string($this->strip_all($data['id']));
			$update = "UPDATE ".PREFIX."community_initiatives SET description= '".$description."' WHERE id='".$id."'";
			return $this->query($update);
			
		} */
		
		function deleteOurInitiatives($id) {
			
			$id = $this->escape_string($this->strip_all($id));
			
			$query = "delete from ".PREFIX."our_initiatives where id='".$id."'";
			$this->query($query);
			
		}
		
		
		// our team
		
		function getUniqueOurLeadership($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."leadership_team where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		function getTeamRowCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."team where leader_id = '".$id."'");
			return $this->num_rows($count);
			
		}
		
		function getTeamSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."team where leader_id = '".$id."'");
			return $count;
			
		}
		
		function updateLeaderShip($data,$file){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$title 	= $this->escape_string($this->strip_all($data['title']));
			$image_person 	= $this->escape_string($this->strip_all($data['image_person']));
			$imageDescription 	= $this->escape_string($this->strip_selected($data['imageDescription'],$allowTags));
			$updateId 	= $this->escape_string($this->strip_all($data['updateid']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../images/team/';
			
			if(!empty($updateId)){
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
					$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
					$file_name = $this->getValidatedPermalink($file_name);
					$Detail = $this->getUniqueOurLeadership($updateId);
					$cropData = $this->strip_all($data['cropData1']);
					$this->unlinkImage("team", $Detail['image_name'], "large");
					$this->unlinkImage("team", $Detail['image_name'], "crop");
					$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 585, $cropData, $imgDir, $file_name.'-'.time().'-1');
					$this->query("update ".PREFIX."leadership_team set image_name='$image_name' where id='$updateId'");
				}
				
				$update = "UPDATE ".PREFIX."leadership_team SET title= '".$title."',image_person='".$image_person."', description= '".$imageDescription."' WHERE id='".$updateId."'";
				$this->query($update);
				
				$leader_id = $updateId;

				/*--- insert team */			
				$this->deleteTeamByLeaderId($leader_id);
				foreach($data['name'] as $key=>$value) {
					$name = $this->escape_string($this->strip_all($data['name'][$key]));
					$designation = $this->escape_string($this->strip_all($data['designation'][$key]));
					$description = $this->escape_string($this->strip_all($data['desc'][$key]));
					
					$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
					$this->query("insert into ".PREFIX."team(name, designation, description, display_order, leader_id,created) values ('$name', '$designation', '$description', '$display_order', '$leader_id','$date')");
				}
			}else{
			
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
					$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
					$file_name = $this->getValidatedPermalink($file_name);
					$cropData = $this->strip_all($data['cropData1']);
					$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 1366, $cropData, $imgDir, $file_name.'-'.time().'-1');
				} else {
					$image_name = '';
				}
				$insert = "INSERT INTO ".PREFIX."leadership_team(`title`, `description`, `image_name`) VALUES ('".$title."','".$imageDescription."','".$image_name."')";
				$this->query($insert);
				$leader_id = $this->last_insert_id();

				foreach($data['name'] as $key=>$value) {
					$name = $this->escape_string($this->strip_all($data['name'][$key]));
					$designation = $this->escape_string($this->strip_all($data['designation'][$key]));
					$description = $this->escape_string($this->strip_all($data['desc'][$key]));
					
					$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
					$this->query("insert into ".PREFIX."team(name, designation, description, display_order, leader_id) values ('$name', '$designation', '$description', '$display_order', '$leader_id')");
				}
			}
			
			
			
		}
		function updateBoardContent($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$title 	= $this->escape_string($this->strip_selected($data['title'],$allowTags));
			$updateId 	= $this->escape_string($this->strip_all($data['updateid']));
			$update = "UPDATE ".PREFIX."board_of_director_content SET title= '".$title."'";
				$this->query($update);
		}
		function updateHistory($data,$file){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			
			$title 	= $this->escape_string($this->strip_all($data['title']));
			$name 	= $this->escape_string($this->strip_all($data['name']));
			$position 	= $this->escape_string($this->strip_all($data['position']));
			$description 	= $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$updateId 	= $this->escape_string($this->strip_all($data['updateid']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			
			if(!empty($updateId)){
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 215, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."history set image_name='$image_name'");
				}
				
				$update = "UPDATE ".PREFIX."history SET title= '".$title."',name='".$name."',position='".$position."', description= '".$description."' WHERE id='".$updateId."'";
				$this->query($update);
				
				$history_id = $updateId;

				/*--- insert team */			
				$this->deleteMilestoneHistorybyId($history_id);
				foreach($data['text1'] as $key=>$value) {
					$text1 = $this->escape_string($this->strip_all($data['text1'][$key]));
					$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
					$this->query("insert into ".PREFIX."history_milestone(text1, history_id, display_order) values ('$text1', '$history_id', '$display_order')");
				}
			}else{
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 215, $cropData, $imgDir, $file_name.'-'.time().'-1');
				
				}
				$insert = "INSERT INTO ".PREFIX."history(`title`,`image_name`,`description`,`name`,`position`) VALUES ('".$title."','".$image_name."','".$description."','".$name."','".$position."')";
				$this->query($insert);
				$history_id = $this->last_insert_id();

				foreach($data['text1'] as $key=>$value) {
					$text1 = $this->escape_string($this->strip_all($data['text1'][$key]));
					$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
					$this->query("insert into ".PREFIX."history_milestone(text1, history_id, display_order) values ('$text1', '$history_id', '$display_order')");
				}
			}
		}
		function updateHeaderContent($data,$file){
			//print_r($data);exit;
			$email 	= $this->escape_string($this->strip_all($data['email']));
			$phone 	= $this->escape_string($this->strip_all($data['phone']));
			$updateId 	= $this->escape_string($this->strip_all($data['updateid']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			
			if(!empty($updateId)){
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 251, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."header_content set image_name='$image_name'");
				}
				
				$update = "UPDATE ".PREFIX."header_content SET email= '".$email."',phone='".$phone."' WHERE id='".$updateId."'";
				$this->query($update);
				
			}else{
				if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 251, $cropData, $imgDir, $file_name.'-'.time().'-1');
				
				}else{
				$image_name = '';
				}
				$insert = "INSERT INTO ".PREFIX."header_content(`email`,`phone`,`image_name`) VALUES ('".$email."','".$phone."','".$image_name."')";
				$this->query($insert);
				
			}
		}
		function getMilestoneHistoryCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."history_milestone where history_id = '".$id."'");
			return $this->num_rows($count);
			
		}
		function getHomeVideoLinkCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."home_video_links where home_id = '".$id."'");
			return $this->num_rows($count);
			
		}
		function getCgReportCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."cg_report_quarter where cg_id = '".$id."'");
			return $this->num_rows($count);
			
		}
		function getFinancialHighlights($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."history_milestone where history_id = '".$id."'");
			return $this->num_rows($count);
			
		}
		
		function getMilestoneSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."history_milestone where history_id = '".$id."' order by display_order ASC");
			return $count;
			
		}
		function getVideoLinkSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."home_video_links where home_id = '".$id."' order by display_order ASC");
			return $count;
			
		}
		function getCgReportQuarterSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."cg_report_quarter where cg_id = '".$id."' order by display_order<>0 DESC");
			return $count;
			
		}
		function getAboutUsExportImageCount($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."aboutus_export_image where aboutus_id = '".$id."'");
			return $this->num_rows($count);
			
		}
		function getAboutUsExportImageeSQL($id){
			$id = $this->escape_string($this->strip_all($id));
			$count = $this->query("select * from ".PREFIX."aboutus_export_image where aboutus_id = '".$id."' order by display_order ASC");
			return $count;
			
		}
		function deleteMilestoneHistorybyId($id) {
			
			$id = $this->escape_string($this->strip_all($id));
			
			$query = "delete from ".PREFIX."history_milestone where history_id='".$id."'";
			$this->query($query);
			
		}
		function deleteHistoryImage($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "update ".PREFIX."history set image_name='' where id='".$id."'";
			return $this->query($query);
		}
		
		function deleteTeamByLeaderId($id) {
			
			$id = $this->escape_string($this->strip_all($id));
			
			$query = "delete from ".PREFIX."team where leader_id='".$id."'";
			$this->query($query);
			
		}
		// Contact Us
		function getContactusContent(){
			$query = "select * from ".PREFIX."contactus";
			return $this->fetch($this->query($query));
		}
		function addContactForm($data){
			$name = $this->escape_string($this->strip_all($data['name']));
			$mobile = $this->escape_string($this->strip_all($data['mobile']));
			$email = $this->escape_string($this->strip_all($data['email']));
			$message = $this->escape_string($this->strip_all($data['message']));
			$date = date("Y-m-d H:i:s");
			$query = "insert into ".PREFIX."contact_form (name,mobile,email,message,created) values ('".$name."', '".$mobile."', '".$email."','".$message."','".$date."')";
			return $this->query($query);
		}
		function getAdminDetails(){
			$query = "select * from ".PREFIX."admin where id='1'";
			return $this->fetch($this->query($query));
		}
		// Gallery Review
		function getGalleryContent(){
			$query = "select * from ".PREFIX."gallery_content";
			return $this->fetch($this->query($query));
		}
		function getGallery(){
			$query = "select * from ".PREFIX."gallery order by display_order ASC";
			return $this->query($query);
		}
		function getClientReview(){
			$query = "select * from ".PREFIX."client_reviews order by display_order ASC";
			return $this->query($query);
		}
		
		// Gallery Review
		// Home Content
		function getCarousel(){
			$query = "select * from ".PREFIX."home_carousel order by display_order ASC";
			return $this->query($query);
		}
		function getHowWeDoIt(){
			$query = "select * from ".PREFIX."how_we_do order by display_order ASC";
			return $this->query($query);
		}
		function getHomeContent($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."gallery_content where id='".$id."'";
			return $this->fetch($this->query($query));
		}
		
		// Home Content
		// Social Links 
		function getSocialLinks(){
			$query = "select * from ".PREFIX."footer ";
			return $this->fetch($this->query($query));
		}
		// Social Links
		
		function getPackageDetails($packageId,$categoryId){
			$packageId = $this->escape_string($this->strip_all($packageId));
			$categoryId = $this->escape_string($this->strip_all($categoryId));
			if($packageId == 'quick-wash'){
				$query = "select * from ".PREFIX."quick_wash_master where  category='".$categoryId."'";
				$res = $this->fetch($this->query($query));
				return $res['time']; 
			}else{ 
				$query = "select * from ".PREFIX."subscription_master where package_name='".$packageId."' and category='".$categoryId."'";
				$res = $this->fetch($this->query($query));
				return $res['validity_period']; 
			}
		}
		function getPersonalDetails($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."personal_details where id='".$id."'";
			return $this->fetch($this->query($query));
		}
		function getGiftedPersonDetails($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."personal_details where gifted_from='".$id."'";
			return $this->fetch($this->query($query));
		}
		function getCoupon(){
			$query = "select * from ".PREFIX."discount_coupon_master where special_coupon='Yes'";
			return $this->fetch($this->query($query));
		}

	} 
?>