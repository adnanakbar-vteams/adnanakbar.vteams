<?
/*
This code allows to stop recurring transactions
*/
	include ("arb.class.php");
	// Initiate authentication class instance
	$authorize = new Authenticate;

	// The transaction's unique id to be cancelled
	$subscriptionId = 553;

	//build xml to post to authorize.net.
	// Here $loginname is merchant login name and $transactionkey is the merchant's unique authentication key.
	$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>".
				"<ARBCancelSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">".
				"<merchantAuthentication>".
				"<name>" . $loginname . "</name>".
				"<transactionKey>" . $transactionkey . "</transactionKey>".
				"</merchantAuthentication>" .
				"<subscriptionId>" . $subscriptionId . "</subscriptionId>".
			"</ARBCancelSubscriptionRequest>";
	
	//send the xml via curl
	$response = $authorize->send_request_via_curl($content);
	//if curl is unavilable you can try using fsockopen
			
	//if the connection and send worked $response holds the return from Authorize.net
	if ($response)
	{
		// Transaction is successful
		/*
			a number of xml functions exist to parse xml results, but they may or may not be avilable on your system
			please explore using SimpleXML in php 5 or xml parsing functions using the expat library in php 4
			parse_return is a function that shows how you can parse though the xml return if these other options are not avilable to you
		*/
		list ($resultCode, $code, $text, $subscriptionId) = $authorize->parse_return($response);

		$filecontent .= "Cancelled Subscription\r\n";
		$filecontent .= "=======================\r\n";
		$filecontent .= "Name: Adnan Akbar\r\n";
		$filecontent .= "Subscription Id: $subscriptionId\r\n";
		$filecontent .= "Response Text: $text\r\n";
		$filecontent .= "Response Reason Code: $code\r\n";
	
		/* write data to log file or database for autorecurring billing cancellations */
		$fp = fopen('silent_REQUEST.log', "a");
		fwrite($fp, $filecontent);
		fclose($fp);
		
		// You can even write your custom code here to cancel subscriptions or services of this customer here and return a success message
	}
	else
	{
		// Transaction is failed
		$filecontent .= "Cancell Subscription Failed\r\n";
		$filecontent .= "===========================\r\n";
		$filecontent .= "Name: Adnan Akbar\r\n";
		$filecontent .= "Subscription Id: $subscriptionId\r\n";
		$filecontent .= "Response Text: $text\r\n";
		$filecontent .= "Response Reason Code: $code\r\n";

		// Write data to log file for failure of the cancellation
		$fp = fopen('silent_REQUEST.log', "a");
		fwrite($fp, $filecontent);
		fclose($fp);
		
		// You can write your custom code here for the failure response and inform the customer with an error message here.
	}
?>