<?php 

include('site-config.php');

$i = 1;

$count = $func->escape_string($func->strip_all($_POST['count']));

while($i <= $count){

    ob_start(); ?> 
    <div class="col-md-2">
        <label>Vehicle <?php echo $i; ?></label>
    </div>
    <div class="col-md-3">
        <select class="form-control" name="category[]">
        <option>Select Category</option>
        <?php $getAllCategories = $func->getAllCategories();
            while($rw = $func->fetch($getAllCategories)){
        ?> 
        <option value="<?php echo $rw['id']; ?>"  ><?php echo $rw['category_name']; ?></option>
        <?php } ?>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-control" name="package[]">
        <option>Select Packages</option>
        <?php $getAllPackages = $func->getAllPackages();
                while($rw = $func->fetch($getAllPackages)){
        ?> 
        <option value="<?php echo $rw['id']; ?>"  ><?php echo $rw['package_name']; ?></option>
        <?php } ?>
        <option value="quick-wash" >Quick Wash</option>
        </select>
    </div>
    <div class="clearfix"></div>
<?php
$i++;
}
$detail = ob_get_contents();
ob_end_clean();

echo $detail;


?>