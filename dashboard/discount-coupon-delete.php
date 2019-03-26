<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';
	$admin = new AdminFunctions();
	// $pageName = "Product Types";
	// $pageURL = 'product-types-delete.php';
	$parentPageURL = 'discount-coupon-master.php';

	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	$admin->checkUserPermissions('discount_coupon_delete',$loggedInUserDetailsArr);
	if(isset($_GET['id'])){
		$id = $admin->escape_string($admin->strip_all($_GET['id']));
		if(!isset($id) || empty($id)){
			header("location:".$parentPageURL."?deletefail");
			exit;
		}

		//delete from database
		$result = $admin->deleteDiscountCoupon($id);
		header("location:".$parentPageURL."?deletesuccess");
	}
?>