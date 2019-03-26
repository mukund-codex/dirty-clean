<?php
    include 'site-config.php';  
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
                    <li>FAQs</li>
                </ul>
            </div>
        </section>
        <section class="terms-sec faqs">
            <div class="container">
                <h1 class="title1">FAQ<span>s</span></h1>
                <span class="bar-center"></span>
                <h3 class="title4">Booking</h3>
                <div class="faq-content">
                    <?php
                        $faqDetails = ($func->query("SELECT * from ".PREFIX."faqs where category = 'Booking' order by display_order ASC"));
                        while ($faqs = $func->fetch($faqDetails)) {
                    ?>
                    <div class="faq-question">
                        <input id="<?php echo $faqs['id']; ?>" type="checkbox" class="panel">
                        
                        <label for="<?php echo $faqs['id']; ?>" class="panel-title"><div class="plus">+</div> <?php echo $faqs['question']; ?></label>
                        <div class="panel-content">
                             <?php echo $faqs['answer']; ?>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- <div class="faq-question">
                        <input id="q2" type="checkbox" class="panel">
                        
                        <label for="q2" class="panel-title"><div class="plus">+</div> Where do you operate? I live out of Mumbai.</label>
                        <div class="panel-content">
                            Currently we offer our complete detailing packages within the geographical limits between Bandra & Borivali. Based on the response and travelling times we will look at expanding to a wider reach if sustainable. Our serviceable Pin-Codes are listed in our booking form. If you don’t see your Pin- Code listed then we currently don’t operate at that location at the moment. If you have any additional queries feel free to mail us.
                        </div>
                    </div>
                  
                    <div class="faq-question">
                        <input id="q3" type="checkbox" class="panel">
                        <label for="q3" class="panel-title"><div class="plus">+</div> Where are you located? What are your timings?</label>
                        <div class="panel-content">
                            We come to you! Whether you are at home, oﬃce or even on vacation. We operate out of a completely self suﬃcient mobile unit. Generally, we do not need any power or water   requirements at your premises and we carry everything needed to get your vehicle looking its  best.<br>
                            Working Hours:<br>
                            We operate from 8 a.m to 8 p.m all days of the week.
                        </div>
                    </div>

                    <div class="faq-question">
                        <input id="q4" type="checkbox" class="panel">
                        <label for="q4" class="panel-title"><div class="plus">+</div> Do you do emergencies? I need my car cleaned ASAP!</label>
                        <div class="panel-content">
                            As a work ethic we don’t normally operate beyond our displayed working hours. But then again, we understand it's not always possible to fit in. While we cannot guarantee availability we will do our best to accommodate you. Needless to say all additional charges beyond working hours will be disclosed to you prior booking and dispatch.
                        </div>
                    </div> -->
                </div>

                <h3 class="title4">Process</h3>
                <div class="faq-content"> 
                    <?php
                        $faqDetails = ($func->query("SELECT * from ".PREFIX."faqs where category = 'Process' order by display_order ASC"));
                        while ($faqs = $func->fetch($faqDetails)) {
                    ?>
                    <div class="faq-question">
                        <input id="<?php echo $faqs['id']; ?>" type="checkbox" class="panel">

                        <label for="<?php echo $faqs['id']; ?>" class="panel-title"><div class="plus">+</div> <?php echo $faqs['question']; ?></label>
                        <div class="panel-content">
                            <?php echo $faqs['answer']; ?>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- <div class="faq-question">
                        <input id="q6" type="checkbox" class="panel">
                        <label for="q6" class="panel-title"><div class="plus">+</div> Do you have a pre-work checklist? That scratch wasn’t there before.</label>
                        <div class="panel-content">
                            Yes, we start off with a detailed video/photographs of the vehicle in your presence highlighting all prior defects, scratches and paint damage to the interior and exterior. We also explain the limitations of our services, what we can/cannot do and the different services we offer and their benefits.
                        </div>
                    </div>

                    <div class="faq-question">
                        <input id="q7" type="checkbox" class="panel">
                        <label for="q7" class="panel-title"><div class="plus">+</div> Do I need an appointment or prior booking? What if there is a delay?</label>
                        <div class="panel-content">
                            While not a necessity we recommend pre-booking via our web form, this way we can confirm your place in the queue. We try our best to reach you within 90 mins of a confirmed booking. Incase of any unforeseen delays/break-down of equipment we will inform you well in advance and reschedule if needed at no additional cost to you.
                        </div>
                    </div>
                  
                    <div class="faq-question">
                        <input id="q8" type="checkbox" class="panel">
                        <label for="q8" class="panel-title"><div class="plus">+</div> What type pf payment options do you have?</label>
                        <div class="panel-content">
                            As of now we accept Cash, Credit/Debit card at-site and Online payments. We do not offer   Credit. All payments to be made at the end of the service and in full.
                        </div>
                    </div> -->
                </div>
                
                <h3 class="title4">Billing</h3>
                <div class="faq-content">
                    <?php
                        $faqDetails = ($func->query("SELECT * from ".PREFIX."faqs where category = 'Billing' order by display_order ASC"));
                        while ($faqs = $func->fetch($faqDetails)) {
                    ?>
                    <div class="faq-question">
                        <input id="<?php echo $faqs['id']; ?>" type="checkbox" class="panel">
                        <label for="<?php echo $faqs['id']; ?>" class="panel-title"><div class="plus">+</div> <?php echo $faqs['question']; ?></label>
                        <div class="panel-content">
                            <?php echo $faqs['answer']; ?>
                           <!-- <ul>
                                While we have priced all our services at competitive rates, we also offer additional discounts if certain conditions are met:

                                <li>3+ cars at a single location - 10% off on the entire bill - Valid for same category vehicles and same service package. Valid once/day. Max 4 Vehicles.</li>
                                <li>Pay for 11 months - Get 3 additional washes! Applicable for any service package paid annually.</li>
                                <li>Budget wash - Quick wash for those in a hurry - 2 vehicle minimum subject to a max of 6 vehicles.</li>
                                <small>** Refer detailed Terms/Conditions</small>
                           </ul> -->
                        </div>
                    </div>
                <?php } ?>
                    <!-- <div class="faq-question">
                        <input id="q10" type="checkbox" class="panel">

                        <label for="q10" class="panel-title"><div class="plus">+</div> What other types of services do you offer? Denting, Painting, Mechanical Repair?</label>
                        <div class="panel-content">
                            <ul>
                                Yes there are! In case we come across any issues which require more than normal effort, use of cleaning materials, equipment and time like:
                                <li>Heavy Mold/Mildew/Fungus coating all over the car exterior and interior</li>
                                <li>Deeply ingrained and excessive PetHair/Cigarette Burns</li>
                                <li>Human Waste/Vomit/Animal Poop/Paan and other stains not of a normal nature</li>
                                <li>Severe Muck/Mud stains of an abnormal nature (Racing/Off-road/Rally Cross)</li>
                                <li>Any other stains/issues not occurring during normal use (eg: Chemicals/Tar/Acid/Oil/Rust etc) Our technician will advise you of this during the pre-work inspection.</li>
                                ** Refer detailed Terms/Conditions
                            </ul>
                        </div>
                    </div>
                  
                    <div class="faq-question">
                        <input id="q11" type="checkbox" class="panel">
                        <label for="q11" class="panel-title"><div class="plus">+</div> Are there any hidden costs? Are taxes included?</label>
                        <div class="panel-content">
                            We have no hidden costs or anything extra. All charges are disclosed to you upfront. All services and complete job scope is also displayed on our website. What you see is what you pay for. We don’t encourage tips but if you feel our staff have done a great job then go ahead and  appreciate them. If we can give you better value for your money then please use the feedback form and let us know. We try to provide you the most bang for your buck!
                        </div>
                    </div>
                  
                    <div class="faq-question">
                        <input id="q12" type="checkbox" class="panel">
                        <label for="q12" class="panel-title"><div class="plus">+</div> Is satisfaction guaranteed?</label>
                        <div class="panel-content">
                            Yes your satisfaction is guaranteed! We are committed to giving you the best clean ever and are only happy when you are happy! In case you are not satisfied with our level of work please bring it to our notice. We will inspect and redo the complete section until you are fully satisfied. The best way to assure satisfaction is to thoroughly review your vehicle BEFORE our team leaves.
                        </div>
                    </div> -->
                </div>
                
                <h3 class="title4">General</h3>
                <div class="faq-content">    
                    <?php
                        $faqDetails = ($func->query("SELECT * from ".PREFIX."faqs where category = 'General' order by display_order ASC"));
                        while ($faqs = $func->fetch($faqDetails)) {
                    ?>
                    <div class="faq-question">
                        <input id="<?php echo $faqs['id']; ?>" type="checkbox" class="panel">

                        <label for="<?php echo $faqs['id']; ?>" class="panel-title"><div class="plus">+</div> <?php echo $faqs['question']; ?>.</label>
                        <div class="panel-content">
                            <?php echo $faqs['answer']; ?>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- <div class="faq-question">
                        <input id="q14" type="checkbox" class="panel">
                        <label for="q14" class="panel-title"><div class="plus">+</div> How long does the service take?</label>
                        <div class="panel-content">
                            Time required for services depends on the type of vehicle, the service package requested, the number of technicians, and the equipment available. Following are approximate times for a dual- crew team.

                            Dirty Detailing Package — 1.0 - 2.0 hours Very Dirty Detailing Package -- 2.0 - 3.0 hours<br>
                            Extremely Dirty Detailing Package -- 3.0 - 4.0 hours DirtyClean Plus+ Detailing Package -- 4.0 - 5.0 hours <br>
                            Interior Only -- 1.0 - 2.0 hours<br>
                            Exterior Only -- 1.0 - 2.0 hours<br>
                            Budget Wash -- 30 minutes/car (Min 2 cars)
                        </div>
                    </div>

                    <div class="faq-question">
                        <input id="q15" type="checkbox" class="panel">
                        <label for="q15" class="panel-title"><div class="plus">+</div> Are any permissions required? How much space do you need to work?</label>
                        <div class="panel-content">
                            Yes your society/office premise permission is required prior commencing work. We will not be held responsible if we are denied our full operational methods and required space. We need<br>
                            unobstructed access to all 4 doors and the trunk/hood with at least 2 feet space on either side to move about freely. Plus sufficient space to park our van either at the side or the front/back of the vehicle being serviced. We do not clean any vehicle on the road/public space as it is not permitted by law. We also ensure the place is reasonably cleaned and no residue is left behind one we are done.
                        </div>
                    </div>
                  
                    <div class="faq-question">
                        <input id="q16" type="checkbox" class="panel">
                        <label for="q16" class="panel-title"><div class="plus">+</div> Can I gift a detailing package?</label>
                        <div class="panel-content">
                            Yes you can. We offer gifting services for all our packages. You just need to select the ‘gift’ option from the booking form. We need the recipients name, address and contact details. Choose the correct date and time slot available. Multiple gifts can also be booked to different recipients. All gift payments to be made in advance.
                        </div>
                    </div>
                  
                    <div class="faq-question">
                        <input id="q17" type="checkbox" class="panel">

                        <label for="q17" class="panel-title"><div class="plus">+</div> Do you have a disclaimer policy? Where can I read the detailed terms and conditions?</label>
                        <div class="panel-content">
                            Refer to our Disclaimer and Terms & Conditions for all legal binding issues with using our services. Click on this link -
                        </div>
                    </div> -->
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
                $(document).ready(function() {

                });
            </script>
</body>

</html>
</html>