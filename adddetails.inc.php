<?php 

include('site-config.php');
error_reporting(0);
$first_name = $func->escape_string($func->strip_all($_POST['first_name']));
$last_name = $func->escape_string($func->strip_all($_POST['last_name']));
$mobile = $func->escape_string($func->strip_all($_POST['mobile']));
$email = $func->escape_string($func->strip_all($_POST['email']));
$address = $func->escape_string($func->strip_all($_POST['address']));
$landmark = $func->escape_string($func->strip_all($_POST['landmark']));
$zipcode = $func->escape_string($func->strip_all($_POST['zipcode']));
$booking_date = $func->escape_string($func->strip_all($_POST['date']));
$booking_time = $func->escape_string($func->strip_all($_POST['time']));
$no_vehicles = $func->escape_string($func->strip_all($_POST['no_vehicles']));
if(empty($no_vehicles)){
	$no_vehicles = 1;
}
if(!empty($_POST['gifting'])){
	$gifting = $func->escape_string($func->strip_all($_POST['gifting']));
}else{
	$gifting='';
}
$category = $func->escape_string($func->strip_all($_POST['category_name']));
$package = $func->escape_string($func->strip_all($_POST['package_name']));
if(empty($package)){
	$package = 'quick-wash';
}
if($gifting == 'on'){
	$gifting = 1;
}else{
	$gifting = 0;
}

$func->query("INSERT INTO ".PREFIX."personal_details (first_name, last_name, mobile, email, address, landmark, zipcode, booking_date, booking_time, no_vehicles, gifted) VALUES ('".$first_name."', '".$last_name."', '".$mobile."', '".$email."', '".$address."', '".$landmark."', '".$zipcode."', '".$booking_date."', '".$booking_time."', '".$no_vehicles."', '".$gifting."')");

$last_id=$func->last_insert_id();

if($no_vehicles > 1) {
	$j=0;
	foreach($_POST['category'] as $key=>$value) {
		if($_POST['category'][$key]!=''){
			$category = $func->escape_string($func->strip_all($_POST['category'][$key]));
			$package = $func->escape_string($func->strip_all($_POST['package'][$key]));
			if($package == 'quick-wash'){	
				$price = $func->escape_string($func->strip_all($_GET['price']));
				$pricequery = $func->query("SELECT * FROM ".PREFIX."quick_wash_master WHERE category = '".$category."'");
				$priceres = $func->fetch($pricequery);
				$price = $priceres['price'];
			}else{	
				$pricequery = $func->query("SELECT * FROM ".PREFIX."subscription_master WHERE category = '".$category."' and package_name = '".$package."'");
				$priceres = $func->fetch($pricequery);
				$price = $priceres['package_price'];
			}
			$func->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package, price) values ('".$last_id."','".$category."','".$package."', '".$price."')");
			$j++;
		}
	}
}else if($no_vehicles == '1'){
	if($package == 'quick-wash'){	
		$price = $func->escape_string($func->strip_all($_GET['price']));
		$pricequery = $func->query("SELECT * FROM ".PREFIX."quick_wash_master WHERE category = '".$category."'");
		$priceres = $func->fetch($pricequery);
		$price = $priceres['price'];
	}else{	
		$pricequery = $func->query("SELECT * FROM ".PREFIX."subscription_master WHERE category = '".$category."' and package_name = '".$package."'");
		$priceres = $func->fetch($pricequery);
		$price = $priceres['package_price'];
	}
	$func->query("insert into ".PREFIX."booking_details (personal_id, vehicle_category, vehicle_package, price) values ('".$last_id."','".$category."','".$package."', '".$price."')");
}

//getsummarydetails

$contents = '';
$total = '';
$query1 = $func->query("SELECT * from ".PREFIX."booking_details where personal_id='".$last_id."'");
while($res = $func->fetch($query1)){

	$category = $func->fetch($func->query("SELECT * FROM ".PREFIX."category_master WHERE id = '".$res['vehicle_category']."'"));
	$package = $func->fetch($func->query("SELECT * FROM ".PREFIX."package_master WHERE id = '".$res['vehicle_package']."'"));
	if(empty($package['package_name'])){
		$package_name = $res['vehicle_package'];
	}else{
		$package_name = $package['package_name'];
	}
	$contents .= "<tr><td data-column='#'>1</td><td data-column='Vehicle Type'>".$category['category_name']."</td><td data-column='Packages'>".$package_name."</td><td data-column='Qty'><span class='qty-span'>1</span></td><td data-column='Total Price'><i class='fa fa-inr' aria-hidden='true'></i>".number_format($res['price'],2)."</td></tr>";
	$total += $res['price'];
}

$myObj = new stdClass();
$myObj->contents = $contents;
$myObj->total = number_format($total,2);
$myJSON = json_encode($myObj);
//include('details-mail.inc.php');
echo $myJSON;
?>


