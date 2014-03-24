<?php 
/**
 * Class	DelveAuthUtil
 * Description	The purpose of this class is to handle limelight server upload functionality
 * @version	8.0
 */
	class DelveAuthUtil
	{
		static function authenticate_request($http_verb, $resource_url, $access_key, $secret, $params = null)
		{
			$parsed_url = parse_url($resource_url);
			$str_to_sign = strtolower($http_verb . '|' . $parsed_url['host'] . '|' . $parsed_url['path']) . '|';
			$url = $resource_url . '?';
	
			if ($params == null) $params = array();
			if (!array_key_exists('expires', $params)) $params['expires'] = time() + 300;
			$params['access_key'] = $access_key;
	
			$keys = array_keys($params);
			sort($keys);
	
			foreach ($keys as $key)
			{
				$str_to_sign .= $key . '=' .$params[$key] . '&';
				$url .= rawurlencode($key) . '=' .rawurlencode($params[$key]) . '&';
			}
	
			$str_to_sign = chop($str_to_sign,'&');
			$signature = base64_encode(hash_hmac('sha256', $str_to_sign, $secret, true));
			$url .= 'signature=' . rawurlencode($signature);
	
			return $url;
		}
	}
	
	/**
	 * Action	do_put
	 * Description	To submit curl request to assign channel
	 *
	 * @return mix
	 */
	function do_put($url) { 
		// Get the curl session object 
		$session = curl_init($url); 
		curl_setopt($session, CURLOPT_VERBOSE, TRUE);     
		curl_setopt ($session, CURLOPT_PUT, true); 
		curl_setopt ($session, CURLOPT_HEADER, false); 
		curl_setopt ($session, CURLOPT_RETURNTRANSFER, true); 
		// Do the PUT and then close the session 
		$response = curl_exec($session); 

		if (curl_errno($session)) { 
			print ( "curl_errno= " . curl_errno($session). "<br>"); 
			   print( "curl_error= " . curl_error($session) . "<br>"); 
		} else { 
			   curl_close($session); 
		} 
	} 
	/**
	 * Action	assign_to_channel
	 * Description	To assign media id of uploaded file to specific channel
	 *
	 * @return mix
	 */
	function assign_to_channel($media_id_to_add,$clientName){
	
		$access_key = "####";
		$secret 	 = "#####";
		$org_id     = "#####";
		$channel_id = "";
		$channel_request = "http://api.delvenetworks.com/rest/organizations/$org_id/channels/all.xml";
		$signed_channel_request = DelveAuthUtil::authenticate_request("GET", $channel_request, $access_key, $secret);  
		$channelXml = new SimpleXMLElement(file_get_contents($signed_channel_request)); 
		foreach($channelXml->channel as $channelData){
			if($channelData->title == $clientName){
				$channel_id = $channelData->id;
			}
		}
		if(!$channel_id){
			echo "error: could not retrieve media id when uploading ".$media_id_to_add;
			return;
		}
		$add_media_to_channel_url = "http://api.videoplatform.limelight.com/rest/organizations/$org_id/channels/$channel_id/media/$media_id_to_add";
		echo "Adding: $media_id_to_add to Channel: $clientName <br>&nbsp;&nbsp;&nbsp;&nbsp;$add_media_to_channel_url <hr>";
		# obtain a signature for the add media to channel URL  
		$signed_add_media_url = DelveAuthUtil::authenticate_request("PUT", $add_media_to_channel_url, $access_key, $secret);  
		do_put($signed_add_media_url);
	}
	/**
	 * Action	upload_video
	 * Description	To upload video file on lime light server useing curl request
	 *
	 * @return string
	 */
	function upload_video($filename,$clientName,$guid) { 
		// Authenticate the upload URL
		$access_key 	= "#####";
		$secret 		= "#####";
		$org_id 		= "#####";
		$add_media_url 	= "http://api.videoplatform.limelight.com/rest/organizations/$org_id/media.xml";
		$signed_url 	= DelveAuthUtil::authenticate_request("POST", $add_media_url, $access_key, $secret);

		$title_a 	= explode("/",$filename);
		$arr_size 	= sizeof($title_a) - 1;
		$title 		= $title_a[$arr_size];
		
		$date = date("Y-m-d h:is");
		$post_data = array(
			"title"			=> "$clientName-$title",
			"guid"			=> "$guid",
			"description"	=> "$clientName - $title - $guid",
			"media_file"	=> "@".$filename
		);
		
		echo "POSTING <br>&nbsp;&nbsp;&nbsp;&nbsp;$filename <br>TO <br>&nbsp;&nbsp;&nbsp;&nbsp;$signed_url <hr>"; 
		
		$ch = curl_init($signed_url); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$resp = curl_exec($ch);
		curl_close ($ch);
		// parse response to get id
		$xml = new SimpleXMLElement($resp);
		$mediaId = $xml->id;
		if(!$mediaId){
			echo "error: could not retrieve media id when uploading ".$filename;
			return;
		} else {
			assign_to_channel($mediaId,$clientName);
			// remove file from local server
			if(is_file($filename)) { 
				unlink($filename);
			}
		}
		return $mediaId;
	}
?>