<?php
    include('site-config.php');
    $data = $func->getAllBanners();
  
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dirty Clean</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/jquery.fancybox.min.css">
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
    <div class="wrapper">
        <?php 
            include 'include/header.php';
        ?>
        <section>
            <div class="single-item home-banner-slider">
            <?php if($data->num_rows > 0){
                while($row = $func->fetch($data)){ 
                $file_name = str_replace('', '-', strtolower( pathinfo($row['banner_img'], PATHINFO_FILENAME)));
                $ext = pathinfo($row['banner_img'], PATHINFO_EXTENSION); 
            ?>
                <div class="p-relative">
                    <img src="img/banner/<?php echo $file_name.'_crop.'.$ext ?>" class="img-responsive">
                    <div class="caption-content">
                        <h2><?php echo $row['title']; ?></h2>
                        <h3><?php echo $row['sub_title']; ?></h3>
                    </div>
                </div>
            <?php } } ?>
                <!-- <div>
                    <img src="images/banner1.jpg" class="img-responsive">
                    <div class="caption-content">
                        <h2>Mobile Car Wash and Detailing</h2>
                        <h3>We come to you, at work or at home, for full car wash and detailing services</h3>
                    </div>
                </div>-->
            </div>
        </section>
        <section>
            <div class="container wash_parent">
                <div class="row">
                    <div id="owl-demo_images" class="owl-carousel owl-theme" align="center">
                    <?php $carousel = $func->getCarousel();
                          if($carousel->num_rows > 0){
                          while($row = $func->fetch($carousel)){
                             $file_name = str_replace('', '-', strtolower( pathinfo($row['image_name'], PATHINFO_FILENAME)));
                             $ext = pathinfo($row['image_name'], PATHINFO_EXTENSION);
                        
                    ?>
                        <div class="item img-thumbnail grow">
                            <div class="ser-rcorners1">
                                <a href="<?php if(!empty($row['link'])){ echo $row['link']; }else{ echo "#"; } ?>">
                                    <img src="<?php echo BASE_URL ?>/img/<?php echo $file_name.'_crop.'.$ext ?>">
                                    <div class="ser-text">
                                        <h2><?php echo $row['title']; ?></h2>
                                        <p><?php echo $row['sub_title']; ?></p>
                                    </div>
                                    <span class="some-element">
                                        <img src="images/round-arw.png">
                                    </span>
                                </a>
                            </div>
                        </div>
                     <?php } } ?>  
                      
                    </div>
                </div>
            </div>
        </section>
        <section class="sec-padding wel-text">
            <div class="container">
                <div class="row">
                    <h2 class="p-relative">Welcome to<span class="f-bold">Dirty Clean<span></h2>
                    <div class="col-lg-8 col-md-7 about_blue_pattern">
                        <div class="about-content animated animatedFadeInUp fadeInUp" data-wow-delay="300">
                            <div class="tittle-cus">
                                <h1>Welcome to <br class="hidden-md hidden-sm"> <span>Dirty Clean<span></h1>
                                <span class="bar"></span>
                            </div>
                            <?php $welcome = $func->getHomeContent(3); 
                                  $about = $func->getAboutUsContent();
                                  $file_name = str_replace('', '-', strtolower( pathinfo($welcome['image'], PATHINFO_FILENAME)));
                                  $ext = pathinfo($welcome['image'], PATHINFO_EXTENSION);
                            ?>
                            <div class="about-con-rgt">
                               <?php echo $welcome['description']; ?>
                                <a class="margin-lf15" href="about-us.php" role="button">Read More</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-5 fadeInRigh abt-video-div">
                        <div class="about-image  p-relative">
                            <img src="<?php echo BASE_URL ?>/img/banner/<?php echo $file_name."_crop.".$ext; ?>"  class="p-relative">
                            <a class="p-absolute various fancybox" href="<?php if(!empty($welcome['video_link'])){ echo $welcome['video_link'];  } ?>" data-lity="">
                                <i class="fa fa-play play_size p-relative fs-20 color-fff transition-2 pl-3px radius-50 text-center color-red-hvr bg-fff-hvr"></i>
                            </a>
                            <!-- <a class="various fancybox" href="https://www.youtube.com/embed/jid2A7ldc_8?autoplay=1">Youtube (iframe)</a> -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--welcome section close-->
        <section class="sec-padding6 how-we-do">
            <div class="container">
                <h1 class="margin-bt6"> How we <span>Do it?</span></h1>
                <span class="bar-center"></span>
                <div class="row margin-tp">
                    <div class="process-slider">
                        <?php $howWeDo = $func->getHowWeDoIt(); ?>
                        <?php if($howWeDo->num_rows > 0){ 
                              $i = 1;
                              while($rw = $func->fetch($howWeDo)){
                              $file_name = str_replace('', '-', strtolower( pathinfo($rw['image_name'], PATHINFO_FILENAME)));
                              $ext = pathinfo($rw['image_name'], PATHINFO_EXTENSION);
                              
                        ?>
                            <div class="col-lg-3">
                                <div class="process ">
                                    <div class="box radius-50" style="background-image: url(img/<?php echo $file_name.'_crop.'.$ext ?>);"></div>
                                    <h3 class="text-uppercase"><?php echo $i++; ?>. Stage</h3>
                                    <h2><?php echo $rw['title']; ?></h2> <?php echo $rw['description']; ?>
                                </div>
                            </div>
                        
                        <?php } } ?>
                    </div>
                </div>
            </div>
        </section>
        <!--process section close-->
        <section class="sec-padding6 testimonial ">
            <div class="container-fluid client_slide_image pattern_images">
                <div class="row">
                    <div class="container">
                        <div class="row">
                        <?php $detailing = $func->getHomeContent(4); ?>
                            <h1 class="margin-bt6">WHAT IS <span>DETAILING</span></h1>
                            <span class="bar-center"></span>
                            <div class="margin-tp ">
                                <div class="mCustomScrollbar mcscroll" data-mcs-theme="dark">
                                    <div class="client_width content">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <?php echo $detailing['description']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <?php 
            include 'include/footer.php';
        ?>
    </div>   
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.fancybox.min.js"></script>
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

            $('.single-item').slick({
                arrows: false,
                dots: true,
                autoplay: true,
                autoplaySpeed: 2000,
            });

            $('.four-grd').slick({
                slidesToShow: 4,
                slidesToScroll: 1,
                autoplay: false,
                autoplaySpeed: 2000,
                arrows: true,
            });
            $('.client_slide').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: false,
                autoplaySpeed: 2000,
                arrows: false,
                dots: true,
            });

            $('.process-slider').slick({
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
                        arrows: false,
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
                        arrows: false,
                    }

                }]
            });

            $('#owl-demo_images').owlCarousel({
                items: 4,
                autoPlay: 3000, //Set AutoPlay to 3 seconds
                navigation: false, // Show next and prev buttons
                stopOnHover: true,
                pagination: false,
                nav: true,
                dots: false,
                // navText: ['<span class="fas fa-chevron-left fa-2x"></span>','<span class="fas fa-chevron-right fa-2x"></span>'],

                itemsDesktop: [1199, 3],
                itemsDesktopSmall: [979, 3],

                responsiveClass:true,
                responsive:{
                    0:{
                        items:2,
                        nav:true
                    },
                    600:{
                        items:3,
                        nav:true
                    },
                    1000:{
                        items:4,
                        nav:true,
                        loop:true
                    }
                }
            });


            $(".various").fancybox({
                type: "iframe", //<--added
                maxWidth: 800,
                maxHeight: 600,
                fitToView: false,
                width: '70%',
                height: '70%',
                autoSize: false,
                closeClick: false,
                openEffect: 'none',
                closeEffect: 'none'
            });

        });

        (function($){
            $(window).on("load",function(){
                $(".content").mCustomScrollbar({
                     axis:"y"
                });
            });
        })(jQuery);
    </script>
</body>

</html>