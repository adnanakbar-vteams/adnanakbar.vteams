<?
/*
This code allows the credit card authentication and transaction handling
*/
	include ("arb.class.php");
	// Initiate authentication class instance
	$authorize = new Authenticate;
	
	// The unique id of the customer to record in merchant data for future references.
	$userid = 553;
	// The transaction amount
	$amount = 1.00;
	// Unique reference id for this transaction to identify the customer from our database against which this transaction was performed.
	$refId = 553;
	// Transaction title or purpose
	$name = "One year paid subscription";
	// Term for which this transaction is made e.g. one year here
	$length = 12;
	// Unit of the term and it may be days or months
	$unit = "months";
	// When the transaction period will expire
	$expires = strtotime("+1 year");
	// When to start the transaction and deduct amount from the credit card. It may be today's date or may be future date for free trials
	$startDate = date('Y-m-d');
	// Number of total recurring payments
	$totalOccurrences = 5;
	// Number of trial occurences
	$trialOccurrences = 0;
	// In case if the trial amount is different provide the amount else provide 0 (Zero)
	$trialAmount = 0;
	// Credit Card number
	$cardNumber = "4111111111111111";
	// Credit card expiry  only year and month
	$expirationDate = "2016-06";
	// First name of customer
	$firstName = "Adnan";
	// Last name of the customer
	$lastName = "Akbar";
	// Unique invoide number for the transaction
	$invoiceNumber = 553;
	// Customer id in our database
	$cust_id = 553;
	// Address of customer
	$address = "27 G-5, Wapda Town";
	$city = "Lahore";
	$state = "Punjab";
	$zip = "55000";
	$country = "Pakistan";
	
	//build xml to post data to authorize.net for authentication
	$content =
			"<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
				"<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
					"<merchantAuthentication>".
						"<name>" . $loginname . "</name>".
						"<transactionKey>" . $transactionkey . "</transactionKey>".
					"</merchantAuthentication>".
					"<refId>" . $refId . "</refId>".
					"<subscription>".
						"<name>" . $name . "</name>".
						"<paymentSchedule>".
							"<interval>".
								"<length>". $length ."</length>".
								"<unit>". $unit ."</unit>".
							"</interval>".
							"<startDate>" . $startDate . "</startDate>".
							"<totalOccurrences>". $totalOccurrences . "</totalOccurrences>".
							"<trialOccurrences>". $trialOccurrences . "</trialOccurrences>".
						"</paymentSchedule>".
						"<amount>". $amount ."</amount>".
						"<trialAmount>" . $trialAmount . "</trialAmount>".
						"<payment>".
							"<creditCard>".
								"<cardNumber>" . $cardNumber . "</cardNumber>".
								"<expirationDate>" . $expirationDate . "</expirationDate>".
								"<cardCode>".$_POST["cvv"]."</cardCode>".
							"</creditCard>".
						"</payment>".
						"<order>".
							"<invoiceNumber>". $invoiceNumber . "</invoiceNumber>".
						"</order>".
						"<customer>".
							"<id>". $userid . "</id>".
						"</customer>".
						"<billTo>".
							"<firstName>". $firstName . "</firstName>".
							"<lastName>" . $lastName . "</lastName>".
							"<address>" . $address . "</address>".
							"<city>" . $city . "</city>".
							"<state>" . $state . "</state>".
							"<zip>" . $zip . "</zip>".
							"<country>" . $country . "</country>".
						"</billTo>".
					"</subscription>".
				"</ARBCreateSubscriptionRequest>";
		
	//send the xml via curl to authorize.net to get authentication response
	$response = $authorize->send_request_via_curl($content);
	if ($response){
		// Getting response information
		list ($refId, $resultCode, $code, $text, $subscriptionId) = $authorize->parse_return($response);
		// Dumping the response date into a log file for checking any errors of issues to track bugs
		$fp = fopen('data.log', "a");
		fwrite($fp, "$refId\r\n");
		fwrite($fp, "$subscriptionId\r\n");
		fwrite($fp, "$amount\r\n");
		fclose($fp);
					
		// Building errors array for common error messages codes and descriptions to display our own custom messages against the errors
		$AuthErrorCodes = array(
			'E00001' => 'An error occurred during processing. Please try again.',
			'E00002' => 'The content-type specified is not supported.',
			'E00003' => 'An error occurred while parsing the XML request.',
			'E00004' => 'The name of the requested API method is invalid.',
			'E00005' => 'The merchantAuthentication.transactionKey is invalid or not present.',
			'E00006' => 'The merchantAuthentication.name is invalid or not present.',
			'E00007' => 'User authentication failed due to invalid authentication values.',
			'E00008' => 'User authentication failed. The payment gateway account or user is inactive.',
			'E00009' => 'The payment gateway account is in Test Mode. The request cannot be processed.',
			'E00010' => 'User authentication failed. You do not have the appropriate permissions.',
			'E00011' => 'Access denied. You do not have the appropriate permissions.',
			'E00012' => 'A duplicate subscription already exists.',
			'E00013' => 'Card Number, CVV, Name on Card or Expiration is invalid.',
			'E00014' => 'A required field is not present.',
			'E00015' => 'The field length is invalid.',
			'E00016' => 'The field type is invalid.',
			'E00017' => 'The startDate cannot occur in the past.',
			'E00018' => 'The credit card expires before the subscription startDate.',
			'E00019' => 'The customer taxId or driversLicense information is required.',
			'E00020' => 'The payment gateway account is not enabled for eCheck.Net subscriptions.',
			'E00021' => 'The payment gateway account is not enabled for credit card subscriptions.',
			'E00022' => 'The interval length cannot exceed 365 days or 12 months.',
			'E00023' => '',
			'E00024' => 'The trialOccurrences is required when trialAmount is specified.',
			'E00025' => 'Automated Recurring Billing is not enabled.',
			'E00026' => 'Both trialAmount and trialOccurrences are required.',
			'E00027' => 'The test transaction was unsuccessful.',
			'E00028' => 'The trialOccurrences must be less than totalOccurrences.',
			'E00029' => 'Payment information is required.',
			'E00030' => 'A paymentSchedule is required.',
			'E00031' => 'The amount is required.',
			'E00032' => 'The startDate is required.',
			'E00033' => 'The subscription Start Date cannot be changed.',
			'E00034' => 'The interval information cannot be changed.',
			'E00035' => 'The subscription cannot be found.',
			'E00036' => 'The payment type cannot be changed.',
			'E00037' => 'The subscription cannot be updated.',
			'E00038' => 'The subscription cannot be canceled.',
			'E00045' => 'The root node does not reference a valid XML namespace.'
			);
		if($resultCode == "Ok"){
			// If result code is "OK" that means the transaction is successful and you can write your code against success here
			// i.e. insert the transaction information into your database, extend paid membership etc.
					//$resultCode = "Ok";
		}else {
			// The transaction failed return the error message to the cutomer using $AuthErrorCodes[$code] array to display to the customer.
		}
	} else {
		// Write code to to display a genral transaction error message here.
	}
?>