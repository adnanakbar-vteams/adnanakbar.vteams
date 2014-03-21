<?
/*
	Commission Junction Stuff
*/

// This is fake id but can be changed to access the CJ API.
$websiteId = "32323";

$ini = ini_set("soap.wsdl_cache_enabled", "0");
try {
	
	$client = new SoapClient("https://rtpubcommission.api.cj.com/wsdl/version2/realtimeCommissionServiceV2.wsdl", array('trace' => true));
        //Enter the request parameters for realTimeCommissionSearch below.
        //For detailed usage of the parameter values, please refer to CJ Web Services online documentation
        $results = $client->retrieveLatestTransactions(array(
	        "developerKey" => Yii::app()->params['cj_developerKey'],
                "websiteIds" => $websiteId,
                "lookBackXHours" => '7',
                "advertiserIds" => '',
                "countries" => '',
                "adIds" => '',
                "includeDetails" => '',
                "sortBy" => '',
                "sortOrder" => 'asc',));
        // The entire response structure will be printed in the next line

} catch (Exception $e) {
       $error = "There was an error with your request or the service is unavailable.";
}
?>