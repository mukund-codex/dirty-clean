<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';
	$func = new AdminFunctions();
	$pageName = "Detailling Package";
	$parentPageURL = 'vendor-subscription.php';
	$pageURL = 'subscription-add.php';
	
	if(!$loggedInUserDetailsArr = $func->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	
	if(isset($_GET['edit'])){
		$func->checkUserPermissions('vendor_subscription_update',$loggedInUserDetailsArr);
	}else{
		$func->checkUserPermissions('vendor_subscription_create',$loggedInUserDetailsArr);
	}

	//include_once 'csrf.class.php';
	$csrf = new csrf();
	$token_id = $csrf->get_token_id();
	$token_value = $csrf->get_token($token_id);
	
	if(isset($_POST['register'])){
		if($csrf->check_valid('post')) {
			$package_name 		= trim($func->escape_string($func->strip_all($_POST['package_name'])));
			$validity_period 	= trim($func->escape_string($func->strip_all($_POST['validity_period'])));
			$package_price 		= trim($func->escape_string($func->strip_all($_POST['package_price'])));
			
			if(empty($package_name)){
				header("location:".$pageURL."?registerfail&msg=Please enter a Package Name");
				exit();
			}
			else if(empty($validity_period)){
				header("location:".$pageURL."?registerfail&msg=Please enter a Validity Period");
				exit();
			}
			else {
				//add to database
				$result = $func->addSubscription($_POST);
				header("location:".$pageURL."?registersuccess");
			}
		}
	}
	
	if(isset($_GET['edit'])){
		$id = $func->escape_string($func->strip_all($_GET['id']));
		$data = $func->getUniqueSubscriptionById($id);
		// $pieces = explode(" ", $data['validity_period']);
		// $duration = $pieces[0]/60; // piece1
	}
	if(isset($_POST['update'])) {
		if($csrf->check_valid('post')) {
			$id 				= trim($func->escape_string($func->strip_all($_POST['id'])));
			$package_name 		= trim($func->escape_string($func->strip_all($_POST['package_name'])));
			$validity_period 	= trim($func->escape_string($func->strip_all($_POST['validity_period'])));
			$package_price 		= trim($func->escape_string($func->strip_all($_POST['package_price'])));
			if(empty($package_name)){
				header("location:".$pageURL."?updatefail&msg=Please enter a Package Name&edit&id=".$id);
				exit();
			}
			// else if(empty($validity_period)){
			// 	header("location:".$pageURL."?updatefail&msg=Please enter a Validity Period&edit&id=".$id);
			// 	exit();
			// }
			else {
				//update to database
				$result = $func->updateSubscription($_POST);
				header("location:".$pageURL."?updatesuccess&edit&id=".$id);
			}
		}
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
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">
	
	<link href="css/font-awesome.min.css" rel="stylesheet">
	<link href="css/nanoscroller.css" rel="stylesheet">
	<!--<link href="css/emoji.css" rel="stylesheet">-->
	<link href="css/cover.css" rel="stylesheet">
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
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
	<script type="text/javascript" src="js/additional-methods.js"></script>
	<script>
		$(document).ready(function() {
			$("#myform").validate({
				rules: {
					category: {
						required: true
					},
					 package_name: {
						required: true
					},
					validity_period: {
						required: true
					},
					package_price: {
						required: true
					},
					active: {
						required: true
					},
					duration_from: {
						required: true
					},
					duration_to: {
						required: true
					}
					
				},
				messages: {
					category:{
						required: "Please Select Category"
					},
					 package_name:{
						required: "Please Select Package Name"
					},
				validity_period:{
						required: "Please Enter Duration"
					},
					package_price:{
						required: "Please Enter Price"
					},
					active: {
						required: "Please Select Active Status"
					},
					duration_from: {
						required: "Please enter duration from"
					},
					duration_to: {
						required: "Please enter duration to"
					}
					
				}
			});
		});
	</script>
</head>
<body class="sidebar-wide">
	<?php include 'include/navbar.php' ?>

	<div class="page-container">

		<?php include 'include/sidebar.php' ?>

		<div class="page-content">

		<!--
			<div class="page-header">
				<div class="page-title">
					<h3>Dashboard <small>Welcome Eugene. 12 hours since last visit</small></h3>
				</div>
				<div id="reportrange" class="range">
					<div class="visible-xs header-element-toggle"><a class="btn btn-primary btn-icon"><i class="icon-calendar"></i></a></div>
					<div class="date-range"></div>
					<span class="label label-danger">9</span>
				</div>
			</div>
		-->

		<div class="breadcrumb-line">
			<div class="page-ttle hidden-xs" style="float:left;">
<?php
				if(isset($_GET['edit'])){ ?>
					<?php echo 'Edit '.$pageName; ?>
<?php			} else { ?>
					<?php echo 'Add New '.$pageName; ?>
<?php			} ?>
			</div>
			<ul class="breadcrumb">
				<li><a href="index.php">Home</a></li>
				<li><a href="<?php echo $parentPageURL; ?>"><?php echo $pageName; ?></a></li>
				<li class="active">
<?php
				if(isset($_GET['edit'])){ ?>
					<?php echo 'Edit '.$pageName; ?>
<?php			} else { ?>
					<?php echo 'Add New '.$pageName; ?>
<?php			} ?>
				</li>
			</ul>
		</div>

		<a href="<?php echo $parentPageURL; ?>" class="label label-primary">Back to <?php echo $pageName; ?></a><br/><br/>
<?php
		if(isset($_GET['registersuccess'])){ ?>
			<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-checkmark3"></i> <?php echo $pageName; ?> successfully added.
			</div><br/>
<?php	} ?>
	
<?php
		if(isset($_GET['registerfail'])){ ?>
			<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-close"></i> <strong><?php echo $pageName; ?> not added.</strong> <?php echo $func->escape_string($func->strip_all($_GET['msg'])); ?>.
			</div><br/>
<?php	} ?>

<?php
		if(isset($_GET['updatesuccess'])){ ?>
			<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-checkmark3"></i> <?php echo $pageName; ?> successfully updated.
			</div><br/>
<?php	} ?>
	
<?php
		if(isset($_GET['updatefail'])){ ?>
			<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-close"></i> <strong><?php echo $pageName; ?> not updated.</strong> <?php echo $func->escape_string($func->strip_all($_GET['msg'])); ?>.
			</div><br/>
<?php	} ?>
			<form role="form" action="" method="post" id="myform" enctype="multipart/form-data">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h6 class="panel-title"><i class="icon-library"></i>Detailling Package Details</h6>
					</div>
					<div class="panel-body">
						<div class="form-group">
							<div class="row">
								<div class="col-md-3">
									<label>Category<span style="color:red;">*</span></label>
									<select name="category" id="category" class="form-control" required>
										<option value="">Select Category</option>
										<?php $getAllCategories = $func->getAllCategories();
											  while($rw = $func->fetch($getAllCategories)){
										?> 
										<option <?php if(isset($_GET['edit']) && $data['category'] == $rw['id']){ echo "selected"; } ?> value="<?php echo $rw['id']; ?>"  ><?php echo $rw['category_name']; ?></option>
										<?php } ?>
									</select>
								</div>
								
								<div class="col-sm-3">
									<label>Package Name<span style="color:red;">*</span></label>
									<select name="package_name" id="package_name" class="form-control" required>
										<option value="">Select Package Name</option>
										<?php $getAllPackages = $func->getAllPackages();
											  while($rw = $func->fetch($getAllPackages)){
										?> 
										<option <?php if(isset($_GET['edit']) && $data['package_name'] == $rw['id']){ echo "selected"; } ?> value="<?php echo $rw['id']; ?>"  ><?php echo $rw['package_name']; ?></option>
										<?php } ?>
									</select>
								</div>
								<!--<div class="col-sm-2">
									<label>Duration (In Hours)<span style="color:red;">*</span></label>
									<input type="text" class="form-control" required="required" name="validity_period" id="validity_period" value="<?php //if(isset($_GET['edit'])){ echo $data['validity_period']/60; }?>"/>
								</div>-->
								<div class="col-sm-3">
									<label>Package Price(In INR)<span style="color:red;">*</span></label>
									<input type="text" class="form-control" required="required" name="package_price" id="package_price" value="<?php if(isset($_GET['edit'])){ echo $data['package_price']; }?>"/>
								</div>
								<div class="col-sm-2">
									<label>Active<span style="color:red;">*</span></label>
									<select name="active" id="active" class="select-multiple" required>
										<option value="No" <?php if(isset($_GET['edit']) && $data['active']=='No'){ echo "selected"; } ?>>No</option>
										<option value="Yes" <?php if(isset($_GET['edit']) && $data['active']=='Yes'){ echo "selected"; } ?>>Yes</option>
									</select>
								</div>								
							</div><br>
							<div class="row">
								<div class="col-sm-3">
									<label>Duration From (In Hours)<span style="color:red;">*</span></label>
									<input type="text" class="form-control" required="required" placeholder="Duration From" name="duration_from" id="duration_from" value="<?php if(isset($_GET['edit'])){ echo $data['duration_from']/60; }?>"/>
								</div>	
								<div class="col-sm-3">
									<label>Duration To (In Hours)<span style="color:red;">*</span></label>
									<input type="text" class="form-control" required="required" placeholder="Duration To" name="duration_to" id="duration_to" value="<?php if(isset($_GET['edit'])){ echo $data['duration_to']/60; }?>"/>
								</div>	
							</div>
							<br>
							<div class="row">
								<div class="col-md-12">
									<label>Interior Features</label>
									<textarea col="5" rows="4" class="form-control" name="interior_feature" id=""><?php if(isset($_GET['edit']) && !empty($data['interior_feature'])){ echo $data['interior_feature']; } ?></textarea>
								</div>
							</div>
							<br>
							<div class="row">
								<div class="col-md-12">
									<label>Exterior Features</label>
									<textarea col="5" rows="4" class="form-control" name="exterior_feature" id=""><?php if(isset($_GET['edit']) && !empty($data['exterior_feature'])){ echo $data['exterior_feature']; } ?></textarea>
								</div>
							</div>
							<br>
							<div class="row">
								<div class="col-md-12">
									<label>Engine Bay Features</label>
									<textarea col="5" rows="4" class="form-control" name="engine_bay_feature" id=""><?php if(isset($_GET['edit']) && !empty($data['engine_bay_feature'])){ echo $data['engine_bay_feature']; } ?></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="form-actions text-right">
				<input type="hidden" name="<?php echo $token_id; ?>" value="<?php echo $token_value; ?>" />
<?php
			if(isset($_GET['edit'])){ ?>
					<input type="hidden" class="form-control" name="id" id="" required="required" value="<?php echo $id ?>"/>
					<button type="submit" name="update" class="btn btn-warning"><i class="icon-pencil"></i>Update <?php echo $pageName; ?></button>
<?php		} else { ?>
					<button type="submit" name="register" class="btn btn-danger"><i class="icon-signup"></i>Add <?php echo $pageName; ?></button>
<?php		} ?>
				</div>
			</form>

<?php 	include "include/footer.php"; ?>
    
		</div>
	</div>
	<script type="text/javascript" src="js/editor/ckeditor/ckeditor.js"></script>
	<script type="text/javascript" src="js/editor/ckfinder/ckfinder.js"></script>
	<script type="text/javascript">
		
		var editor = CKEDITOR.replace( 'interior_feature', {
			height: 100,
			filebrowserImageBrowseUrl : 'js/editor/ckfinder/ckfinder.html?type=Images',
			filebrowserImageUploadUrl : 'js/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
			toolbarGroups: [
				
				{"name":"document","groups":["mode"]},
				{"name":"clipboard","groups":["undo"]},
				{"name":"basicstyles","groups":["basicstyles"]},
				{"name":"links","groups":["links"]},
				{"name":"paragraph","groups":["list"]},
				{"name":"insert","groups":["insert"]},
				{"name":"insert","groups":["insert"]},
				{"name":"styles","groups":["styles"]},
				{"name":"paragraph","groups":["align"]},
				{"name":"about","groups":["about"]},
				{"name":"colors","tems": [ 'TextColor', 'BGColor' ] },
			],
			removeButtons: 'Iframe,Flash,Strike,Smiley,Subscript,Superscript,Anchor,Specialchar'
		} );

		var editor = CKEDITOR.replace( 'exterior_feature', {
			height: 100,
			filebrowserImageBrowseUrl : 'js/editor/ckfinder/ckfinder.html?type=Images',
			filebrowserImageUploadUrl : 'js/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
			toolbarGroups: [
				
				{"name":"document","groups":["mode"]},
				{"name":"clipboard","groups":["undo"]},
				{"name":"basicstyles","groups":["basicstyles"]},
				{"name":"links","groups":["links"]},
				{"name":"paragraph","groups":["list"]},
				{"name":"insert","groups":["insert"]},
				{"name":"insert","groups":["insert"]},
				{"name":"styles","groups":["styles"]},
				{"name":"paragraph","groups":["align"]},
				{"name":"about","groups":["about"]},
				{"name":"colors","tems": [ 'TextColor', 'BGColor' ] },
			],
			removeButtons: 'Iframe,Flash,Strike,Smiley,Subscript,Superscript,Anchor,Specialchar'
		} );

		var editor = CKEDITOR.replace( 'engine_bay_feature', {
			height: 100,
			filebrowserImageBrowseUrl : 'js/editor/ckfinder/ckfinder.html?type=Images',
			filebrowserImageUploadUrl : 'js/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
			toolbarGroups: [
				
				{"name":"document","groups":["mode"]},
				{"name":"clipboard","groups":["undo"]},
				{"name":"basicstyles","groups":["basicstyles"]},
				{"name":"links","groups":["links"]},
				{"name":"paragraph","groups":["list"]},
				{"name":"insert","groups":["insert"]},
				{"name":"insert","groups":["insert"]},
				{"name":"styles","groups":["styles"]},
				{"name":"paragraph","groups":["align"]},
				{"name":"about","groups":["about"]},
				{"name":"colors","tems": [ 'TextColor', 'BGColor' ] },
			],
			removeButtons: 'Iframe,Flash,Strike,Smiley,Subscript,Superscript,Anchor,Specialchar'
		} );
		CKFinder.setupCKEditor( editor, '../' );
	</script>
</body>
</html>