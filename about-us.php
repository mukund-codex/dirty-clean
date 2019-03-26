<?php 

    include('site-config.php');

    $data = $func->getAboutUsContent();
   
    $file_name = str_replace('', '-', strtolower( pathinfo($data['image_name'], PATHINFO_FILENAME)));
    $ext = pathinfo($data['image_name'], PATHINFO_EXTENSION);
    //echo $file_name."_crop.".$ext;
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
    <link rel='stylesheet prefetch' href='css/jquery-ui.css'>
    <link rel='stylesheet prefetch' href='css/jquery.timepicker.min.css'>
    <link rel="stylesheet" href="css/bootstrap-select.min.css">
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
                    <li>About</li>
                </ul>
            </div>
        </section>

        <section class="contact-us-sec about-us-sec">
            <div class="container">
                <h1 class="title1">Abo<span>ut</span></h1>
                <span class="bar-center"></span>
                <br>
                <br>
                
                <div class="row">
                     <div class="col-md-8">
                         <!-- <p>As founders and an integral part of the DirtyClean team, we are about as diverse as it gets! Mariners, Engineers, Home-Makers and Mechanical Professionals, we’ve come together to form DirtyClean. What we do have in common is a passion for Detailing & Clean Vehicles. We take Pride in our Rides! We quite literally guarantee the quality of our work. You see, we treat each vehicle like our own! We have a fully equipped self contained van, we even carry our own power generator and water allowing us to take our services just about anywhere! All you have to do is sit back relax and let us work our magic. Excited? Give us a call, we guarantee you’ll love our work results!</p> -->
                         <?php if(!empty($data['desc1'])) { echo $data['desc1']; }else{ echo ""; } ?>
                     </div>
                     <div class="col-md-4">
                        <div class="about-us-img">
                            <!-- <img src="images/about.jpg"> -->
                            <?php if(!empty($data['image_name'])){ ?>
                                <img src="img/<?php echo $file_name."_crop.".$ext; ?>" >
                            <?php } ?>
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
            <script src='js/jquery-ui.min.js'></script>
            <script src="js/jquery.timepicker.min.js"></script>
            <script src="js/bootstrap-select.min.js"></script>
            <script src="js/index.js"></script>
            <script type="text/javascript">
                $(window).scroll(function() {
                    if ($(window).scrollTop() > 100) {
                        $(".header-fix").addClass("fix_nav");
                    } else {
                        $(".header-fix").removeClass("fix_nav");
                    }
                });
            </script>
</body>

</html>