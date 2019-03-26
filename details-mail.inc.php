<?php 
include('site-config.php');
error_reporting(0);
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Page Title</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="main.css">
    <script src="main.js"></script>
</head>
<body>
    <div class="container">
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
                         <?php $contents; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</body>
</html>

<?php 
$mybody = ob_get_contents();
ob_end_clean();

$mail->IsSMTP();
$mail->Host = "shareittofriends.com";

$mail->SMTPAuth = true;
//$mail->SMTPSecure = "ssl";
$mail->Port = 587;
$mail->Username = "noreply@shareittofriends.com";
$mail->Password = "noreply@1234";
$mail->SMTPDebug = 2;
$mail->From = "noreply@shareittofriends.com";
$mail->AddAddress($email);

$mail->IsHTML(true);

$mail->Subject = "Booking Details";
$mail->Body = $mybody;

$mail->Send();
exit;
?>