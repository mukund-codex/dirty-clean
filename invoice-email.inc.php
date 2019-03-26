<?php
	ob_start();
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Order Confirmation</title>
    <style>


		 @import url(https://fonts.googleapis.com/css?family=Roboto:300); /*Calling our web font*/

		/* Some resets and issue fixes */
        #outlook a { padding:0; }
		body{ width:100% !important; -webkit-text; size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0; }     
        .ReadMsgBody { width: 100%; }
        .ExternalClass {width:100%;} 
        .backgroundTable {margin:0 auto; padding:0; width:100%;!important;} 
        table td {border-collapse: collapse;}
        .ExternalClass * {line-height: 115%;}	
        
        /* End reset */
		
		
		/* These are our tablet/medium screen media queries */
        @media screen and (max-width: 630px){
                
				
			/* Display block allows us to stack elements */                      
            *[class="mobile-column"] {display: block;} 
			
			/* Some more stacking elements */
            *[class="mob-column"] {float: none !important;width: 100% !important;}     
			     
			/* Hide stuff */
            *[class="hide"] {display:none !important;}          
            
			/* This sets elements to 100% width and fixes the height issues too, a god send */
			*[class="100p"] {width:100% !important; height:auto !important;}			        
				
			/* For the 2x2 stack */			
			*[class="condensed"] {padding-bottom:40px !important; display: block;}
			
			/* Centers content on mobile */
			*[class="center"] {text-align:center !important; width:100% !important; height:auto !important;}            
			
			/* 100percent width section with 20px padding */
			*[class="100pad"] {width:100% !important; padding:20px;} 
			
			/* 100percent width section with 20px padding left & right */
			*[class="100padleftright"] {width:100% !important; padding:0 20px 0 20px;} 
			
			/* 100percent width section with 20px padding top & bottom */
			*[class="100padtopbottom"] {width:100% !important; padding:20px 0px 20px 0px;} 
			
		
        }
			
        
    </style>
</head>
<body style="padding:0; margin:0; background:#687079" bgcolor="#687079">
	<table border="0" cellpadding="0" cellspacing="0" style="margin: 0; padding: 0" width="100%">
	    <tr>
	        <td align="center" valign="top">
	            <table width="640" border="0" cellspacing="0" cellpadding="0" class="hide">
	                <tr>
	                    <td height="20"></td>
	                </tr>
	            </table>
	            <table width="640" cellspacing="0" border="0" cellpadding="21" bgcolor="#fff" class="100p" style="border-bottom:solid 1px #ddd;">
	                <tr>
	                    <td background="#fff" bgcolor="#fff" width="640" valign="top" class="100p">
							<div>
								<table width="640" border="0" cellspacing="0" cellpadding="20" class="100p">
									<tr>
										<td valign="top">
											<table border="0" cellspacing="0" cellpadding="0" width="600" class="100p">
												<tr>
													<td align="center" width="100%" class="100p"><a href="<?php echo BASE_URL; ?>/index.php" target="_blank" ><img  src="images/logo (1).png" alt="" border="0" style="display:block" /></a></td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</div>
	                    </td>
	                </tr>
	            </table>
	            <table width="640" border="0" cellspacing="0" cellpadding="20" bgcolor="#ffffff" class="100p">
	                <tr>
	                    <td style="font-size:16px; color:#444;">
							<font face="'Roboto', Arial, sans-serif">

							<p>Dear <?php echo $data['first_name']." ".$data['last_name']; ?>,</p>
							<p>Please find the Booking Confirmation for your Booking ID - <strong><?php echo $booking_id; ?></strong></p>

							<div style="border-top:solid 1px #ddd;">
								<div style="text-align:center; color:#000;"><h2>Booking Details</h2></div>

								<div style="width:640px; height:auto; border:1px solid #ddd; margin:10px 0 0 0 ">
									<div style="width:100%;">
										<div style="float:left; padding:10px; width:300px;"><strong>Sender:</strong></div>
										<?php if(!empty($data['gifting']) && $data['gifting']=='1'){ ?>
											<div style="float:right; padding:10px; width:300px;"><strong>Recipient:</strong></div>
										<?php } ?>
									</div>
									<div style="clear:both;"></div>
								</div>
								<div style="width:640px; height:auto; border:1px solid #ddd; margin:-1px 0 0 0;">
									<div style="width:100%;">
										<div style="float:left; padding:10px; width:300px;">
											<?php 
												echo "Name - ".$data['first_name']." ".$data['last_name']."<br>";
												echo "Email - ".$data['email']."<br>";
												echo "Mobile - ".$data['mobile']."<br>";
												echo "Address - ".$data['address']." ".$data['landmark']."<br>";
												echo "Pincode - ".$data['zipcode']."<br>";
											?>
										</div>
										<?php if(!empty($data['gifting']) && $data['gifting']=='1' ){
												 
											?>
											<div style="float:right; padding:10px; width:300px;">
												<?php 
													echo "Name - ".$giftedData['first_name']." ".$giftedData['last_name']."<br>";
													echo "Email - ".$giftedData['email']."<br>";
													echo "Mobile - ".$giftedData['mobile']."<br>";
													echo "Address - ".$giftedData['address']." ".$giftedData['landmark']."<br>";
													echo "Pincode - ".$giftedData['zipcode']."<br>";
												?>
											</div>
										<?php } ?>
									</div>
									<div style="clear:both;"></div>
								</div>
								
								<!-- <div style="width:640px; height:auto; border:1px solid #ddd; margin:10px 0 0 0 ">
									<div style="width:100%; font-size:12px;">
										<div style="display:inline-block; padding:8px; width:30%; border-right:1px solid #ddd;">Order No :</div>
										<div style="display:inline-block; padding:8px; width:29%;">Order Date :</div>
										<?php /* <div style="float:left;  margin:10px; width:100px; border-right:1px solid medium #000;">Shipping Date :</div>
										<div style="float:left;  margin:10px; width:100px; border-right:1px solid medium #000;">Shipping Time :</div> */ ?>
									</div>
								</div>
								<div style="width:640px; height:auto; border:1px solid #ddd; margin:-1px 0 0 0 ">
									<div style="width:100%; font-size:12px;">
										<div style="display:inline-block; padding:8px; width:30%; border-right:1px solid #ddd;"></div>
										<div style="display:inline-block; padding:8px; width:29%;"></div>
										<?php /*<div style="float:left; margin:10px; width:100px; border-right:1px solid medium #000;">'.$pd['delivery_date'].'</div>
										<div style="float:left; margin:10px; width:100px; border-right:1px solid medium #000;">'.$pd['delivery_time'].'</div> */ ?>
									</div>
								</div> -->
								
								<div style="width:640px; margin:10px 0 0 0; float:left;">
									<table width="640" border="0" cellspacing="0" cellpadding="0" style="margin: 20px 0 0 0; font-size:12px;">
										<tr>
											<td width="234" style="padding:5px;border:solid 1px #ddd;">Category</td>
											<td width="234" style="padding:5px;border:solid 1px #ddd;">Package Name</td>
											<td width="234" style="padding:5px;border:solid 1px #ddd;">Price</td>
										</tr>
									<?php 
									 
											$summary = $this->getBookingDetails($data['id']);
											
											$total='';
											
											 while($res = $this->fetch($summary)){ 
											 $category = $this->getCategoryDetails($res['vehicle_category']);
											 $category_name = $category['category_name'];
										     $package = $this->getPackageDetailsDetails($res['vehicle_package']);
											  if(empty($package['package_name'])){
													$package_name = $res['vehicle_package'];
												}else{
													$package_name = $package['package_name'];
												}  
												$total = $total + $res['price'];
									 ?>
												<tr>
													<td width="234" style="padding:5px;border:solid 1px #ddd;"><?php echo $category_name; ?></td>
													<td width="234" style="padding:5px;border:solid 1px #ddd;"><?php echo $package_name; ?></td>
													<td width="234" style="padding:5px;border:solid 1px #ddd;"><?php echo $res['price']; ?></td>
												</tr>
									<?php }  ?>
										
									</table>
									<div style="float:right; margin:20px 0 0 0; text-align:right;color:#2a8e9d">
										<div><strong>Subtotal: <span style="margin-left:10px;">Rs. <?php echo number_format($total,2); ?></span></strong></div>
										<?php $sub_total = str_replace(',', '', $discountAmt);
											  $finalTotal = $total-$sub_total;
										?>
										<div><strong>Final Total: <span style="margin-left:10px;">Rs. <?php echo number_format($finalTotal,2); ?></span></strong></div>
									</div>
								</div>

								<div style="clear:both"></div>
								<div style="border-top:1px solid #ddd; margin-top:30px; font-size:12px; text-align:right; padding:5px; color:#848484">
									Contact customer care at or call  in case of any query.<br>
									This is a computer generated Order Confirmation and does not need signature.
								</div>
							</div>

							</font>
	                    </td>
	                </tr>
	            </table>
				 <table width="640" border="0" cellspacing="0" cellpadding="0" class="hide">
	                <tr>
	                    <td height="20"></td>
	                </tr>
	            </table>
			</td>
	    </tr>
	</table>
</body>
</html>

<?php 
	$invoiceMsg = ob_get_contents();
	ob_end_clean();
?>