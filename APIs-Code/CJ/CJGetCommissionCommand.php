<?php
class CJGetCommissionCommand extends CConsoleCommand {
	public function getHelp() {
		echo "Get Commission on Sales FROM CJ";
	}
	
	public function run($args) {
		$ini = ini_set("soap.wsdl_cache_enabled", "0");
		$results = null;
		$error = null;
		try {
			$client = new SoapClient("https://rtpubcommission.api.cj.com/wsdl/version2/realtimeCommissionServiceV2.wsdl", array('trace' => true));
			$results = $client->retrieveLatestTransactions(array(
				"developerKey" => Yii::app()->params['cj_developerKey'],
				"websiteIds" => '',
				"lookBackXHours" => '7',
				"advertiserIds" => '16',
				"countries" => '',
				"adIds" => '',
				"includeDetails" => '',
				"sortBy" => '',
				"sortOrder" => 'asc',));
			// The entire response structure will be printed in the next line
		} catch (Exception $e) {
			$error = "There was an error with your request or the service is unavailable.";
		}
		
		
		if(isset($results) && !empty($results)) {
			$results_array = $this->objectToArray($results);
			
			$db_orderIds = array();
			$data_array = array();	
			
			foreach($results_array['out']['transactions']['RealTimeCommissionDataV2'] as $key => $val) {
				if($key == "orderId") {
					$orderIds[] = $val;
				}
			}
			
			foreach($orderIds as $orderid) {
				foreach($results_array['out']['transactions']['RealTimeCommissionDataV2'] as $key => $val) {
					if($key == 'details') {
						$data_array[$orderid]['details'] = "'".addslashes(serialize($val['Detail']))."'";
					}else{
						$data_array[$orderid][$key] = "'".addslashes($val)."'";
					}
				}
			}
			
			$cjCommissions = new cjCommissions;
			$orderIds = $cjCommissions->getAllCJCommission();
			
			if(!empty($orderIds)) {
				foreach($orderIds as $key => $val) {
					$db_orderIds[] = $val['orderId'];
				}
			}
			foreach($data_array as $key => $val) {
				if(!in_array($key, $db_orderIds)) {
					$cjCommissions->adId = $val['adId'];
					$cjCommissions->advertiserId = $val['advertiserId'];
					$cjCommissions->advertiserName = $val['advertiserName'];
					$cjCommissions->commissionAmount = $val['commissionAmount'];
					$cjCommissions->country = $val['country'];
					$cjCommissions->details = $val['details'];
					$cjCommissions->eventDate = $val['eventDate'];
					$cjCommissions->orderId = $val['orderId'];
					$cjCommissions->saleAmount = $val['saleAmount'];
					$cjCommissions->sid = $val['sid'];
					$cjCommissions->websiteId = $val['websiteId'];
					$cjCommissions->created = $val['created'];
					$cjCommissions->save(false);
				}
			}
		}
		 mail("adnan@nxvt.com","Cron Tab","Cron Tab Chal gai...of CJ".date("Y-m-d H:i:s"));
	}
	
	function objectToArray($d) {
		if (is_object($d))
			$d = get_object_vars($d);
		if (is_array($d))
			return array_map(__FUNCTION__, $d);
		else
			return $d;
	}
}