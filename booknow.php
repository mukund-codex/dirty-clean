<?php 
include('site-config.php');
	if(isset($_POST['category']) && !empty($_POST['category'])){
		$category_id = $_POST['category']; 
	}
	if(isset($_POST['package']) && !empty($_POST['package'])){
		$package_id = $_POST['package']; 
		
	}
	session_start();
    if(!empty($_SESSION['booking_time'])){
        $booking_time = $_SESSION['booking_time'];
        //echo $booking_time;exit;
    }

	if(isset($_POST['coupon']) && !empty($_POST['coupon'])){
		$coupon = $_POST['coupon']; 
	}
	$result = '';

	if(isset($_POST['submit'])){
		$result = $func->addPersonalDetails1($_POST);
		//$addCoupon = $func->escape_string($func->strip_all($_POST['coupon']));
		header("location:booknow.php?edit=".$result);

	}
	if(isset($_POST['update'])){
		$id = trim($func->escape_string($func->strip_all($_POST['id'])));
		$result = $func->updatePersonalDetails($_POST);
		header("location:booknow.php?edit=".$id);
	}
	if(isset($_GET['edit']) && !empty($_GET['edit'])){
		$data = $func->getPersonalDetails($_GET['edit']);
		$gifteddata = $func->getGiftedPersonDetails($_GET['edit']);
		$bookingDetails = $func->getBookingDetails($data['id']);
		$bookingCount = $bookingDetails->num_rows;; 
	}
	if(isset($_POST['payment_proceed'])){
		$person_id = trim($func->escape_string($func->strip_all($_POST['person_id'])));
		$res = $func->updatePersonalPaymentDetails($_POST);
		
		header("location: thank-you.php");
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
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/font-awesome.css">
    <link rel="icon" type="image/ico" href="images/favicon.png">
    <style>
        .couponcode-div input{
            color: #6f6d6d;
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
        <form method="post" id="myForm">
            <section class="book-now-sec">
                <div class="container">
                    <h1 class="title1">Enter Your <span>details</span></h1>
                    <span class="bar-center"></span>
                    <div class="details-form">
                        <div class="personal-details">
                            <div class="gifting-our-services">
                                <label class="container-ck">Gifting our services?
                                    <input type="checkbox" name="gifting" id="gifting" <?php if((isset($_POST['gifted']) && $_POST['gifted']=='1') || isset($_GET['edit']) && $data['gifted'] == '1'){ echo "checked"; } ?>>
                                    <span class="checkmark-ck"></span>
                                </label>
                            </div>
                            <h4 class="title4">Personal Details</h4>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 paddingl0">
                                    <div class="form-group first-form-group">
                                        <label>Name<em>*</em></label>
                                        <input type="hidden" name="category_id" value="<?php if(isset($_POST['category'])){ echo $category_id; } ?>">
                                        <input type="hidden" name="package_id" value="<?php if(isset($_POST['package'])){ echo $package_id; } ?>">
                                        <input type="hidden" name="coupon" value="<?php if(isset($_POST['coupon'])){ echo $coupon; } ?>">
                                       
                                        
                                        <input type="text" name="first_name" value="<?php if(isset($_GET['edit'])){ echo $data['first_name']; } ?>" required class="form-control inline-inp width50 border-r0" placeholder="First">
                                        <input type="text" name="last_name" value="<?php if(isset($_GET['edit'])){ echo $data['last_name']; } ?>" required class="form-control inline-inp width50 border-l0" placeholder="Last">
                                    </div>
                                    <div class="form-group">
                                        <label>Mobile<em>*</em></label>
                                        <input type="text" name="" class="form-control inline-inp width10 border-r0" placeholder="+91" readonly>
                                        <input type="text" name="mobile" value="<?php if(isset($_GET['edit'])){ echo $data['mobile']; } ?>" required class="form-control inline-inp width90 border-l0" placeholder="Number">
                                    </div>
                                    <div class="form-group">
                                        <label>Email<em>*</em></label>
                                        <input type="email" name="email" required value="<?php if(isset($_GET['edit'])){ echo $data['email']; } ?>" class="form-control" placeholder="Email">
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 paddingr0">
                                    <div class="form-group">
                                        <label>Address<em>*</em></label>
                                        <input type="text" name="address" required  value="<?php if(isset($_GET['edit'])){ echo $data['address']; } ?>" class="form-control" placeholder="Address">
                                        <input type="text" name="landmark" required class="form-control" value="<?php if(isset($_GET['edit'])){ echo $data['landmark']; } ?>" placeholder="Landmark">
                                        <input type="text" name="zipcode" required class="form-control"  value="<?php if(isset($_GET['edit'])){ echo $data['zipcode']; } ?>" placeholder="Zipcode">
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php //if((isset($_POST['gifted']) && $_POST['gifted']=='1') || isset($_GET['edit']) && $data['gifted'] == '1'){ ?>
						<hr>
						  <div class="personal-details" id="giftedDetails" style="display:none">
                           
                            <h4 class="title4">Gift receiptent details</h4>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 paddingl0">
                                    <div class="form-group first-form-group">
                                        <label>Name<em>*</em></label>
                                          
                                        <input type="text" name="g_first_name" value="<?php if(isset($_GET['edit'])){ echo $gifteddata['first_name']; } ?>" required class="form-control inline-inp width50 border-r0" placeholder="First">
                                        <input type="text" name="g_last_name" value="<?php if(isset($_GET['edit'])){ echo $gifteddata['last_name']; } ?>" required class="form-control inline-inp width50 border-l0" placeholder="Last">
                                    </div>
                                    <div class="form-group">
                                        <label>Mobile<em>*</em></label>
                                        <input type="text" name="" class="form-control inline-inp width10 border-r0" placeholder="+91" readonly>
                                        <input type="text" name="g_mobile" value="<?php if(isset($_GET['edit'])){ echo $gifteddata['mobile']; } ?>" required class="form-control inline-inp width90 border-l0" placeholder="Number">
                                    </div>
                                    <div class="form-group">
                                        <label>Email<em>*</em></label>
                                        <input type="email" name="g_email" required value="<?php if(isset($_GET['edit'])){ echo $gifteddata['email']; } ?>" class="form-control" placeholder="Email">
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 paddingr0">
                                    <div class="form-group">
                                        <label>Address<em>*</em></label>
                                        <input type="text" name="g_address" required  value="<?php if(isset($_GET['edit'])){ echo $gifteddata['address']; } ?>" class="form-control" placeholder="Address">
                                        <input type="text" name="g_landmark" required class="form-control" value="<?php if(isset($_GET['edit'])){ echo $gifteddata['landmark']; } ?>" placeholder="Landmark">
                                        <input type="text" name="g_zipcode" required class="form-control"  value="<?php if(isset($_GET['edit'])){ echo $gifteddata['zipcode']; } ?>" placeholder="Zipcode">
                                    </div>
                                </div>
                            </div>
                        </div>
				<?php //} ?>
                        <div class="booking-details">
                            <h4 class="title4">Booking Details</h4>
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="" name="date" id="date"  value="<?php if(isset($_GET['edit'])){ echo $data['booking_date']; } ?>" required class="form-control datepicker">
                                        <span class="date-icon"></span>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6" id="timeDiv">
                                    <div class="form-group">
                                        <label>Time</label>
                                        <input type="" name="time" id="time" value="<?php if(isset($_GET['edit']) && !empty($data['booking_time'])){ echo $data['booking_time']; }else { echo $booking_time; } ?>" class="form-control timepicker">
                                        <span class="time-icon"></span>
                                    </div>
                                </div>
							
                                <div class="col-md-6 col-sm-12">
                                    <label class="container-ck">More than one vehicle ?
                                        <input type="checkbox" id="check" <?php if(isset($_GET['edit']) && $bookingCount > 1){ echo "checked";  } ?> name="check_count" >
                                        <span class="checkmark-ck"></span>
                                    </label>
                                    <br>
									
									<div style="display:none" id="showMore">
                                    <select name="no_vehicles" class="selectpicker width100px" id="getCount" onchange="getval(this);" >
                                        <option <?php if(isset($_GET['edit']) && $bookingCount == '1'){ echo "selected"; } ?> readonly>1</option>
                                        <option <?php if(isset($_GET['edit']) && $bookingCount == '2'){ echo "selected"; } ?> value="2">2</option>
                                        <option <?php if(isset($_GET['edit']) && $bookingCount == '3'){ echo "selected"; } ?> value="3">3</option>
                                        <option <?php if(isset($_GET['edit']) && $bookingCount == '4'){ echo "selected"; } ?> value="4">4</option>
                                        <option <?php if(isset($_GET['edit']) && $bookingCount == '5'){ echo "selected"; } ?> value="5">5</option>
                                    </select>
									</div>
                                </div>
                            </div>
                            <br>
							
							<div id="content">
							<input type="hidden" class="form-control" name="category[]" value="">
							<input type="hidden" class="form-control" name="package[]" value="">
							<?php 
							if(isset($_GET['edit'])){
							$j = 1;
							 while($row = $func->fetch($bookingDetails)){
							?>
							<div class="row vehicle-detl divcount" id="<?php echo $j; ?>">
							<div class="col-md-3 col-sm-12">
							<label>Vehicle <?php echo $j++; ?></label>
							</div>
							<div class="col-md-3 col-sm-6">
							<input type="hidden" name="booking_id[]" value="<?php echo $row['id']; ?>">
								<select class="form-control" name="category[]">
									<option value="">Category</option>
									<?php $getAllCategories = $func->getAllCategories();
										  while($rw = $func->fetch($getAllCategories)){
									?>
									<option <?php if($row['vehicle_category'] == $rw['id']){ echo "selected";  } ?> value="<?php echo $rw['id']; ?>"><?php echo $rw['category_name']; ?></option>
									<?php }  ?>
									
								</select>
							</div>
							<div class="col-md-3 col-sm-6">
								<select class="form-control" name="package[]">
									<option value="">Package</option>
									<?php $getpackage = $func->getAllPackages();
										  while($rw1 = $func->fetch($getpackage)){
									?>
									<option <?php if($row['vehicle_package'] == $rw1['id']){ echo "selected";  } ?> value="<?php echo $rw1['id']; ?>"><?php echo $rw1['package_name']; ?></option>
									<?php } ?>
									<option <?php if($row['vehicle_package'] == 'quick-wash'){ echo "selected";  } ?> value="quick-wash">Quick Wash</option>
								</select>
							</div>
							</div>
							<?php } }  ?>
							<div id="addedContent"></div>
							</div>
                            
                            
							 <div class="row vehicle-detl">
                                <div class="col-md-12">
                                   <!-- <a href="javascript:;" class="lightblue-btn round-corner a-btn booknow-btn">Book Now</a>-->
								   <?php if(isset($_GET['edit'])){ ?>
								   <input type="hidden" name="id" value="<?php if(isset($_GET['edit'])){ echo $data['id']; } ?>" >
								   <input type="submit" name="editupdate" class="lightblue-btn round-corner a-btn booknow-btn" value="Book Now">
								   <?php }else{ ?>
								   <input type="submit" name="submit" class="lightblue-btn round-corner a-btn booknow-btn" value="Book Now">
								   <?php } ?>
                                </div>
                            </div>
							
                        </div>
                    </div>
                </div>
            </section>
		</form>
		<?php if(isset($_GET['edit'])){  ?>
            <section class="booking-summary-sec" id="<?php echo $_GET['edit']; ?>" style="display:block">
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
                            <tbody>
							<?php  
							
							$summary = $func->getBookingDetails($_GET['edit']);
								  if($summary->num_rows > 0){
									   $total = 0;
									  $i= 1;
								  while($res = $func->fetch($summary)){ 
									$category = $func->getCategoryDetails($res['vehicle_category']);
									$category_name = $category['category_name'];
								 
								  $package = $func->getPackageDetailsDetails($res['vehicle_package']);
								  if(empty($package['package_name'])){
										$package_name = $res['vehicle_package'];
									}else{
										$package_name = $package['package_name'];
									}  
									$total = $total + $res['price'];
							?>
                                <tr>
                                    <td data-column="#"><?php echo $i++; ?></td>
                                    <td data-column="Vehicle Type"><?php echo $category_name; ?></td>
                                    <td data-column="Packages"><?php echo $package_name; ?></td>
                                    <td data-column="Qty">
                                        <span class="qty-span">1</span>
                                    </td>
                                    <td data-column="Total Price"><i class="fa fa-inr" aria-hidden="true"></i> <?php echo $res['price']; ?></td>
                                </tr>
							  <?php } } 
							
							  ?>
                            </tbody>
                        </table>
                    </div>
                     <div class="booking-summary-total text-right">
                        <div class="sub-total">
                            <h3>Sub Total  <span id="price"><i class="fa fa-inr" aria-hidden="true"></i> <?php echo $total; ?> </span></h3>
                            <input type="hidden" name="sub-total" id="sub-total" value="<?php echo $total; ?>" />
                        </div>
                        <div class="couponcode-div">
                            <div class="form-group">
                                <input type="text" name="couponcode" value="<?php if(isset($_GET['coupon']) && !empty($_GET['coupon'])){ echo $_GET['coupon'];  } ?>" id="couponcode" placeholder="#couponcode">
                                <button type="button" id="apply_coupon" onclick="couponCode();">Apply</button>
                                <button type="button" id="remove_coupon" onclick="RemovecouponCode();" style="display:none;">Remove</button>
                                <span class="substract-amt" id="substract-amt"></span>
                               
                                <p><small>(* inclusive of all taxes)</small></p>
                            </div>
                        </div>
                        <div class="sub-total">
                            <h3>Total  <span id="final_total"><i class="fa fa-inr" aria-hidden="true"></i> <?php echo $total; ?></span></h3>
                            <input type="hidden" name="input_total" id="input_total" value=""/>
                        </div>
                        <div class="terms">
                            <label class="container-ck" onclick=showSubmit();>I agree to the <span>*</span><a href="terms-n-conditions.php"><u>Terms &amp; Conditions</u></a>
                                <input type="checkbox" id="terms" name="terms">
                                <span class="checkmark-ck"></span>
                            </label>
                        </div>
                        <div class="">
                            <a href="javascript:;" class="lightblue-btn round-corner a-btn continue-btn" id="continue" style="display:none;">Continue</a>
                        </div>
                    </div>
                </div>
            </section>
		<?php } ?>
            <section class="mode-of-payment-sec" id="mode-of-payment">
			<form method="post" >
                <div class="container">
                    <h1 class="title1">Mode of <span>payment</span></h1>
                    <span class="bar-center"></span>
                    <div class="details-form">
                        <div class="mode-of-payment">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <label class="container-r">Cash / card at location
                                        <input type="hidden" name="person_id" value="<?php if(isset($_GET['edit'])){ echo $data['id']; } ?>" >
                                         <input type="hidden" name="dis_amt" id="dis_amt" value=""/>
                                         <input type="hidden" name="final_time" id="final_time" value="<?php echo $booking_time; ?>" />
                                         <input type="hidden" name="finally_total" id="finally_total" value=""/>
                                        <input type="radio" name="mode_of_payment" value="COD" checked>
                                        <span class="checkmark-r"></span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="container-r">Net Banking / Credit Card / Debit Card
                                        <input type="radio" name="mode_of_payment" value="Online Payment">
                                        <span class="checkmark-r"></span>
                                    </label>
                                </div>
                                <div class="col-md-12">
                                    <input class="lightblue-btn round-corner a-btn" name="payment_proceed" type="submit" value="proceed to payment">
                                  <!--  <a href="thank-you.php" class="lightblue-btn round-corner a-btn">proceed to payment</a>-->
                                </div>
                                <div class="col-md-12">
                                    <img src="images/cards.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				</form>
            </section>
      
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
            <script type="text/javascript">
            jQuery.validator.addMethod("lettersonly", function(value, element) {
            return this.optional(element) || /^[a-zA-Z\/\' ]+$/i.test(value);
            }, "Letters only please");
			 $("#myForm").validate({
                rules: {
                    first_name: {
                        required: true,
                        lettersonly:true,
                        },
                    last_name: {
                        required: true,
                        lettersonly:true
                        },
                    mobile: {
                        required: true,
                        number:true,
                        maxlength:10,
                        minlength:10
                        },
                    email: {
                        required: true,
                        email:true
                        },
                    address: {
                        required: true,
                        },
                    landmark: {
                        required: true,
                        },
                    zipcode: {
                        required: true,
                        },
                    date: {
                        required: true,
                        },
                    time: {
                        required: true,
                        }
                    },
                messages: {
                    first_name: {
                        required: "Please enter First Name",
                        //lettersonly: "Enter only letters"
                    },
                        last_name: {
                        required: "Please enter Last Name",
                        //lettersonly: "Enter only letters"
                    },
                    mobile: {
                        required: "Please enter Mobile",
                        number: "Enter only number",
                    },
                    email: {
                        required: "Please enter Email",
                        email: "Enter valid Email"
                    },
                    address: {
                        required: "Please enter Address",
                    },
                    landmark: {
                        required: "Please enter Landmark",
                    },
                    zipcode: {
                        required: "Please select Zipcode",
                    },
                    date: {
                        required: "Please select date",
                    },
                    time: {
                        required: "Please select Time",
                    }
                    },
            });
							
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
                    element1.innerHTML = '<i class="fa fa-inr" aria-hidden="true"></i>';
                    $.ajax({
                      type: "POST",
                      url: "couponCode.inc.php",
                      data: {couponcode:couponcode, sub_total:sub_total},               
                      success: function(response) {
                        var data = JSON.parse(response);
                        console.log(data);
                        element.innerHTML += data.discount_value;
                        $("#dis_amt").val(data.discount_value);
                        element1.innerHTML += data.final_total;
                        $("#input_total").val(data.final_total);
                        $("#finally_total").val(data.final_total);
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
                    element1.innerHTML = '<i class="fa fa-inr" aria-hidden="true"></i>'+total;
                    $("#remove_coupon").hide();
                    $("#apply_coupon").show();
                }

 
                function showSubmit(){
                    if($('#terms'). prop("checked") == true){
                        $("#continue").show();
                        var time = $("#time").val();
                        console.log(time);
                        $("#final_time").val(time);
                    }
                    else{
                        $("#continue").hide();
                    }
                }

                $(window).scroll(function() {
                    if ($(window).scrollTop() > 100) {
                        $(".header-fix").addClass("fix_nav");
                    } else {
                        $(".header-fix").removeClass("fix_nav");
                    }
                });
				
					
                $(document).ready(function() {

                    /*$('.booknow-btn').click(function(){
                        $('html, body').animate({
                          scrollTop: ($('.booking-summary-sec').offset().top - 100)
                        }, 1200);
                       return false;
                    });*/
    
					
					// $(".booknow-btn").click(function() {
					    $(".booking-summary-sec").slideDown("slow");
					// });
					if($('#gifting'). prop("checked") == true){
						$("#giftedDetails").show();
					}
					if($('#check'). prop("checked") == true){
						$("#showMore").show();
					}
					$("#gifting").change(function(){
					if($('#gifting'). prop("checked") == true){
						$("#giftedDetails").show();
					}else{
						$("#giftedDetails").hide();
					}
					});
					$("#check").click(function(){
						 $("#showMore").toggle(this.checked);
					});
					
					
					//$("#getCount").change(function(){
						
					//});
					
                    $(".datepicker").datepicker({
                        minDate: 0,
                        dateFormat: "dd/mm/yy",
                        autoclose: true,
                    });
                    $(".timepicker").timepicker({});

					$("#date").on("change",function(){
						 var date = $(this).val();
						
						 //$("#timeDiv").show();
						
						$("#timeDiv").load("getTimeByDate.php",{
							date:date
							
						});
					});

                    $(".continue-btn").click(function() {
                        $(".mode-of-payment-sec").slideDown("slow");
                        
                        $('html, body').animate({
                          scrollTop: ($('#mode-of-payment').offset().top - 100)
                        }, 1200);
                       return false;
                    });
					
					
                });
				function getval(sel){
					//alert(sel.value);
						var i;
						var count = sel.value;
						var count1 = sel.value;
						var content='';
						var bookingCount = '';
						<?php if(isset($_GET['edit'])){ ?>
							bookingCount = '<?php echo $bookingCount; ?>';
							
							
							var divcount = $('.divcount').length;
							//if(sel.value > bookingCount){
							count = sel.value-divcount;
						//	}
							 if(count1<divcount){
								count1++;
								while(count1<=divcount){
									$("#"+divcount).remove();
									divcount--;
								}
							} 
							console.log(divcount);
						<?php } ?>
						
						var i=1;
						var vehicleCount = 0;
						var vehicleCount = $('.divcount').length;
						vehicleCount++;
						//console.log("sda "+vehicleCount);
						while(i<=count){
							
							/*  if(sel.value >= bookingCount){
								vehicleCount= parseInt(bookingCount) + i;
							}else{
								vehicleCount =vehicleCount + i;
							}  */
							content+='<div class="row vehicle-detl "  ><div class="col-md-3"><label>Vehicle '+vehicleCount+'</label></div><div class="col-md-3"><select class="form-control" name="category[]"><option value="">Category</option>';
							<?php $getAllCategories = $func->getAllCategories();
								  while($rw = $func->fetch($getAllCategories)){
							?>
							content+='<option value="<?php echo $rw['id']; ?>"><?php echo $rw['category_name']; ?></option>'; 
							<?php }  ?>
							content+='</select></div> <div class="col-md-3"><select class="form-control" name="package[]"><option value="">Package</option>';
							<?php $getpackage = $func->getAllPackages();
								  while($rw1 = $func->fetch($getpackage)){
							?>
							content+='<option value="<?php echo $rw1['id']; ?>"><?php echo $rw1['package_name']; ?></option>';
							<?php } ?>
							content+='<option value="quick-wash">Quick Wash</option></select></div></div>';
							
						i++;	
						vehicleCount++;
						} 
						<?php if(isset($_GET['edit'])){ ?>
						if(count<bookingCount){
							//$("#content").find("#2").remove();
						}
						$("#addedContent").html(content);
						<?php }else{ ?>
						$("#content").html(content);
						<?php } ?>
					}
				//content+='<div class="row vehicle-detl"><div class="col-md-2"><label>Vehicle '+i+'</label></div><div class="col-md-2"><select class="form-control" name="category[]"><option>Category</option> <option value="1">1</option> <option value="2">2</option> <option value="3">3</option></select></div> <div class="col-md-3"><select class="form-control" name="package[]"><option>Package</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select></div></div>';
            </script>
            <script>
                    <?php 
                       if(isset($_GET['edit'])){
                    ?>
                         $('html, body').animate({
                         scrollTop: $( '#<?php echo $_GET['edit']; ?>' ).offset().top - 100}, 1200);
                    <?php
                       }
                    ?>
            </script>
</body>

</html>