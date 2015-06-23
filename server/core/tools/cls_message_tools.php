<?php
namespace core\tools;
use Flight;

Flight::map('sendSMSVertCodeMsg', array("core\\tools\\ClsMessageTools_Juhe", "sendSMSVertCodeMsg"));
class ClsMessageTools_Juhe {
	static public function sendSMSVertCodeMsg($mobile, $sms_vert_code){
		return ClsMessageTools_Juhe::sendSMS($mobile, 2210, array("code"=>$sms_vert_code));
	}

	static private function sendSMS($mobile, $tpl_id, $tpl_values) {
		$url = "http://v.juhe.cn/sms/send";
		$message_key = "1d67f826225831e9ccbfe988319307c5";
		$tpl_value_arr = array();
		foreach ($tpl_values as $key => $value) {
			$tpl_value_arr[] = "#".$key."#=".$value;
		}
		$tpl_value = urlencode(implode("&", $tpl_value_arr));
		$query_data = "mobile={$mobile}&tpl_id={$tpl_id}&tpl_value={$tpl_value}&key={$message_key}";

		$ch = curl_init();
		$url = $url . "?". $query_data;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		$error = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$response = json_decode($response);
		$send_result = ($error == "200" && $response->error_code==0) ? '1' : '0';
		ClsMessageTools_Juhe::saveSMS($mobile, $tpl_value, $send_result, $message);
		return $send_result;
	}

	static private function saveSMS($mobile, $content, $send_result, &$message) {
		require_once __DIR__."/../../models/MessageHistoryModel.php";
		$data = array('dest_mobile'=>$mobile, 'content'=>$content, 'server_name'=>'juhe', 'result'=>$send_result);
		$message_history_model = new \model\MessageHistoryModel($data);
		return $message_history_model->insertIntoDB($message);
	}
}