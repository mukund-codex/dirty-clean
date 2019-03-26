<?php include('site-config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dirty Clean</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel='stylesheet prefetch' href='css/slick.css'>
    <link rel='stylesheet prefetch' href='css/slick-theme.css'>
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link rel="stylesheet" href="css/owl.theme.default.css">
    <link rel="stylesheet" href="css/jquery.mCustomScrollbar.css" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="icon" type="image/ico" href="images/favicon.png">
</head>
<body>
    <?php 
        include 'include/header.php';
    ?>
    <section class="inner-banner-sec">
        <img src="images/inner-banner.jpg">
    </section>
    <section class="breadcrumb-sec"> 
        <div class="container">
            <ul class="breadcrumb">
                <li><a href="index.php">Home</a></li>
                <li>Pricing</li>
            </ul>
        </div>
    </section>
    <section class="pricing-sec">
        <div class="container">
		<?php
		 //echo $_POST['coupon']; 
		if(isset($_POST['gifted']) || isset($_POST['addCoupon'])){ ?>
			<div class="alert alert-success alert-dismissible" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<i class="icon-checkmark3"></i> Select Packages.
			</div><br/>
            <?php	} ?>
            <div class="VehicleType">
                <h3>Choose Your Vehicle Type</h3>
                <div class="vehicle-type-3">
                    <div class="row vehicle-type-slider">
                    <?php $getAllCategories = $func->getAllCategories();
							$i = 1;
                        while($rw = $func->fetch($getAllCategories)){
					  
                    ?> 
                        <div class="col-md-4 col-sm-4 text-center">
                            <label class="container-r"><?php echo $rw['category_name']; ?>
                                <input type="radio" name="radio" <?php if(isset($_GET['category']) && !empty($_GET['category'])){ echo "checked"; } ?> id="radio<?php echo $i++; ?>"   value="<?php echo $rw['id']; ?>" onclick="getPackages('<?php echo $rw['id']; ?>');">
                                <span class="checkmark-r"></span>
                            </label>
                            <?php 
                            	$file_name = str_replace('', '-', strtolower( pathinfo($rw['image'], PATHINFO_FILENAME)));
                                $ext = pathinfo($rw['image'], PATHINFO_EXTENSION);
                                 ?>
                            <div class="vehicle-imgs">
                            <img src="img/banner/<?php echo $file_name.'_crop.'.$ext ?>" width="100%"  />
                            </div>
                        </div>
                    <?php } ?>
					
                    </div>
                </div>
            </div>
            <div class="package-detailing">
                <h1 class="title1">detailing <span>pACKAGES</span></h1>
                <span class="bar-center"></span>
                <div class="row margintop25 package-slider" id="displaypackage">
                    <!--- Details coming from getPackages AJAX -->
                </div>
            </div>
        </div>
    </section>
    <section class="quick-wash-sec">
        <div class="container" id="quick-wash">
                <!--- Details coming from getPackages AJAX -->
        </div>
    </section>
    <section class="get-services-sec">
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-sm-6">
                    <div class="get-serv-div">
                        <img src="images/get-services.jpg">
                        <div class="get-services-info" id="gifting">
                            <!-- <h4 class="title3">Gift a service</h4>
                            <a href="#" class="book-now orange">Book Now</a>
                            <p><span>*</span>Refer <a href="terms-n-conditions.php">Terms & Conditions</a></p> -->
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="get-serv-div">
                        <img src="images/get-services-1.jpg">
                        <div class="get-services-info" id ="bulkbooking">
                            <!-- <h4 class="title3">Get 10% Off<br><span>ON bULK BOOKINGS</span></h4>
                            <a href="#" class="book-now blue">#BULK300</a>
                            <p><span>*</span>Refer <a href="terms-n-conditions.php">Terms & Conditions</a></p>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php 
        include 'include/footer.php';
    ?>
    
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src='js/slick.min.js'></script>
    <script src='js/owl.carousel.js'></script>
    <script src='js/owl.navigation.js'></script>
    <script src="js/index.js"></script>
    <script type="text/javascript">
        $(window).scroll(function() {
            if ($(window).scrollTop() > 100) {
                $(".header-fix").addClass("fix_nav");
            } else {
                $(".header-fix").removeClass("fix_nav");
            }
        });
        $(document).ready(function() {
            //$('.package-slider').unslick();
            // $('.package-slider').slick("unslick");
            $('.vehicle-type-slider').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                arrows: false,
                dots: false,
                infinite: false,
                responsive: [
                {
                    breakpoint: 1024,
                    settings:{
                        slidesToShow: 4,
                        arrows: false,
                    }

                },
                {
                    breakpoint: 600,
                    settings:{
                        slidesToShow: 2,
                        arrows: true,
                    }

                },
                {
                    breakpoint: 801,
                    settings:{
                        slidesToShow: 3,
                        arrows: false,
                    }

                },
                {
                    breakpoint: 480,
                    settings:{
                        slidesToShow: 1,
                        arrows: true,
                    }

                }]
            });

            




			<?php 
			if(!isset($_POST['gifted_package'])){ 
			?>
			getPackages('1');
            $("#radio1").prop("checked", true);
			<?php }else{ 
			if($_POST['category']=='1'){ ?>
			getPackages('1');
            $("#radio1").prop("checked", true);	
			<?php }
			else if($_POST['category']=='2'){ 
			?>
			getPackages('2');
            $("#radio2").prop("checked", true);
			<?php }else if($_POST['category']=='3'){ ?>
			getPackages('3');
            $("#radio3").prop("checked", true);
			<?php } ?>
			<?php } ?>
        });
		
		
		
        //Get Detailing Package Ajax
        function getPackages(category){
            var category = category;
            var gifted = 0;
			<?php if(isset($_POST['gifted_package'])){ ?>
			gifted = 1;
			<?php } ?>
			var coupon = '';
			<?php if(isset($_POST['addCoupon'])){ ?>
			coupon = '<?php echo $_POST['coupon']; ?>';
			<?php } ?>
            console.log(category);
            // $('.package-slider').slick("unslick");
            var element = document.getElementById("displaypackage");
            element.innerHTML = '';
            var element2 = document.getElementById("quick-wash");
            element2.innerHTML = '';
            var element3 = document.getElementById("gifting");
            element3.innerHTML = '';
            var element4 = document.getElementById("bulkbooking");
            element4.innerHTML = '';
			
            $.ajax({
                type: "POST",
                url: "getDetailingPackages.inc.php",
                data: {category:category,gifted:gifted,coupon:coupon},               
                success: function(response) {
                    var data = JSON.parse(response); 
                    console.log(data);            
					
					element.innerHTML += data.packages;  
                    element2.innerHTML += data.quickwash;   
                    element3.innerHTML += data.gifting;
                    element4.innerHTML += data.bulk; 
                    $(".package-slider").removeClass("slick-initialized");
                    $(".package-slider").removeClass("slick-slider");
                    $('.package-slider').slick({
                        slidesToShow: 4,
                        slidesToScroll: 1,
                        autoplay: true,
                        autoplaySpeed: 2000,
                        arrows: false,
                        dots: false,
                        infinite: false,
                        responsive: [
                        {
                            breakpoint: 1024,
                            settings:{
                                slidesToShow: 4,
                                arrows: false,
                            }

                        },
                        {
                            breakpoint: 600,
                            settings:{
                                slidesToShow: 2,
                                arrows: true,
                            }

                        },
                        {
                            breakpoint: 801,
                            settings:{
                                slidesToShow: 2,
                                arrows: true,
                            }

                        },
                        {
                            breakpoint: 480,
                            settings:{
                                slidesToShow: 1,
                                arrows: true,
                            }

                        }]
                    });             
                }
            });
                       
            // $.ajax({
            //     type: "POST",
            //     url: "getQuickWash.inc.php",
            //     data: {category:category},               
            //     success: function(response) {
            //         console.log(response);
            //         // var data = JSON.parse(response);                    
            //         element2.innerHTML += response;                   
            //     }
            // });
        }
    </script>
</body>
</html>