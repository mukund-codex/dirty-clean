<?php 
	include_once 'include/config.php';
	include_once 'include/user-functions.php';
	$admin = new CompanyFunctions();
	
	$result = $admin->query("SET NAMES utf8");
	
	if(!$loggedInUserDetailsArr = $admin->sessionExists()){
		header("location: user-login.php");
		exit();
	}
	
	if(isset($_GET['timeline_type'])){
		//exit;
		$timeline_type=$admin->escape_string($admin->strip_all($_GET['timeline_type']));
		$company_id=$admin->escape_string($admin->strip_all($_GET['id']));
		
		$linkParam = "id=$company_id&timeline_type=$timeline_type";
		$timeext = "";
		if($timeline_type=="Month"){
			$month = $admin->escape_string($admin->strip_all($_GET['month']));
			$year=$admin->escape_string($admin->strip_all($_GET['year']));
			$linkParam.="&month=$month&year=$year";
			//$timeext = "Month-$month-Year-$year";
			
			// $month_val = date("M", strtotime($month));
			 $year_val = date("y", strtotime($year));
			  if($month == "February"){ $month_val = "Feb";}else{  $month_val = date("M", strtotime($month)); }
			 $timeext = $month_val.'-'.$year_val;
										 
			$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='".$timeline_type."' and month='".$month."' and year='".$year."' and company_id='".$company_id."'";
		}elseif($timeline_type=="Qtr"){
			$qtr=$admin->escape_string($admin->strip_all($_GET['qtr']));
			$finyear=$admin->escape_string($admin->strip_all($_GET['finyear']));
			
			$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='".$timeline_type."' and qtr='".$qtr."' and year='".$finyear."' and company_id='".$company_id."'";
			$yesrdata =  $admin->fetch($admin->query($sql));	
			$linkParam.="&qtr=$qtr&finyear=$finyear";
			//$timeext = "Quarter-$qtr-Year-$finyear";
			
			$exp_yesr = explode("-",$finyear);
			
			if($qtr == 'Q1'){
				$qtr = 'Qtr1';
				if(is_null($yesrdata['id'])){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('April','May','June') and year='".$exp_yesr[0]."' and company_id='".$company_id."'";
				}
			}else if($qtr == 'Q2'){
				$qtr = 'Qtr2';
				if(is_null($yesrdata['id'])){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('July','August','September') and year='".$exp_yesr[0]."' and company_id='".$company_id."'";
				}
			}else if($qtr == 'Q3'){
				$qtr = 'Qtr3';
				if(is_null($yesrdata['id'])){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('October','November','December') and year='".$exp_yesr[0]."' and company_id='".$company_id."'";
				}
			}else if($qtr == 'Q4'){
				$qtr = 'Qtr4';
				if(is_null($yesrdata['id'])){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('January','February','March') and year='".$exp_yesr[1]."' and company_id='".$company_id."'";
				}
			}
			$timeext = $qtr;
		}elseif($timeline_type=="Yr"){
			$finyear=$admin->escape_string($admin->strip_all($_GET['finyear']));
			$linkParam.="&finyear=$finyear";
			//$timeext = "Year-$finyear";
			
			$yformate= explode('-',$finyear);
			$timeline =  $yformate[0].'-'.substr($yformate[1],2);
			$timeext = $timeline_type." ".$timeline;
			
			$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='".$timeline_type."' and year='".$finyear."' and company_id='".$company_id."'";
			$yesrdata =  $admin->fetch($admin->query($sql));
			//$admin->num_rows($admin->query($sql)) <=0  &&
			if(is_null($yesrdata['id'])){
				
				if(isset($_SESSION['year_value']) and $_SESSION['year_value']=='HY I'){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('April','May','June','July','August','September') and year='".$yformate[0]."' and company_id='".$company_id."'";
				}
				if(isset($_SESSION['year_value']) and $_SESSION['year_value']=='Upto Qtr III'){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('April','May','June','July','August','September','October','November','December') and year='".$yformate[0]."' and company_id='".$company_id."'";
				}
				if(isset($_SESSION['year_value']) and $_SESSION['year_value']=='FY'){
					$sql="select group_concat(id) as id from ".PREFIX."company_timeline where timeline_type='Month' and month in ('April','May','June','July','August','September','October','November','December','January','February','March') and year='".$finyear."' and company_id='".$company_id."'";
				}
			}
		}
		//echo $sql;
		//exit;
		$timelineRsult=$admin->query($sql);
		if($admin->num_rows($timelineRsult)>0){
			$timelineRow=$admin->fetch($timelineRsult);
			$timeline_id = $timelineRow['id'];
			
			$company_details=$admin->fetch($admin->query("select * from ".PREFIX."company_master where id='".$company_id."'"));
			$query="select id,company_id,timeline_id,customer_name,product_name,domestic,zones,countries,sum(qty) qty, sum(sales_value) sales_value from ".PREFIX."customer_product_matrix where company_id='$company_id' and timeline_id in ($timeline_id) group by customer_name,product_name order by id asc";
			
			$produictMatrixResult=$admin->query($query);
			$ProductSegmentAliasResult=$admin->query("select * from ".PREFIX."product_segment_alias where company_id='$company_id' and timeline_id in ($timeline_id) group by product_segment_alias");
			$CustomerSegmentAliasResult=$admin->query("select * from ".PREFIX."customer_segment_alias where company_id='$company_id' and timeline_id in ($timeline_id) group by customer_segment_alias");
			$product_ctm_aliasResult=$admin->query("select *,group_concat(id) as nid from ".PREFIX."product_ctm_alias where company_id='$company_id' and timeline_id in ($timeline_id) group by product_ctm_alias");
			$customer_cc_aliasResult=$admin->query("select *,group_concat(id) as nid from ".PREFIX."customer_cc_alias where company_id='$company_id' and timeline_id in ($timeline_id) group by customer_cc_alias");
			$cost_to_convertResult=$admin->query("select * from ".PREFIX."activity_master where company_id='$company_id' and timeline_id in ($timeline_id) and cost_buckets='".$company_details['ctc_alias']."' group by activity_master");
			$cost_to_sustainResult=$admin->query("select * from ".PREFIX."activity_master where company_id='$company_id' and timeline_id in ($timeline_id) and cost_buckets='".$company_details['cts_alias']."' group by activity_master");
			$cost_to_serveResult=$admin->query("select * from ".PREFIX."activity_master where company_id='$company_id' and timeline_id in ($timeline_id) and cost_buckets='".$company_details['snm_alias']."'  group by activity_master");
			
			$customerProductMatrixAlias=$admin->query("select * from ".PREFIX."customer_product_matrix_alias where company_id='$company_id' and timeline_id in ($timeline_id)");

		} else{
			header('location:cost-reveal.php?id='.$company_id."&fail=Timeline not found");
			exit;
		}
	}	
?>
<?php

	header("Content-type: text/x-csv");
	header('Content-Disposition: attachment; filename=data.csv');
	header("Content-Type: application/vnd.ms-excel"); 
	header("Content-type: application/octet-stream");
	header('Content-Type: image/jpeg');
	//header("Content-Disposition: attachment; filename=".$loggedInUserDetailsArr['company_name']."-TCR-".$timeext.".xls"); 
	header("Content-Disposition: attachment; filename= Transaction CostReveal - ".$timeext.".xls"); 
	header("Pragma: no-cache"); 
	header("Expires: 0");

 ?>

<table border="1px" style="border-collapse:collapse">
	<thead>
		<tr>
			<th colspan="14" style="text-align:center;" height="20"><b>Transaction CostReveal Report</b></th>
		</tr>
		<tr>
			<th rowspan="2"> Customer</th>
			<th rowspan="2"> Product</th>
			
			<?php if($admin->num_rows($ProductSegmentAliasResult)>0){ ?> <th colspan="<?php echo $admin->num_rows($ProductSegmentAliasResult); ?>">Product Segment</th><?php } ?>
			<th colspan="<?php echo $admin->num_rows($CustomerSegmentAliasResult); ?>">Customer Segment</th>
			
			<?php
				$productSegmentsRS = $admin->getCustomerProductAliasByCompanyIdlimit_new($company_id,$timeline_id);
				$calquery = $admin->query("select * from ".PREFIX."customer_product_matrix_alias where company_id='$company_id' and timeline_id in ($timeline_id) group by customer_product_alias order by id");	
				$data = $admin->fetch($calquery);
				if(isset($data) && !empty($data)){
						while($productSegment1= $admin->fetch($productSegmentsRS)) 
						{?>
							<th rowspan="2"><?php echo $productSegment1['customer_product_alias']; ?></th>
					<?php }} else{ ?>
						
						<th rowspan="2">Quantity</th>
						<th rowspan="2">Sales Value</th>
						<th rowspan="2">Domestic /Export</th>
						<th rowspan="2">Zones</th>
						<th rowspan="2">Countries</th>
			<?php } ?>
			
			
			<th colspan="<?php echo $admin->num_rows($product_ctm_aliasResult); ?>" <?php if($admin->num_rows($product_ctm_aliasResult)==0){ echo "rowspan='2'"; } ?>> <?php echo $company_details['ctm_alias'] ?></th>
			<th rowspan="2"> <?php echo $company_details['ctm_alias'] ?> Total</th>
			<th colspan="<?php echo $admin->num_rows($cost_to_convertResult); ?>"> <?php echo $company_details['ctc_alias'] ?></th>
			<th rowspan="2"> <?php echo $company_details['ctc_alias'] ?> Total</th>
			<th colspan="<?php echo $admin->num_rows($cost_to_sustainResult); ?>"> <?php echo $company_details['cts_alias'] ?></th>
			<th rowspan="2"> <?php echo $company_details['cts_alias'] ?> Total</th>
			<?php if($admin->num_rows($customer_cc_aliasResult)>0){
					//$i=1;
					//while($customer_cc_aliasRow=$admin->fetch($customer_cc_aliasResult)){
			?>
				<th  colspan="<?php echo $admin->num_rows($customer_cc_aliasResult); ?>"> CC<!-- Place behalf cc--></th>	
			<?php	//$i++; 
					//}
				}
				//mysqli_data_seek($customer_cc_aliasResult,0);
			?>
			<th colspan="<?php echo $admin->num_rows($cost_to_serveResult); ?>"> <?php echo $company_details['snm_alias'] ?></th>
			<th rowspan="2"> <?php echo $company_details['snm_alias'] ?> Total</th>
			<th rowspan="2"> Total Cost</th>
			<th rowspan="2"> Profit/Loss</th>
		</tr>
		<tr style="margin-top:50px">
			
			<?php if($admin->num_rows($ProductSegmentAliasResult)>0){?>
			<?php
				$i=1;
				while($ProductSegmentAliasRow=$admin->fetch($ProductSegmentAliasResult)){
			?>
			<th rowspan="1"><!--Product Segment echo $i; --> <?php echo $ProductSegmentAliasRow['product_segment_alias']; ?> </th>	
			<?php	$i++; 
				}
				}				
			?>
			
			<?php if($admin->num_rows($CustomerSegmentAliasResult)>0){?>			
			<?php
				$i=1;
				while($CustomerSegmentAliasRow=$admin->fetch($CustomerSegmentAliasResult)){
			?>
			<th rowspan="1"><!--Customer Segment echo $i; " (".--> <?php echo $CustomerSegmentAliasRow['customer_segment_alias']; ?> </th>	
			<?php	$i++; 
				}
				}
				
			?>
			
			<?php if($admin->num_rows($product_ctm_aliasResult)>0){
				$i=1;
				while($product_ctm_aliasRow=$admin->fetch($product_ctm_aliasResult)){
			?>
			<th><?php echo $product_ctm_aliasRow['product_ctm_alias']; ?> </th>	
			<?php	$i++; 
				}
				}
				mysqli_data_seek($product_ctm_aliasResult,0);
			?>
			<?php if($admin->num_rows($cost_to_convertResult)>0){
				$i=1;
				while($cost_to_convertRow=$admin->fetch($cost_to_convertResult)){
			?>
			<th><?php  ?><?php echo $cost_to_convertRow['activity_master']; ?> </th>	
			<?php	$i++; 
				}
				}
				mysqli_data_seek($cost_to_convertResult,0);
			?>
			<?php if($admin->num_rows($cost_to_sustainResult)>0){
				$i=1;
				while($cost_to_sustainRow=$admin->fetch($cost_to_sustainResult)){
			?>
			<th><?php  ?><?php echo $cost_to_sustainRow['activity_master']; ?> </th>	
			<?php	$i++; 
				}
				}
				mysqli_data_seek($cost_to_sustainResult,0);
			?>
			
			<?php if($admin->num_rows($customer_cc_aliasResult)>0){
					$i=1;
					while($customer_cc_aliasRow=$admin->fetch($customer_cc_aliasResult)){
			?>
				<th><?php echo $customer_cc_aliasRow['customer_cc_alias']; ?> </th>	
			<?php	$i++; 
					}
				}
				mysqli_data_seek($customer_cc_aliasResult,0);
			?>
			
			
			
			<?php if($admin->num_rows($cost_to_serveResult)>0){
				$i=1;
				while($cost_to_serveRow=$admin->fetch($cost_to_serveResult)){
			?>
			<th><?php  ?><?php echo $cost_to_serveRow['activity_master']; ?> </th>	
			<?php	$i++; 
				}
				}
				mysqli_data_seek($cost_to_serveResult,0);
			?>
		</tr>
	</thead>
	
	
	<tbody>
		<?php  while($produictMatrixRow=$admin->fetch($produictMatrixResult)){ 
				$productDetails=$admin->getUniqueProductMasterByName_new($produictMatrixRow['product_name'],$company_id,$timeline_id);
				$productDetails1=$admin->getUniqueProductMasterByName_new1($produictMatrixRow['product_name'],$company_id,$timeline_id);
				$customerDetails=$admin->getUniqueCustomerMasterByName_new1($produictMatrixRow['customer_name'],$company_id,$timeline_id);
				$customerDetails_new=$admin->getUniqueCustomerMasterByName_new($produictMatrixRow['customer_name'],$company_id,$timeline_id);
				$specificProductQuantity=$admin->getspecificProductQuantity_new($produictMatrixRow['product_name'],$company_id,$timeline_id);
				$specificCustomerQuantity=$admin->getspecificCustomerQuantity_new($produictMatrixRow['customer_name'],$company_id,$timeline_id);	
				$pid = isset($productDetails['id']) ? $productDetails['id'] : 0;
				$npid = isset($productDetails1['nid']) ? $productDetails1['nid'] : 0;
		?>
		<tr>
			<td><?php echo $produictMatrixRow['customer_name']; ?></td>
			<td><?php echo $produictMatrixRow['product_name']; ?></td>
			<?php 	mysqli_data_seek($ProductSegmentAliasResult,0);
					while($ProductSegmentAliasRow=$admin->fetch($ProductSegmentAliasResult)){ 
						$productSegment=$admin->getsegmentByProductIdAndSegmentId($ProductSegmentAliasRow['id'],$pid);
			?>
			<td><?php echo $productSegment['alias_value']; ?></td>
			<?php } ?>
			<?php 	mysqli_data_seek($CustomerSegmentAliasResult,0);
					while($CustomerSegmentAliasRow=$admin->fetch($CustomerSegmentAliasResult)){ 
						$customerSegment=$admin->getsegmentByCustomerIdAndSegmentId($CustomerSegmentAliasRow['id'],$customerDetails_new['id']);
			?>
			<td><?php echo $customerSegment['alias_value']; ?></td>
			<?php } ?>
			
			<?php if(isset($data) && !empty($data)){?>
			<?php if(!empty($produictMatrixRow['qty'])){ ?><td><?php echo $produictMatrixRow['qty']; ?></td><?php }else{ ?> <td><?php echo '0'; ?></td> <?php }?>
			<?php if(!empty($produictMatrixRow['domestic']) || isset($produictMatrixRow['sales_value'])){ ?><td><?php $sales_value = str_replace(",","",$produictMatrixRow['sales_value']); echo number_format($sales_value,2);  ?></td><?php }?>
			<?php if(!empty($produictMatrixRow['domestic'])){ ?><td><?php  echo $produictMatrixRow['domestic']; ?></td><?php }?>
			<?php if(!empty($produictMatrixRow['zones'])){ ?><td><?php  echo $produictMatrixRow['zones']; ?></td><?php }?>
			<?php if(!empty($produictMatrixRow['countries'])){ ?><td><?php  echo $produictMatrixRow['countries']; ?></td><?php }?>
			<?php } else{ ?>
			<td><?php echo $produictMatrixRow['qty']; ?></td>
			<td><?php $sales_value = str_replace(",","",$produictMatrixRow['sales_value']); echo number_format($sales_value,2);  ?></td>
			<td><?php  echo $produictMatrixRow['domestic']; ?></td>
			<td><?php  echo $produictMatrixRow['zones']; ?></td>
			<td><?php  echo $produictMatrixRow['countries']; ?></td>
			<?php }?>
			
			<?php //=== For CTM Calculation
				$CTMtotal=0;
				mysqli_data_seek($product_ctm_aliasResult,0);
				if($admin->num_rows($product_ctm_aliasResult)>0){
					
					while($product_ctm_aliasRow=$admin->fetch($product_ctm_aliasResult)){
						$productctmaliasValue=	$admin->getctmaliasvaluByAliasIdAndProductId_new($product_ctm_aliasRow['nid'],$npid);
						
						if($productctmaliasValue['alias_value']=="-"){
							$productctmaliasValue=0;
						}else{
							$productctmaliasValue=str_replace(',','',$productctmaliasValue['alias_value']);
						}
						
						if($specificProductQuantity['qty'] != 0 ){
							$ctmcalc = (($productctmaliasValue / $specificProductQuantity['qty']) * str_replace(',','',str_replace(',','',$produictMatrixRow['qty'])));   //calculation Pending;
							//echo ",";
						}else{
							$ctmcalc = 0;
						}
						$CTMtotal=($CTMtotal+$ctmcalc);
						
				?>
				<td><?php echo @number_format($ctmcalc,2); ?> </td>	
			<?php	$i++; 
					}
					//exit;
				}else{
					echo "<td>0</td>";
				}
				//echo "<br/>";
			?>
			<td><?php echo @number_format($CTMtotal,2); ?></td>
			
			<?php	//=== For CTC Calculation
				$CTCTotal=0;
				mysqli_data_seek($cost_to_convertResult,0); 
				if($admin->num_rows($cost_to_convertResult)>0){
				while($cost_to_convertRow=$admin->fetch($cost_to_convertResult)){
					$amt=$admin->getActivityCostByActivityAndProduct_new($produictMatrixRow,$cost_to_convertRow['activity_master'],$specificProductQuantity['qty'],$npid,$company_id,$timeline_id);
					$CTCTotal=$CTCTotal+$amt;
					
			?>
			<td><?php echo @number_format($amt,2); ?> </td>	
			<?php 	$i++; 
					
				}
				
				}else{
					echo "<td>0</td>";
				}
				?>
			<td><?php echo @number_format($CTCTotal,2); ?></td>	
			
			<?php	//=== For CTSustain Calculation
				$CTSustainTotal=0;
				mysqli_data_seek($cost_to_sustainResult,0); 
				if($admin->num_rows($cost_to_sustainResult)>0){
				while($cost_to_sustainRow=$admin->fetch($cost_to_sustainResult)){
					$amt=$admin->getActivityCostByActivityAndProduct_new($produictMatrixRow,$cost_to_sustainRow['activity_master'],$specificProductQuantity['qty'],$npid,$company_id,$timeline_id);
					$CTSustainTotal=$CTSustainTotal+$amt;
					
			?>
			<td><?php echo @number_format($amt,2); ?> </td>	
			<?php	$i++; 
			
				}
				
				}else{
					echo "<td>0</td>";
				} 
				?>
			<td><?php echo @number_format($CTSustainTotal,2);  ?></td>	
			
			<?php   //=== For CC Calculation
				$CCtotal=0;
				mysqli_data_seek($customer_cc_aliasResult,0);
				if($admin->num_rows($customer_cc_aliasResult)>0){
					while($customer_cc_aliasRow=$admin->fetch($customer_cc_aliasResult)){
						$cid = isset($customerDetails['nid'])? $customerDetails['nid']: 0;
						$customerccaliasValue=	$admin->getccaliasvaluByAliasIdAndCustomerId_new($customer_cc_aliasRow['nid'],$cid);
						
						if($customerccaliasValue['alias_value']=='-'){
							$customerccaliasValue=0;
						}else{
							$customerccaliasValue=str_replace(',','',$customerccaliasValue['alias_value']);
						}
						
						if($customerccaliasValue == 0 || $specificCustomerQuantity['qty'] == 0){
							$cccalc = '0';
						}else{
							
							$cccalc=(($customerccaliasValue/$specificCustomerQuantity['qty'])*str_replace(',','',str_replace(',','',$produictMatrixRow['qty'])));   //calculation Pending;
						}
						$CCtotal=($CCtotal+$cccalc);
				?>
				<td><?php  echo @number_format($cccalc,2);  ?> </td>	
			<?php	$i++; 
					}
					//exit;
				} 
			?>
			
			<?php	//=== For CTServe Calculation
			
				$CTServeTotal=$CCtotal;
				mysqli_data_seek($cost_to_serveResult,0);
				if($admin->num_rows($cost_to_serveResult)>0){	
					while($cost_to_serveRow=$admin->fetch($cost_to_serveResult)){
						$cid2 = !empty($customerDetails['nid']) ? $customerDetails['nid']:0;
						
						$amt=$admin->getActivityCostByActivityAndCustomer_new($produictMatrixRow,$cost_to_serveRow['activity_master'],$specificCustomerQuantity['qty'],$cid2,$company_id,$timeline_id);
						$CTServeTotal=$CTServeTotal+$amt;
					?>
					<td><?php echo @number_format($amt,2);  ?> </td>	
					<?php	 
					}
				}else{
					echo "<td>0</td>";
				}
				?>
			<td><?php  echo @number_format($CTServeTotal,2);  ?></td>	
			<td><?php  $TotalCost=$CTMtotal+$CTCTotal+$CTSustainTotal+$CTServeTotal; echo @number_format($TotalCost,2); ?></td>
			<td><?php if($produictMatrixRow['sales_value']=='-'){
						$sales_value=0;
					  }else if(empty($produictMatrixRow['sales_value'])){
						  $sales_value = 0;
					  }
					  else{
						  $sales_value= str_replace(',','',$produictMatrixRow['sales_value']);
					  }						  
					  echo number_format($sales_value-$TotalCost,2); ?></td>
		</tr>
		<?php   } ?>
		
		<?php // Free result set
			mysqli_free_result($produictMatrixResult);
			mysqli_free_result($ProductSegmentAliasResult);
			mysqli_free_result($CustomerSegmentAliasResult);
			mysqli_free_result($product_ctm_aliasResult);
			mysqli_free_result($cost_to_convertResult);
			mysqli_free_result($cost_to_sustainResult);
			mysqli_free_result($customer_cc_aliasResult);
			mysqli_free_result($cost_to_serveResult);
			
		?>
	</tbody>
</table>