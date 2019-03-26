<?php
	$basename = basename($_SERVER['REQUEST_URI']);
	$currentPage = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
?>
<!-- Sidebar -->
<div class="sidebar collapse">
    <div class="sidebar-content">
		<!-- Main navigation -->
		<ul class="navigation">
			
			<li <?php if($currentPage=='personal-details.php') { echo 'class="active"'; }?>><a href="personal-details.php"><span>Booking Details</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='banner-master.php') { echo 'class="active"'; }?>><a href="banner-master.php"><span>Banner Master</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='welcome.php') { echo 'class="active"'; }?>><a href="welcome.php"><span>Home Content</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='about_us.php') { echo 'class="active"'; }?>><a href="about_us.php"><span>About Us</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='gallery-review-master.php') { echo 'class="active"'; }?>><a href="gallery-review-master.php"><span>Gallery Review</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='contact_us.php') { echo 'class="active"'; }?>><a href="contact_us.php"><span>Contact Us Content</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='contact_form.php') { echo 'class="active"'; }?>><a href="contact_form.php"><span>Contact Us Form</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='social-links.php') { echo 'class="active"'; }?>><a href="social-links.php"><span>Social Links</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='manage-faqs.php') { echo 'class="active"'; }?>><a href="manage-faqs.php"><span>FAQs</span> <i class="icon-diamond"></i></a></li>
			<li><a href="#" class="expand"><span>Package Master</span> <i class="icon-paragraph-justify2"></i></a>
				<ul>
					<li <?php if($currentPage=='category-master.php') { echo 'class="active"'; }?>><a href="category-master.php"><span>Category Master</span></a></li>
					<li <?php if($currentPage=='package-master.php') { echo 'class="active"'; }?>><a href="package-master.php"><span>Package Master</span></a></li>
					<li <?php if($currentPage=='feature-master.php') { echo 'class="active"'; }?>><a href="feature-master.php"><span>Feature Master</span></a></li>
					<li <?php if($currentPage=='vendor-subscription.php') { echo 'class="active"'; }?>><a href="vendor-subscription.php"><span>Detailing Package Master</span></a></li>
					<!-- <li <?php if($currentPage=='subscription-features-master.php') { echo 'class="active"'; }?>><a href="subscription-features-master.php"><span>Subscription Features Master</span></a></li> -->
					<li <?php if($currentPage=='quick-wash-master.php') { echo 'class="active"'; }?>><a href="quick-wash-master.php"><span>Quick Wash Master</span></a></li>
				</ul>
			</li>
			<li <?php if($currentPage=='discount-coupon-master.php') { echo 'class="active"'; }?>><a href="discount-coupon-master.php"><span>Discount Coupon Master</span> <i class="icon-diamond"></i></a></li>
			<li <?php if($currentPage=='terms-n-conditions.php') { echo 'class="active"'; }?>><a href="terms-n-conditions.php"><span>Terms & Conditions</span> <i class="icon-diamond"></i></a></li>
			
		</ul>
      <!-- /main navigation -->
	</div>
</div>
<!-- /sidebar -->
