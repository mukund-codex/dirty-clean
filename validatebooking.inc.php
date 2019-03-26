<?php 

include('site-config.php');

$errorArr = array();
$fn = $func->escape_string($func->strip_all($_POST['first_name']));
$ln = $func->escape_string($func->strip_all($_POST['last_name']));
$mb = $func->escape_string($func->strip_all($_POST['mobile']));
$em = $func->escape_string($func->strip_all($_POST['email']));
$add = $func->escape_string($func->strip_all($_POST['address']));
$bd = $func->escape_string($func->strip_all($_POST['date']));
$bt = $func->escape_string($func->strip_all($_POST['time']));

if($fn == ''){
    echo "false";
}else if($ln == ''){
    echo "false";
}else if($mb == ''){
    echo "false";
}else if($em == ''){
    echo "false";
}else if($add == ''){
    echo "false";
}else if($bd == ''){
    echo "false";
}else if($bt == ''){
    echo "false";
}else{
    echo "true";
}

?>