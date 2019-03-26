<?php
	include('site-config.php');
	$data = $func->getContactusContent();
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
                    <li>Contact</li>
                </ul>
            </div>
        </section>
        <section class="contact-us-sec">
            <div class="container">
                <h1 class="title1">Get In <span>Touch</span></h1>
                <span class="bar-center"></span>
                <br/>
                <div class="row">
                    <div class="col-md-6 col-md-offset-1 col-sm-7">
                        <form class="contact-form" method="post" action="emailpro.php" id="contactForm">
                            <!-- <div class="form-group">
                                <input type="" name="name" required placeholder="Name" autocomplete="off" class="form-control">
                            </div> -->
                            <div class="form-group">
                                <div class="palceholder">
                                    <label for="name">Name</label>
                                    <span class="star">*</span>
                                </div>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="form-group">
                                <div class="palceholder">
                                    <label for="name">Mobile Number</label>
                                    <span class="star">*</span>
                                </div>
                                <input type="text" name="mobile" maxlength="10" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <div class="palceholder">
                                    <label for="name">Email</label>
                                    <span class="star">*</span>
                                </div>
                                <input  type="email" name="email" required class="form-control">
                            </div>
                            <div class="form-group">
                                <div class="palceholder">
                                    <label for="name">Message</label>
                                    <span class="star">*</span>
                                </div>
                                <textarea rows="4" name="message" required class="form-control"></textarea>
                            </div>
                            
                            <div class="form-group">
                              <!--  <div class="g-recaptcha" data-sitekey="6LdyEoEUAAAAAHSXG5X7OlPLOVXBww_88btcI5Nq" style="transform:scale(0.77);-webkit-transform:scale(0.77);transform-origin:0 0;-webkit-transform-origin:0 0;"></div>-->
								 <span class="msg-error error"></span>
                            </div>

                            <div class="form-group">
                                <button class="a-btn round-corner lightblue-btn">Submit</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5 col-sm-5">
                        <a href="<?php echo BASE_URL;?>/pricing.php" class="blue-btn round-corner a-btn c-book-btn">Schedule an Appointment</a>
                        <p>For support or any questions:</p>
                        <p><span><i class="fa fa-phone" aria-hidden="true"></i></span> <?php echo $data['phone']; ?></p>
                        <p><span><i class="fa fa-envelope-o" aria-hidden="true"></i></span> <?php echo $data['email']; ?> </p>
                        <div>
                            <iframe src=" <?php echo $data['map_link']; ?>" width="100%" height="200" frameborder="0" style="border:0" allowfullscreen></iframe>
                        </div>
                       
                    </div>
                </div>
                <div class="row address-sec">
                    <br>
                    <div class="col-md-5 col-sm-6 col-md-offset-1">
                        <h4>Registered Office</h4>
                        <?php echo $data['registered_office']; ?>
                    </div>
                    <div class="col-md-5 col-sm-6">
                        <h4>Workshop</h4>
                        <?php echo $data['workshop']; ?>
                    </div>                   
                </div>
            </div>
        </section>
            <!-- <div class="row">
                <div class="col-md-12">
                    <iframe src=" <?php //echo $data['map_link']; ?>" width="100%" height="350" frameborder="0" style="border:0" allowfullscreen></iframe>
                </div>
            </div> -->

    <?php 
        include 'include/footer.php';
    ?>

            <script src="js/jquery.min.js"></script>
			<script src="<?php echo BASE_URL;?>/js/jquery.validate.js" type="text/javascript"></script>
            <script src="js/bootstrap.min.js"></script>
            <script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
            <script src='js/slick.min.js'></script>
            <script src='js/owl.carousel.js'></script>
            <script src='js/owl.navigation.js'></script>
            <script src='js/jquery-ui.min.js'></script>
            <script src="js/jquery.timepicker.min.js"></script>
            <script src="js/bootstrap-select.min.js"></script>
            <script src="js/index.js"></script>
            <script src='https://www.google.com/recaptcha/api.js'></script>
          <script>
            $("form").submit(function(event) {
              var recaptcha = $("#g-recaptcha-response").val();
              if (recaptcha === "") {
                  event.preventDefault();
                  alert("Please check the recaptcha");
              }
            });
			 $("#contactForm").validate({
                                rules: {
                                    name: {
                                        required: true,
										 lettersonly:true
									
                                    },
									email: {
										required: true,
										email: true
									},
									message: {
										required: true,
									},
									
                                   
                                    
                                },
                                messages: {
                                    name: {
                                        required: "Please enter Name",
									},
									email: {
										required: "Please enter Email",
									},
									message: {
										required: "Please enter Message",
									}
									
									
                                },
                            });
          </script>
            <script type="text/javascript">
                $(window).scroll(function() {
                    if ($(window).scrollTop() > 100) {
                        $(".header-fix").addClass("fix_nav");
                    } else {
                        $(".header-fix").removeClass("fix_nav");
                    }
                });
                $(document).ready(function() {
                    $('.palceholder').click(function() {
                      $(this).siblings('input').focus();
                      $(this).siblings('textarea').focus();
                    });
                    $('.form-control').focus(function() {
                      $(this).siblings('.palceholder').hide();
                    });
                    $('.form-control').blur(function() {
                      var $this = $(this);
                      if ($this.val().length == 0)
                        $(this).siblings('.palceholder').show();
                    });
                    $('.form-control').blur();
                });
            </script>
</body>

</html>
</html>