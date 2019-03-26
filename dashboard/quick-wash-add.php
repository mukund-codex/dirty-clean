<?php
	include_once 'include/config.php';
	include_once 'include/admin-functions.php';
	$admin = new AdminFunctions();

	$pageName = "Quick Wash";
	$pageURL = 'quick-wash-add.php';

	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	
	$admin->checkUserPermissions('subscription_features_update',$loggedInUserDetailsArr);
	
	//include_once 'csrf.class.php';
	$csrf = new csrf();
	$token_id = $csrf->get_token_id();
	$token_value = $csrf->get_token($token_id);
	
	if(isset($_GET['edit']) && !empty($_GET['id'])){
		$id = $admin->escape_string($admin->strip_all($_GET['id']));
		$row = $admin->getUniqueQuickWashById($id);
	}

	if(isset($_POST['add'])) {

        $result = $admin->addQuickwashDetails($_POST, $_FILES);
        header("location:".$pageURL."?updatesuccess");
    }
    
    if(isset($_POST['update'])) {

        $result = $admin->updateQuickWash($_POST, $_FILES);
        header("location:".$pageURL."?updatesuccess&edit&id=".$id);
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo TITLE ?></title><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"><link rel="icon" href="../img/logo.ico" type="image/x-icon">
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
	<link href="css/bootstrap-datetimepicker.css" rel="stylesheet">
	<script src="js/moment.min.js"></script>
	<script src="js/bootstrap-datetimepicker.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#form").validate({
				//ignore: [],
				//debug: false,
				rules: {
					
					image_name: {
						extension: 'jpg|png|jpeg'
					},
					title:{
						required:true
					},
					
				},
				messages: {
					/* video_name: {
						extension: 'Upload image with webm, mp4 or ogv extension'
					}, */
					
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

		<div class="breadcrumb-line">
			<div class="page-ttle hidden-xs" style="float:left;">
				<?php
				if(isset($_GET['edit'])){ ?>
					<?php echo 'Edit '.$pageName; ?>
				<?php	} else { ?>
					<?php echo 'Add New '.$pageName; ?>
				<?php	} ?>
			</div>
			<ul class="breadcrumb">
				<li><a href="banner-master.php">Home</a></li>
				<li><a href="<?php echo $parentPageURL; ?>"><?php echo $pageName; ?></a></li>
				<li class="active">
					<?php
					if(isset($_GET['edit'])){ ?>
						<?php echo 'Edit '.$pageName; ?>
					<?php	} else { ?>
						<?php echo 'Add New '.$pageName; ?>
					<?php	} ?>
				</li>
			</ul>
		</div>

		
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
						<h6 class="panel-title"><i class="icon-library"></i> Package Detailing Master</h6>
					</div>
					<div class="panel-body">
						<div class="form-group">
						    <div class="row">
								<div class="col-md-3">
									<label>Category</label>
									<select name="category" id="category" class="form-control">
										<option value="">Select Category</option>
										<?php $getAllCategories = $admin->getAllCategories();
											  while($rw = $admin->fetch($getAllCategories)){
										?> 
										<option <?php if(isset($_GET['edit']) && $row['category'] == $rw['category_name']){ echo "selected"; } ?> value="<?php echo $rw['id']; ?>"  ><?php echo $rw['category_name']; ?></option>
										<?php } ?>
									</select>
								</div>
								
								<div class="col-sm-3">
									<label>Time ( In Mins )<span style="color:red">*</span> </label>
									<input type="text" name="timemins" id="timemins" class="form-control" value="<?php if(isset($_GET['edit']) && !empty($row['time'])) { echo $row['time']; } ?>"/>
								</div>
                                <div class="col-sm-3">
									<label>Price<span style="color:red">*</span> </label>
									<input type="text" name="price" id="price" class="form-control" value="<?php if(isset($_GET['edit']) && !empty($row['time'])) { echo $row['price']; } ?>"/>
								</div>
							</div>	
                            <br>
                            <div class="row">                                
                                <div class="col-sm-12">
                                    <label>Main Feature <span style="color:red">*</span> </label>
                                    <textarea col="5" rows="4"  class="form-control" required  name="main_feature" id=""><?php if(isset($_GET['edit']) && !empty($row['main_features'])){ echo $row['main_features']; } ?></textarea>
                                </div> 
                            </div><br>	
							<div class="row">
								<div class="col-sm-12">
                                    <label>Interior Feature <span style="color:red">*</span> </label>
                                    <textarea col="5" rows="4"  class="form-control" required  name="interior_feature" id=""><?php if(isset($_GET['edit']) && !empty($row['interior_feature'])){ echo $row['interior_feature']; } ?></textarea>
                                </div> 
							</div>	<br>
							<div class="row">
								<div class="col-sm-12">
                                    <label>Exterior Feature <span style="color:red">*</span> </label>
                                    <textarea col="5" rows="4"  class="form-control" required  name="exterior_feature" id=""><?php if(isset($_GET['edit']) && !empty($row['exterior_feature'])){ echo $row['exterior_feature']; } ?></textarea>
                                </div> 
							</div>	<br>
							<div class="row">
								<div class="col-sm-3">
									<label>Image <span style="color:red">*</span></label>
									<input type="file" class="form-control" name="image_name" id="image_name" accept="image/jpg,image/png,image/jpeg" <?php if(!isset($_GET['edit'])) { echo 'required'; } ?> id="" data-image-index="0" />
									<span class="help-text">
										Files must be less than <strong>3 MB</strong>.<br>
										Allowed file types: <strong>png jpg jpeg</strong>.<br>
										Images must be exactly <strong>454x235</strong> pixels.
									</span>
									<br>
									<?php if(isset($_GET['edit'])) {
										$file_name = str_replace('', '-', strtolower( pathinfo($row['image'], PATHINFO_FILENAME)));
										$ext = pathinfo($row['image'], PATHINFO_EXTENSION);
									?>
										<img src="../img/quick-wash/<?php echo $file_name.'_crop.'.$ext ?>" width="100"  />
									<?php
									} ?>
								</div>
							</div>		
					    </div>
                    </div>
				</div>
				<div class="form-actions text-right">
                    <?php if(isset($_GET['edit'])){ ?>
					<input type="hidden" name="id" name="id" value="<?php echo $row['id']; ?>" class="form-control" />
 					<button type="submit" name="update" id="update" class="btn btn-warning"><i class="icon-pencil"></i>Update <?php echo $pageName; ?></button>
                    <?php }else{ ?>
                    <button type="submit" name="add" id="add" class="btn btn-warning"><i class="icon-pencil"></i>Add <?php echo $pageName; ?></button>
                    <?php } ?>
				</div>
			</form>

<?php 	include "include/footer.php"; ?>
    
		</div>
	</div>

	<link href="css/crop-image/cropper.min.css" rel="stylesheet">
	<script src="js/crop-image/cropper.min.js"></script>
	<script src="js/crop-image/image-crop-app.js"></script>
	<script>
        //CKEDITOR.replace( 'text1' );
        $("form").submit( function(e) {
            var messageLength = CKEDITOR.instances['editor'].getData().replace(/<[^>]*>/gi, '').length;
            if( !messageLength ) {
                alert( 'Please enter a message' );
                e.preventDefault();
            }
        });
	$(document).ready(function(){
		$('input[type="file"]').change(function(){
			// loadImageInModal(this);
			loadImagePreview(this, (454 / 235));
		});
	});
	function validateInputArr(eleArr, errorMsg){
	var errArr = [];
	eleArr.each(function(index, ele){
	var eleObj = jQuery(ele);
	var eleVal = eleObj.val();
	if(eleVal){
	eleObj.closest("div").find('div.error').remove();
	} else {
	eleObj.closest("div").find('div.error').remove();
	eleObj.closest("div").append('<div class="error" style="color:red;">' + errorMsg + '</div>');
	errArr.push(index);
	}
	});
	return errArr;
	}

	function validateNumberArr(eleArr, errorMsg){
	var errArr = [];
	eleArr.each(function(index, ele){
	var eleObj = jQuery(ele);
	var eleVal = eleObj.val();
	if(eleVal==""){
		eleObj.closest("div").find('div.error').remove();
		eleObj.closest("div").append('<div class="error" style="color:red;">' + errorMsg + '</div>');
		errArr.push(index);	
	} else if(isNaN(eleVal))  {
		eleObj.closest("div").find('div.error').remove();
		eleObj.closest("div").append('<div class="error" style="color:red;">Please enter display order in number.</div>');
		errArr.push(index); 		
	} else {
		eleObj.closest("div").find('div.error').remove();
	}
	
	
	
	
	
	});
	return errArr;
	}
	$('#update').on('click', function(event) {
		var formData = $("#form").valid();
		var text1=$("input[name='video_link[]']");
		var display_order=$("input[name='display_order[]']");
		
		var text1Validate = validateInputArr(text1,"Please enter Video Link.");
		var displayOrderValidate = validateNumberArr(display_order,"Please enter Display Order.");
		if(formData  && text1Validate.length==0 && displayOrderValidate.length==0 ){
			 $("#form").submit();
		}else{
			return false;
		}
	});

	
    </script>
	<script>
	$(document).ready(function() {
		
	});
	</script>
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
        var editor = CKEDITOR.replace( 'main_feature', {
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