<?php
error_reporting(E_ALL);

echo "PushScript\n";

set_time_limit(0);

define('APPKEY', 'Dy6e2tIpThaluGDGr408Cw'); // App Key
define('PUSHSECRET', 'vs-xoH_fR1-Vu4ZCA8ehuA'); // Master Secret

define('APPKEY', 'uGY_nb_RSCaE8ofA9PknsQ'); // App Key
define('PUSHSECRET', 'f0KP1GmyR_mBwdNDIAnxmQ'); // Master Secret

define('PUSHURL', 'https://go.urbanairship.com/api/push/');

// altes API 1 Format
//$contents = array();
//$contents['badge'] = "+1";
//$contents['alert'] = "Test Push";

//$push = array("aps" => $contents);
//$push['device_tokens'] = $devices;


//API 3 neues Format
/*
{
	"audience":{
		"AND":[
			{"device_token":["AE50338C76DE5ADE4036DBA1CC16E891B9742F8BB6C05BF489AC4905A9BA731E"]},
			{"tag":["sports","entertainment"]}
		]
	},
	"notification":{
		"alert":"Test Push",
		"badge":"+1"
	},
	"device_types":["ios"]
}


{
	"audience":"all",
	"notification":{
		"alert":"Test Push",
		"badge":"+1"
	},
	"device_types":["ios"]
}
 
 
 {
 "audience":{"tag":["3","9"]}, 
 "notification":{
 	"ios":{
 		"alert":"Test Push",
 		"badge":"+1",
 		"extra":{
 			"url":"http:\/\/www.nebelhorn.com"
 		}
 	}
 },
 "device_types":["ios"]
 } 
*/
/*
$device_token['device_token'] = array('AE50338C76DE5ADE4036DBA1CC16E891B9742F8BB6C05BF489AC4905A9BA731E'); // Device Token
$tag['tag'] = array('3', '9');
$audience['AND'] = array($device_token, $tag);
*/
$device_types = array('ios');

$tag['tag'] = array('categories');
$iosContent = array();
$iosContent['alert'] = "Nachricht mx 120";
$iosContent['badge'] = "+1";
$iosExtraContent = array();
$iosExtraContent['articleHierarchyIDs'] = array('categorieid', 'articleid');
$iosContent['extra'] = $iosExtraContent;

$alertContent = array();
$alertContent['ios'] = $iosContent;


$push = array("audience" => $tag); //$audience, wenn devicetoke dabei
										//$tag, wenn nur auf tags separiert
$push['notification'] = $alertContent;
$push['device_types'] = $device_types;

$json = json_encode($push);

//$json = '{"audience" : "all","device_types" : "all","notification" : {"alert" : "This is a broadcast."}}';
echo "json ".$json."\n";



/*
POST /api/push HTTP/1.1
Authorization: Basic <master authorization string>
Content-Type: application/json
Accept: application/vnd.urbanairship+json; version=3;
*/

$session = curl_init(PUSHURL);
curl_setopt($session, CURLOPT_USERPWD, APPKEY . ':' . PUSHSECRET);
curl_setopt($session, CURLOPT_POST, True);
curl_setopt($session, CURLOPT_POSTFIELDS, $json);
curl_setopt($session, CURLOPT_HEADER, False);
curl_setopt($session, CURLOPT_RETURNTRANSFER, True);
curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Accept: application/vnd.urbanairship+json; version=3;'));


$content = curl_exec($session);
/*echo "content ".$content; // just for testing what was sent

// Check if any error occured
$response = curl_getinfo($session);
if($response['http_code'] != 200) {
	echo "Got negative response from server, http code: ".
	$response['http_code'] . "\n";
}else{
	echo "It worked!\n";
}
*/
curl_close($session);


?>