<!-- Navbar -->

	<div class="navbar navbar-inverse" role="navigation">
		<div class="navbar-header">
		<a class="sidebar-toggle"><img src="images/toggleIcon.jpg"></a>
		<a class="navbar-brand" href="banner-master.php"><img style="width: 250px;height: 50px;" src="../images/logo (1).png" alt="Dirty Clean" title="Dirty Clean"></a>
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-icons"><span class="sr-only">Toggle navbar</span><i class="icon-grid3"></i></button>
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar"><span class="sr-only">Toggle navigation</span><i class="icon-paragraph-justify2"></i></button>
		</div>
		
		<ul class="nav navbar-nav navbar-right collapse" id="navbar-icons">
			<li class="user dropdown">
				<a class="dropdown-toggle" data-toggle="dropdown">
					<span>
<?php
	// $loggedInUserDetailsArr = $admin->getUniqueUserById($_SESSION[$admin->getSystemUserType().'UserId']);
	echo $loggedInUserDetailsArr['fname'].' '.$loggedInUserDetailsArr['lname'];
?>
					</span>
					<i class="caret"></i>
				</a>
				<ul class="dropdown-menu dropdown-menu-right icons-right">
					<li><a href="my-account.php"><i class="icon-user"></i> Admin Details</a></li>
					<li><a href="admin-logout.php"><i class="icon-exit"></i> Logout</a></li>
				</ul>
			</li>
		</ul>
	</div>
<!-- /navbar -->