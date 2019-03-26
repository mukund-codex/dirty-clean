<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';
	$admin = new AdminFunctions();

	$pageName = "Subscription Features";
	$pageURL = 'subscription-features-add.php';

	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	
	$admin->checkUserPermissions('subscription_features_update',$loggedInUserDetailsArr);
	
	//include_once 'csrf.class.php';
	$csrf = new csrf();
	$token_id = $csrf->get_token_id();
	$token_value = $csrf->get_token($token_id);
	
	$data = $admin->getAllSubscriptionFeatures();
	
	if(isset($_POST['update'])) {
		if($csrf->check_valid('post')) {
			$features 			= $_POST['feature'];
			if(count($features) == 0){
				header("location:".$pageURL."?updatefail&msg=Please enter atleast one feature for this package");
				exit();
			}
			else {
				//update to database
				$result = $admin->updateSubscriptionFeatures($_POST);
				header("location:".$pageURL."?updatesuccess");
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
	<link href="css/emoji.css" rel="stylesheet">
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

	<script type="text/javascript">
		$(document).ready(function() {
			$("#form").validate({
				rules: {
					"features[]":{
						required:true
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
				<?php echo 'Update '.$pageName; ?>
				</li>
			</ul>
		</div>

		<br/><br/>
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
				<i class="icon-close"></i> <strong><?php echo $pageName; ?> not added.</strong> <?php echo $admin->escape_string($admin->strip_all($_GET['msg'])); ?>.
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
				<i class="icon-close"></i> <strong><?php echo $pageName; ?> not updated.</strong> <?php echo $admin->escape_string($admin->strip_all($_GET['msg'])); ?>.
			</div><br/>
<?php	} ?>
			<form role="form" action="" method="post" id="form" enctype="multipart/form-data">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h6 class="panel-title"><i class="icon-library"></i>Subscription Features Details</h6>
						<button type="button" class="btn btn-default pull-right" id="add-a-clone"><i class="icon-bubble-plus"></i>Add More</button>
					</div>
					<div class="panel-body" id="clone-house">
						<?php 
							if($admin->num_rows($data) > 0){
								$feature_Counter	= 1;
								while($row = $admin->fetch($data)){
						?>
						<div class="form-group clone-row" <?php if($feature_Counter==1){ ?>id="clone-me" <?php } ?>>
							<div class="row">
								<div class="col-sm-2">
									<label>Category</label>
									<select name="category[]" id="category[]" class="form-control">
										<option>Select Category</option>
										<option value="Category 1" <?php if(!empty($row['category']) && $row['category'] == 'Category 1') { echo "selected"; } ?> >Category 1</option>
										<option value="Category 2" <?php if(!empty($row['category']) && $row['category'] == 'Category 2') { echo "selected"; } ?> >Category 2</option>
										<option value="Category 3" <?php if(!empty($row['category']) && $row['category'] == 'Category 3') { echo "selected"; } ?> >Category 3</option>
									</select>
								</div>
								<div class="col-sm-2">
									<label>Package Name</label>
									<select name="package_name[]" id="package_name" class="form-control">
										<option>Package Name</option>
										<option value="bronze package" <?php if(!empty($row['package_name']) && $row['package_name'] == 'bronze package'){ echo "selected"; } ?> >Bronze Package</option>
										<option value="silver package" <?php if(!empty($row['package_name']) && $row['package_name'] == 'silver package'){ echo "selected"; } ?> >Silver Package</option>
										<option value="gold package" <?php if(!empty($row['package_name']) && $row['package_name'] == 'gold package'){ echo "selected"; } ?> >Gold Package</option>
										<option value="platinum package" <?php if(!empty($row['package_name']) && $row['package_name'] == 'platinum package'){ echo "selected"; } ?> >Platinum Package</option>
									</select>
								</div>
								<div class="col-sm-2">
									<label>Features Category</label>
									<select name="feature_category[]" id="feature_category" class="form-control">
										<option>Feature Category</option>
										<option value="Interior" <?php if(!empty($row['feature_category']) && $row['feature_category'] == 'Interior') { echo "selected"; } ?> >Interior</option>
										<option value="Exterior" <?php if(!empty($row['feature_category']) && $row['feature_category'] == 'Exterior') { echo "selected"; } ?> >Exterior</option>
										<option value="Engine Bay" <?php if(!empty($row['feature_category']) && $row['feature_category'] == 'Enginer Bay') { echo "selected"; } ?> >Engine Bay</option>
									</select>
								</div>
								<div class="col-sm-5">
									<label>Feature</label>
									<textarea class="form-control" name="feature[]" value="<?php if(!empty($row['feature'])){ echo $row['feature']; }?>"><?php if(!empty($row['feature'])){ echo $row['feature']; }?></textarea>
								</div>
								<div class="remove-row-wrapper">
									<?php if($feature_Counter!=1){ 
									?>
										<div class="col-sm-1">
											<label>Remove</label>
											<button type="button" class="btn btn-default form-control icon-close remove-row" ></button>
										</div>
								<?php } ?>
								</div>
							</div>
						</div>
					<?php  $feature_Counter++; 	} 
								}else{ ?>
						<div class="form-group clone-row" id="clone-me">
							<div class="row">
								<div class="col-sm-2">
									<label>Category</label>
									<select name="category[]" id="category" class="form-control">
										<option>Select Category</option>
										<option value="Category 1">Category 1</option>
										<option value="Category 2">Category 2</option>
										<option value="Category 3">Category 3</option>
									</select>
								</div>
								<div class="col-sm-2">
									<label>Package Name</label>
									<select name="package_name[]" id="package_name" class="form-control">
										<option>Package Name</option>
										<option value="bronze package">Bronze Package</option>
										<option value="silver package">Silver Package</option>
										<option value="gold package">Gold Package</option>
										<option value="platinum package">Platinum Package</option>
									</select>
								</div>
								<div class="col-sm-2">
									<label>Features Category</label>
									<select name="feature_category[]" id="feature_category" class="form-control">
										<option>Feature Category</option>
										<option value="Interior">Interior</option>
										<option value="Exterior">Exterior</option>
										<option value="Engine Bay">Engine Bay</option>
									</select>
								</div>
								<div class="col-sm-5">
									<label>Feature</label>
									<textarea class="form-control" name="feature" value=""></textarea>
								</div>
								<div class="remove-row-wrapper"></div>
							</div>
						</div>
					<?php   	} ?>
					</div>
				</div>
				<div class="form-actions text-right">
				<input type="hidden" name="<?php echo $token_id; ?>" value="<?php echo $token_value; ?>" />
					<button type="submit" name="update" class="btn btn-warning"><i class="icon-pencil"></i>Update <?php echo $pageName; ?></button>
				</div>
			</form>

<?php 	include "include/footer.php"; ?>
    
		</div>
	</div>
	<script>
		$(document).ready(function(){
						
			$("#add-a-clone").on("click", function(){
				// part 1: get the target
				var target = $("#clone-me");
				// part 2: copy the target
				var newNode = target.clone(); // clone a node
				newNode.attr("id",""); // remove id from the cloned node
				//newNode.find("select").val(""); // clear all fields
				newNode.find("input").val(""); // clear all fields
				newNode.find("textarea").val(""); // clear all fields
				// part 3: add a remove button
				var closeBtnNode = $('<div class="col-sm-1"><label>Remove</label><button type="button" class="btn btn-default form-control icon-close remove-row" ></button></div>');
				newNode.find(".remove-row-wrapper").html(closeBtnNode);
				// part 4: append the copy
				$("#clone-house").append(newNode); // append the node to dom
				$(".remove-row").on("click", removeRow);
			});

			$(".remove-row").on("click", removeRow);
			
			function removeRow(){
				$(this).closest(".clone-row").remove();
			}
			
		});
	</script>
	<script type="text/javascript" src="js/editor/ckeditor/ckeditor.js"></script>
	<script type="text/javascript" src="js/editor/ckfinder/ckfinder.js"></script>
	<script type="text/javascript">
		
		var editor = CKEDITOR.replace( 'feature', {
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
		// var editor = CKEDITOR.replace( 'workshop', {
		// 	height: 100,
		// 	filebrowserImageBrowseUrl : 'js/editor/ckfinder/ckfinder.html?type=Images',
		// 	filebrowserImageUploadUrl : 'js/editor/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
		// 	toolbarGroups: [
				
		// 		{"name":"document","groups":["mode"]},
		// 		{"name":"clipboard","groups":["undo"]},
		// 		{"name":"basicstyles","groups":["basicstyles"]},
		// 		{"name":"links","groups":["links"]},
		// 		{"name":"paragraph","groups":["list"]},
		// 		{"name":"insert","groups":["insert"]},
		// 		{"name":"insert","groups":["insert"]},
		// 		{"name":"styles","groups":["styles"]},
		// 		{"name":"paragraph","groups":["align"]},
		// 		{"name":"about","groups":["about"]},
		// 		{"name":"colors","tems": [ 'TextColor', 'BGColor' ] },
		// 	],
		// 	removeButtons: 'Iframe,Flash,Strike,Smiley,Subscript,Superscript,Anchor,Specialchar'
		// } );
	
		
		
		CKFinder.setupCKEditor( editor, '../' );
	</script>
</body>
</html>