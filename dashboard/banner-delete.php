<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';
	$admin = new AdminFunctions();
	// $pageName = "Product Types";
	// $pageURL = 'product-types-delete.php';
	$parentPageURL = 'banner-master.php';

	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	if(isset($_GET['id'])){
		$id = $admin->escape_string($admin->strip_all($_GET['id']));
		if(!isset($id) || empty($id)){
			header("location:".$parentPageURL."?deletefail");
			exit;
		}

		//delete from database
		$result = $admin->deleteBanner($id);
		header("location:".$parentPageURL."?deletesuccess");
	}
?>