<?php 

include('site-config.php');
//error_reporting(0);

$myObj = new stdClass();
$msg = 'Failed';

$couponcode = $func->escape_string($func->strip_all($_POST['couponcode']));
$sub_total = $func->escape_string($func->strip_all($_POST['sub_total']));
$sub_total = str_replace(',', '', $sub_total);
//$sub_total = number_format($sub_total);

$getquery = $func->query("SELECT * FROM ".PREFIX."discount_coupon_master where coupon_code = '".$couponcode."'");

if($func->num_rows($getquery) > 0){

	$getdetails = $func->fetch($getquery);
	$coupon_type = $getdetails['coupon_type'];
	$coupon_value = $getdetails['coupon_value'];
	$active = $getdetails['active'];
	$coupon_usage = $getdetails['coupon_usage'];
	$valid_from = $getdetails['valid_from'];
	$valid_to = $getdetails['valid_to'];
	$min_purchase = $getdetails['minimum_purchase_amount'];

	if($coupon_type == 'percent'){
		$discount_rate = $coupon_value/100; //0.5
		$discount_value = $sub_total * $discount_rate; //3000 * 0.5
		$dis_value =  $sub_total - $discount_value; // 3000 - 1500
		$final_total = $dis_value; // 1500
	}else if($coupon_type == 'amount'){
		$discount_value = $coupon_value;
		$dis_value =  $sub_total - $discount_value;
		$final_total = $dis_value;
	}

	if($active != 'Yes'){
		$myObj->msg = $msg;
	}

	$myObj->final_total = number_format($final_total,2);
	$myObj->discount_value = number_format($discount_value,2);
	$myObj->coupon_type = $coupon_type;
	$myObj->coupon_value = number_format($coupon_value,2);
	$myObj->active = $active;
	$myObj->coupon_usage = $coupon_usage;
	$myObj->valid_from = $valid_from;
	$myObj->valid_to = $valid_to;
	$myObj->min_purchase = number_format($min_purchase,2);

	$myJSON = json_encode($myObj);
	echo $myJSON;

}else{
	$myObj->msg = $msg;
}


?>