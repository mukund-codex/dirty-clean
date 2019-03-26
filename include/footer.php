<?php $socialLinks = $func->getSocialLinks();     ?>
    <section class="social_links">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <div class="client_icon">
                            <ul>
                                <!-- <span>
                                    View More on 
                                </span> -->
								
                                <li>
                                    <a href="<?php if(!empty($socialLinks['facebook'])){ echo $socialLinks['facebook']; }else{ echo "#";  } ?>"><i class="fa fa-facebook"></i></a>
                                </li>
                                <li>
                                    <a href="<?php if(!empty($socialLinks['twitter'])){ echo $socialLinks['twitter']; }else{ echo "#";  } ?>" class="fa fa-twitter"></a>
                                </li>
                                <li>
                                    <a href="<?php if(!empty($socialLinks['google'])){ echo $socialLinks['google']; }else{ echo "#";  } ?>" class="fa fa-google"></a>
                                </li>
                                <li>
                                    <a href="<?php if(!empty($socialLinks['instagram'])){ echo $socialLinks['instagram']; }else{ echo "#";  } ?>" class="fa fa-instagram"></a>
                                </li>
                            </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--testimonials section close-->
    <!-- <section class="f-getintouch">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 remove_pad">
                    <h1>Get in Touch</h1>
                    <p>Lorem ipsum dummy text</p>
                </div>
                <div class="col-lg-4">
                    <div class="call-us "><span><img src="images/mail.png"></span><a href="mailto:info@dirtyclean.com">info@dirtyclean.com</a></div>
                </div>
                <div class="col-lg-4">
                    <div class="call-us dirt_mail"><span><img src="images/call.png"></span><a href="callto:+91 81087 44789">+91 81087 44789</a></div>
                </div>
            </div>
        </div>
    </section> -->
    <!--Get in Touch section close-->

    <footer>
        <div class="container">
            <div class="row section-padding-f">
                <div class="link-cus">
                    <a href="index.php">Home</a> | 
                    <a href="about-us.php">About Us</a> | 
                    <a href="pricing.php">Pricing  &  Packages</a> | 
                    <!-- <a href="booknow.php">Book an Appointments</a> |  -->
                    <a href="faqs.php">Faqs</a> | 
                    <a href="gallery-review.php">Gallery &amp; Reviews</a> | 
                    <a href="terms-n-conditions.php">Terms &amp; Conditions</a> | 
                    <a href="contact-us.php">Contact Us</a>
                </div>
                <div class="copyright">
                    <div class="float-lt">
                        Dirty Clean &copy; <?php echo date('Y'); ?>. All rights reserved.
                    </div>
                    <!--  <div class="pull-right">Design & Developed by <a href="http://www.innovins.com/">Innovins</a></div> -->
                </div>
            </div>
        </div>
    </footer>