<?php
    include('site-config.php');
    $data = $func->getGalleryContent();
  
?>
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
                <li>Gallery & Reviews</li>
            </ul>
        </div>
    </section>
    <section class="gallery-review-sec">
        <div class="container">
            <div class="row gallery-review-slider">
                <div class="col-md-5">
                    <h1><?php echo $data['title']; ?></h1>
                    <span class="bar"></span>
                    <br>
                    <?php echo $data['description']; ?>

                    <a href="pricing.php" class="blue-btn round-corner a-btn">Schedule an Appointment</a>
                </div>
                <div class="col-md-7">
                    <div class="gallery-slider">
					 <?php $gallery = $func->getGallery(); 
						   if($gallery->num_rows > 0){
						   while($row = $func->fetch($gallery)){
							$file_name = str_replace('', '-', strtolower( pathinfo($row['image_name'], PATHINFO_FILENAME)));
							$ext = pathinfo($row['image_name'], PATHINFO_EXTENSION);
					 ?>
                        <div>
                            <img src="<?php echo BASE_URL.'/';?>img/gallery/<?php echo $file_name.'_crop.'.$ext ?>">
                        </div>
					<?php } } ?>   
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="reviews-2-sec">
        <div class="container">
            <h1>Client <span>Reviews</span></h1>
            <span class="white-bar"></span>
            <div class="reviews-2-slider">
			<?php $clientReview = $func->getClientReview();
				  if($clientReview->num_rows > 0){
				  while($rw = $func->fetch($clientReview)){
					$file_name = str_replace('', '-', strtolower( pathinfo($rw['image_name'], PATHINFO_FILENAME)));
					$ext = pathinfo($rw['image_name'], PATHINFO_EXTENSION);
			?>
                <div class="review-box">
                    <div class="reviewer-img">
                        <img src="<?php echo BASE_URL."/"; ?>img/<?php echo $file_name.'_crop.'.$ext ?>">
                    </div>
                    <div class="review-text">
                        <span class="qoute"><img src="images/q.png"></span>
                       <?php echo $rw['review']; ?>
                        <h4><span>-</span> <?php echo $rw['name']; ?></h4>
                    </div>
                    <div class="clearfix"></div>
                </div>
				  <?php }} ?>
               
              
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


            $('.gallery-slider').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                arrows: true,
                dots: false,
            });

            $('.reviews-2-slider').slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                arrows: true,
                dots: false,
            });
        });
    </script>
</body>
</html>