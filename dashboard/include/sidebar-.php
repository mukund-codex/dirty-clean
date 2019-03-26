<?php
	$basename = basename($_SERVER['REQUEST_URI']);
	$currentPage = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
?>
<!-- Sidebar -->
<div class="sidebar collapse">
    <div class="sidebar-content">
		<!-- Main navigation -->
		<ul class="navigation">
			
			
			<li ><a href="banner-master.php"><span>Banner Master</span> <i class="icon-diamond"></i></a></li>
			<li><a href="category-master.php"><span>Category Master</span> <i class="icon-diamond"></i></a></li>
			<li><a href="product-master.php"><span>Product Master</span> <i class="icon-diamond"></i></a></li>
			<li><a href="news-master.php"><span>News Master</span> <i class="icon-diamond"></i></a></li>
			<li><a href="aboutus.php"><span>Welcome Page</span> <i class="icon-diamond"></i></a></li>
			<li><a href="contactus-cms.php"><span>Contact Us</span> <i class="icon-diamond"></i></a></li>
			<li><a href="career.php"><span>Career</span> <i class="icon-diamond"></i></a></li>
			
			<li><a href="newsletter-subscription-master.php"><span>Newsletter</span> <i class="icon-diamond"></i></a></li>
			<li><a href="#" class="expand"><span>Investors</span><i class="icon-paragraph-justify2"></i></a>
				<ul>
					<li><a href="audited-results.php"><span>Audited Results</span></a></li>
					<li><a href="unaudited-results.php"><span>Unaudited Results</span></a></li>
					<li><a href="cg-reports.php"><span>CG Reports</span></a></li>
					<li><a href="board-meeting-intimation.php"><span>Board Meeting Intimation</span></a></li>
					<li><a href="codes-policies.php"><span>Codes & Policies</span></a></li>
					<li><a href="listingAndOtherInformation.php"><span>Listing & Other Information</span></a></li>
				</ul>
			</li>
			<li><a href="#" class="expand"><span>About Us</span> <i class="icon-paragraph-justify2"></i></a>
				<ul>
					<li><a href="history.php"><span>History</span></a></li>
					<li><a href="awards.php"><span>Awards & Recognitions</span></a></li>
					<li><a href="leadership-team.php"><span>Leadership Team</span></a></li>
					<li><a href="group-companies.php"><span>Group Companies</span></a></li>
					<li><a href="our-presence.php"><span>Our Presence</span></a></li>
					<li><a href="community-initiatives.php"><span>Community Initiatives (CSR)</span></a></li>
					
				</ul>
			</li>
			
			
			

			
			
		</ul>
      <!-- /main navigation -->
	</div>
</div>
<!-- /sidebar -->
