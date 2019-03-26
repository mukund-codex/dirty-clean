<?php include('site-config.php'); 
	  
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
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="icon" type="image/ico" href="images/favicon.png">
    <style>
        .header-fix{
            background: #000;
        }
    </style>
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
                    <li>Terms &amp; Conditions</li>
                </ul>
            </div>
        </section>
        <section class="terms-sec">
            <div class="container">
                <h1 class="title1">Terms &amp; <span>Conditions</span></h1>
                <span class="bar-center"></span>
				<?php $data = $func->getHomeContent(5); ?>
                <?php echo $data['description']; ?>

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
                $(document).ready(function() {

                });
            </script>
</body>

</html>
</html>