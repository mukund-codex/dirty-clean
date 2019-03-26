<?php
	include('database.php');
	include('SaveImage.class.php');
	include('include/classes/CSRF.class.php');
	error_reporting(0);
	/*
	 * AdminFunctions
	 * v1 - updated loginSession(), logoutSession(), adminLogin()
	 */
	class AdminFunctions extends Database {
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
		/* function sessionExists(){
			if( isset($_SESSION[SITE_NAME]) && 
				isset($_SESSION[SITE_NAME][$this->userType.'UserId']) && 
				isset($_SESSION[SITE_NAME][$this->userType.'UserType']) && 
				!empty($_SESSION[SITE_NAME][$this->userType.'UserId']) &&
				$_SESSION[SITE_NAME][$this->userType.'UserType']==$this->userType){

				return $loggedInUserDetailsArr = $this->getLoggedInUserDetails();
				// return true; // DEPRECATED
			} else {
				return false;
			}
		} */
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
			if(!in_array($permission,$userPermissionsArray) and $loggedInUserDetailsArr['user_role']!='super') {
				header("location: dashboard.php");
				exit;
			}
		}
		
		// === BANNER STARTS ===
		function getAllBanners() {
			$query = "select * from ".PREFIX."banner_master";
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
			
			$title = $this->escape_string($this->strip_all($data['title']));
			$sub_title = $this->escape_string($this->strip_all($data['sub_title']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/banner/';
			if(isset($file['banner_img']['name']) && !empty($file['banner_img']['name'])){
				$imageName = str_replace( " ", "-", $file['banner_img']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['banner_img'], 1366, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}

			$query = "insert into ".PREFIX."banner_master (banner_img,title,sub_title,link,active,display_order,created) values ('$image_name', '$title', '$sub_title','$link', '$active', '$display_order','$date')";
			return $this->query($query);
		}
		
		function updateBanner($data,$file) {
			//print_r($data);exit;
			$id = $this->escape_string($this->strip_all($data['id']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$title = $this->escape_string($this->strip_all($data['title']));
			$sub_title = $this->escape_string($this->strip_all($data['sub_title']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/banner/';
			if(isset($file['banner_img']['name']) && !empty($file['banner_img']['name'])) {
				$imageName = str_replace( " ", "-", $file['banner_img']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueBannerById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['banner_img'], "large");
				$this->unlinkImage("", $Detail['banner_img'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['banner_img'], 1366, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."banner_master set banner_img='$image_name' where id='$id'");
			}

			$query = "update ".PREFIX."banner_master set title='$title',sub_title='$sub_title', active='$active', link='$link', display_order='$display_order' where id='$id'";
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
		// Welcome Content
		function getUniqueWelcomeById($id){
			$id = $this->escape_string($this->strip_all($id));
			$sql = "SELECT `image_name` FROM ".PREFIX."home_page WHERE `id`='".$id."'";
			$this->query($sql);
		}
		function updateHomePage($data,$file){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			//$link = $this->escape_string($this->strip_all($data['link']));
			$title = $this->escape_string($this->strip_selected($data['title'],$allowTags));
			
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['banner_img']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueBannerById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 441, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."home_page set image_name='$image_name' where id='$id'");
			}
			$query = "update ".PREFIX."home_page set title='$title' where id='$id'";
			$this->query($query);
		}
		
		// Welcome Content End
		// About Us Content
		function getUniqueAboutUsById($id){
			$id = $this->escape_string($this->strip_all($id));
			$sql = "SELECT `image_name` FROM ".PREFIX."about_us WHERE `id`='".$id."'";
			$this->query($sql);
		}
		function updateAboutUsPage($data,$file){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$id = $this->escape_string($this->strip_all($data['id']));
			$desc1 = $this->escape_string($this->strip_selected($data['desc1'],$allowTags));
			$desc2 = $this->escape_string($this->strip_selected($data['desc2'],$allowTags));
			
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueAboutUsById($id);
				$cropData = $this->strip_all($data['cropData1']);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 387, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."about_us set image_name='$image_name' ");
			}
			$query = "update ".PREFIX."about_us set desc1='$desc1',desc2='$desc2' ";
			$this->query($query);
		}
		
		// About Us Content End
		
		// Feature Master
		
		
		function getUniqueFeatureById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."feature_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function updateFeatureMaster($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$feature_name = $this->escape_string($this->strip_all($data['feature_name']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$query = "update ".PREFIX."feature_master set feature_name='".$feature_name."', display_order='".$display_order."' where id='".$id."'";
			$this->query($query);
		}
		// Feature Master
		
		// Package Mastger
		function getUniquePackageById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."package_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		function updatePackageMaster($data,$file) {
			
			$id = $this->escape_string($this->strip_all($data['id']));
			$package_name = $this->escape_string($this->strip_all($data['package_name']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$query = "update ".PREFIX."package_master set package_name='".$package_name."', display_order='".$display_order."' where id='".$id."'";
			$this->query($query);
		}
		// Package Master
		
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
		function getAllFeatures() {
			$query = "select * from ".PREFIX."feature_master order by display_order ASC";
			$sql = $this->query($query);
			return $sql;
		}
		function addCategory($data,$file){
			
			$category_name = $this->escape_string($this->strip_all($data['category_name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			/* $SaveImage = new SaveImage();
			$imgDir = '../img/category/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 240, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}  */
			
			$query = "insert into ".PREFIX."category_master(category_name, active, display_order,created) values ('".$category_name."', '".$active."', '".$display_order."', '".$date."')";
			$this->query($query);

			//$category_id = $this->last_insert_id();

			
		}
		function updateCategory($data,$file) {
			
			$id = $this->escape_string($this->strip_all($data['id']));
			$category_name = $this->escape_string($this->strip_all($data['category_name']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			
			$Detail = $this->getUniqueCategoryById($id);
			$SaveImage = new SaveImage();
			$imgDir = '../img/banner/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])) {
				$cropData = $this->strip_all($data['cropData1']);
				$imageName = str_replace( " ", "-", $file['image_name']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$this->unlinkImage("category", $Detail['image_name'], "large");
				$this->unlinkImage("category", $Detail['image_name'], "crop");
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 269, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."category_master set image='$image_name' where id='$id'");
			} 
			
			
			$query = "update ".PREFIX."category_master set category_name='".$category_name."', active='".$active."', display_order='".$display_order."' where id='".$id."'";
			$this->query($query);

			$category_id = $id;

		}
		function deleteCategory($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."category_master where id='".$id."'";
			$this->query($query);
			/* $sql = $this->query("select id from ".PREFIX."sub_category_master where category_id='$id'");
			while($result = $this->fetch($sql)) {
				$this->deleteSubCategory($result['id']);
			}
			$sql = $this->query("select id from ".PREFIX."product_master where category_id='$id'");
			while($detail = $this->fetch($sql)) {
				$product_id = $detail['id'];
				$this->deleteProduct($product_id);
			} */
			
		}
		

		
		// === CATEGORY ENDS ===
		// === SUB CATEGORY STARTS ===
		function getUniqueSubCategoryById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."sub_category_master where id='".$id."'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}
		
		
		function getAllSubCategories($category_id) {
			$category_id = $this->escape_string($this->strip_all($category_id));
			$query = "select * from ".PREFIX."sub_category_master where category_id='$category_id' and active='Yes'";
			$sql = $this->query($query);
			return $sql;
		}
		function addSubCategory($data,$file){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
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
			$query = "insert into ".PREFIX."sub_category_master(image_name, category_id, sub_category_name, permalink, active, display_order,description) values ('".$image_name."','".$category_id."', '".$category_name."', '".$permalink."', '".$active."','".$display_order."','".$description."')";
			$this->query($query);
		}
		function updateSubCategory($data,$file) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
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
			$query = "update ".PREFIX."sub_category_master set sub_category_name='".$category_name."', permalink='".$permalink."', active='".$active."',display_order='".$display_order."',description='".$description."' where id='".$id."'";
			$this->query($query);
		}
		function deleteSubCategory($id) {
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."sub_category_master where id='".$id."'";
			$this->query($query);
			
		}
		
		// === SUB CATEGORY ENDS ===
		// Gallery Review 
		function updateGalleryContent($data){
			$id = $this->escape_string($this->strip_all($data['id']));
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><center>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$title = $this->escape_string($this->strip_all($data['title']));
			$query = "update ".PREFIX."gallery_content set description='".$description."',title='".$title."' where id='".$id."'";
			$this->query($query);
		}
		function addGallery($data,$file) {
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/gallery/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 699, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."gallery(image_name,display_order,created) values ('$image_name', '$display_order','$date')";
			return $this->query($query);
		}
		function updateGallery($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/gallery/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$Detail = $this->getUniqueGalleryById($id);
				$this->unlinkImage("gallery", $Detail['image_name'], "large");
				$this->unlinkImage("gallery", $Detail['image_name'], "crop");
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 699, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."gallery set image_name='".$image_name."' where id='".$id."'");
			} 
			$query = "update ".PREFIX."gallery set display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		} 
		function getUniqueGalleryById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."gallery where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteGallery($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueGalleryById($id);
			$imgDir = '../img/gallery/';
			$this->unlinkImage("gallery", $Detail['image_name'], "large");
			$this->unlinkImage("gallery", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."gallery where id='$id'";
			$this->query($query);
			return true;
		}
		function addClientReview($data,$file) {
			//$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			//$review = $this->escape_string($this->strip_selected($data['review'],$allowTags));
			$review =$this->escape_string($this->strip_all($data['review']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 200, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."client_reviews(name,image_name,review,display_order,created) values ('$name', '$image_name','$review','$display_order','$date')";
			return $this->query($query);
		}
		function updateClientReview($data,$file) {
		//	$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
		//	$review = $this->escape_string($this->strip_selected($data['review'],$allowTags));
			$review =$this->escape_string($this->strip_all($data['review']));
			$id = $this->escape_string($this->strip_all($data['id']));
			$name = $this->escape_string($this->strip_all($data['name']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$Detail = $this->getUniqueGalleryById($id);
				$this->unlinkImage("gallery", $Detail['image_name'], "large");
				$this->unlinkImage("gallery", $Detail['image_name'], "crop");
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 200, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."client_reviews set image_name='".$image_name."' where id='".$id."'");
			} 
			$query = "update ".PREFIX."client_reviews set name='".$name."',review='".$review."', display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		} 
		function getUniqueClientReviewById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."client_reviews where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function deleteClientReviews($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueClientReviewById($id);
			$imgDir = '../img/';
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."client_reviews where id='$id'";
			$this->query($query);
			return true;
		}
		
		// Gallery Review
		// Home Content
		function getUniqueHowWeDoById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."how_we_do where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function updateHowWeDo($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$title = $this->escape_string($this->strip_all($data['title']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$Detail = $this->getUniqueHowWeDoById($id);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 60, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."how_we_do set image_name='".$image_name."' where id='".$id."'");
			} 
			$query = "update ".PREFIX."how_we_do set title='".$title."',description='".$description."', display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		}
		function getUniqueHomeCarouselById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "Select * from ".PREFIX."home_carousel where id='".$id."' ";
			return $this->fetch($this->query($query));
		}
		function addHomeCarousel($data,$file) {
			$title = $this->escape_string($this->strip_all($data['title']));
			$sub_title = $this->escape_string($this->strip_all($data['sub_title']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$date = date("Y-m-d H:i:s");
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 48, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			$query = "insert into ".PREFIX."home_carousel(title,sub_title,link,image_name,display_order,created) values ('$title','$sub_title','$link','$image_name', '$display_order','$date')";
			return $this->query($query);
		}
		function updateHomeCarousel($data,$file) {
			$id = $this->escape_string($this->strip_all($data['id']));
			$title = $this->escape_string($this->strip_all($data['title']));
			$sub_title = $this->escape_string($this->strip_all($data['sub_title']));
			$link = $this->escape_string($this->strip_all($data['link']));
			$display_order = $this->escape_string($this->strip_all($data['display_order']));
			$SaveImage = new SaveImage();
			$imgDir = '../img/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$Detail = $this->getUniqueHomeCarouselById($id);
				$this->unlinkImage("", $Detail['image_name'], "large");
				$this->unlinkImage("", $Detail['image_name'], "crop");
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 48, $cropData, $imgDir, $file_name.'-'.time().'-1');
				$this->query("update ".PREFIX."home_carousel set image_name='".$image_name."' where id='".$id."'");
			} 
			$query = "update ".PREFIX."home_carousel set title='".$title."',sub_title='".$sub_title."',link='".$link."',display_order='".$display_order."' where id='".$id."'";
			return $this->query($query);
		} 
		function deleteHomeCarousel($id) {
			$id = $this->escape_string($this->strip_all($id));
			$Detail = $this->getUniqueHomeCarouselById($id);
			$imgDir = '../img/';
			$this->unlinkImage("", $Detail['image_name'], "large");
			$this->unlinkImage("", $Detail['image_name'], "crop");
			$query = "delete from ".PREFIX."home_carousel where id='$id'";
			$this->query($query);
			return true;
		}
		function updateWelcomeContent($data, $file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['description'],$allowTags));
			$link = $this->escape_string($this->strip_all($data['video_link']));

			$SaveImage = new SaveImage();
			$imgDir = '../img/banner/';
			if(isset($file['thumbnail']['name']) && !empty($file['thumbnail']['name'])){
				$imageName = str_replace( " ", "-", $file['thumbnail']['name'] );
				$file_name = strtolower( pathinfo($imageName, PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['thumbnail'], 387, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}
			//echo $image_name;exit;
			$query = "update ".PREFIX."gallery_content set description='".$description."',video_link='".$link."', image = '".$image_name."' where id='".$id."'";
			$this->query($query);
		}
		function updateHomeDetailingContent($data){
			$id = $this->escape_string($this->strip_all($data['id']));
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a>";
			$description = $this->escape_string($this->strip_selected($data['detailing'],$allowTags));
			
			$query = "update ".PREFIX."gallery_content set description='".$description."' where id='".$id."'";
			$this->query($query);
		}
		
		
		// Home Content
		// === Contact Us === 
		
		function updateContactUsPage($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$registered_office = $this->escape_string($this->strip_selected($data['registered_office'],$allowTags));
			$workshop = $this->escape_string($this->strip_selected($data['workshop'],$allowTags));
			$phone = $this->escape_string($this->strip_all($data['phone']));
			$email = $this->escape_string($this->strip_all($data['email']));
			$map_link = $this->escape_string($this->strip_all($data['map_link']));
			
			
			$query = "update ".PREFIX."contactus set phone='".$phone."', email='".$email."',map_link='".$map_link."',registered_office='".$registered_office."',workshop='".$workshop."'";
			$this->query($query);
			
		}
		function deleteContactForm($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."contact_form where id='$id'";
			$this->query($query);
			return true;
		}
		// Social Links
		function updateSocialLinks($data){
			$facebook = $this->escape_string($this->strip_all($data['facebook']));
			$twitter = $this->escape_string($this->strip_all($data['twitter']));
			$google = $this->escape_string($this->strip_all($data['facebook']));
			$instagram = $this->escape_string($this->strip_all($data['instagram']));
			$query = "update ".PREFIX."footer set facebook='".$facebook."',twitter='".$twitter."',google='".$google."',instagram='".$instagram."' ";
			return $this->query($query);
		}
		
		// Social Links
		
		/** * Function to update FAQ section */
		function updateFAQs($data){
			$this->deleteAllFAQs();
			if(sizeof($data['category'])>0) {
				$j=0;
				foreach($data['category'] as $key=>$value) {
					if($data['category'][$key]!=''){
						$category = $this->escape_string($this->strip_all($data['category'][$key]));
						$question = $this->escape_string($this->strip_all($data['question'][$key]));
						$answer = $this->escape_string($this->strip_all($data['answer'][$key]));
						$display_order = $this->escape_string($this->strip_all($data['display_order'][$key]));
						$this->query("insert into ".PREFIX."faqs (category, question, answer, display_order) values ('".$category."','".$question."','".$answer."','".$display_order."')");
						$j++;
					}
				}
			}
		}

		/** * Function to delete all questions and answers of FAQ section */
		function deleteAllFAQs(){
			return $this->query("truncate table ".PREFIX."faqs");
		}

		function getFAQs(){
			return $this->query("select * from ".PREFIX."faqs order by id DESC");
		}

		// === DISCOUNT COUPON STARTS ===
		function getAllDiscountCoupons() {
			$query = "select * from ".PREFIX."discount_coupon_master";
			$sql = $this->query($query);
			return $sql;
		}

		function getUniqueDiscountCouponById($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "select * from ".PREFIX."discount_coupon_master where id='$id'";
			$sql = $this->query($query);
			return $this->fetch($sql);
		}

		function addDiscountCoupon($data) {
			$coupon_code = $this->escape_string($this->strip_all($data['coupon_code']));
			$coupon_type = $this->escape_string($this->strip_all($data['coupon_type']));
			$coupon_value = $this->escape_string($this->strip_all($data['coupon_value']));
			$valid_from = $this->escape_string($this->strip_all($data['valid_from']));
			$valid_to = $this->escape_string($this->strip_all($data['valid_to']));
			$coupon_usage = $this->escape_string($this->strip_all($data['coupon_usage']));
			$minimum_purchase_amount = $this->escape_string($this->strip_all($data['minimum_purchase_amount']));
			$special_coupon = $this->escape_string($this->strip_all($data['special_coupon']));
			$active = $this->escape_string($this->strip_all($data['active']));

			$query = "insert into ".PREFIX."discount_coupon_master (coupon_code, coupon_type, coupon_value, valid_from, valid_to, coupon_usage, minimum_purchase_amount, special_coupon, active) values ('$coupon_code', '$coupon_type', '$coupon_value', '$valid_from', '$valid_to', '$coupon_usage', '$minimum_purchase_amount', '$special_coupon', '$active')";
			return $this->query($query);
		}

		function updateDiscountCoupon($data) {
			$coupon_code = $this->escape_string($this->strip_all($data['coupon_code']));
			$coupon_type = $this->escape_string($this->strip_all($data['coupon_type']));
			$coupon_value = $this->escape_string($this->strip_all($data['coupon_value']));
			$valid_from = $this->escape_string($this->strip_all($data['valid_from']));
			$valid_to = $this->escape_string($this->strip_all($data['valid_to']));
			$coupon_usage = $this->escape_string($this->strip_all($data['coupon_usage']));
			$minimum_purchase_amount = $this->escape_string($this->strip_all($data['minimum_purchase_amount']));
			$special_coupon = $this->escape_string($this->strip_all($data['special_coupon']));
			$active = $this->escape_string($this->strip_all($data['active']));
			$id = $this->escape_string($this->strip_all($data['id']));

			$query = "update ".PREFIX."discount_coupon_master set coupon_code='$coupon_code', coupon_type='$coupon_type', coupon_value='$coupon_value', valid_from='$valid_from', valid_to='$valid_to', coupon_usage='$coupon_usage', minimum_purchase_amount='$minimum_purchase_amount', special_coupon='$special_coupon', active='$active' where id='$id'";
			return $this->query($query);
		}

		function deleteDiscountCoupon($id) {
			$id = $this->escape_string($this->strip_all($id));
			$query = "delete from ".PREFIX."discount_coupon_master where id='$id'";
			$this->query($query);
			return true;
		}

		// === Contact Us End ===
		
		/*===================== Vendor Subscription Start =====================*/
		
		/** * Function to get unique subscription details */
		function getUniqueSubscriptionById($id) {
			$id 	= $this->escape_string($this->strip_all($id));
			$query 	= "select * from ".PREFIX."subscription_master where id='".$id."'";
			$sql 	= $this->query($query);
			return $this->fetch($sql);
		}
		
		/** * Function to get all subscriptions with details */
		function getAllSubscriptions() {
			$query 	= "select * from ".PREFIX."subscription_master";
			return $this->query($query);
		}
		
		/** * Function to get all subscription features */
		function getAllSubscriptionFeatures() {
			$query 	= "select * from ".PREFIX."subscription_features_master";
			return $this->query($query);
		}
		
		/** * Function to get all active subscriptions with details */
		function getAllActiveSubscriptions() {
			$query 	= "select * from ".PREFIX."subscription_master where active = 'Yes'";
			return $this->query($query);
		}
		
		/** * Function to add new subscription details */
		function addSubscription($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$category           = $this->escape_string($this->strip_all($data['category']));
			$package_name 		= $this->escape_string($this->strip_all($data['package_name']));
			//$duration 	        = $this->escape_string($this->strip_all($data['validity_period']));
			//$validity_period    = $duration*60;
			$package_price	 	= $this->escape_string($this->strip_all($data['package_price']));
			$active 			= $this->escape_string($this->strip_all($data['active']));			
			$interior_feature   = $this->escape_string($this->strip_selected($data['interior_feature'],$allowTags));
			$exterior_feature   = $this->escape_string($this->strip_selected($data['exterior_feature'],$allowTags));
			$engine_bay_feature   = $this->escape_string($this->strip_selected($data['engine_bay_feature'],$allowTags));
			$duration_from   = $this->escape_string($this->strip_all($data['duration_from']));
			$duration_to   = $this->escape_string($this->strip_all($data['duration_to']));

			if(empty($package_price)){
				$package_price	= 0;
			}
			
			$replace_keywords 	= array("-:-", "-:", ":-", " : ", " :", ": ", ":",
				"-@-", "-@", "@-", " @ ", " @", "@ ", "@", 
				"-.-", "-.", ".-", " . ", " .", ". ", ".", 
				"-\\-", "-\\", "\\-", " \\ ", " \\", "\\ ", "\\",
				"-/-", "-/", "/-", " / ", " /", "/ ", "/", 
				"-&-", "-&", "&-", " & ", " &", "& ", "&", 
				"-,-", "-,", ",-", " , ", " ,", ", ", ",", 
				" ",
				"---", "--", " - ", " -", "- ",
				"-#-", "-#", "#-", " # ", " #", "# ", "#",
				"-$-", "-$", "$-", " $ ", " $", "$ ", "$",
				"-%-", "-%", "%-", " % ", " %", "% ", "%",
				"-^-", "-^", "^-", " ^ ", " ^", "^ ", "^",
				"-*-", "-*", "*-", " * ", " *", "* ", "*",
				"-(-", "-(", "(-", " ( ", " (", "( ", "(",
				"-)-", "-)", ")-", " ) ", " )", ") ", ")",
				"-!-", "-!", "!-", " ! ", " !", "! ", "!");
			$menu_names			= str_replace($replace_keywords,'-',$package_name); 
			$permalink			= strtolower($menu_names);
			
			$query = "insert into ".PREFIX."subscription_master(category, package_name, package_price, interior_feature, exterior_feature, engine_bay_feature, permalink, active, duration_from, duration_to) values ('".$category."','".$package_name."', '".$package_price."','".$interior_feature."', '".$exterior_feature."', '".$engine_bay_feature."', '".$permalink."', '".$active."', '".$duration_from."', '".$duration_to."')";
			return $this->query($query);
		}
		
		/** * Function to update existing subscription details */
		function updateSubscription($data) {
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$id 				= $this->escape_string($this->strip_all($data['id']));			
			$category           = $this->escape_string($this->strip_all($data['category']));
			$package_name 		= $this->escape_string($this->strip_all($data['package_name']));
			//$duration 	        = $this->escape_string($this->strip_all($data['validity_period']));
			//$validity_period    = $duration * 60;
			$package_price	 	= $this->escape_string($this->strip_all($data['package_price']));
			$active 			= $this->escape_string($this->strip_all($data['active']));
			$interior_feature   = $this->escape_string($this->strip_selected($data['interior_feature'],$allowTags));
			$exterior_feature   = $this->escape_string($this->strip_selected($data['exterior_feature'],$allowTags));
			$engine_bay_feature   = $this->escape_string($this->strip_selected($data['engine_bay_feature'],$allowTags));
			$duration_from   = $this->escape_string($this->strip_all($data['duration_from'])) * 60;
			$duration_to   = $this->escape_string($this->strip_all($data['duration_to'])) * 60;
			
			if(empty($package_price)){
				$package_price	= 0;
			}
			
			$replace_keywords 	= array("-:-", "-:", ":-", " : ", " :", ": ", ":",
				"-@-", "-@", "@-", " @ ", " @", "@ ", "@", 
				"-.-", "-.", ".-", " . ", " .", ". ", ".", 
				"-\\-", "-\\", "\\-", " \\ ", " \\", "\\ ", "\\",
				"-/-", "-/", "/-", " / ", " /", "/ ", "/", 
				"-&-", "-&", "&-", " & ", " &", "& ", "&", 
				"-,-", "-,", ",-", " , ", " ,", ", ", ",", 
				" ",
				"---", "--", " - ", " -", "- ",
				"-#-", "-#", "#-", " # ", " #", "# ", "#",
				"-$-", "-$", "$-", " $ ", " $", "$ ", "$",
				"-%-", "-%", "%-", " % ", " %", "% ", "%",
				"-^-", "-^", "^-", " ^ ", " ^", "^ ", "^",
				"-*-", "-*", "*-", " * ", " *", "* ", "*",
				"-(-", "-(", "(-", " ( ", " (", "( ", "(",
				"-)-", "-)", ")-", " ) ", " )", ") ", ")",
				"-!-", "-!", "!-", " ! ", " !", "! ", "!");
			$menu_names			= str_replace($replace_keywords,'-',$package_name); 
			$permalink			= strtolower($menu_names);
			//validity_period='".$validity_period."',
			$query = "update ".PREFIX."subscription_master set category = '".$category."', package_name='".$package_name."', package_price='".$package_price."',interior_feature='".$interior_feature."', exterior_feature='".$exterior_feature."', engine_bay_feature='".$engine_bay_feature."', permalink='".$permalink."', active='".$active."', duration_from='".$duration_from."', duration_to='".$duration_to."' where id='".$id."'";
			return $this->query($query);
		}
		
		//get unique subscription feature by id
		function getUniqueSubscriptionFeatureById($id) {
			$id 	= $this->escape_string($this->strip_all($id));
			$query 	= "select * from ".PREFIX."subscription_features_master where id='".$id."'";
			$sql 	= $this->query($query);
			return $this->fetch($sql);
		}

		//delete unqiue subscription feature by id
		function deleteSubscriptionFeature($id) {
			$id = $this->escape_string($this->strip_all($id));
			
			$query = "delete from ".PREFIX."subscription_features_master where id='".$id."'";
			return $this->query($query);
		}

		/** * Function to add new subscription details */
		function updateSubscriptionFeatures($data){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$category = $this->escape_string($this->strip_all($data['category']));
			$package_name = $this->escape_string($this->strip_all($data['package_name']));
			$feature_category = $this->escape_string($this->strip_all($data['feature_category']));
			$feature = $this->escape_string($this->strip_selected($data['feature'],$allowTags));
			$id = $this->escape_string($this->strip_all($data['id']));
			//echo "UPDATE ".PREFIX."subscription_features_master SET category = '".$category."', package_name = '".$package_name."', feature_category = '".$feature_category."', feature = '".$feature."' where id = '".$id."'";exit;
			$query = "UPDATE ".PREFIX."subscription_features_master SET category = '".$category."', package_name = '".$package_name."', feature_category = '".$feature_category."', feature = '".$feature."' where id = '".$id."'";
			return $this->query($query);

		}
		
		/** * Function to delete existing subscription details */
		function deleteSubscription($id) {
			$id = $this->escape_string($this->strip_all($id));
			
			$query = "delete from ".PREFIX."subscription_master where id='".$id."'";
			return $this->query($query);
		}
		
		function addSubscriptionFeatures($data){
			//print_r($data);exit;
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$category = $this->escape_string($this->strip_all($data['category']));
			$package_name = $this->escape_string($this->strip_all($data['package_name']));
			$feature_category = $this->escape_string($this->strip_all($data['feature_category']));
			$feature = $this->escape_string($this->strip_selected($data['feature'],$allowTags));
			
			$query = "INSERT INTO ".PREFIX."subscription_features_master (category, package_name, feature_category, feature) value('".$category."', '".$package_name."', '".$feature_category."', '".$feature."')";
			//echo $query;exit;
			return $this->query($query);
		}

		function addQuickwashDetails($data, $file){
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$category = $this->escape_string($this->strip_all($data['category']));
			$time = $this->escape_string($this->strip_all($data['timemins']));
			$main_features = $this->escape_string($this->strip_selected($data['main_feature'], $allowTags));
			$interior_feature = $this->escape_string($this->strip_selected($data['interior_feature'], $allowTags));
			$exterior_feature = $this->escape_string($this->strip_selected($data['exterior_feature'], $allowTags));
			$price = $this->escape_string($this->strip_all($data['price']));
			//$image = $this->escape_string($this->strip_all($file['image']));

			$SaveImage = new SaveImage();
			$imgDir = '../img/quick-wash/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 454, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}

			$query = "INSERT INTO ".PREFIX."quick_wash_master (category, time, main_features, interior_feature, exterior_feature, price, image) value('".$category."', '".$time."', '".$main_features."', '".$interior_feature."', '".$exterior_feature."', '".$price."', '".$image_name."')";
			return $this->query($query);
		}
		
		function getUniqueQuickWashById($id){
			$id = $this->escape_string($this->strip_all($id));
			$query = "SELECT * FROM ".PREFIX."quick_wash_master WHERE id='".$id."'";
			return $this->fetch($this->query($query));
		}
		
		function updateQuickWash($data, $file){
			$id = $this->escape_string($this->strip_all($data['id']));
			$allowTags = "<strong><em><b><p><u><ul><li><ol><s><sub><sup><h1><img><h2><h3><h4><h5><h6><div><i><span><br><table><tr><th><td><thead><tbody><a><hr>";
			$category = $this->escape_string($this->strip_all($data['category']));
			$time = $this->escape_string($this->strip_all($data['timemins']));
			$main_features = $this->escape_string($this->strip_selected($data['main_feature'], $allowTags));
			$interior_feature = $this->escape_string($this->strip_selected($data['interior_feature'], $allowTags));
			$exterior_feature = $this->escape_string($this->strip_selected($data['exterior_feature'], $allowTags));
			$price = $this->escape_string($this->strip_all($data['price']));

			$SaveImage = new SaveImage();
			$imgDir = '../img/quick-wash/';
			if(isset($file['image_name']['name']) && !empty($file['image_name']['name'])){
				$file_name = strtolower( pathinfo($file['image_name']['name'], PATHINFO_FILENAME));
				$file_name = $this->getValidatedPermalink($file_name);
				$Detail = $this->getUniqueQuickWashById($id);
				$this->unlinkImage("", $Detail['banner_img'], "large");
				$this->unlinkImage("", $Detail['banner_img'], "crop");
				$cropData = $this->strip_all($data['cropData1']);
				$image_name = $SaveImage->uploadCroppedImageFileFromForm($file['image_name'], 454, $cropData, $imgDir, $file_name.'-'.time().'-1');
			} else {
				$image_name = '';
			}

			$query = "UPDATE ".PREFIX."quick_wash_master SET category = '".$category."', time = '".$time."', main_features = '".$main_features."', interior_feature = '".$interior_feature."', exterior_feature = '".$exterior_feature."', price = '".$price."'";
		}

		function deleteQuickWashById($id){
			$id = $this->escape_string($this->strip_all($id));

			$query = "delete from ".PREFIX."quick_wash_master where id='".$id."'";
			$this->query($query);
		}
		/*===================== Vendor Subscription End =====================*/		
		
	} 
?>