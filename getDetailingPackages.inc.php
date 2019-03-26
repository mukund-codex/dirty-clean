<?php 

include('site-config.php');
error_reporting(E_ALL);
$myObj = new stdClass();
$msg = 'Failed';
$color_class = '';
$category = $func->escape_string($func->strip_all($_POST['category']));
$gifted = $func->escape_string($func->strip_all($_POST['gifted']));
$coupon = $func->escape_string($func->strip_all($_POST['coupon']));

$query = $func->query("SELECT a.* FROM ".PREFIX."subscription_master a INNER JOIN ".PREFIX."package_master b on a.package_name = b.id WHERE category = '".$category."' order by display_order");
$detail = '';
ob_start(); 
while($details = $func->fetch($query)){
    $package = $func->fetch($func->query("SELECT * FROM ".PREFIX."package_master WHERE id = '".$details['package_name']."' order by display_order ASC"));
    //$pieces = explode(" ", $details['validity_period']);
    $duration_from = $details['duration_from']/60; 
    $duration_to = $details['duration_to']/60;
    if($package['package_name'] == 'Bronze Package'){
        $color_class = 'bronze';
    }else if($package['package_name'] == 'Silver Package'){
        $color_class = 'silver';
    }else if($package['package_name'] == 'Gold Package'){
        $color_class = 'gold';
    }else{
        $color_class = 'platinum';
    }
 ?> 
    <div class='col-md-3 col-sm-6'>
        <div class='package <?php echo $color_class; ?>'>
            <div class='package-title'>
                <h2> <?php echo strtoupper($package['package_name']); ?></h2>
            </div>
            <div class='package-price'>
                <sup class='p_currency'><i class='fa fa-inr' aria-hidden='true'></i></sup>
                <span class='p_price'><?php echo number_format($details['package_price'], 2); ?></span>
                <span class='p_measure'>Per Vehicle</span>
            </div>
            <div class='padding15'>
                <div class='package-duration'>
                    <h2><?php echo $duration_from; ?> to <?php echo $duration_to; ?> Hours</h2>
                </div>
                <div class='exterior feature-div'>
                    <?php if(!empty($details['exterior_feature'])){ ?>
                        <h3>Exterior</h3>
                        <?php echo $details['exterior_feature']; ?>
                    <?php } ?>
                </div>
                <div class="interior feature-div">
                    <?php if(!empty($details['interior_feature'])){ ?>
                        <h3>Interior</h3>
                        <?php echo $details['interior_feature']; ?>
                    <?php } ?>
                </div>
                <div class="engine-bay feature-div">
                    <?php if(!empty($details['engine_bay_feature'])){ ?>
                        <h3>Engine Bay</h3>
                        <?php echo $details['engine_bay_feature']; ?>
                    <?php } ?>
                </div>

                <div class='schedule-bnt-div'>
				<form action="booknow.php" method="post">
				<input type="hidden" name="category" value="<?php echo $category; ?>">
				<input type="hidden" name="package" value="<?php echo $details['package_name']; ?>">
				<input type="hidden" name="gifted" value="<?php echo $gifted; ?>">
				<input type="hidden" name="coupon" value="<?php echo $coupon; ?>">
				<input type="submit" class='schedule-bnt a-btn round-corner hrefVal' value='Schedule'>
                  <!--  <a href='booknow.php?category=<?php //echo $category; ?>&package=<?php// echo $details['package_name']; ?>&gifted=<?php //echo $gifted; ?>'  class='schedule-bnt a-btn round-corner orange-btn hrefVal'>Schedule</a>-->
				</form>
                </div>
            </div>
        </div>
    </div>
<?php
}
$detail = ob_get_contents();
ob_end_clean();
$myObj->packages = $detail;

// echo $detail;

$query = $func->query("SELECT * FROM ".PREFIX."quick_wash_master WHERE category = '".$category."'");
$detail1 = '';
ob_start(); 
while($details = $func->fetch($query)){
    $file_name = str_replace('', '-', strtolower( pathinfo($details['image'], PATHINFO_FILENAME)));
	$ext = pathinfo($details['image'], PATHINFO_EXTENSION);
    ?> 
    <h1 class="title1">Quick <span>Wash</span></h1>
            <span class="quick-wash-min"><span><?php echo $details['time']; ?> Mins</span></span>
            <span class="bar-center"></span>
            <div class="row">
                <div class="col-md-5 text-center">
                    <img src="img/quick-wash/<?php echo $file_name.'_crop.'.$ext ?>">
                    <div class="car-price">
                        <sup class="p_currency"><i class="fa fa-inr" aria-hidden="true"></i></sup>
                        <span class="p_price"><?php echo number_format($details['price'], 2); ?> /-</span>
                        <span class="p_measure">Per Vehicle</span>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-md-12">
                            <?php echo $details['main_features']; ?>
                        </div>
                    </div>
                    <div class="row">
                        <?php if(!empty($details['exterior_feature'])){ ?>
                            <div class="col-md-6 col-sm-6">
                                <h4 class="title2">Exterior</h4>
                                <?php echo $details['exterior_feature']; ?>
                            </div>
                        <?php } ?>
                        <?php if(!empty($details['interior_feature'])){ ?>
                        <div class="col-md-6 col-sm-6">
                            <h4 class="title2">Interior</h4>
                            <?php echo $details['interior_feature']; ?>
                        </div>
                        <?php } ?>
                        <div class="col-md-12">
						<form method="post" action="booknow.php">
						<input type="hidden" name="category" value="<?php echo $category; ?>">
						<input type="hidden" name="package" value="<?php echo 'quick-wash'; ?>">
						<input type="submit"  class="schedule-bnt a-btn round-corner lightblue-btn"  value="Schedule">
						
						</form>
                          <!--  <a href="booknow.php?&category=<?php// echo $category; ?>&package=quick-wash" class="schedule-bnt a-btn round-corner lightblue-btn">Schedule</a>-->
                        </div>
                    </div>
                </div>
            </div>
	<?php
	}
	$detail1 = ob_get_contents();
	ob_end_clean();

	ob_start();
	?>
	<h4 class="title3">Gift a service</h4>
	<form method="post" action="pricing.php">
	<input type="hidden" name="category" value="<?php echo $category; ?>">
	<input type="hidden" name="gifted" value="1">
	<input type="submit" name="gifted_package" class="book-now orange" value="Book Now">
	</form>
	<!--<a href="pricing.php?gifted&category=<?php //echo $category; ?>" class="book-now orange" >Book Now</a>-->
	<p><span>*</span>Refer <a href="terms-n-conditions.php">Terms & Conditions</a></p>
	
	<?php 
	$gifting = ob_get_contents();
	ob_end_clean();

	ob_start();
	$coupon = $func->getCoupon();
	?>
	<h4 class="title3">Get <?php if($coupon['coupon_type'] == 'percent'){ echo $coupon['coupon_value']."%"; }else{ echo $coupon['coupon_value']; } ?> Off<br><span>ON bULK BOOKINGS</span></h4>
	<form method="post" action="pricing.php">
	<input type="hidden" name="category" value="<?php echo $category; ?>">
	<input type="hidden" name="coupon" value="<?php echo $coupon['coupon_code'];  ?>">
	<input type="submit" name="addCoupon" class="book-now blue" value="#<?php echo $coupon['coupon_code']; ?>">
	</form>
	
	<p><span>*</span>Refer <a href="terms-n-conditions.php">Terms & Conditions</a></p>
	<?php 
	$bulk = ob_get_contents();
	ob_end_clean();

$myObj->quickwash = $detail1;
$myObj->gifting = $gifting;
$myObj->bulk = $bulk;
$myJSON = json_encode($myObj);
echo $myJSON;


?>