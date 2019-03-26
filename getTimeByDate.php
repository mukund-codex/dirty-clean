<?php

	include('site-config.php');
	if(isset($_POST['date']) && !empty($_POST['date'])){
		$date = $_POST['date'];
		$qry ="SELECT b.`vehicle_package`,b.`vehicle_category`,p.* FROM ".PREFIX."booking_details b INNER JOIN ".PREFIX."personal_details p ON b.`personal_id`=p.`id` where p.booking_date='".$date."' group by b.personal_id";
		$res = $func->query($qry);
		if($res->num_rows > 0){
			$disableTimeRange = array();
		while($row = $func->fetch($res)){
			$booking_time = $row['booking_time'];
			$packageDetails = $func->getPackageDetails($row['vehicle_package'],$row['vehicle_category']);
			$validity_time = $packageDetails*$row['no_vehicles'];
			
			$endTime = date("h:ia", strtotime($booking_time." +".$validity_time." minutes"));
			
			$disableTime = array($booking_time, $endTime);
			$disableTimeRange[] = $disableTime;
		}
			$php_array = $disableTimeRange;
		}else{
			$php_array = array();
		}
	$js_array =  json_encode($php_array);
	//print_r($js_array);
	
?>

	<div class="form-group">
	   <label>Time</label>
		 <input type="" name="time" required id="time" class="form-control timepicker timecheck">
		 <span class="time-icon"></span>
	</div>
	<?php } ?>	
 
	<script>
	 $(document).ready(function() {
	 
	var items = <?php echo $js_array; ?>;
	
	$('.timepicker').timepicker({
		'disableTimeRanges': items
	});
	
	 });
	</script>
	