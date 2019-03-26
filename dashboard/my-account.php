<?php
	include 'include/config.php';
	include 'include/admin-functions.php';
	$admin = new AdminFunctions();
	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: admin-login.php");
		exit();
	}
	
	//include 'csrf.class.php';
	$csrf = new csrf();
	$token_id = $csrf->get_token_id();
	$token_value = $csrf->get_token($token_id);

	// $loggedInID = $admin->escape_string($admin->strip_all($_SESSION['user_id']));
	// $admin_result = $admin->fetch($admin->query("select * from ".PREFIX."admin where id='".$loggedInID."'"));

	$invalid_user_name = false;
	$invalid_username = false;
	$email_invalid = false;
	$old_password_invalid = false;
	$password_mismatch = false;
	$password_not_strong = false;
	$password_update=0;
	$update_success=0;
	
	if(isset($_POST['update'])){
		if($csrf->check_valid('post')) {
			$fname = trim($admin->escape_string($_POST['f_name']));
			$lname = trim($admin->escape_string($_POST['l_name']));
			$email = trim($admin->escape_string($_POST['email_id']));
			// $contact = trim($admin->escape_string($_POST['contact_no']));
			$username = trim($admin->escape_string($_POST['username']));
			
			if(isset($_POST['old-password']) && !empty($_POST['old-password'])){
				// $old_password = md5(trim($admin->escape_string($_POST['old-password'])));
				$old_password = trim($admin->escape_string($_POST['old-password']));
				$password = trim($admin->escape_string($_POST['password']));
				$password2 = trim($admin->escape_string($_POST['password2']));
				if(strlen($password) < 8){
					$password_not_strong = true;
					header("location:my-account.php?updatefail&msg=Password length should be greater than 8 characters");
					exit;
				}
				if($password!=$password2){
					$password_mismatch = true;
					header("location:my-account.php?updatefail&msg=Password mismatch, please re-type the new password correctly");
					exit;
				}

				// if($old_password!=$admin_result['password']) { // DEPRECATED
				if(!password_verify($old_password, $loggedInUserDetailsArr['password'])) {
					$old_password_invalid = true;
					header("location:my-account.php?updatefail&msg=Old password does not match");
					exit;
				}
				if( $password_not_strong==true || $password_mismatch==true || $old_password_invalid==true ){
					//do nothing
				}else{
					// $pass_hash = md5($password); // DEPRECATED
					$pass_hash = password_hash($password, PASSWORD_DEFAULT);
					$id=$loggedInUserDetailsArr['id'];
					$admin->query("update ".PREFIX."admin set password='$pass_hash', last_modified=now() where id='$id'");
					$password_update=1;
				}
			}else if(empty($_POST['old-password']) and (!empty($_POST['password']) || !empty($_POST['password2']))){
				header("location:my-account.php?updatefail&msg=Old password is empty");
				exit;
			}
		
			$id=$loggedInUserDetailsArr['id'];
			// $loggedInID = $admin->escape_string($admin->strip_all($_SESSION['user_id']));
			/* echo "update ".PREFIX."admin set first_name='$fname',last_name='$lname',email='$email',contact='$contact',username='$username' where id='$id'";
			exit; */
			$admin->query("update ".PREFIX."admin set fname='$fname',lname='$lname',email='$email',username='$username', last_modified=now() where id='$id'");
			$update_success=1;
			// $_SESSION['user_fname']=$fname;
			// $_SESSION['user_lname']=$lname;
		
			//echo $update_success." , ".$password_update;

			header("location:my-account.php?updatesuccess=$update_success&passsuccess=$password_update");
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

	<link href="css/font-awesome.min.css" rel="stylesheet">
	<!--<link href="css/nanoscroller.css" rel="stylesheet">
	<link href="css/cover.css" rel="stylesheet">-->

	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=latin,cyrillic-ext" rel="stylesheet" type="text/css">
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
	<script>
	$(document).ready(function() {
		 $("#account").validate({
			rules:{
				 f_name:{
					 required:true,
					 lettersonly:true
				 },
				 l_name:{
					required:true,
					lettersonly:true
				 },
				 email_id:{
					required:true 
				 },
				 username:{
					required:true
				 },
				 password2:{
					equalTo:"#password"
				 },
				 
			 },
			messages:{
				 f_name:{
					 required:"Please enter your first name",
				 },
				 l_name:{
					required:"Please enter your last name", 
				 },
				 email_id:{
					required:"Please enter your email-id", 
				 },
				 username:{
					required:"Please enter your username", 
				 },
				 password2:{
					password:"Please Enter re-type same as password", 
				 },
			 },
		 });
		 jQuery.validator.addMethod("lettersonly", function(value, element) {
			return this.optional(element) || /^[a-zA-Z ]+$/i.test(value);
		}, "Can not contain number or special characters");
	 });
	</script>
</head>
<body class="sidebar-wide">
	<?php include 'include/navbar.php' ?>

	<div class="page-container">
		<?php include 'include/sidebar.php' ?>

		<div class="page-content">
    
			<div class="page-header">
				<div class="page-title">
					<h3>Admin Dashboard <small>Welcome <?php echo $loggedInUserDetailsArr['fname']; ?>. 
<?php
					if(isset($loggedInUserDetailsArr['last_modified']) && !empty($loggedInUserDetailsArr['last_modified'])){
						$timeDiffTimeStamp = (time() - strtotime($loggedInUserDetailsArr['last_modified']));
						$timeDiffInDay = $timeDiffTimeStamp / (24 * 60 * 60) % 60;
						$timeDiffInHour = $timeDiffTimeStamp / (24 * 60) % 60;
						$timeDiffInMin = $timeDiffTimeStamp / 60 % 60;
						$timeDiffInSec = $timeDiffTimeStamp % 60;
						echo $timeDiffInDay." days(s) ".$timeDiffInHour." hours(s) ".$timeDiffInMin." minute(s) ".$timeDiffInSec." second(s) since last modified";
					}
?>
		</small></h3>
				</div>
				<!-- <div id="reportrange" class="range">
					<div class="visible-xs header-element-toggle"><a class="btn btn-primary btn-icon"><i class="icon-calendar"></i></a></div>
					<div class="date-range"></div>
					<span class="label label-danger">9</span>
				</div> -->
			</div>

			<div class="breadcrumb-line">
				<div class="page-ttle hidden-xs" style="float:left;">My Profile</div>
				<ul class="breadcrumb">
					<li><a href="banner-master.php">Home</a></li>
					<li><a href="my-account.php">Profile</a></li>
				</ul>
			</div>

	<?php
		if(isset($_GET['updatesuccess']) && $_GET['updatesuccess']>0){ ?>
			<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-checkmark"></i> Profile successfully updated.
			</div><br/>
	<?php	} ?>

	<?php
		if(isset($_GET['passsuccess']) && $_GET['passsuccess']>0){ ?>
			<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-key"></i> Password successfully updated.
			</div><br/>
	<?php	} ?>
	
	<?php
		if(isset($_GET['updatefail'])){ 
					$msg=$_GET['msg'];	?>
			<div class="alert alert-danger alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-close"></i> <strong>Profile not updated.</strong> <?php echo $msg; ?>.
			</div><br/>
	<?php	} ?>

	<!-- Animated graphs -->
	<form role="form" action="" name="account" id="account" method="post">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h6 class="panel-title"><i class="icon-office"></i> User Details</h6>
        </div>
        <div class="panel-body">
			<div class="form-group">
          	<div class="row">
              	<div class="col-sm-6">
            		<label>First Name :</label>
            		<input type="text" class="form-control" name="f_name" id="f_name" value="<?php echo $loggedInUserDetailsArr['fname']; ?>"/>
					<span class="help-block">Can not contain number or special characters</span>
            	</div>
                
                <div class="col-sm-6">
            		<label>Last name :</label>
            		<input type="text" class="form-control" name="l_name" id="l_name" required="required" value="<?php echo $loggedInUserDetailsArr['lname']; ?>"/>
					<span class="help-block">Can not contain number or special characters</span>
					<input type="hidden" name="<?php echo $token_id; ?>" value="<?php echo $token_value; ?>" />
            	</div>
            </div>
			</div>

			<div class="form-group">
          	<div class="row">
              	<div class="col-sm-6">
            		<label>Email Id :</label>
            		<input type="email" class="form-control" name="email_id" id="email_id" required="required" value="<?php  echo $loggedInUserDetailsArr['email']; ?>"/>
					<span class="help-block">All admin emails will be sent to this email address</span>
            	</div>
                
            </div>
			</div>
        </div>
      </div>      
	  <div class="panel panel-default">
        <div class="panel-heading">
          <h6 class="panel-title"><i class="icon-office"></i>Change Password</h6>
        </div>
        <div class="panel-body">
			<div class="form-group">
          	<div class="row">
				<div class="col-sm-3">
					<label>User Name :</label>
					<input type="text" class="form-control" name="username" id="username" value="<?php  echo $loggedInUserDetailsArr['username']; ?>" >
            	</div>
                
				<div class="col-sm-3">
            		<label>Old Password :</label>
            		<input type="password" class="form-control" name="old-password" id="old-password">
					<span class="help-block">Leave blank if you don't want to change password</span>
            	</div>
                
                <div class="col-sm-3">
            		<label>New Password :</label>
            		<input type="password" class="form-control" name="password" id="password">
					
            	</div>
				
                <div class="col-sm-3">
            		<label>Retype New Password :</label>
            		<input type="password" class="form-control" name="password2" id="password2">
            	</div>
				
            </div>
			</div>
			
        </div>
      </div><!--/panel-default-->

		<div class="form-actions text-right">
			<button type="submit" name="update" id="update" class="btn btn-danger"><i class="icon-signup"></i>Update Profile</button>
		</div>
    </form>
    <!-- Footer -->
    <div class="footer clearfix">
      <div class="pull-left">
		<p>Copyright &copy; <?php echo COPYRIGHT ?> <?php echo SITE_NAME ?>. All rights Reserved. Developed by - <a href="http://www.innovins.com/" target="_blank">Innovins</a></p>
	  </div>
      <!--<div class="pull-right icons-group"> <a href="#"><i class="icon-screen2"></i></a> <a href="#"><i class="icon-balance"></i></a> <a href="#"><i class="icon-cog3"></i></a> </div>-->
    </div>
    <!-- /footer -->
  </div>
  <!-- /page content -->
</div>
<!-- /page container -->

</body>
</html>