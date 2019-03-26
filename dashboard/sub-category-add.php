<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';
	$admin = new AdminFunctions();

	$category_id = $admin->escape_string($admin->strip_all($_GET['category_id']));

	$pageName = "Sub Category Master";
	$parentPageURL = 'sub-category-master.php?id='.$category_id;
	$pageURL = 'sub-category-add.php';

	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}

	
	//include_once 'csrf.class.php';
	$csrf = new csrf();
	$token_id = $csrf->get_token_id();
	$token_value = $csrf->get_token($token_id);
	
	if(isset($_POST['register'])){
		if($csrf->check_valid('post')) {
			$allowed_ext = array('image/jpeg','image/jpg');
			
			$name = trim($admin->escape_string($admin->strip_all($_POST['name'])));
			
			if(empty($name)){
				header("location:".$pageURL."?registerfail&msg=Please enter a name&category_id=".$category_id);
				exit();
			}
			/* else if(!in_array($_FILES['image_name']['type'],$allowed_ext)) {
				header("location:".$pageURL."?registerfail&msg=Please upload jpg image only");
				exit();
			} */
			else {
				//add to database
				$result = $admin->addSubCategory($_POST,$_FILES);
				header("location:".$pageURL."?registersuccess&category_id=".$category_id);
			}
		}
	}
	if(isset($_GET['edit'])){
		$id = $admin->escape_string($admin->strip_all($_GET['id']));
		$data = $admin->getUniqueSubCategoryById($id);
	}
	if(isset($_POST['update'])) {
		if($csrf->check_valid('post')) {
			$allowed_ext = array('image/jpeg','image/jpg');
			$id = trim($admin->escape_string($admin->strip_all($_POST['id'])));
			$name = trim($admin->escape_string($admin->strip_all($_POST['name'])));
			if(empty($id) || empty($name)){
				header("location:".$pageURL."?updatefail&msg=Please enter a name&edit&id=".$id."&category_id=".$category_id);
				exit();
			}
			/* else if(!empty($_FILES['image_name']['name']) and !in_array($_FILES['image_name']['type'],$allowed_ext)) {
				header("location:".$pageURL."?updatefail&msg=Please upload jpg image only&edit&id=".$id);
				exit();
			} */
			else {
				//update to database
				$result = $admin->updateSubCategory($_POST,$_FILES);
				header("location:".$pageURL."?updatesuccess&edit&id=".$id."&category_id=".$category_id);
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
	<script type="text/javascript" src="js/additional-methods.js"></script>
	<script src="//cdn.ckeditor.com/4.5.5/full/ckeditor.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			
			$("#form").validate({
				rules: {
					
					name: {
						required: true
						// letterswithspaceonly : true
					},
					active: {
						required: true
					},
					image_name:{
						extension:'png|jpg'
					},
					display_order:{
						required:true,
						number:true
					}
				
				},
				messages: {
					image_name: {
						extension: 'Upload image with jpg, jpeg or png extension'
					},
					display_order:{
						required: 'Please enter Display Order',
						number: 'please enter number'
					}
				}
			});
			jQuery.validator.addMethod("letterswithspaceonly", function(value, element) {
			return this.optional(element) || /^[^-\s][a-zA-Z\s-]+$/i.test(value);
			}, "Please enter valid value");
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
				<li><a href="banner-master.php">Home</a></li>
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
						<h6 class="panel-title"><i class="icon-library"></i>Sub Category Details</h6>
					</div>
					<div class="panel-body">
						<div class="form-group">
							<div class="row">
								<div class="col-sm-4">
									<label>Image <span style="color:red">*</span></label>
									<input type="file" accept="image/jpg,image/png,image/jpeg" class="form-control" name="image_name" <?php if(!isset($_GET['edit'])) { echo 'required'; } ?> id="" data-image-index="0" />
									<br>
									<span class="help-text">
										Files must be less than <strong>2 MB</strong>.<br>
										Allowed file types: <strong>png jpg jpeg</strong>.<br>
										Images must be exactly <strong>1300x300</strong> pixels.
									</span><br>
									<?php if(isset($_GET['edit'])) {
										$file_name = str_replace('', '-', strtolower( pathinfo($data['image_name'], PATHINFO_FILENAME)));
										$ext = pathinfo($data['image_name'], PATHINFO_EXTENSION);
									?>
										<img src="../img/cat-banner/<?php echo $file_name.'_crop.'.$ext ?>" width="100"  />
									<?php
									} ?>
								</div>
								<div class="col-sm-4">
									<label>Sub Category Name <span style="color:red">*</span></label>
									<input type="text" class="form-control" required="required" name="name" id="" value="<?php if(isset($_GET['edit'])){ echo $data['sub_category_name']; }?>"/>
								</div>
								<div class="col-sm-4">
									<label>Active <span style="color:red">*</span></label>
									<select class="form-control" required="required" name="active" id="" >
										<option value="Yes" <?php if(isset($_GET['edit']) && $data['active']=="Yes"){ echo 'selected="selected"'; }?> >Yes</option>
										<option value="No" <?php if(isset($_GET['edit']) && $data['active']=="No"){ echo 'selected="selected"'; }?> >No</option>
									</select>
									<span class="help-block"></span>
								</div>
							</div><br>
							<div class="row">
							<div class="col-sm-4">
								<label>Display Order <span style="color:red">*</span></label>
									<input type="text" min="1" placeholder="Display Order" class="form-control" required="required" name="display_order" id="" value="<?php if(isset($_GET['edit'])){ echo $data['display_order']; }?>"/>
								</div>
							</div><br>
							<div class="row">
								<div class="col-sm-12">
									<label>Description <span style="color:red">*</span> </label>
									<textarea type="text" class="form-control" required  name="description" id="" /><?php if(!empty($data['description'])){ echo $data['description']; }?></textarea>
								</div>
							</div>
						</div>
						
					</div>
					
				</div>
				<div class="form-actions text-right">
				<input type="hidden" name="<?php echo $token_id; ?>" value="<?php echo $token_value; ?>" />
				<input type="hidden" name="category_id" value="<?php echo $category_id; ?>" />
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

	<link href="css/crop-image/cropper.min.css" rel="stylesheet">
	<script src="js/crop-image/cropper.min.js"></script>
	<script src="js/crop-image/image-crop-app.js"></script>
	<script type="text/javascript" src="js/editor/ckeditor/ckeditor.js"></script>
	<script type="text/javascript" src="js/editor/ckfinder/ckfinder.js"></script>
	<script>
	var editor = CKEDITOR.replace( 'description', {
			height: 300,
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
	<script>
	$(document).ready(function() {
		$('input[name="image_name"]').change(function(){
			// loadImageInModal(this);
			loadImagePreview(this, (1300 / 300));
		});
	});
	</script>
</body>
</html>