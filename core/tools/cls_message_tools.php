<?php
namespace core\tools;
use Flight;

Flight::map('sendSMSCode', array("core\\tools\\ClsMessageTools", "sendSMS"));

class ClsMessageTools {

	static public function sendSMS($mobiles=array(), $content) {
		$path="config/message.ini";
		$config = new \Config_Lite($path);
		$url = $config['yidu']['message_url'];
		$key = $config['yidu']['message_key'];
		$secret = $config['yidu']['message_secret'];
		$session = $config['yidu']['message_session'];
		$postfix = $config['yidu']['message_postfix'];
		$msg = $content . " " . $postfix;

		$send_mobiles = implode(',', $mobiles);
		$post_data = "zh={$key}&mm={$secret}&sms_type={$session}&hm={$send_mobiles}&nr=$msg";
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($ch);
		if ($response == NULL) {
			curl_close($ch);
			ClsMessageTools::saveSMS($mobiles, $msg, 0);
			return false;
		}

		$error = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($error != "200") {
			curl_close($ch);
			ClsMessageTools::saveSMS($mobiles, $msg, 0);
            return false;
		}
	
		if(strpos($response,"0:") === 0) {
			$response = 0;
		}

		curl_close($ch);
		ClsMessageTools::saveSMS($mobiles, $msg, 1);
		return $response;
	}
	
	static private function saveSMS($mobiles=array(), $content, $send_result) {
		if (!empty($mobiles)) {
			foreach ($mobiles as $key => $mobile) {
				$sql = "insert into o_o.message_history
                         (result, type, send_time, dest_mobile, user_id, content, server_name) 
						values 
                         ({$send_result}, 'SINGLE', now(), '{$mobile}', '49', '{$content}', 'yidu')   
				";
				Flight::sms_db()->query($sql);
			}
		}
	}
}


