<?php

class Webservices extends AppModel {	
  var $name = 'Webservices';

  var $useTable = false;
  var $accountsJson = '';
  var $accountsText = '';
  var $dailJobJson = '';
  var $dailJobText = '';
  
  /**
   *
   * @param int $propId
   * @return array accounts
   */
  public function getPropertyInfo() {  
    $getProperty = array();
	$cakeHost = $_SERVER['HTTP_HOST'];
	$cakeHostArr = explode('.', $cakeHost);
	$baseProperty = '';
	if(sizeof($cakeHostArr) > 0){
	  $baseProperty = $cakeHostArr[0];  
	  
	  $getSql = " Select * From  properties where name = 'serviceform-". $baseProperty ."' ";// where username = '". $username ."' and password = '". $password ."' 
	  $propRes = $this->query($getSql);
	  if ($propRes) {
		foreach($propRes[0]['properties'] as $key => $val ){
		  $getProperty[$key] = $val;
		}
	  }
	  
	}
	return $getProperty;	
  }
  
  /**
   *
   * @param int $propId
   * @return array accounts
   */
  public function listProperties() {  
    $listProperty = array();
	$getSql = " Select * From  properties order by id asc ";// where username = '". $username ."' and password = '". $password ."' 
	$propRes = $this->query($getSql);
	if ($propRes) {
	  for($i=0; $i<sizeof($propRes);$i++){
		$propertyJson = json_encode($propRes[$i]);
		if($i>0)
			$propertyText = $propertyText .",".$propertyJson;
		else
			$propertyText = $propertyJson;
	  }
	  $propertyText = str_replace("}},{", "},",$propertyText);
	  $propertyText = str_replace('{"properties":', "[",$propertyText); 
	  $propertyText = str_replace('"properties":', "",$propertyText); 
	  $propertyText = str_replace('}}', "}]",$propertyText); 
	  $propertyText = str_replace(':null', ':""', $propertyText);
	}	  	
	return $propertyText;	
  }
  /**
   *
   * @param int $propId
   * @return array accounts
   */
  public function listAccount($clientId, $data) {  
    $this->Webservices->belongsTo = false;
	$accounts = array();
	$getSql = " Select * From accounts where client_id='". $clientId ."' and role in ('tech', 'manager') order by id ";// where username='". $username ."' and password = '". $password ."' 
	$accRes = $this->query($getSql);
	if ($accRes) {
	  for($i=0; $i<sizeof($accRes);$i++){
		$accountsJson = json_encode($accRes[$i]);
		if($i>0)
			$accountsText = $accountsText .",".$accountsJson;
		else
			$accountsText = $accountsJson;
	  }
	  $accountsText = str_replace("}},{", "},",$accountsText);
	  $accountsText = str_replace('{"accounts":', "[",$accountsText); 
	  $accountsText = str_replace('"accounts":', "",$accountsText); 
	  $accountsText = str_replace('}}', "}]",$accountsText); 
	  $accountsText = str_replace(':null', ':""', $accountsText);
	}
   	return  $accountsText;
  } 

  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getDailyJobDataTransactions($propId) {
	$old_job_date = mktime(0, 0, 0, date("m")-3, date("d"), date("Y"));
	$job_date_monts = date('Y-m-d', $old_job_date);

	$getSql = " Select id, job_date, DailyJob_Time_Left, DailyJob_Time_Returned, DailyJob_Pre_Trip_Notes, DailyJob_On_Site_Notes, DailyJob_Temperature, DailyJob_Weather 
						From transactions where job_date IN ( 
							select distinct (t.job_date) as job_date from transactions as t where t.job_date not in ('2069-12-31 00:00:00', '1969-12-31 00:00:00', '0000-00-00 00:00:00')
							and ucase(t.job_status) in ('SALES CALL', 'PROPOSAL', 'APPROVED', 'WORK ORDER', 'IN PROGRESS' )
							and date_format(t.job_date, '%Y-%m-%d') > '". $job_date_monts ."'
						)
						and ucase(transactions.job_status) in ('SALES CALL', 'PROPOSAL', 'APPROVED', 'WORK ORDER', 'IN PROGRESS' ) 
						and transactions.id = (select max(id) from transactions as t1 where t1.job_date = transactions.job_date) ";

	$getRes = $this->runSelect($getSql, $propId, "prod");	
	if ($getRes) {
	  $jobsDataArr = array();
	  $jobsData = array();
      while($getRows = $getRes->fetch_assoc())
	    $jobsDataArr[] = $getRows;
	  
	  if(sizeof($jobsDataArr) > 0)
	  	$jobsData = array_reverse($jobsDataArr, true);		
	    $j=0;
		for($i=(sizeof($jobsData)-1); $i>=0;$i--){		  
		  $dailyJobDataJson = json_encode($jobsData[$i]);
		  if($j>0)
			$dailyJobDataText = $dailyJobDataText .",".$dailyJobDataJson;
		  else
			$dailyJobDataText = $dailyJobDataJson;
	      
		  $j++;
		}
		$dailyJobDataText = str_replace('"[', '[', $dailyJobDataText);
		$dailyJobDataText = str_replace(']"', ']', $dailyJobDataText);
		$dailyJobDataText = stripslashes($dailyJobDataText);
		$dailyJobDataText = str_replace(':"",', ':"-",', $dailyJobDataText);
		$dailyJobDataText = str_replace('""', '\""', $dailyJobDataText);
		$dailyJobDataText = str_replace('\""}', '""}', $dailyJobDataText);
		$dailyJobDataText = str_replace(':"-",', ':"",', $dailyJobDataText);
		$dailyJobDataText = str_replace(':null', ':""', $dailyJobDataText);
		$dailyJobDataText = '['.$dailyJobDataText.']';
	}
	return $dailyJobDataText;
  } 
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getFormFieldIds($propId) {
	$getSql = " select fields_id from fields_forms where forms_abbrv='dailyjob' ";   
	$getRes = $this->runSelect($getSql, $propId, "prod");	
	if ($getRes) {
	  $fieldIds = array();
      while($getRows = $getRes->fetch_assoc())
	    $fieldIds[] = $getRows['fields_id'];

	  $gSql = " Select * from fields where id in (". implode(",", $fieldIds) .") order by field_order ";
	  $fieldsRs = $this->query($gSql);
	  
	  if ($fieldsRs && $propId = 91) {
	    if ($propId == 91) {
		  $fieldsRs[]['fields'] = array('id' => 90820,'name' => 'complete','field_order' => 0,'type' => 'int',
										'length' => 0,'validation_rules' => '',
										'date_added' => '2012-12-12 06:49:44','widget' => 'hidden',
										'widget_params' => '[{"hidden_id":""},{"hidden_value":""}]',
										'on_transactions' => 1,'on_contacts' => 0,
										'is_list_field' => 0,'property_id' => 91);
		}

	    for($i=0; $i<sizeof($fieldsRs);$i++){
		  $dailJobJson = json_encode($fieldsRs[$i]);
		  if($i>0)
			$dailJobText = $dailJobText .",".$dailJobJson;
		  else
			$dailJobText = $dailJobJson;
	    }
	    $dailJobText = str_replace("}},{", "},",$dailJobText); 
	    $dailJobText = str_replace('{"fields":', "[",$dailJobText);
		$dailJobText = str_replace('"fields":', "",$dailJobText);
		$dailJobText = str_replace('"[{', "[{",$dailJobText);
		$dailJobText = str_replace('}]"', "}]",$dailJobText);
		$dailJobText = str_replace('}}', "}]",$dailJobText);		
		$dailJobText = stripslashes($dailJobText);
		$dailJobText = str_replace(':null', ':""', $dailJobText);
	  }
	}
	return $dailJobText;
  } 

  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getJobsCompCanDataTransactions($propId) {

	$getSql = " Select distinct order_num, id, job_status From transactions as `trans` where `trans`.job_status in ('COMPLETED', 'CANCELLED')  
										and `trans`.id = (select max(t.id) from transactions t where t.is_job = 1 and t.order_num = `trans`.order_num ) " ;
	$getRes = $this->runSelect($getSql, $propId, "prod"); // dev-prop-specific	
	if ($getRes) {
	  $jobsDataArr = array();
	  $jobsData = array();
      while($getRows = $getRes->fetch_assoc())
	    $jobsDataArr[] = $getRows;

	  $ordersArray = array();
	  for($j=0;$j<sizeof($jobsDataArr);$j++ ){
	    $ordersArray[] = $jobsDataArr[$j]['order_num'];
	  }
	  $ordersString = "";
	  if(sizeof($ordersArray) > 0){
	    $ordersString = implode("','", $ordersArray);
	  }

	  $csql = " Select distinct order_num From transactions where order_num not in ('". $ordersString ."') " ;
	  $cRes = $this->runSelect($csql, $propId, "prod");	 //dev-prop-specific
	  if ($cRes) {
	    while($cRows = $cRes->fetch_assoc()){
	      // Checking into database further
		  $salesPersonOrderNum = "";
		  $crewOrderNum = "";
	      $spsql = " SELECT order_num FROM `transactions` Where order_num = '". $cRows['order_num'] ."' and ( job_status in ('LEAD', 'SALES CALL', 'PROPOSAL', 'APPROVED') 
		  																										and Tree_Sales_Person != '' ) " ;
	      $spRes = $this->runSelect($spsql, $propId, "prod");	 // dev-prop-specific
	      if($spRows = $spRes->fetch_assoc()){
			 $salesPersonOrderNum = $spRows['order_num'];
		  }
		  if($salesPersonOrderNum != ""){
	        $crsql = " SELECT order_num FROM `transactions` where order_num = '". $salesPersonOrderNum ."' and ( job_status in ('WORK ORDER', 'IN PROGRESS') and technician_id != '' ) ";
	        $crRes = $this->runSelect($crsql, $propId, "prod");	// dev-prop-specific
	        if($crRows = $crRes->fetch_assoc()){
			  $crewOrderNum = $crRows['order_num'];
		    }
		  }
		  if($salesPersonOrderNum != "" && $crewOrderNum != "" ){
		    $jobsDataArr[] = array("order_num" => $salesPersonOrderNum, "id" => "", "job_status" => "");
		  }
		}
	  }

	  if(sizeof($jobsDataArr) > 0)
	  	$jobsData = array_reverse($jobsDataArr, true);		
	    $j=0;
		for($i=(sizeof($jobsData)-1); $i>=0;$i--){		  
		  $dailyJobDataJson = json_encode($jobsData[$i]);
		  if($j>0)
			$dailyJobDataText = $dailyJobDataText .",".$dailyJobDataJson;
		  else
			$dailyJobDataText = $dailyJobDataJson;
	      
		  
		  $j++;
		}
		$dailyJobDataText = str_replace('"[', '[', $dailyJobDataText);
		$dailyJobDataText = str_replace(']"', ']', $dailyJobDataText);
		$dailyJobDataText = stripslashes($dailyJobDataText);
		$dailyJobDataText = str_replace(':"",', ':"-",', $dailyJobDataText);
		$dailyJobDataText = str_replace('""', '\""', $dailyJobDataText);
		$dailyJobDataText = str_replace('\""}', '""}', $dailyJobDataText);
		$dailyJobDataText = str_replace(':"-",', ':"",', $dailyJobDataText);
		$dailyJobDataText = str_replace(':null', ':""', $dailyJobDataText);
		$dailyJobDataText = '['.$dailyJobDataText.']';
	}
	return $dailyJobDataText;
  } 
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getJobsDataTransactions($transSchema, $propId, $techId, $jobId, $start, $end) {

	$old_job_date = mktime(0, 0, 0, date("m")-3, date("d"), date("Y"));
	$job_date_monts = date('Y-m-d', $old_job_date);
	
	$totalTrans = 0;
	$subSql = '';$tsql='';$limitBy='';$dailyJobDataText='';
	
	if($jobId != '' ){  // && ($start == '' || $start == '0') && ($end == '' || $end == '0'
	  $subSql .= " and id > ". $jobId ." " ;	  
	}
	
	if($techId != '' ){ //techId	 
	  $subSql .= " and ( FIND_IN_SET ('". $techId ."', technician_id) and ucase(job_status) in ('WORK ORDER', 'IN PROGRESS' ) 
	  			  		OR 
	  					Tree_Sales_Person = '". $techId ."' and ucase(job_status) in ( 'SALES CALL', 'PROPOSAL', 'APPROVED' )  ) ";
	}
	$subSql .= " and order_num not in ( Select distinct order_num From transactions as `trans` where `trans`.job_status in ('COMPLETED', 'CANCELLED')   ) ";
										//and `trans`.id = (select max(t.id) from transactions t where t.is_job = 1 and t.order_num = `trans`.order_num )  ) ";
										
	$subSql .= " 	 and date_format(date_created, '%Y-%m-%d 00:00:00') > '". $job_date_monts ." 00:00:00' 
					and id = (select max(t1.id) from transactions t1 where t1.is_job = 1 and t1.order_num = `transactions`.order_num ) ";

	$countArr = array();
	$sql = " select count(*) as total from transactions where id is not null ". $subSql;
	$res = $this->runSelect($sql, $propId, "prod");
    if($res){
	  while($getRes = $res->fetch_assoc())
	    $totalTrans = $getRes['total'];
	}
	
	if($start != '' && $end != '' ){
	  $end = intval($end) - intval($start);
	  $limitBy = " limit ". $start.', '. $end;
	}
	
	$selectFields = '';
	if(sizeof($transSchema) > 0){
	  $selectFields = implode(',', $transSchema);
	}
	else{
	  $selectFields = '*';
	}
	
	$getSql = " Select ". $selectFields ." from transactions where id is not null ". $subSql ." order by id asc ". $limitBy;
	$getRes = $this->runSelect($getSql, $propId, "prod");	
	if ($getRes) {
	  $jobsDataArr = array();
	  $jobsData = array();
      while($getRows = $getRes->fetch_assoc()){
		
		if($getRows['Proposal_Date'] != ''){
	      $prposal_date = date("Y-m-d", strtotime($getRows['Proposal_Date']));
		  $proposal_from = $getRows['Proposal_From'];
		  $proposal_to = $getRows['Proposal_To'];

		  $getRows['Proposal_From'] = date("h:i A", strtotime($prposal_date ." ". $proposal_from));
		  $getRows['Proposal_To'] = date("h:i A", strtotime($prposal_date ." ". $proposal_to));
		}
		if($getRows['job_date'] != '' && $getRows['job_date'] != '1969-12-31 00:00:00' && $getRows['job_date'] != '2069-12-31 00:00:00' ){	      
		  $job_date = date("Y-m-d", strtotime($getRows['job_date']));
		  $job_date_from = $getRows['Job_Date_From'];
		  $job_date_to = $getRows['Job_Date_To'];
		  
		  $getRows['Job_Date_From'] = date("h:i A", strtotime($job_date ." ". $job_date_from));
		  $getRows['Job_Date_To'] = date("h:i A", strtotime($job_date ." ". $job_date_to));		  
		}
		elseif($getRows['job_date'] == '1969-12-31 00:00:00' || $getRows['job_date'] == '2069-12-31 00:00:00' ){	      
		  $job_date = "";
		  $job_date_from = "";
		  $job_date_to = "";		  
		  $getRows['job_date'] = "";
		  $getRows['Job_Date_From'] = "";
		  $getRows['Job_Date_To'] = "";
		}		
		$getRows['DailyJob_Time_Left'] = date('h:i A', strtotime($getRows['DailyJob_Time_Left']));
		$getRows['DailyJob_Time_Returned'] = date('h:i A', strtotime($getRows['DailyJob_Time_Returned']));
		
		$getRows['Client_Signature'] = "";		
		$getRows['Tree_Entered_Signature'] = "";
		$getRows['Tree_Accepted_Signature'] = "";
		$getRows['Tree_SG_Hand_Drawing'] = "";
		$getRows['Tree_PHC_Hand_Drawing'] = "";		
		$getRows['Tree1_HandDrawing'] = "";
		$getRows['Tree3_HandDrawing'] = "";
		$getRows['Tree4_HandDrawing'] = "";
		$getRows['Tree5_HandDrawing'] = "";
		$getRows['Tree6_HandDrawing'] = "";
		$getRows['Tree7_HandDrawing'] = "";
		$getRows['Tree2_HandDrawing'] = "";
	    $jobsDataArr[] = $getRows;
	  }

	  if(sizeof($jobsDataArr) > 0)
	  	$jobsData = array_reverse($jobsDataArr, true);		
	    $j=0;
		for($i=(sizeof($jobsData)-1); $i>=0;$i--){		  
		  $dailyJobDataJson = json_encode($jobsData[$i]);
		  if($j>0)
			$dailyJobDataText = $dailyJobDataText .",".$dailyJobDataJson;
		  else
			$dailyJobDataText = $dailyJobDataJson;
	      
		  
		  $j++;
		}
		$dailyJobDataText = str_replace('"[', '[', $dailyJobDataText);
		$dailyJobDataText = str_replace(']"', ']', $dailyJobDataText);
		$dailyJobDataText = stripslashes($dailyJobDataText);
		$dailyJobDataText = str_replace(':"",', ':"-",', $dailyJobDataText);
		$dailyJobDataText = str_replace('""', '\""', $dailyJobDataText);
		$dailyJobDataText = str_replace('\""}', '""}', $dailyJobDataText);
		$dailyJobDataText = str_replace(':"-",', ':"",', $dailyJobDataText);
		$dailyJobDataText = str_replace(':null', ':""', $dailyJobDataText);
		$dailyJobDataText = '['.$dailyJobDataText.']';
	}
	return $totalTrans."||".$dailyJobDataText;
  } 
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getCustomersData($propId) {
	$customers = array();
	$cstSql = " select * from customers order by id asc ";
	$cstRes = $this->runSelect($cstSql, $propId, "prod");	

	if($cstRes){
	  while($cstRows = $cstRes->fetch_assoc())
	    $customers[] = $cstRows;	  

	  if(sizeof($customers) > 0){
	    for($i=0; $i<sizeof($customers);$i++){
		  $custJson = json_encode($customers[$i]);
		  if($i>0)
			$custDetailText = $custDetailText .",".$custJson;
		  else
			$custDetailText = $custJson;
	    }
		$custDetailText = '['.$custDetailText.']';
		$custDetailText = str_replace(':null', ':""', $custDetailText);
	  }
	}
	return $custDetailText;
  }

  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getSalesPersonsData($propId, $clientId) {
	$salePersons = array();
	$spSql = " select id, firstname, lastname from accounts where role = 'manager' and active = 1 and client_id = '". $clientId ."' order by id asc ";
	$spRes = $this->runSelect($spSql, $propId, "prod");	

	if($spRes){
	  while($spRows = $spRes->fetch_assoc())
	    $salePersons[] = $spRows;	  

	  if(sizeof($salePersons) > 0){
	    for($i=0; $i<sizeof($salePersons);$i++){
		  $salePersonsJson = json_encode($salePersons[$i]);
		  if($i>0)
			$salePersonsText = $salePersonsText .",".$salePersonsJson;
		  else
			$salePersonsText = $salePersonsJson;
	    }
		$salePersonsText = '['.$salePersonsText.']';
		$salePersonsText = str_replace(':null', ':""', $salePersonsText);
	  }
	}
	return $salePersonsText;
  }

  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getServicesData($propId) {
	$services = array();
	$srvSql = " select * from services order by service asc ";
	$srvRes = $this->runSelect($srvSql, $propId, "prod");	

	if($srvRes){
	  while($srvRows = $srvRes->fetch_assoc())
	    $services[] = $srvRows;	  
		
	  if(sizeof($services) > 0){
	    for($i=0; $i<sizeof($services);$i++){
		  $servicesJson = json_encode($services[$i]);
		  if($i>0)
			$servicesText = $servicesText .",".$servicesJson;
		  else
			$servicesText = $servicesJson;
	    }
		$servicesText = '['.$servicesText.']';
		$servicesText = str_replace(':null', ':""', $servicesText);
	  }
	}
	return $servicesText;
  }
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getLocalizationData($propId) {
	$localization = array();
	$localSql = " select * from localizations order by id asc ";
	$locRes = $this->runSelect($localSql, $propId, "prod");	

	if($locRes){
	  while($locRows = $locRes->fetch_assoc())
	    $localization[] = $locRows;	  

	  if(sizeof($localization) > 0){
	    for($i=0; $i<sizeof($localization);$i++){
		  $localJson = json_encode($localization[$i]);
		  if($i>0)
			$localText = $localText .",".$localJson;
		  else
			$localText = $localJson;
	    }
		$localText = '['.$localText.']';
		$localText = str_replace(':null', ':""', $localText);
	  }
	}
	return $localText;
  }
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getAssetsData($propId) {
	$assets = array();
	$asetSql = " select * from assets order by id asc ";
	$asetRes = $this->runSelect($asetSql, $propId, "prod");	

	if($asetRes){
	  while($asetRows = $asetRes->fetch_assoc())
	    $assets[] = $asetRows;	  

	  if(sizeof($assets) > 0){
	    for($i=0; $i<sizeof($assets);$i++){
		  $asetJson = json_encode($assets[$i]);
		  if($i>0)
			$asetText = $asetText .",".$asetJson;
		  else
			$asetText = $asetJson;
	    }
		$asetText = '['.$asetText.']';
		$asetText = str_replace(':null', ':""', $asetText);
	  }
	}
	return $asetText;
  }
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getTransactionServicesData($propId, $techId, $autoServiceId, $serviceId) {	

	$old_job_date = mktime(0, 0, 0, date("m")-3, date("d"), date("Y"));
	$job_date_monts = date('Y-m-d', $old_job_date);
  
    $total = 0;
	$tranSql = "";
	$trans = array();
	$tranServicesData = array();
	
	if($techId != '' ){ //techId	 
	  $countArr = array();
	  $tSql = " Select id from transactions where (  FIND_IN_SET ('". $techId ."', technician_id) and ucase(job_status) in ('WORK ORDER', 'IN PROGRESS' ) 
	  			  								OR 
	  												Tree_Sales_Person = '". $techId ."' and ucase(job_status) in ( 'SALES CALL', 'PROPOSAL', 'APPROVED' )  ) 
												and date_format(date_created, '%Y-%m-%d 00:00:00') > '". $job_date_monts ." 00:00:00' 
												and id = (select max(t.id) from transactions t where t.is_job = 1 and t.order_num = `transactions`.order_num ) 
												";
	  $tRes = $this->runSelect($tSql, $propId, "prod");	
      if($tRes){
	    while($tRows = $tRes->fetch_assoc())
	      $trans[] = $tRows['id'];
	  
	    if(sizeof($trans) > 0)
	  	  $tranSql .= " and transaction_id in ( ". implode(',', $trans) . " )";
	  }
	}
	
	if($serviceId != '' && $serviceId != '0' ){ // serviceId
	}
	
	$totalSql = " select count(*) as total from transactionservices where treeNo is not Null ". $tranSql ." ";
	$totalRes = $this->runSelect($totalSql, $propId, "prod");	
    if($totalRes){
	  while($totalRows = $totalRes->fetch_assoc())
	    $total = $totalRows['total'];
	}
	
	if($autoServiceId != '' ){ //autoServiceId	 
	  $tranSql .= " and id > ". $autoServiceId ." " ;
	}

	$srvSql = " select * from transactionservices where treeNo is not Null ". $tranSql ." ";
	$srvRes = $this->runSelect($srvSql, $propId, "prod");	
    if($srvRes){
	  while($srvRows = $srvRes->fetch_assoc())
	    $tranServicesData[] = $srvRows;
	}
	
	if(sizeof($tranServicesData) > 0){
	  for($i=0; $i<sizeof($tranServicesData);$i++){
	    $tranServicesJson = json_encode($tranServicesData[$i]);
		if($i>0)
		  $tranServicesText = $tranServicesText .",".$tranServicesJson;
		else
		  $tranServicesText = $tranServicesJson;
	  }
	  $tranServicesText = '['.$tranServicesText.']';
	  $tranServicesText = str_replace(':null', ':""', $tranServicesText);
	}
	return $total."||".$tranServicesText;
  }
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getpropertySettingsData($propId) {
	$settings = array();
	$setSql = " select * from settings order by id asc ";
	$setRes = $this->runSelect($setSql, $propId, "prod");	

	if($setRes){
	  while($setRows = $setRes->fetch_assoc())
	    $settings[] = $setRows;	  

	  if(sizeof($settings) > 0){
	    for($i=0; $i<sizeof($settings);$i++){
		  $setJson = json_encode($settings[$i]);
		  if($i>0)
			$setText = $setText .",".$setJson;
		  else
			$setText = $setJson;
	    }
		$setText = '['.$setText.']';
		$setText = str_replace(':null', ':""', $setText);
	  }
	}
	return $setText;
  }    
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function  FxnGetSpecifiedColumnSignatureJSON($propId, $signatureColumn, $syncTableName, $order_number){ 
    $treeSignature = "";
	$sigsql = " select max(id), ". $signatureColumn ." as jobs_signature from ". $syncTableName ." where order_num = '". $order_number ."' ";
	$sigRes = $this->runSelect($sigsql, $propId, "prod");
		
	//if($sigRes){
	  if($sigRows = $sigRes->fetch_assoc()){
	  	$treeSignature = $sigRows['jobs_signature'];
	  }	  
	//}
    return $treeSignature;
  }
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function addPropertySyncData($propId, $syncTableName, $syncDataArray, $syncTreeImage1, $syncTreeImage2, $syncTreeImage3, $syncTreeImage4, $syncTreeImage5, $syncTreeImage6, $syncTreeImage7, $syncTreeEnteredPath, $syncTreeAcceptedPath, $syncTreeSignaturePath1, $syncTreeSignaturePath2, $syncTreeSignaturePath3, $syncTreeSignaturePath4, $syncTreeSignaturePath5, $syncTreeSignaturePath6, $syncTreeSignaturePath7) {
	
	if(eregi('customers', $syncTableName)){
	  $customerId = array();
	  if(is_array($syncDataArray)){
	    foreach($syncDataArray as $key => $val)
	    {
	      if(is_array($val)){
		    $keyColumns = array();
		    foreach( $val as $column => $value ){
		      $keyColumns[] = $column ." = '". addslashes($value) ."' ";
		    }
	      }

		  if(sizeof($keyColumns) > 0){	      
	        $custId = "";
			$gsql = " Select id from ". $syncTableName ." where customer_number = '". $val['customer_number'] ."' ";
			$cstRes = $this->runSelect($gsql, $propId, "prod");
			if($cstRes){
	  		  if($cstRows = $cstRes->fetch_assoc()){
	    		$custId = $cstRows['id'];
			  }
			}		    
						
			if($custId != ""){
			  $usql = " Update ". $syncTableName ." Set ". implode(', ', $keyColumns) ." where id = '". $custId ."' ";
			  $this->runSelect($usql, $propId, "prod");
			}
			else{			
			  $sql = " insert into ". $syncTableName ." set ". implode(', ', $keyColumns) ." ";
	          $this->runSelect($sql, $propId, "prod");		
			}
			
			$cstSql = " Select id from ". $syncTableName ." where id = '". $val['id'] ."' ";			
			$cstRes = $this->runSelect($cstSql, $propId, "prod");
			
			if($cstRes){
	  		  if($cstRows = $cstRes->fetch_assoc()){
	    		$customerId[] = $cstRows['id'];
			  }
			}
		  }
	    }

		if(sizeof($customerId) > 0){
		  echo implode(",",$customerId);
		}
		else{
		  echo "Error";
		}
      }
	  else{
	    echo "Error";
	  }     
	}
	if(eregi('transactions', $syncTableName)){

	  $transId = array();	  	  
	  if(is_array($syncDataArray)){
	    $job_date = '';$job_date_from = ''; $job_date_to = '';
		foreach($syncDataArray as $key => $val)
	    {
	      if(is_array($val)){
		    $keyColumns = array();
		    $job_date = '';$job_date_from = ''; $job_date_to = '';
			foreach( $val as $column => $value ){
		      if( $column != 'id' ){
			    if( strtolower($column) == "tree_cleanup"){
			      $value = str_replace('14u00e2u20acu009du00e2u20acu201c16u00e2u20acu009d',  html_entity_decode("14â&#8364;&#157;â&#8364;&#8220;16â&#8364;&#157;", ENT_COMPAT, 'UTF-8'), $value);
			    }
				if( stristr($value, "'" ) == true ){
				  $keyColumns[] = $column ." = '". addslashes($value) ."' ";
				}
				else{
				  $treeSignatureEntAcpt = "";
				  
				  // Case 1 :  When SIgnature JSON Data posted then update only
				  // Case 2 :  where [] = shows that When IOS, clear the signature, but is sy pehly signature DB main exists krty thay 
				  // 			  		  is waqt jb clear kr k post kry ga to "[]" aaye ga, ab signature ko db main null update krna hai. i.e. tree_entered_signature = '' 
				  // 
				  // Case 3 :  for [null] =  In IOS, a job Edit with Signature exists in a job, and not modify any signature, that means IOS did not send any data along with Post, 
				  //  					   but send [null]. 
				  // 					       At this point, db entring new entry into DB, at that point you've to get its actual signature from (WEB / IOS) and update into that Field.
				  //					       Tree_Entered_Signature = [Get last actual Signature from its previous record, then update only that signature]
				  
				  //case 1 & 2
				  if( ($column == "Tree_Entered_Signature" && $value != "[null]" && trim($value) != "[]" ) || ($column == "Tree_Accepted_Signature" && $value != "[null]" && trim($value) != "[]") ||  ($column == "Tree1_HandDrawing" && $value != "[null]" && trim($value) != "[]" ) || ($column == "Tree2_HandDrawing" && $value != "[null]" && trim($value) != "[]") ||  ($column == "Tree3_HandDrawing" && $value != "[null]" && trim($value) != "[]" ) || ($column == "Tree4_HandDrawing" && $value != "[null]" && trim($value) != "[]") || ($column == "Tree5_HandDrawing" && $value != "[null]" && trim($value) != "[]" ) || ($column == "Tree6_HandDrawing" && $value != "[null]" && trim($value) != "[]") ||  ($column == "Tree7_HandDrawing" && $value != "[null]" && trim($value) != "[]" ) ){
				    $keyColumns[] = $column ." = '". $value ."' ";
				  }
				  elseif( ($column == "Tree_Entered_Signature" && trim($value) == "[]" ) || ($column == "Tree_Accepted_Signature" && trim($value) == "[]") ||  ($column == "Tree1_HandDrawing" && trim($value) == "[]" ) || ($column == "Tree2_HandDrawing" && trim($value) == "[]") ||  ($column == "Tree3_HandDrawing" && trim($value) == "[]" ) || ($column == "Tree4_HandDrawing" && trim($value) == "[]") || ($column == "Tree5_HandDrawing" && trim($value) == "[]" ) || ($column == "Tree6_HandDrawing" && trim($value) == "[]") ||  ($column == "Tree7_HandDrawing" && trim($value) == "[]" ) ){
				    $keyColumns[] = $column ." = '' ";
				  }
				  // case 3
				  elseif( ($column == "Tree_Entered_Signature" && $value == "[null]" ) || ($column == "Tree_Accepted_Signature" && $value == "[null]" ) ||  ($column == "Tree1_HandDrawing" && $value == "[null]" ) || ($column == "Tree2_HandDrawing" && $value == "[null]" ) ||  ($column == "Tree3_HandDrawing" && $value == "[null]" ) || ($column == "Tree4_HandDrawing" && $value == "[null]" ) || ($column == "Tree5_HandDrawing" && $value == "[null]" ) || ($column == "Tree6_HandDrawing" && $value == "[null]" ) ||  
				  ($column == "Tree7_HandDrawing" && $value == "[null]" ) ){

				    $sigssql = " select max(id) as id from ". $syncTableName ." where order_num = '". $val['order_num'] ."' ";
				    $sigsRes = $this->runSelect($sigssql, $propId, "prod");
					
					$treeSignatureId = "";					
				    if($sigsRows = $sigsRes->fetch_assoc()){
					  $treeSignatureId = $sigsRows['id'];
					}
					if($treeSignatureId != ""){
						$sisql = " select ". $column ." as tree_hand_drawing_signatures from ". $syncTableName ." where id = ". $treeSignatureId ." ";
						$siRes = $this->runSelect($sisql, $propId, "prod");
								
						if($siRows = $siRes->fetch_assoc()){
						  $treeSignatureEntAcpt = $siRows['tree_hand_drawing_signatures'];
						}
				    }
					$keyColumns[] = $column ." = '". $treeSignatureEntAcpt ."' ";
				  }				  
				  else{
					if( strtolower($column) == "job_date" && $value == ""  ) { 
					  $keyColumns[] = " job_date = '1969-12-31 00:00:00' "; // , Job_Date_From = '', Job_Date_To = ''
					}
					elseif( strtolower($column) == "job_date" && $value != "" ) {
					  $job_date = date("Y-m-d", strtotime($value));
					  $keyColumns[] = $column ." = '". $value ."' "; // date_format("Y-m-d h:i:s", strtotime(
					}
					elseif( strtolower($column) == "job_date_from" && $value != "" ) {
					  $job_date_from = date("H:i", strtotime($job_date ." ". $value));
					  $keyColumns[] = $column ." = '". $job_date_from ."' ";
					}
					elseif( strtolower($column) == "job_date_to" && $value != "" ) {
					  $job_date_to = date("H:i", strtotime($job_date ." ". $value));
					  $keyColumns[] = $column ." = '". $job_date_to ."' ";
					}
					elseif( strtolower($column) != "job_date_from" && strtolower($column) != "job_date_to" )
					  $keyColumns[] = $column ." = '". $value ."' "; //html_entity_decode($value,ENT_QUOTES)
				  }
				}
			  }
		    }
	      }
	      else{
			if( strtolower($key) == "tree_cleanup"){
			  $value = htmlentities(str_replace('14u00e2u20acu009du00e2u20acu201c16u00e2u20acu009d', html_entity_decode("14â&#8364;&#157;â&#8364;&#8220;16â&#8364;&#157;", ENT_COMPAT, 'UTF-8'), $val));
			  //$value = json_decode($value);
			}
			if( $key != 'id' ){
			  if( stristr($val, "'" ) == true ){
	            $keyColumns[] = $key ." = '". addslashes($val) ."' ";
			  }
			  else{
				$treeSignatureEntAcpt1 = "";
				  
				// Case 1 :  When SIgnature JSON Data posted then update only
				// Case 2 :  where [] = shows that When IOS, clear the signature, but is sy pehly signature DB main exists krty thay 
				// 			  		  is waqt jb clear kr k post kry ga to "[]" aaye ga, ab signature ko db main null update krna hai. i.e. tree_entered_signature = '' 
				// 
				// Case 3 :  for [null] =  In IOS, a job Edit with Signature exists in a job, and not modify any signature, that means IOS did not send any data along with Post, 
				//  					   but send [null]. 
				// 					       At this point, db entring new entry into DB, at that point you've to get its actual signature from (WEB / IOS) and update into that Field.
				//					       Tree_Entered_Signature = [Get last actual Signature from its previous record, then update only that signature]
				  
				  //case 1 & 2
				if( ($key == "Tree_Entered_Signature" && $val != "[null]" && trim($val) != "[]" ) || ($key == "Tree_Accepted_Signature" && $val != "[null]" && trim($val) != "[]") ||  ($key == "Tree1_HandDrawing" && $val != "[null]" && trim($val) != "[]" ) || ($key == "Tree2_HandDrawing" && $val != "[null]" && trim($val) != "[]") ||  ($key == "Tree3_HandDrawing" && $val != "[null]" && trim($val) != "[]" ) || ($key == "Tree4_HandDrawing" && $val != "[null]" && trim($val) != "[]") || ($key == "Tree5_HandDrawing" && $val != "[null]" && trim($val) != "[]" ) || ($key == "Tree6_HandDrawing" && $val != "[null]" && trim($val) != "[]") ||  ($key == "Tree7_HandDrawing" && $val != "[null]" && trim($val) != "[]" ) ){
				  $keyColumns[] = $key ." = '". $val ."' ";
				}
				elseif( ($key == "Tree_Entered_Signature" && trim($val) == "[]" ) || ($key == "Tree_Accepted_Signature" && trim($val) == "[]") ||  ($key == "Tree1_HandDrawing" && trim($val) == "[]" ) || ($key == "Tree2_HandDrawing" && trim($val) == "[]") ||  ($key == "Tree3_HandDrawing" && trim($val) == "[]" ) || ($key == "Tree4_HandDrawing" && trim($val) == "[]") || ($key == "Tree5_HandDrawing" && trim($val) == "[]" ) || ($key == "Tree6_HandDrawing" && trim($val) == "[]") ||  ($key == "Tree7_HandDrawing" && trim($val) == "[]" ) ){
				  $keyColumns[] = $key ." = '' ";
				}
				// case 3
				elseif( ($key == "Tree_Entered_Signature" && $val == "[null]" ) || ($key == "Tree_Accepted_Signature" && $val == "[null]" ) ||  ($key == "Tree1_HandDrawing" && $val == "[null]" ) || ($key == "Tree2_HandDrawing" && $val == "[null]" ) ||  ($key == "Tree3_HandDrawing" && $val == "[null]" ) || ($key == "Tree4_HandDrawing" && $val == "[null]" ) || ($key == "Tree5_HandDrawing" && $val == "[null]" ) || ($key == "Tree6_HandDrawing" && $val == "[null]" ) ||  
				  ($key == "Tree7_HandDrawing" && $val == "[null]" ) ){
				  
//				    $sigssql = " select max(id) as id, ". $key ." from ". $syncTableName ." where order_num = '". $val['order_num'] ."' ";
//				    $sigsRes = $this->runSelect($sigssql, $propId, "prod");
//							
//				    if($sigsRows = $sigsRes->fetch_assoc()){
//				      //echo "Data: <pre>";
//					  //print_r($sigsRows);
//					  //echo "</pre>";
//					  $treeSignatureId = $sigsRows['id'];
//					  $treeSignatureEntAcpt1 = $sigsRows[$key];
//					}
					
					$sigssql = " select max(id) as id from ". $syncTableName ." where order_num = '". $val['order_num'] ."' ";
				    $sigsRes = $this->runSelect($sigssql, $propId, "prod");
					
					$treeSignatureId = "";					
				    if($sigsRows = $sigsRes->fetch_assoc()){
					  $treeSignatureId = $sigsRows['id'];
					}
					if($treeSignatureId != ""){
						$sisql = " select ". $key ." as tree_hand_drawing_signatures from ". $syncTableName ." where id = ". $treeSignatureId ." ";
						$siRes = $this->runSelect($sisql, $propId, "prod");
								
						if($siRows = $siRes->fetch_assoc()){
						  $treeSignatureEntAcpt = $siRows['tree_hand_drawing_signatures'];
						}
				    }
				    $keyColumns[] = $key ." = '". $treeSignatureEntAcpt ."' ";				  
				}
				else{
				  if( strtolower($key) == "job_date" && $val == ""  ) { 
					  $keyColumns[] = " job_date = '1969-12-31 00:00:00' "; // , Job_Date_From = '', Job_Date_To = ''
					}
					elseif( strtolower($key) == "job_date" && $val != "" ) {
					  $job_date = date("Y-m-d", strtotime($val));
					  $keyColumns[] = $key ." = '". $val ."' "; // date_format("Y-m-d h:i:s", strtotime(
					}
					elseif( strtolower($key) == "job_date_from" && $val != "" ) {
					  $job_date_from = date("H:i", strtotime($job_date ." ". $val));
					  $keyColumns[] = $key ." = '". $job_date_from ."' ";
					}
					elseif( strtolower($key) == "job_date_to" && $val != "" ) {
					  $job_date_to = date("H:i", strtotime($job_date ." ". $val));
					  $keyColumns[] = $key ." = '". $job_date_to ."' ";
					}
					elseif( strtolower($key) != "job_date_from" && strtolower($key) != "job_date_to" )
				  		$keyColumns[] = $key ." = '". $val ."' ";
				  
				  //if( ($key == "job_date" ) && ( date_format("Y-m-d h:i:s", strtotime($val)) == "2069-12-31 00:00:00" || 
//													  date_format("Y-m-d h:i:s", strtotime($val)) == "1969-12-31 00:00:00" || 
//													  date_format("Y-m-d h:i:s", strtotime($val)) == "0000-00-00 00:00:00" || $val == "" ) ) { 
//					  $keyColumns[] = " job_date = '1969-12-31 00:00:00', Job_Date_From = '', Job_Date_To = '' ";
//				  }
//				  elseif( ($key == "job_date" && date_format("Y-m-d h:i:s", strtotime($val)) != "2069-12-31 00:00:00" && 
//														date_format("Y-m-d h:i:s", strtotime($val)) != "1969-12-31 00:00:00" && 
//														date_format("Y-m-d h:i:s", strtotime($val)) != "0000-00-00 00:00:00" ) 
//					  		  || $key == "Job_Date_From" || $key == "Job_Date_To" ) {
//				    $keyColumns[] = $key ." = '". $val ."' "; // date_format("Y-m-d h:i:s", strtotime(
//				  }
//				  elseif( $key != "Job_Date_From" && $key != "Job_Date_To" )
//				    $keyColumns[] = $key ." = '". $val ."' "; //html_entity_decode($value,ENT_QUOTES)				  
				}
			  }
			}
	      }
		  if(sizeof($keyColumns) > 0){    
	        $sql = " insert into ". $syncTableName ." set ". implode(', ', $keyColumns) ." ";
	        $this->runSelect($sql, $propId, "prod");
			
			$maxsql = " select max(id) as id from ". $syncTableName ." where order_num = '". $val['order_num'] ."' ";

			$maxRes = $this->runSelect($maxsql, $propId, "prod");
			
			if($maxRes){
	  		  if($maxRows = $maxRes->fetch_assoc()){
	    		$transId[] = $val['id']."_".$maxRows['id'];
			  }
			}			
		  }
		  
		  		  
	    }
		if(sizeof($transId) > 0){
		  echo implode(",",$transId);
		}
		else{
		  echo "Error";
		}
	  }
	  $image_names = array();
	  $root_path = "/var/www/serviceform_sites/serviceform-sandbox/app/webroot";
	  $uploaded_dir = "/uploaded_assets/";
	  if($syncTreeImage1['name'] != "" && intval($syncTreeImage1['error']) == 0 && $syncTreeImage1 != "null" )
	  {
			// manipulating image name to get job Id
			$tree_image_1 = $syncTreeImage1['name'];
			$tree_image_1_arr = explode("_", $tree_image_1);
			$lastIndex = sizeof($tree_image_1_arr) - 1;
			$tree_image_1_id_arr = explode(".", $tree_image_1_arr[$lastIndex]);
			$tree_image_1_id = $tree_image_1_id_arr[0];
			
			// uploading image
			$upload_image1 =  $uploaded_dir . $tree_image_1;			
			move_uploaded_file($syncTreeImage1['tmp_name'],  $root_path.$upload_image1);
			
			if(file_exists( $root_path.$upload_image1 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree_Image_1 = 'uploaded_assets/". $tree_image_1 ."' where id = '". $tree_image_1_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $image_names[] = $tree_image_1;
			}
	    }
		if($syncTreeImage2['name'] != "" && intval($syncTreeImage2['error']) == 0 && $syncTreeImage2 != "null")
	    {
			// manipulating image name to get job Id
			$tree_image_2 = $syncTreeImage2['name'];
			$tree_image_2_arr = explode("_", $tree_image_2);
			$lastIndex = sizeof($tree_image_2_arr) - 1;
			$tree_image_2_id_arr = explode(".", $tree_image_2_arr[$lastIndex]);
			$tree_image_2_id = $tree_image_2_id_arr[0];
			
			// uploading image
			$upload_image2 =  $uploaded_dir . $tree_image_2;
			move_uploaded_file($syncTreeImage2['tmp_name'],  $root_path.$upload_image2);
			
			if(file_exists( $root_path.$upload_image2 )){
			  
			  // updating into database
			  $imgsql2 = " Update ". $syncTableName ." Set Tree_Image_2 = 'uploaded_assets/". $tree_image_2 ."' where id = '". $tree_image_2_id ."' ";	
			  //echo 'imgsql2: '. $imgsql2 .'<br />';
			  $this->runSelect($imgsql2, $propId, "prod");
			  $image_names[] = $tree_image_2;
			}
	    }
		if($syncTreeImage3['name'] != "" && intval($syncTreeImage3['error']) == 0 && $syncTreeImage3 != "null")
	    {
			// manipulating image name to get job Id
			$tree_image_3 = $syncTreeImage3['name'];
			$tree_image_3_arr = explode("_", $tree_image_3);
			$lastIndex = sizeof($tree_image_3_arr) - 1;
			$tree_image_3_id_arr = explode(".", $tree_image_3_arr[$lastIndex]);
			$tree_image_3_id = $tree_image_3_id_arr[0];
			
			// uploading image
			$upload_image3 =  $uploaded_dir . $tree_image_3;
			move_uploaded_file($syncTreeImage3['tmp_name'],  $root_path.$upload_image3);
			
			if(file_exists( $root_path.$upload_image3 )){
			  //echo "Job Id: ". $tree_image_3_id ." : Image Name: ". $upload_image3 ."<br />";
			  
			  // updating into database
			  $imgsql3 = " Update ". $syncTableName ." Set Tree_Image_3 = 'uploaded_assets/". $tree_image_3 ."' where id = '". $tree_image_3_id ."' ";	
			  $this->runSelect($imgsql3, $propId, "prod");
			  $image_names[] = $tree_image_3;
			}			
	    }
		if($syncTreeImage4['name'] != "" && intval($syncTreeImage4['error']) == 0 && $syncTreeImage4 != "null")
	    {
			// manipulating image name to get job Id
			$tree_image_4 = $syncTreeImage4['name'];
			$tree_image_4_arr = explode("_", $tree_image_4);
			$lastIndex = sizeof($tree_image_4_arr) - 1;
			$tree_image_4_id_arr = explode(".", $tree_image_4_arr[$lastIndex]);
			$tree_image_4_id = $tree_image_4_id_arr[0];
			
			// uploading image
			$upload_image4 =  $uploaded_dir . $tree_image_4;
			move_uploaded_file($syncTreeImage4['tmp_name'],  $root_path.$upload_image4);
			
			if(file_exists( $root_path.$upload_image4 )){
			  
			  // updating into database
			  $imgsql4 = " Update ". $syncTableName ." Set Tree_Image_4 = 'uploaded_assets/". $tree_image_4 ."' where id = '". $tree_image_4_id ."' ";	
			  //echo 'imgsql4: '. $imgsql4 .'<br />';
			  $this->runSelect($imgsql4, $propId, "prod");	  
			  $image_names[] = $tree_image_4;
			}			
	    }
		if($syncTreeImage5['name'] != "" && intval($syncTreeImage5['error']) == 0 && $syncTreeImage5 != "null")
	    {
			// manipulating image name to get job Id
			$tree_image_5 = $syncTreeImage5['name'];
			$tree_image_5_arr = explode("_", $tree_image_5);
			$lastIndex = sizeof($tree_image_5_arr) - 1;
			$tree_image_5_id_arr = explode(".", $tree_image_5_arr[$lastIndex]);
			$tree_image_5_id = $tree_image_5_id_arr[0];
			
			// uploading image
			$upload_image5 =  $uploaded_dir . $tree_image_5;
			move_uploaded_file($syncTreeImage5['tmp_name'],  $root_path.$upload_image5);
			
			if(file_exists( $root_path.$upload_image5 )){
			  
			  // updating into database
			  $imgsql5 = " Update ". $syncTableName ." Set Tree_Image_5 = 'uploaded_assets/". $tree_image_5 ."' where id = '". $tree_image_5_id ."' ";	
			  $this->runSelect($imgsql5, $propId, "prod");
			  $image_names[] = $tree_image_5;
			}			
	    }
		if($syncTreeImage6['name'] != "" && intval($syncTreeImage6['error']) == 0 && $syncTreeImage6 != "null")
	    {
			// manipulating image name to get job Id
			$tree_image_6 = $syncTreeImage6['name'];
			$tree_image_6_arr = explode("_", $tree_image_6);
			$lastIndex = sizeof($tree_image_6_arr) - 1;
			$tree_image_6_id_arr = explode(".", $tree_image_6_arr[$lastIndex]);
			$tree_image_6_id = $tree_image_6_id_arr[0];
			
			// uploading image
			$upload_image6 =  $uploaded_dir . $tree_image_6;
			move_uploaded_file($syncTreeImage6['tmp_name'],  $root_path.$upload_image6);
			
			if(file_exists( $root_path.$upload_image6 )){			  
			  // updating into database
			  $imgsql6 = " Update ". $syncTableName ." Set Tree_Image_6 = 'uploaded_assets/". $tree_image_6 ."' where id = '". $tree_image_6_id ."' ";	
			  $this->runSelect($imgsql6, $propId, "prod");		  
			  $image_names[] = $tree_image_6;
			}			
	    }
		if($syncTreeImage7['name'] != "" && intval($syncTreeImage7['error']) == 0 && $syncTreeImage7 != "null")
	    {
			// manipulating image name to get job Id
			$tree_image_7 = $syncTreeImage7['name'];
			$tree_image_7_arr = explode("_", $tree_image_7);
			$lastIndex = sizeof($tree_image_7_arr) - 1;
			$tree_image_7_id_arr = explode(".", $tree_image_7_arr[$lastIndex]);
			$tree_image_7_id = $tree_image_7_id_arr[0];
			
			// uploading image
			$upload_image7 =  $uploaded_dir . $tree_image_7;
			move_uploaded_file($syncTreeImage7['tmp_name'],  $root_path.$upload_image7);
			
			if(file_exists( $root_path.$upload_image7 )){			  
			  // updating into database
			  $imgsql7 = " Update ". $syncTableName ." Set Tree_Image_7 = 'uploaded_assets/". $tree_image_7 ."' where id = '". $tree_image_7_id ."' ";	
			  $this->runSelect($imgsql7, $propId, "prod");
			  $image_names[] = $tree_image_7;
			}			
	    }
		if(sizeof($image_names) > 0){
		  echo implode(",", $image_names);
		}
		
		// Tree HandDrawing Images Path - Tree 1
		$treeHandDrawingPath = array();
	    //$root_path = "/var/www/tpmcms/devsites/serviceform-sandbox/app/webroot";
		$root_path = "/var/www/serviceform_sites/serviceform-sandbox/app/webroot";
	    $uploaded_dir = "/img/scribbles/";
	    if($syncTreeSignaturePath1['name'] != "" && intval($syncTreeSignaturePath1['error']) == 0 && $syncTreeSignaturePath1 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image1 = $syncTreeSignaturePath1['name'];
			$tree_handdrawing_image1_arr = explode("_", $tree_handdrawing_image1);
			$lastIndex = sizeof($tree_handdrawing_image1_arr) - 1;
			$tree_handdrawing_image1_id_arr = explode(".", $tree_handdrawing_image1_arr[$lastIndex]);
			$tree_handdrawing_image1_id = $tree_handdrawing_image1_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image1 =  $uploaded_dir . $tree_handdrawing_image1;			
			move_uploaded_file($syncTreeSignaturePath1['tmp_name'],  $root_path.$upload_tree_handdrawing_image1);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image1 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree1_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image1 ."' where id = '". $tree_handdrawing_image1_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image1;
			}
	    }
		// Tree HandDrawing Images Path - Tree 2
	    if($syncTreeSignaturePath2['name'] != "" && intval($syncTreeSignaturePath2['error']) == 0 && $syncTreeSignaturePath2 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image2 = $syncTreeSignaturePath2['name'];
			$tree_handdrawing_image2_arr = explode("_", $tree_handdrawing_image2);
			$lastIndex = sizeof($tree_handdrawing_image2_arr) - 1;
			$tree_handdrawing_image2_id_arr = explode(".", $tree_handdrawing_image2_arr[$lastIndex]);
			$tree_handdrawing_image2_id = $tree_handdrawing_image2_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image2 =  $uploaded_dir . $tree_handdrawing_image2;			
			move_uploaded_file($syncTreeSignaturePath2['tmp_name'],  $root_path.$upload_tree_handdrawing_image2);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image2 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree2_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image2 ."' where id = '". $tree_handdrawing_image2_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image2;
			}
	    }
		// Tree HandDrawing Images Path - Tree 3
	    if($syncTreeSignaturePath3['name'] != "" && intval($syncTreeSignaturePath3['error']) == 0 && $syncTreeSignaturePath3 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image3 = $syncTreeSignaturePath3['name'];
			$tree_handdrawing_image3_arr = explode("_", $tree_handdrawing_image3);
			$lastIndex = sizeof($tree_handdrawing_image3_arr) - 1;
			$tree_handdrawing_image3_id_arr = explode(".", $tree_handdrawing_image3_arr[$lastIndex]);
			$tree_handdrawing_image3_id = $tree_handdrawing_image3_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image3 =  $uploaded_dir . $tree_handdrawing_image3;			
			move_uploaded_file($syncTreeSignaturePath3['tmp_name'],  $root_path.$upload_tree_handdrawing_image3);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image3 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree3_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image3 ."' where id = '". $tree_handdrawing_image3_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image3;
			}
	    }
		// Tree HandDrawing Images Path - Tree 4
	    if($syncTreeSignaturePath4['name'] != "" && intval($syncTreeSignaturePath4['error']) == 0 && $syncTreeSignaturePath4 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image4 = $syncTreeSignaturePath4['name'];
			$tree_handdrawing_image4_arr = explode("_", $tree_handdrawing_image4);
			$lastIndex = sizeof($tree_handdrawing_image4_arr) - 1;
			$tree_handdrawing_image4_id_arr = explode(".", $tree_handdrawing_image4_arr[$lastIndex]);
			$tree_handdrawing_image4_id = $tree_handdrawing_image4_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image4 =  $uploaded_dir . $tree_handdrawing_image4;			
			move_uploaded_file($syncTreeSignaturePath4['tmp_name'],  $root_path.$upload_tree_handdrawing_image4);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image4 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree4_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image4 ."' where id = '". $tree_handdrawing_image4_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image4;
			}
	    }
		// Tree HandDrawing Images Path - Tree 5
	    if($syncTreeSignaturePath5['name'] != "" && intval($syncTreeSignaturePath5['error']) == 0 && $syncTreeSignaturePath5 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image5 = $syncTreeSignaturePath5['name'];
			$tree_handdrawing_image5_arr = explode("_", $tree_handdrawing_image5);
			$lastIndex = sizeof($tree_handdrawing_image5_arr) - 1;
			$tree_handdrawing_image5_id_arr = explode(".", $tree_handdrawing_image5_arr[$lastIndex]);
			$tree_handdrawing_image5_id = $tree_handdrawing_image5_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image5 =  $uploaded_dir . $tree_handdrawing_image5;			
			move_uploaded_file($syncTreeSignaturePath5['tmp_name'],  $root_path.$upload_tree_handdrawing_image5);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image5 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree5_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image5 ."' where id = '". $tree_handdrawing_image5_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image5;
			}
	    }
		// Tree HandDrawing Images Path - Tree 6
	    if($syncTreeSignaturePath6['name'] != "" && intval($syncTreeSignaturePath6['error']) == 0 && $syncTreeSignaturePath6 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image6 = $syncTreeSignaturePath6['name'];
			$tree_handdrawing_image6_arr = explode("_", $tree_handdrawing_image6);
			$lastIndex = sizeof($tree_handdrawing_image6_arr) - 1;
			$tree_handdrawing_image6_id_arr = explode(".", $tree_handdrawing_image6_arr[$lastIndex]);
			$tree_handdrawing_image6_id = $tree_handdrawing_image6_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image6 =  $uploaded_dir . $tree_handdrawing_image6;			
			move_uploaded_file($syncTreeSignaturePath6['tmp_name'],  $root_path.$upload_tree_handdrawing_image6);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image6 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree6_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image6 ."' where id = '". $tree_handdrawing_image6_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image6;
			}
	    }
		// Tree HandDrawing Images Path - Tree 6
	    if($syncTreeSignaturePath7['name'] != "" && intval($syncTreeSignaturePath7['error']) == 0 && $syncTreeSignaturePath7 != "null" )
	    {
			// manipulating image name to get job Id
			$tree_handdrawing_image7 = $syncTreeSignaturePath7['name'];
			$tree_handdrawing_image7_arr = explode("_", $tree_handdrawing_image7);
			$lastIndex = sizeof($tree_handdrawing_image7_arr) - 1;
			$tree_handdrawing_image7_id_arr = explode(".", $tree_handdrawing_image7_arr[$lastIndex]);
			$tree_handdrawing_image7_id = $tree_handdrawing_image7_id_arr[0];
			
			// uploading image
			$upload_tree_handdrawing_image7 =  $uploaded_dir . $tree_handdrawing_image7;			
			move_uploaded_file($syncTreeSignaturePath7['tmp_name'],  $root_path.$upload_tree_handdrawing_image7);
			
			if(file_exists( $root_path.$upload_tree_handdrawing_image7 )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree7_HandDrawing_Path = '/img/scribbles/". $tree_handdrawing_image7 ."' where id = '". $tree_handdrawing_image7_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeHandDrawingPath[] = $tree_handdrawing_image7;
			}
	    }
		if(sizeof($treeHandDrawingPath) > 0){
		  echo ",".implode(",", $treeHandDrawingPath);
		}
		
		// Tree Entered Signature Images
	    $treeSignaturePath = array();
	    //$root_path = "/var/www/tpmcms/devsites/serviceform-sandbox/app/webroot";
		$root_path = "/var/www/serviceform_sites/serviceform-sandbox/app/webroot";
	    $uploaded_dir = "/img/scribbles/";
	    if($syncTreeEnteredPath['name'] != "" && intval($syncTreeEnteredPath['error']) == 0 && $syncTreeEnteredPath != "null" )
	    {
			// manipulating image name to get job Id
			$tree_entered_image = $syncTreeEnteredPath['name'];
			$tree_entered_image_arr = explode("_", $tree_entered_image);
			$lastIndex = sizeof($tree_entered_image_arr) - 1;
			$tree_entered_image_id_arr = explode(".", $tree_entered_image_arr[$lastIndex]);
			$tree_entered_image_id = $tree_entered_image_id_arr[0];
			
			// uploading image
			$upload_tree_entered_image =  $uploaded_dir . $tree_entered_image;			
			move_uploaded_file($syncTreeEnteredPath['tmp_name'],  $root_path.$upload_tree_entered_image);
			
			if(file_exists( $root_path.$upload_tree_entered_image )){
			  
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree_entered_signature_path = '/img/scribbles/". $tree_entered_image ."' where id = '". $tree_entered_image_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeSignaturePath[] = $tree_entered_image;
			}
	    }		
	    if($syncTreeAcceptedPath['name'] != "" && intval($syncTreeAcceptedPath['error']) == 0 && $syncTreeAcceptedPath != "null" )
	    {
			// manipulating image name to get job Id
			$tree_accepted_image = $syncTreeAcceptedPath['name'];
			$tree_accepted_image_arr = explode("_", $tree_accepted_image);
			$lastIndex = sizeof($tree_accepted_image_arr) - 1;
			$tree_accepted_image_id_arr = explode(".", $tree_accepted_image_arr[$lastIndex]);
			$tree_accepted_image_id = $tree_accepted_image_id_arr[0];
			
			// uploading image
			$upload_tree_accepted_image =  $uploaded_dir . $tree_accepted_image;			
			move_uploaded_file($syncTreeAcceptedPath['tmp_name'],  $root_path.$upload_tree_accepted_image);
			
			if(file_exists( $root_path.$upload_tree_accepted_image )){
			  // updating into database
			  $imgsql = " Update ". $syncTableName ." Set Tree_accepted_signature_path = '/img/scribbles/". $tree_accepted_image ."' where id = '". $tree_accepted_image_id ."' ";	
			  $this->runSelect($imgsql, $propId, "prod");
			  
			  $treeSignaturePath[] = $tree_accepted_image;
			}
	    }
		if(sizeof($treeSignaturePath) > 0){
		  echo ",".implode(",", $treeSignaturePath);
		}
    }		  
	if( eregi('transactionservices', $syncTableName) ){
	  $tServId = array();
	  if(is_array($syncDataArray)){
	    foreach($syncDataArray as $key => $val)
	    {
	      if(is_array($val)){
		    $keyColumns = array();
		    foreach( $val as $column => $value ){
		      if(trim($column) != 'id' || ($column == "Service" && $value != "") || ($column == "ServiceDescription" && $value != "") || ($column == "EstimatedHours" && $value != "")){
			  	$keyColumns[] = $column ." = '". addslashes($value) ."' "; //html_entity_decode($value,ENT_QUOTES)
			  }
		    }
	      }
	      else{
			if( trim($key) != 'id' || ($key == "Service" && $val != "") || ($key == "ServiceDescription" && $val != "") || ($key == "EstimatedHours" && $val != "")){
	          $keyColumns[] = $key ." = '". addslashes($val) ."' ";
			}
	      }

		  if(sizeof($keyColumns) > 0){	      
	        $transId = "";
			$gsql = " Select id from ". $syncTableName ." where transaction_id = '". $val['transaction_id'] ."' and treeNo = '". $val['treeNo'] ."' 
																and serviceId = '". $val['serviceId'] ."'  ";

			$treeRes = $this->runSelect($gsql, $propId, "prod");
			if($treeRes){
	  		  if($treeRows = $treeRes->fetch_assoc()){
	    		$transId = $treeRows['id'];
			  }	  
			}
					    
			$tServId[] = $val['id'];
						
			if($transId != ""){
			  $usql = " Update ". $syncTableName ." Set ". implode(', ', $keyColumns) ." where id = '". $transId ."' ";
			  $this->runSelect($usql, $propId, "prod");
			}
			else{			
			  $sql = " insert into ". $syncTableName ." set ". implode(', ', $keyColumns) ." ";
	          $this->runSelect($sql, $propId, "prod");			  
			}
			
//			$trSql = " Select id from ". $syncTableName ." where transaction_id = '". $val['transaction_id'] ."' and treeNo = '". $val['treeNo'] ."' 
//																and serviceId = '". $val['serviceId'] ."' ";
//			//echo 'trSql: '. $trSql .'<br />';
//			$trRes = $this->runSelect($trSql, $propId, "prod");
//			
//			if($trRes){
//	  		  if($trRows = $trRes->fetch_assoc()){
//	    		$tServId[] = $trRows['id'];
//			  }
//			}
		  }
	    }
        if( sizeof($tServId) > 0){
		  echo implode(",",$tServId);
		}
		else{
		  echo "Error";
		}
      }
	  else{
	    echo "Error";
	  }
	}
  }
  
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function convertDataTypes($fieldType) {
	if( eregi('int', $fieldType) ){
	  return 'int';	
	}
	elseif( eregi('decimal', $fieldType) ){
	  return 'int';	
	}
	elseif( eregi('varchar', $fieldType) ){
	  return 'text';	
	}
	elseif( eregi('timestamp', $fieldType) ){
	  return 'text';
	}
	elseif( eregi('datetime', $fieldType) ){
	  return 'text';
	}
	elseif( eregi('enum', $fieldType) ){
	  return 'text';
	}
	elseif( eregi('text', $fieldType) ){
	  return 'text';
	}
	else{
	  return $fieldType;	
	}
  }
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getTableSchema($propId, $tableName = '') {
	
	$fieldsTrans = array();
	$fieldArr = array();
	$jobFields = array();
	$fsql = " Show Fields from ". $tableName;
	$res = $this->runSelect($fsql, $propId, "prod");	
    if($res){
	  while($getRes = $res->fetch_assoc())
	    $fieldsTrans[] = $getRes;
	}
	foreach($fieldsTrans as $key => $val){	  
	  $fieldType = $this->convertDataTypes($val['Type']);
	  $fieldArr[] =  array($val['Field'] => $fieldType);
	}
	
	if(sizeof($fieldArr) > 0){
	  foreach($fieldArr as $key => $val){
	    foreach($val as $fkey => $fval){
		  $jobFields[$fkey] = $fval;
		}
	  }	 
	}
	return $jobFields;
  }
  
  /**
   *
   * @param int $propId
   * @return array fields
   */
  public function getCMSTableSchema($tableName = '') {
	
	$fieldsTrans = array();
	$fieldArr = array();
	$cmsFields = array();
	$fsql = " Show Fields from ". $tableName;
	$getRes = $this->query($fsql);	
    if($getRes){
	  $fieldsTrans[] = $getRes;
	}

	for($i=0; $i<sizeof($fieldsTrans[0]);$i++){
	  $fieldType = $this->convertDataTypes($fieldsTrans[0][$i]['COLUMNS']['Type']);
	  $fieldArr[] =  array($fieldsTrans[0][$i]['COLUMNS']['Field'] => $fieldType);
	}

	if(sizeof($fieldArr) > 0){
	  foreach($fieldArr as $key => $val){
	    foreach($val as $fkey => $fval){
		  $cmsFields[$fkey] = $fval;
		}
	  }	 
	}
	return $cmsFields;
  }
  
}
?>