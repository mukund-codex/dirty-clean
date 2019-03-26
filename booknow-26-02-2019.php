<?php

include('site-config.php');

if(isset($_POST['booknow'])){
   
    $result = $func->addPersonalDetails($_POST);

}
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
        button, input, optgroup, select, textarea{
            color:#6f6d6d;
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
                    <li><a href="pricing.php">Pricing & Packages </a></li>
                    <li>Summary</li>
                </ul>
            </div>
        </section>
        <form method="post" name="myform" id="myform">
            <section class="book-now-sec">
                <div class="container">
                    <h1 class="title1">Enter Your <span>details</span></h1>
                    <span class="bar-center"></span>
                    <div class="details-form">
                        <div class="personal-details">
                            <div class="gifting-our-services">
                                <label class="container-ck">Gifting our services?
                                    <input type="checkbox" name="gifting" id="gifting" <?php if(isset($_GET['gifted']) && $_GET['gifted']=='1'){ echo "checked"; } ?>>
                                    <span class="checkmark-ck"></span>
                                </label>
                            </div>
                            <h4 class="title4">Personal Details</h4>
                            <div class="row">
                                <div class="col-md-6 paddingl0">
                                    <div class="form-group">
                                        <label>Name<em>*</em></label>
                                        <input type="text" name="first_name" id="first_name" class="form-control inline-inp width50 border-r0" placeholder="First">
                                        <input type="text" name="last_name" id="last_name" class="form-control inline-inp width50 border-l0" placeholder="Last">
                                    </div>
                                    <div class="form-group">
                                        <label>Mobile<em>*</em></label>
                                        <input type="text" name="" class="form-control inline-inp width10 border-r0" placeholder="+91" readonly>
                                        <input type="text" name="mobile" id="mobile" class="form-control inline-inp width90 border-l0" placeholder="Number">
                                    </div>
                                    <div class="form-group">
                                        <label>Email<em>*</em></label>
                                        <input type="email" name="email" id="email" class="form-control" placeholder="Email">
                                    </div>
                                </div>
                                <div class="col-md-6 paddingr0">
                                    <div class="form-group">
                                        <label>Address<em>*</em></label>
                                        <input type="text" name="address" id="address" class="form-control" placeholder="Text">
                                        <label>&nbsp;</label>
                                        <input type="text" name="landmark" id="landmark" class="form-control" placeholder="Landmark">
                                        <label>&nbsp;</label>
                                        <input type="text" name="zipcode" id="zipcode" class="form-control" placeholder="Zipcode">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-details">
                            <h4 class="title4">Booking Details</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="" name="date" id="date" class="form-control datepicker">
                                        <span class="date-icon"></span>
                                    </div>
                                </div>
                                <div class="col-md-3" id="timeDiv">
                                    <div class="form-group">
                                        <label>Time</label>
                                        <input type="" name="time" id="time" class="form-control timepicker">
                                        <span class="time-icon"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="container-ck">More than one vehicle ?
                                        <input type="checkbox" id="check">
                                        <span class="checkmark-ck"></span>
                                    </label>
                                    <br>
									<div style="display:none" id="showMore">
                                    <select name="no_vehicles"  id="no_vehicles" class="selectpicker width100px" onchange="showdetails();">
                                        <option selected disabled>1</option>
                                        <option>2</option>
                                        <option>3</option>
                                        <option>4</option>
                                        <option>5</option>
                                    </select>
									</div>
                                </div>
                            </div>
                            <br>
                            <input type="hidden" name="category_name" id="category_name" value="<?php if(!empty($_GET['category'])) { echo $_GET['category']; } ?>"/>
                            <input type="hidden" name="package_name" id="package_name" value="<?php if(!empty($_GET['package'])) { echo $_GET['package']; } ?>"/>
                            <div class="row vehicle-detl" id="vehicle-detl" style="display:none;">
                            
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="javascript:;" onclick="submitform();" class="lightblue-btn round-corner a-btn booknow-btn">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="booking-summary-sec">
                <div class="container">
                    <h1 class="title5">Booking Summary</h1>
                    <span class="white-bar"></span>
                    <div class="booking-summary">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Vehicle Type</th>
                                    <th>Packages</th>
                                    <th>Qty</th>
                                    <th>Total Price</th>
                                </tr>
                            </thead>
                            <tbody id="content">
                            </tbody>
                        </table>
                    </div>
                    <div class="booking-summary-total text-right">
                        <div class="sub-total">
                            <h3>Sub Total  <span id="price"><i class="fa fa-inr" aria-hidden="true"></i>  </span></h3>
                            <input type="hidden" name="sub-total" id="sub-total" value="" />
                        </div>
                        <div class="couponcode-div">
                            <div class="form-group">
                                <input type="text" name="couponcode" id="couponcode" placeholder="#couponcode">
                                <button type="button" id="apply_coupon" onclick="couponCode();">Apply</button>
                                <button type="button" id="remove_coupon" onclick="RemovecouponCode();" style="display:none;">Remove</button>
                                <span class="substract-amt" id="substract-amt"></span>
                                <p><small>(* inclusive of all taxes)</small></p>
                            </div>
                        </div>
                        <div class="sub-total">
                            <h3>Total  <span id="final_total"><i class="fa fa-inr" aria-hidden="true"></i></span></h3>
                            <input type="hidden" name="input_total" id="input_total" value=""/>
                        </div>
                        <div class="terms">
                            <label class="container-ck">I agreed to the <span>*</span><a href="terms-n-conditions.php"><u>Terms &amp; Conditions</u></a>
                                <input type="checkbox">
                                <span class="checkmark-ck"></span>
                            </label>
                        </div>
                        <div class="">
                            <a href="javascript:;" class="lightblue-btn round-corner a-btn continue-btn">Continue</a>
                        </div>
                    </div>
                </div>
            </section>
            <section class="mode-of-payment-sec">
                <div class="container">
                    <h1 class="title1">Enter Your <span>details</span></h1>
                    <span class="bar-center"></span>
                    <div class="details-form">
                        <div class="mode-of-payment">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <label class="container-r">COD / card payment at location
                                        <input type="radio" name="mode_of_payment" checked>
                                        <span class="checkmark-r"></span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="container-r">Net Banking / Credit Card / Debit Card
                                        <input type="radio" name="mode_of_payment">
                                        <span class="checkmark-r"></span>
                                    </label>
                                </div>
                                <div class="col-md-12">
                                    <button class="lightblue-btn round-corner a-btn" type="submit">proceed to payment</button>
                                </div>
                                <div class="col-md-12">
                                    <img src="images/cards.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </form>
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
            <script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>
            <script type="text/javascript">
                $(window).scroll(function() {
                    if ($(window).scrollTop() > 100) {
                        $(".header-fix").addClass("fix_nav");
                    } else {
                        $(".header-fix").removeClass("fix_nav");
                    }
                });
                $(document).ready(function() {
                    
					$("#check").click(function(){
						 $("#showMore").toggle(this.checked);
					});
					
                    $(".datepicker").datepicker();
                    $(".timepicker").timepicker({});

                    $(".booknow-btn").click(function() {
                        $(".booking-summary-sec").slideDown("slow");
                    });
					

                    $(".continue-btn").click(function() {
                        $(".mode-of-payment-sec").slideDown("slow");
                    });
					$("#date").on("change",function(){
						 var date = $(this).val();
						
						 //$("#timeDiv").show();
						
						$("#timeDiv").load("getTimeByDate.php",{
							date:date
							
						});
					});
                    
                });

                function showdetails(){
                    var i = 1;
                    var count = $('#no_vehicles').val();
                    if(count > 1){
                        document.getElementById("vehicle-detl").style.display = "block";
                    }
                    var element = document.getElementById("vehicle-detl");
                    element.innerHTML = '';
                    $.ajax({
                      url: "getCategoryPackage.inc.php",
                      data: {count:count},
                      //contentType: false, // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
                      //processData: false,
                      type: "POST",
                      success: function(response) {
                        console.log(response);
                        element.innerHTML += response;
                      }
                    });
                    // while (i <= count) {
                        
                    //      element.innerHTML+= "<div class='col-md-2'><label>Vehicle "+i+"</label></div><div class='col-md-2'><select name='category[]' class='form-control'><option>Category</option><option>2</option><option>3</option><option>4</option><option>5</option></select></div><div class='col-md-3'><select name='package[]' class='form-control'><option>Package</option><option>2</option><option>3</option><option>4</option><option>5</option></select></div><div class='clearfix'></div>";
                    //      i++;
                    // }
                }

                function submitform(){
                    var formData = new FormData($('#myform')[0]);
                    var element = document.getElementById("content");
                    var element1 = document.getElementById("price");
                    element.innerHTML = '';
                    element1.innerHTML = '';
                    var element2 = document.getElementById("final_total");
                    element2.innerHTML = '';
                    $.ajax({
                      url: "validatebooking.inc.php",
                      data: formData,
                      contentType: false, // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
                      processData: false,
                      type: "POST",
                      success: function(response) {
                          console.log(response);
                          if(response == 'true'){
                            $.ajax({
                                url: "adddetails.inc.php",
                                data: formData,
                                contentType: false, // NEEDED, DON'T OMIT THIS (requires jQuery 1.6+)
                                processData: false,
                                type: "POST",
                                success: function(response) {
                                    console.log(response);
                                    var data = JSON.parse(response);
                                    element.innerHTML += data.contents;
                                    element1.innerHTML += data.total;
                                    element2.innerHTML += data.total;
                                    $("#input_total").val(data.total);
                                    $('#sub-total').val(data.total);
                                }
                                });
                          }else{
                              return false;
                          }
                      }
                    });                   
                    
                }

                function couponCode(){
                    var couponcode = $('#couponcode').val();
                    if(couponcode == ''){
                        $("#remove_coupon").hide();
                        $("#apply_coupon").show();
                    }else{
                        $("#remove_coupon").show();
                        $("#apply_coupon").hide();
                    }
                    var sub_total = $('#sub-total').val();
                    console.log(sub_total);
                    var element = document.getElementById("substract-amt");
                    element.innerHTML = '';
                    var element1 = document.getElementById("final_total");
                    element1.innerHTML = '';
                    $.ajax({
                      type: "POST",
                      url: "couponCode.inc.php",
                      data: {couponcode:couponcode, sub_total:sub_total},               
                      success: function(response) {
                        var data = JSON.parse(response);
                        console.log(data);
                        element.innerHTML += data.discount_value;
                        element1.innerHTML += data.final_total;
                        $("#input_total").val(data.final_total);
                      }
                    });
                }

                function RemovecouponCode(){
                    var total = $("#sub-total").val();
                    $('#couponcode').val("");
                    $("#input_total").val("");
                    var element = document.getElementById("substract-amt");
                    element.innerHTML = '';
                    var element1 = document.getElementById("final_total");
                    element1.innerHTML = total;
                    $("#remove_coupon").hide();
                    $("#apply_coupon").show();
                }

            </script>
</body>

</html>