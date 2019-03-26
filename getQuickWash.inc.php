<?php 

include('site-config.php');
error_reporting(0);
$myObj = new stdClass();
$msg = 'Failed';

$category = $func->escape_string($func->strip_all($_POST['category']));

$query = $func->query("SELECT * FROM ".PREFIX."quick_wash_master WHERE category = '".$category."'");
$detail = '';
while($details = $func->fetch($query)){
    $file_name = str_replace('', '-', strtolower( pathinfo($details['image'], PATHINFO_FILENAME)));
	$ext = pathinfo($details['image'], PATHINFO_EXTENSION);
 ob_start(); ?> 
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
                            <div class="col-md-6">
                                <h4 class="title2">Exterior</h4>
                                <?php echo $details['exterior_feature']; ?>
                            </div>
                        <?php } ?>
                        <?php if(!empty($details['interior_feature'])){ ?>
                        <div class="col-md-6">
                            <h4 class="title2">Interior</h4>
                            <?php echo $details['interior_feature']; ?>
                        </div>
                        <?php } ?>
                        <div class="col-md-12">
                            <a href="booknow.php?&category=<?php echo $category; ?>&package=quick-wash" class="schedule-bnt a-btn round-corner lightblue-btn">Schedule</a>
                        </div>
                    </div>
                </div>
            </div>
<?php
}
$detail = ob_get_contents();
ob_end_clean();

echo $detail;
?>