<?php

	include_once 'include/config.php';

	include_once 'include/admin-functions.php';

	$func = new AdminFunctions();

	$category_id = $func->escape_string($func->strip_all($_GET['id']));

	$pageName = "Booking Details";

	$viewURL = 'booking-details.php';

	$tableName = 'booking_details';

	$tableName1 = 'personal_details';

	if(!$loggedInUserDetailsArr = $func->sessionExists()){

		header("location: admin-login.php");

		exit();

	}

	if(isset($_GET['page']) && !empty($_GET['page'])) {

		$pageNo = trim($func->strip_all($_GET['page']));

	} else {

		$pageNo = 1;

	}

	$linkParam = "";

	$query = "SELECT COUNT(*) as num FROM ".PREFIX.$tableName;

	$total_pages = $func->fetch($func->query($query));

	$total_pages = $total_pages['num'];

	include_once "include/pagination.php";

	$pagination = new Pagination();

	$paginationArr = $pagination->generatePagination($pageURL, $pageNo, $total_pages, $linkParam);



    $id = $func->escape_string($func->strip_all($_GET['id']));



	// $sql = "SELECT * FROM ".PREFIX.$tableName." order by created DESC LIMIT ".$paginationArr['start'].", ".$paginationArr['limit']."";

    $sql = "SELECT * FROM ".PREFIX.$tableName." where personal_id = '".$id."'";

	$results = $func->query($sql);

	// echo "SELECT * FROM ".PREFIX.$tableName1." where id = '".$id."'";exit;
	$sql1 = $func->query("SELECT * FROM ".PREFIX.$tableName1." where id = '".$id."'");

	$details = $func->fetch($sql1);

	if(isset($_POST['update'])){
		$payment_status = $func->escape_string($func->strip_all($_POST['payment_status']));
		$query = $func->query("update ".PREFIX."personal_details set payment_status = '".$payment_status."' where id='".$details['id']."' ");
		header("location:booking-details.php?updatesuccess&edit&id=".$id);
	}

?>

<!DOCTYPE html>

<html lang="en">

<head>

	<meta charset="utf-8">

	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title><?php echo TITLE ?></title>

	<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">

	<link href="css/londinium-theme.min.css" rel="stylesheet" type="text/css">

	<link href="css/styles.min.css" rel="stylesheet" type="text/css">

	<link href="css/icons.min.css" rel="stylesheet" type="text/css">



	<link href="css/font-awesome.min.css" rel="stylesheet">

	<!--<link href="css/nanoscroller.css" rel="stylesheet">

	<link href="css/cover.css" rel="stylesheet">-->



	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">

	<script type="text/javascript" src="js/jquery.1.10.1.min.js"></script>

	<script type="text/javascript" src="js/jquery-ui.1.10.2.min.js"></script>

	<script type="text/javascript" src="js/plugins/charts/sparkline.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/uniform.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/select2.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/inputmask.js"></script>

	<script type="text/javascript" src="js/plugins/forms/autosize.js"></script>

	<script type="text/javascript" src="js/plugins/forms/inputlimit.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/listbox.js"></script>

	<script type="text/javascript" src="js/plugins/forms/multiselect.js"></script>

	<script type="text/javascript" src="js/plugins/forms/validate.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/tags.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/switch.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/uploader/plupload.full.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/uploader/plupload.queue.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/wysihtml5/wysihtml5.min.js"></script>

	<script type="text/javascript" src="js/plugins/forms/wysihtml5/toolbar.js"></script>

	<script type="text/javascript" src="js/plugins/interface/daterangepicker.js"></script>

	<script type="text/javascript" src="js/plugins/interface/fancybox.min.js"></script>

	<script type="text/javascript" src="js/plugins/interface/moment.js"></script>

	<script type="text/javascript" src="js/plugins/interface/jgrowl.min.js"></script>

	<script type="text/javascript" src="js/plugins/interface/datatables.min.js"></script>

	<script type="text/javascript" src="js/plugins/interface/colorpicker.js"></script>

	<script type="text/javascript" src="js/plugins/interface/fullcalendar.min.js"></script>

	<script type="text/javascript" src="js/plugins/interface/timepicker.min.js"></script>

	<script type="text/javascript" src="js/plugins/interface/collapsible.min.js"></script>

	<script type="text/javascript" src="js/bootstrap.min.js"></script>

	<script type="text/javascript" src="js/application.js"></script>

</head>

<body class="sidebar-wide">

	<?php include 'include/navbar.php' ?>



	<div class="page-container">



		<?php include 'include/sidebar.php' ?>



 		<div class="page-content">

    

			<div class="breadcrumb-line">

				<div class="page-ttle hidden-xs" style="float:left;"><?php echo $pageName; ?></div>

				<ul class="breadcrumb">

					<li><a href="banner-master.php">Home</a></li>

					<li class="active"><?php echo $pageName; ?></li>

				</ul>

			</div>



			<a href="personal-details.php" class="label label-primary">Back to Personal Details</a><br/><br/>

			

			<!-- <a href="<?php echo $addURL; ?>?category_id=<?php echo $category_id ?>" class="label label-primary">Add <?php echo $pageName; ?></a> -->

			



	<?php

		if(isset($_GET['deletesuccess'])){ ?>

			<div class="alert alert-success alert-dismissible" role="alert">

				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

				<i class="icon-checkmark"></i> <?php echo $pageName; ?> successfully deleted.

			</div><br/>

	<?php	} ?>

	

	<?php

		if(isset($_GET['deletefail'])){ ?>

			<div class="alert alert-danger alert-dismissible" role="alert">

				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>

				<i class="icon-close"></i> <strong><?php echo $pageName; ?> not deleted.</strong> Invalid Details.

			</div><br/>

	<?php	} ?>



			<br/>

			<div class="panel panel-default">



				<div class="datatable-selectable-data">

					<table class="table">

						<thead>

							<tr>

								<th>#</th>

								<th>Customer Name</th>

								<th>Category</th>

								<th>Package</th>

								<th>Price</th>

							</tr>

						</thead>

						<tbody>

<?php

						$x = (10*$pageNo)-9;
						$total = '';
						while($row = $func->fetch($results)){ 
							$total = $total + $row['price'];
?>

							<tr>

								<td><?php echo $x++; ?></td>

								<td><?php echo $details['first_name']." ".$details['last_name']; ?></td>

								<td><?php

                                    $category = $func->fetch($func->query("SELECT category_name from ".PREFIX."category_master WHERE id = '".$row['vehicle_category']."'"));

                                    echo $category['category_name']; ?></td>

                                <td><?php

                                   if($row['vehicle_package'] == 'quick-wash'){

                                       echo 'Quick Wash';

                                   } else{

                                    $package = $func->fetch($func->query("SELECT package_name from ".PREFIX."package_master WHERE id = '".$row['vehicle_package']."'"));

                                    echo $package['package_name']; 

                                   }

                                ?></td>

								<td><?php echo $row['price']; ?></td>

							</tr>

<?php

						}

?>

						</tbody>

				  </table>

				</div>

			</div>



			<div class="row">

				<div class="col-md-12 clearfix">

					<nav class="pull-right">

						<?php // echo $paginationArr['paginationHTML']; ?>

					</nav>

				</div>

			</div>
			<br>
			<div class="row">
				<div class="col-md-3">
					<label>Booking Id</label>
					<input type="text" name="booking_id" id="booking_id" class="form-control" value="<?php echo $details['booking_id']; ?>" readonly />
				</div>
				<div class="col-md-3">
					<label>Sub Total</label>
					<input type="text" name="sub_total" id="sub_total" class="form-control" value="<?php echo $total; ?>" readonly />
				</div>
				<div class="col-md-3">
					<label>Discount Value</label>
					<input type="text" name="sub_total" id="sub_total" class="form-control" value="<?php if(!empty($details['final_total'])) { echo $total-$details['final_total']; } else{ echo ''; } ?>" readonly />
				</div>
				<div class="col-md-3">
					<label>Final Total</label>
					<input type="text" name="sub_total" id="sub_total" class="form-control" value="<?php if(!empty($details['final_total'])) { echo $details['final_total']; } else { echo $total; } ?>" readonly />
				</div>
			</div>
			<br>
			<form method="post">
			<div class="row">
				<div class="col-md-4">
					<label>Payment Mode</label>
					<input type="text" name="payment_mode" id="payment_mode" class="form-control" value="<?php echo $details['payment_mode']; ?>" readonly/>
				</div>
				<div class="col-md-4">
					<label>Payment Status</label>
					<select name="payment_status" id="payment_status" class="form-control">
						<option value="Pending" <?php if($details['payment_mode'] == 'COD' || $details['payment_mode'] == '') { echo 'Selected'; } ?>>Pending</option>
						<option value="Completed" <?php if(!empty($details['payment_status']) && $details['payment_status'] == 'Completed') { echo 'Selected'; } ?>>Completed</option>
					</select>
				</div>
				<div class="col-md-4">
					<label>&nbsp;</label><br>
					<input type="submit" name="update" id="update" class="btn btn-danger" value="Update Payment Status" />
				</div>
			</div>
			</form>
			<br>

<?php 	include "include/footer.php"; ?>

		</div>



	</div>



	<link href="css/jquery.dataTables.min.css" rel="stylesheet">

	<script type="text/javascript" src="js/jquery.dataTables.min.js"></script>

	<script>

		$(document).ready(function() {

			$('.datatable-selectable-data table').dataTable({

				"order": [[ 0, 'asc' ]],

			});

		});

	</script>

</body>

</html>