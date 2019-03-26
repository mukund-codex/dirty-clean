<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';

	$admin = new AdminFunctions();
	$pageName = "FAQs";
	$pageURL = 'manage-faqs.php';
	$tableName = 'faqs';

	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	
	// if($loggedInUserDetailsArr['role']!='super') {
		// header("location: index.php");
		// exit();
	// }
	
	$admin->checkUserPermissions('cms_faqs_manage',$loggedInUserDetailsArr);
	
	$csrf = new csrf();
	$token_id = $csrf->get_token_id();
	$token_value = $csrf->get_token($token_id);
	
	if(isset($_POST['submit'])){	
	
		$result = $admin->updateFAQs($_POST);
		
		header("location:".$pageURL."?registersuccess");
	}
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo TITLE ?> | Add Concept</title>
	<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="css/londinium-theme.min.css" rel="stylesheet" type="text/css">
	<link href="css/styles.min.css" rel="stylesheet" type="text/css">
	<link href="css/icons.min.css" rel="stylesheet" type="text/css">
    <link rel="icon" type="image/ico" href="../images/favicon.png">
	<link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">

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
	<link href="../css/crop-image/cropper.min.css" rel="stylesheet">
	<script src="../js/crop-image/cropper.min.js"></script>
	<script src="../js/crop-image/image-crop-app.js"></script>
	
	<link href="css/font-awesome.min.css" rel="stylesheet">
		<script type="text/javascript">
		$(document).ready(function() {
			 /* $("#form").validate({
				rules: {
				
					'display_order[]':{
						required :true,
						number:true
					},
					
				},
				messages: {
					
					'display_order[]': {
						required: 'Please enter Display Order',
						number: 'Enter only number'
					},
					
				}
			});  */
		});
	</script>
	<style>
		.red{
			color: #ff0000;
		}
		button.btn.btn-default.fa.fa-times.remove-row {
		    position: absolute;
		    padding: 6px;
		    border-radius: 50%;
		    right: 5%;
		    top: 0;
		}
	</style>
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
				<?php echo 'Manage '.$pageName; ?>
			</div>
			<ul class="breadcrumb">
				<li><a href="index.php">Home</a></li>
				<li class="active">
					<?php echo 'Manage '.$pageName; ?>
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
			<form role="form" action="" method="POST" id="form" enctype="multipart/form-data">

				<div class="panel panel-default">
					<div class="panel-heading">
							<h6 class="panel-title"><i class="icon-library"></i> FAQs</h6>
							<button type="button" class="btn btn-default pull-right" id="add-a-clone"><i class="icon-bubble-plus"></i> Add More</button>
					</div>
					<div class="panel-body" id="clone-house">
					<?php
							$faqsResult=$admin->getFAQs();
							$faqsCounter=1;
							if($admin->num_rows($faqsResult)>0){
								while($faqsRow=$admin->fetch($faqsResult)){
					?>
									<div <?php if($faqsCounter==1){ ?>id="clone-me" <?php } ?> class="clone-row">
										<div class="form-group" >
											<div class="row">
												<div class="col-md-2">
													<label>Category</label>
													<select class="form-control" name="category[]">
														<option>Select Category</option>
														<option value="Booking" <?php if($faqsRow['category'] == 'Booking'){ echo 'selected'; } ?> >Booking</option>
														<option value="Process" <?php if($faqsRow['category'] == 'Process'){ echo 'selected'; } ?> >Process</option>
														<option value="Billing" <?php if($faqsRow['category'] == 'Billing'){ echo 'selected'; } ?> >Billing</option>
														<option value="General" <?php if($faqsRow['category'] == 'General'){ echo 'selected'; } ?> >General</option>
													</select>
													<span class="help-block"></span>
												</div>
												<div class="col-sm-3">
													<label>Question</label>
													<textarea class="form-control"  name="question[]" ><?php echo $faqsRow['question'] ?></textarea>
													<span class="help-block"></span>
												</div>
												<div class="col-sm-4">
													<label>Answer</label>
													<textarea class="form-control"  name="answer[]" ><?php echo $faqsRow['answer'] ?></textarea>
													<span class="help-block"></span>
												</div>
												<div class="col-sm-2">
													<label>Display Order <span style="color:red">*</span></label>
													<input type="number" min="1"  class="form-control" required  name="display_order[]" value="<?php echo $faqsRow['display_order'] ?>" >
													<span class="help-block"></span>
												</div>
												<div class="remove-row-wrapper">
													<?php if($faqsCounter!=1){ ?>
														<div class="col-sm-1">
															<label>Remove</label>
															<button type="button" class="btn btn-default form-control icon-close remove-row" ></button>
														</div>
													<?php } ?>
												</div>
											</div>
										</div>
										
									</div>
					<?php  $faqsCounter++;     } 
							}else{ ?>
								<div id="clone-me" class="clone-row">
									<hr>
									<div class="form-group" >
										<div class="row">
											<div class="col-md-2">
												<label>Category</label>
												<select class="form-control" name="category[]">
													<option>Select Category</option>
													<option value="Booking">Booking</option>
													<option value="Process">Process</option>
													<option value="Billing">Billing</option>
													<option value="General">General</option>
												</select>
												<span class="help-block"></span>
											</div>
											<div class="col-sm-3">
												<label>Question</label>
												<textarea class="form-control"  name="question[]" ></textarea>
												<span class="help-block"></span>
											</div>
											<div class="col-sm-4">
												<label>Answer</label>
												<textarea class="form-control"  name="answer[]" ></textarea>
													<span class="help-block"></span>
												<span class="help-block"></span>
											</div>
											<div class="col-sm-2">
													<label>Display Order</label>
													<input type="number" min="1" required class="form-control"  name="display_order[]" >
													<span class="help-block"></span>
											</div>
											<div class="remove-row-wrapper"></div>
										</div>
									</div>
									
								</div>	
					<?php   } ?>
					</div>
				</div>
				
				<div class="form-actions text-right">
					<input type="hidden" name="<?php echo $token_id; ?>" value="<?php echo $token_value; ?>" />
					<button type="submit" name="submit" class="btn btn-danger"><i class="icon-signup"></i>Manage <?php echo $pageName; ?></button>
				</div>
			</form>	
		</div>
	</div>

	<script type="text/javascript">

		$(document).ready(function() {
			
			$("#add-a-clone").on("click", function(){
				// part 1: get the target
				var target = $("#clone-me");
				// part 2: copy the target
				var newNode = target.clone(); // clone a node
				newNode.attr("id",""); // remove id from the cloned node
				newNode.find("select").val(""); // clear all fields
				newNode.find("input").val(""); // clear all fields
				newNode.find("textarea").val(""); // clear all fields
				newNode.find(".memimg").html(""); // clear all fields
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

</body>
</html>