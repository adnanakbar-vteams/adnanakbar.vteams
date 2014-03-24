<?php
class WebservicesController extends AppController {
  var $uses = array('Field','DataMigration', 'Account', 'Webservices');
  var $helpers = array('Html', 'Form','Javascript','Flash','Ui');
  var $name = 'Webservices';
  var $authTokenEncrypt = '';  
  var $fp;	  
  var $fieldsJson = '';
  var $fieldsSchema = '';
  var $fieldsSchemaJson = '';
  var $accountsJson = ''; 
  var $loginSchema = '';
  var $loginSchemaJson = '';
    
  /**
   *
   */
  public function beforeFilter() {
    parent::beforeFilter();
    $this->Auth->Allow('*');
  }
  
  /**
   *
   */
  public function index() {
	$this->layout = "webservices";
  }

  /**
   *
   * @param type $id
   * @return type 
   */
  public function properties( $id )
  {
	Configure::write("debug", 0);
    $this->layout = null;
	$this->autoRender = false;
	$authTokenEncrypt = md5('properties_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ) {	//  && $user != '' && $passwd != '' 
	  $headers = "";
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Properties WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
	  }
	  
	  $propertyJson = $this->Webservices->listProperties();
	  $propertySchema = $this->Webservices->getCMSTableSchema('properties');					
	  $propertySchemaJson = json_encode($propertySchema);
	  print_r($propertySchemaJson);
	  echo "||";
	  print_r($propertyJson);
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function login($id, $user='', $passwd='')
  {
	Configure::write("debug", 0);
    $this->layout = null;
	$this->autoRender = false;
	$authTokenEncrypt = md5('login_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ) {	//  && $user != '' && $passwd != '' 
	  $headers = "";
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Login WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
	  }
	  
      $data['Account']['email'] = html_entity_decode($user);
      $data['Account']['password'] = html_entity_decode($passwd);
	  
	  $propInfo = $this->Webservices->getPropertyInfo();
	  $accountsJson = $this->Webservices->listAccount($propInfo['client_id'], $data);
	  $loginSchema = $this->Webservices->getCMSTableSchema('accounts');					
	  $loginSchemaJson = json_encode($loginSchema);
	  print_r($loginSchemaJson);
	  echo "||";
	  print_r($accountsJson);
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}	
  }
	
  /**
   *
   * @param type $id
   * @return type 
   */
  public function dailyJobForm($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];
	
	$authTokenEncrypt = md5('dailyjobform_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "DailyJobForm WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }
	  $transFieldsJson = $this->Webservices->getDailyJobDataTransactions($propId);
	  print_r($transFieldsJson);			
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }	
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function jobsTransCompCanData($id )
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('jobsTransCompCanData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Jobs Trans Completed/Cancelled Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }
	  $transFieldsJson = $this->Webservices->getJobsCompCanDataTransactions($propId);
	  print_r($transFieldsJson);
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>". $authTokenEncrypt;
	}
	Configure::write("debug", 0);
  }

  /**
   *
   * @param type $id
   * @return type 
   */
  public function jobsTransStructData($id, $techId='', $jobId='0', $start='0', $end='')
  {	  
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('jobstransactionsdata_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Jobs Trans Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }
	  $transFieldsSchemaNew = array();
	  $transFieldsSchema = $this->Webservices->getTableSchema($propId, 'transactions');
	  
	  foreach ($transFieldsSchema as $key => $val){
		if(eregi('signature', $key) || eregi('drawing', $key) ){
		  $transFieldsSchemaNew[$key] = $val;
		}
		else{
		  $transFieldsSchemaNew[$key] = $val;
		}
	  }

	  $transFieldsSchemaJson = json_encode($transFieldsSchemaNew);	  
	  print_r($transFieldsSchemaJson);
	  echo "||";
	  $transFields = array('id','ip','date_created','order_num','DailyJob_Weather','DailyJob_Time_Left',
	  				  'DailyJob_Time_Returned','Job_Date_From','Job_Date_To','Job_Status','Dailyjob_Man_Hr',
					  'DailyJob_Number_Of_People','Street_Address_Property','Apt_Suite_Property',
					  'City_Property','State_Property','Zip_Property','complete','Tree_Stump_Complete',
					  'Customer_Paid','DailyJob_Pre_Trip_Notes','DailyJob_On_Site_Notes','technician_id');
					  
	  $transSchema = array();
	  $transSchema = array_keys($transFieldsSchemaNew);
	  $transFieldsJson = $this->Webservices->getJobsDataTransactions($transSchema, $propId, $techId, $jobId, $start, $end);
	  print_r($transFieldsJson);
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }	
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function servicesData($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];
	$authTokenEncrypt = md5('servicesData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Services Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }
	  
	  $srvFields = $this->Webservices->getTableSchema($propId, 'services');
	  $srvFieldsJson = json_encode($srvFields);
	  print_r($srvFieldsJson);
	  echo "||";						 
	  $srvDataJson = $this->Webservices->getServicesData($propId);
	  print_r($srvDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function transactionServicesData($id, $techId='', $autoServiceId='', $serviceId='')
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('transactionServicesData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Transaction Services Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }
	  
	  $tranSrvFields = $this->Webservices->getTableSchema($propId, 'transactionservices');
	  $tranSrvFieldsJson = json_encode($tranSrvFields);
	  print_r($tranSrvFieldsJson);
	  echo "||";						 
	  $tranSrvDataJson = $this->Webservices->getTransactionServicesData($propId, $techId, $autoServiceId, $serviceId);
	  print_r($tranSrvDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function customersData($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('customersStructData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Customers Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }

	  $cstFields = $this->Webservices->getTableSchema($propId, 'customers');
	  $cstFieldsJson = json_encode($cstFields);
	  print_r($cstFieldsJson);
	  echo "||";						 
	  $custDataJson = $this->Webservices->getCustomersData($propId);
	  print_r($custDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function salesPersonsData($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];
	$clientId = $propInfo['client_id'];

	$authTokenEncrypt = md5('salesPersonsData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Customers Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }

	  $salesPersonsFields = $this->Webservices->getTableSchema($propId, 'accounts');
	  $salesPersonsFieldsJson = json_encode($salesPersonsFields);
	  print_r($salesPersonsFieldsJson);
	  echo "||";						 
	  $salesPersonsDataJson = $this->Webservices->getSalesPersonsData($propId, $clientId);
	  print_r($salesPersonsDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function localizationsData($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('localizationsData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Localizations Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }

	  $localFields = $this->Webservices->getTableSchema($propId, 'localizations');
	  $localFieldsJson = json_encode($localFields);
	  print_r($localFieldsJson);
	  echo "||";						 
	  $localDataJson = $this->Webservices->getLocalizationData($propId);
	  print_r($localDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function assetsData($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('assetsData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Assets Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }

	  $assetFields = $this->Webservices->getTableSchema($propId, 'assets');
	  $assetFieldsJson = json_encode($assetFields);
	  print_r($assetFieldsJson);
	  echo "||";						 
	  $assetDataJson = $this->Webservices->getAssetsData($propId);
	  print_r($assetDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function propertySettingsData($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('propertySettingsData_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Property Settings Structure and Data WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }

	  $assetFields = $this->Webservices->getTableSchema($propId, 'settings');
	  $assetFieldsJson = json_encode($assetFields);
	  print_r($assetFieldsJson);
	  echo "||";						 
	  $assetDataJson = $this->Webservices->getpropertySettingsData($propId);
	  print_r($assetDataJson);
	  
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
  
  /**
   *
   * @param type $id
   * @return type 
   */
  public function propertySynchronization($id)
  {
	$this->layout = null;
	$this->autoRender = false;
	$propInfo = $this->Webservices->getPropertyInfo();
	$propId = $propInfo['id'];

	$authTokenEncrypt = md5('propertySynchronization_auth_token_ios_web_service_encrpt');
	if( isset($id) && $id == $authTokenEncrypt ){	  
	  $fp = fopen('logweb.txt', 'a+');
	  if($fp){
	    fwrite($fp, "Property Synchronization WebService Accessed at ". date("d/m/Y H:i:s")) ."\n";
		fclose($fp);	
      }
	  $emailMesg = '';
	  $syncTableName = '';
	  $syncDataArray = array();	  
	  $syncTreeImages = array();
	  
	  $syncTableName = isset($_REQUEST['syncTable']) ? $_REQUEST['syncTable'] : $_POST['syncTable'] ;
	  $syncData = isset($_REQUEST['syncData']) ? $_REQUEST['syncData'] : $_POST['syncData'];
	  $syncTreeImage1 = isset($_FILES['TreeImage1']) ? $_FILES['TreeImage1'] : "null";
	  $syncTreeImage2 = isset($_FILES['TreeImage2']) ? $_FILES['TreeImage2'] : "null";
	  $syncTreeImage3 = isset($_FILES['TreeImage3']) ? $_FILES['TreeImage3'] : "null";
	  $syncTreeImage4 = isset($_FILES['TreeImage4']) ? $_FILES['TreeImage4'] : "null";
	  $syncTreeImage5 = isset($_FILES['TreeImage5']) ? $_FILES['TreeImage5'] : "null";
	  $syncTreeImage6 = isset($_FILES['TreeImage6']) ? $_FILES['TreeImage6'] : "null";
	  $syncTreeImage7 = isset($_FILES['TreeImage7']) ? $_FILES['TreeImage7'] : "null";
	  
	  $syncTreeEnteredPath = isset($_FILES['TreeEnteredPath']) ? $_FILES['TreeEnteredPath'] : "null";
	  $syncTreeAcceptedPath = isset($_FILES['TreeAcceptedPath']) ? $_FILES['TreeAcceptedPath'] : "null";
	  
	  $syncTreeSignaturePath1 = isset($_FILES['TreeSignaturePath1']) ? $_FILES['TreeSignaturePath1'] : "null";
	  $syncTreeSignaturePath2 = isset($_FILES['TreeSignaturePath2']) ? $_FILES['TreeSignaturePath2'] : "null";
	  $syncTreeSignaturePath3 = isset($_FILES['TreeSignaturePath3']) ? $_FILES['TreeSignaturePath3'] : "null";
	  $syncTreeSignaturePath4 = isset($_FILES['TreeSignaturePath4']) ? $_FILES['TreeSignaturePath4'] : "null";
	  $syncTreeSignaturePath5 = isset($_FILES['TreeSignaturePath5']) ? $_FILES['TreeSignaturePath5'] : "null";
	  $syncTreeSignaturePath6 = isset($_FILES['TreeSignaturePath6']) ? $_FILES['TreeSignaturePath6'] : "null";
	  $syncTreeSignaturePath7 = isset($_FILES['TreeSignaturePath7']) ? $_FILES['TreeSignaturePath7'] : "null";

	  $syncDataArray = json_decode($syncData, true);

	  $this->Webservices->addPropertySyncData($propId, $syncTableName, $syncDataArray, $syncTreeImage1, $syncTreeImage2, $syncTreeImage3, $syncTreeImage4, $syncTreeImage5, $syncTreeImage6, $syncTreeImage7, $syncTreeEnteredPath, $syncTreeAcceptedPath, $syncTreeSignaturePath1, $syncTreeSignaturePath2, $syncTreeSignaturePath3, $syncTreeSignaturePath4, $syncTreeSignaturePath5, $syncTreeSignaturePath6, $syncTreeSignaturePath7 );
	}
	else{
	  echo "<p>Error 404 - Invalid Access.</p>";
	}
	Configure::write("debug", 0);
  }
  
}